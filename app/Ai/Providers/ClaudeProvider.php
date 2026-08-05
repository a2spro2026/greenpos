<?php

namespace App\Ai\Providers;

class ClaudeProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'claude';
    }

    public function label(): string
    {
        return 'Anthropic Claude';
    }

    protected function defaultModel(): string
    {
        return 'claude-3-5-haiku-latest';
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $system = collect($messages)->where('role', 'system')->pluck('content')->implode("\n");
        $chat = collect($messages)->whereIn('role', ['user', 'assistant'])->values()->all();

        $response = $this->http()
            ->withHeaders([
                'x-api-key' => $this->config->credentials['api_key'] ?? '',
                'anthropic-version' => '2023-06-01',
            ])
            ->post(rtrim($this->config?->base_url ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $this->model(),
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'system' => $system ?: 'You are GreenPOS AI.',
                'messages' => $chat,
            ]);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'Claude indisponible. Réponse locale activée.',
            ]));
        }

        $json = $response->json();
        $text = collect($json['content'] ?? [])->where('type', 'text')->pluck('text')->implode("\n");

        return [
            'content' => $text,
            'provider' => $this->code(),
            'model' => $json['model'] ?? $this->model(),
            'usage' => $json['usage'] ?? [],
            'raw' => $json,
        ];
    }
}
