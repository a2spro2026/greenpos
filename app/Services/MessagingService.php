<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessagingService
{
    public function sendEmail(string $to, string $subject, string $body, ?Company $company = null): bool
    {
        $company = $company ?? Workspace::company();
        $fromName = config('messaging.email.from_name', $company?->name ?? 'GreenPOS');

        try {
            Mail::raw($body, function ($message) use ($to, $subject, $fromName) {
                $message->to($to)->subject($subject)->from(config('mail.from.address'), $fromName);
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('messaging.email.failed', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendSms(string $to, string $message, ?Company $company = null): bool
    {
        $settings = $this->integrationSettings($company);
        $url = $settings['sms_url'] ?? config('messaging.sms.url');
        $apiKey = $settings['sms_api_key'] ?? config('messaging.sms.api_key');
        $from = $settings['sms_from'] ?? config('messaging.sms.from');
        $enabled = ($settings['sms_enabled'] ?? false) || config('messaging.sms.enabled');

        if (! $enabled || ! $url) {
            Log::info('messaging.sms.prepared', ['to' => $to, 'message' => $message]);

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(array_filter(['Authorization' => $apiKey ? 'Bearer '.$apiKey : null]))
                ->post($url, [
                    'to' => $to,
                    'from' => $from,
                    'message' => $message,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('messaging.sms.failed', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('messaging.sms.exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendWhatsapp(string $to, string $message, ?Company $company = null): bool
    {
        $settings = $this->integrationSettings($company);
        $base = rtrim($settings['whatsapp_url'] ?? config('messaging.whatsapp.url'), '/');
        $token = $settings['whatsapp_token'] ?? config('messaging.whatsapp.token');
        $phoneId = $settings['whatsapp_phone_id'] ?? config('messaging.whatsapp.phone_number_id');
        $enabled = ($settings['whatsapp_enabled'] ?? false) || config('messaging.whatsapp.enabled');

        if (! $enabled || ! $token || ! $phoneId) {
            Log::info('messaging.whatsapp.prepared', ['to' => $to, 'message' => $message]);

            return false;
        }

        $digits = preg_replace('/\D+/', '', $to);

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->post("{$base}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $digits,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('messaging.whatsapp.failed', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('messaging.whatsapp.exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendChannel(string $channel, string $recipient, string $message, ?string $subject = null, ?Company $company = null): bool
    {
        return match ($channel) {
            'email' => $this->sendEmail($recipient, $subject ?: 'GreenPOS', $message, $company),
            'sms' => $this->sendSms($recipient, $message, $company),
            'whatsapp' => $this->sendWhatsapp($recipient, $message, $company),
            'notification' => true,
            default => false,
        };
    }

    protected function integrationSettings(?Company $company = null): array
    {
        $company = $company ?? Workspace::company();
        if (! $company) {
            return [];
        }

        $row = CompanySetting::query()
            ->where('company_id', $company->id)
            ->where('group', 'integrations')
            ->first();

        return is_array($row?->payload) ? $row->payload : (is_array($row?->value) ? $row->value : []);
    }
}
