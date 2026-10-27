<?php

namespace App\Services;

use App\Models\PosCashMovement;
use App\Models\PosPayment;
use App\Models\PosSession;
use App\Support\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rimal V2 — journal de caisse par session (espèces uniquement).
 */
class PosCashService
{
    /** Coupures par devise (billets + pièces). */
    public const DENOMINATIONS = [
        'MAD' => [200, 100, 50, 20, 10, 5, 2, 1, 0.5, 0.2, 0.1],
        'EUR' => [500, 200, 100, 50, 20, 10, 5, 2, 1, 0.5, 0.2, 0.1, 0.05, 0.02, 0.01],
        'USD' => [100, 50, 20, 10, 5, 2, 1, 0.25, 0.1, 0.05, 0.01],
    ];

    public static function denominationsFor(?string $currency): array
    {
        return self::DENOMINATIONS[strtoupper((string) $currency)] ?? self::DENOMINATIONS['MAD'];
    }

    /** Somme d'un comptage par coupures [valeur => quantité]. */
    public static function countBreakdown(array $breakdown): float
    {
        $total = 0.0;
        foreach ($breakdown as $value => $qty) {
            $total += (float) $value * max(0, (int) $qty);
        }

        return round($total, 2);
    }

    public function addMovement(PosSession $session, string $direction, float $amount, string $reason, ?string $clientUuid = null): PosCashMovement
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages(['session' => 'La caisse est clôturée.']);
        }
        if (! in_array($direction, ['in', 'out'], true) || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Mouvement invalide.']);
        }

        return DB::transaction(function () use ($session, $direction, $amount, $reason, $clientUuid) {
            if ($clientUuid) {
                $existing = PosCashMovement::query()->where('company_id', $session->company_id)->where('client_uuid', $clientUuid)->first();
                if ($existing) {
                    return $existing;
                }
            }
            if ($direction === 'out' && $amount > $this->expectedCash($session) + 0.001) {
                throw ValidationException::withMessages(['amount' => 'Sortie supérieure aux espèces en caisse.']);
            }
            $movement = PosCashMovement::query()->create([
                'company_id' => $session->company_id,
                'store_id' => $session->store_id,
                'pos_session_id' => $session->id,
                'user_id' => Workspace::user()?->id,
                'direction' => $direction,
                'amount' => round($amount, 2),
                'reason' => $reason,
                'client_uuid' => $clientUuid,
            ]);
            $this->refreshTotals($session);

            return $movement;
        });
    }

    public function refreshTotals(PosSession $session): void
    {
        $session->update([
            'cash_in_total' => (float) PosCashMovement::query()->where('pos_session_id', $session->id)->where('direction', 'in')->sum('amount'),
            'cash_out_total' => (float) PosCashMovement::query()->where('pos_session_id', $session->id)->where('direction', 'out')->sum('amount'),
        ]);
    }

    public function cashSales(PosSession $session): float
    {
        return round((float) PosPayment::query()
            ->where('method', 'cash')
            ->whereHas('sale', fn ($q) => $q->where('pos_session_id', $session->id)->where('status', 'completed'))
            ->sum('amount'), 2);
    }

    public function expectedCash(PosSession $session): float
    {
        $in = (float) PosCashMovement::query()->where('pos_session_id', $session->id)->where('direction', 'in')->sum('amount');
        $out = (float) PosCashMovement::query()->where('pos_session_id', $session->id)->where('direction', 'out')->sum('amount');

        return round((float) $session->opening_float + $this->cashSales($session) + $in - $out, 2);
    }

    /**
     * Opérations espèces d'une session, chronologiques, avec solde courant.
     *
     * @return Collection<int, array{at:\Illuminate\Support\Carbon|null,type:string,label:string,user:?string,in:float,out:float,balance:float}>
     */
    public function sessionOperations(PosSession $session): Collection
    {
        $session->loadMissing(['opener', 'closer']);
        $ops = collect();
        $ops->push([
            'at' => $session->opened_at, 'type' => 'opening', 'label' => 'Ouverture — fond de caisse',
            'user' => $session->opener?->name, 'in' => (float) $session->opening_float, 'out' => 0.0,
        ]);

        PosPayment::query()
            ->where('method', 'cash')
            ->whereHas('sale', fn ($q) => $q->where('pos_session_id', $session->id)->where('status', 'completed'))
            ->with('sale.cashier')
            ->get()
            ->each(function (PosPayment $p) use ($ops) {
                $ops->push([
                    'at' => $p->sale?->completed_at ?? $p->created_at, 'type' => 'sale',
                    'label' => 'Vente '.$p->sale?->number, 'user' => $p->sale?->cashier?->name,
                    'in' => (float) $p->amount, 'out' => 0.0,
                ]);
            });

        PosCashMovement::query()->where('pos_session_id', $session->id)->with('user')->get()
            ->each(function (PosCashMovement $m) use ($ops) {
                $ops->push([
                    'at' => $m->created_at, 'type' => $m->direction === 'in' ? 'cash_in' : 'cash_out',
                    'label' => ($m->direction === 'in' ? 'Entrée : ' : 'Sortie : ').$m->reason, 'user' => $m->user?->name,
                    'in' => $m->direction === 'in' ? (float) $m->amount : 0.0,
                    'out' => $m->direction === 'out' ? (float) $m->amount : 0.0,
                ]);
            });

        $balance = 0.0;

        return $ops->sortBy(fn ($o) => [$o['type'] === 'opening' ? 0 : 1, optional($o['at'])->timestamp ?? 0])
            ->values()
            ->map(function ($o) use (&$balance) {
                $balance = round($balance + $o['in'] - $o['out'], 2);
                $o['balance'] = $balance;

                return $o;
            });
    }
}
