<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\PosSyncConflict;
use App\Support\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Rimal V2 — phase 5 : synchronisation idempotente des tickets saisis hors ligne.
 * - client_uuid unique par société : un ticket déjà reçu n'est jamais recréé ;
 * - un ticket refusé (stock, crédit, paiement…) est consigné dans pos_sync_conflicts
 *   avec sa charge utile complète, puis traité depuis l'écran « Conflits de synchro ».
 */
class PosOfflineSyncService
{
    public const CONTEXT_KEYS = ['service_mode', 'employee_id', 'delivery_agent_id', 'ticket_discount_percent', 'ticket_name', 'pos_table_id',
        'service_mode_list_id', 'predefined_ticket_id', 'ticket_group', 'delivery_platform_id', 'appointment_at', 'pickup_date', 'delivery_address'];

    public function __construct(private PosService $pos) {}

    /** @return array{client_uuid:string, ok:bool, duplicate?:bool, conflict?:bool, number?:string, sale_id?:int, message?:string} */
    public function syncOne(array $payload): array
    {
        $company = Workspace::company();
        $uuid = $payload['client_uuid'];

        $existing = PosSale::query()->where('company_id', $company->id)->where('client_uuid', $uuid)->first();
        if ($existing) {
            $this->markSynced($uuid, $existing);

            return ['client_uuid' => $uuid, 'ok' => true, 'duplicate' => true, 'number' => $existing->number, 'sale_id' => $existing->id];
        }

        try {
            $context = ['client_uuid' => $uuid];
            foreach (self::CONTEXT_KEYS as $k) {
                if (array_key_exists($k, $payload)) {
                    $context[$k] = $payload[$k];
                }
            }
            $sale = $this->pos->completeSale($payload['items'], $payload['payments'], $payload['customer_id'] ?? null, $payload['notes'] ?? null, null, $context);
            if (! empty($payload['offline_at'])) {
                $sale->forceFill(['notes' => trim(($sale->notes ? $sale->notes."\n" : '').'Saisi hors ligne le '.Carbon::parse($payload['offline_at'])->format('d/m/Y H:i'))])->save();
            }
            $this->markSynced($uuid, $sale);

            return ['client_uuid' => $uuid, 'ok' => true, 'number' => $sale->number, 'sale_id' => $sale->id];
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Ticket refusé';
            if (array_key_exists('session', $e->errors())) {
                // Caisse fermée : erreur transitoire, le ticket reste dans la file du terminal.
                return ['client_uuid' => $uuid, 'ok' => false, 'retry' => true, 'message' => $message];
            }
            $this->logConflict($payload, $message);

            return ['client_uuid' => $uuid, 'ok' => false, 'conflict' => true, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Synchro POS hors ligne', ['client_uuid' => $uuid, 'error' => $e->getMessage()]);
            $this->logConflict($payload, 'Erreur technique : '.mb_substr($e->getMessage(), 0, 300));

            return ['client_uuid' => $uuid, 'ok' => false, 'conflict' => true, 'message' => 'Erreur technique, ticket mis en conflit.'];
        }
    }

    public function logConflict(array $payload, string $message): PosSyncConflict
    {
        $company = Workspace::company();
        $conflict = PosSyncConflict::query()->firstOrNew(['company_id' => $company->id, 'client_uuid' => $payload['client_uuid']]);
        $conflict->fill([
            'store_id' => Workspace::store()?->id,
            'user_id' => auth()->id(),
            'payload' => $payload,
            'message' => $message,
            'status' => 'open',
            'offline_at' => ! empty($payload['offline_at']) ? Carbon::parse($payload['offline_at']) : null,
        ]);
        $conflict->attempts = $conflict->exists ? $conflict->attempts + 1 : 1;
        $conflict->save();

        return $conflict;
    }

    protected function markSynced(string $uuid, PosSale $sale): void
    {
        PosSyncConflict::query()->where('company_id', $sale->company_id)->where('client_uuid', $uuid)->where('status', 'open')
            ->update(['status' => 'synced', 'pos_sale_id' => $sale->id, 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
    }
}
