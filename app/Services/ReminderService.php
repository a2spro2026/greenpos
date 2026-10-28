<?php

namespace App\Services;

use App\Models\Reminder;
use App\Support\Workspace;
use Illuminate\Support\Collection;

class ReminderService
{
    public function __construct(private MessagingService $messaging)
    {
    }

    public function schedule(array $data): Reminder
    {
        $company = Workspace::company();

        return Reminder::query()->create([
            'company_id' => $company->id,
            'store_id' => $data['store_id'] ?? Workspace::store()?->id,
            'remindable_type' => $data['remindable_type'] ?? null,
            'remindable_id' => $data['remindable_id'] ?? null,
            'channel' => $data['channel'],
            'recipient' => $data['recipient'],
            'message' => $data['message'],
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'status' => 'pending',
            'created_by' => Workspace::user()?->id,
        ]);
    }

    public function processDue(int $limit = 100): int
    {
        $due = Reminder::query()->due()->orderBy('scheduled_at')->limit($limit)->get();
        $sent = 0;

        foreach ($due as $reminder) {
            if ($this->send($reminder)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function send(Reminder $reminder): bool
    {
        if ($reminder->channel === 'notification') {
            app(NotificationService::class)->dispatch([
                'title' => 'Rappel',
                'body' => $reminder->message,
                'category' => 'crm',
                'type' => 'info',
                'channels' => ['internal' => true],
            ]);
            $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);

            return true;
        }

        $ok = $this->messaging->sendChannel(
            $reminder->channel,
            $reminder->recipient,
            $reminder->message,
            'Rappel GreenPOS'
        );

        $reminder->update([
            'status' => $ok ? 'sent' : 'failed',
            'sent_at' => $ok ? now() : null,
            'error' => $ok ? null : 'Échec envoi (gateway non configuré ou erreur réseau).',
        ]);

        return $ok;
    }

    public function markSent(Reminder $reminder): Reminder
    {
        $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);

        return $reminder;
    }

    public function cancel(Reminder $reminder): Reminder
    {
        $reminder->update(['status' => 'cancelled']);

        return $reminder;
    }

    public function pendingForCompany(int $companyId): Collection
    {
        return Reminder::query()
            ->forCompany($companyId)
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get();
    }
}
