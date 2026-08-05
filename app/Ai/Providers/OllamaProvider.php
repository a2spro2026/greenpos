<?php

namespace App\Ai\Providers;

class OllamaProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'ollama';
    }

    public function label(): string
    {
        return 'Ollama (Local)';
    }

    protected function defaultModel(): string
    {
        return 'llama3.2';
    }

    public function isConfigured(): bool
    {
        return (bool) ($this->config?->is_enabled);
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $base = rtrim($this->config?->base_url ?: 'http://127.0.0.1:11434', '/');
        $response = $this->http()
            ->withoutToken()
            ->post($base.'/api/chat', [
                'model' => $this->model(),
                'messages' => $messages,
                'stream' => false,
            ]);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'Ollama indisponible. Vérifiez que le serveur local tourne, puis réessayez.',
            ]));
        }

        $json = $response->json();

        return [
            'content' => $json['message']['content'] ?? '',
            'provider' => $this->code(),
            'model' => $json['model'] ?? $this->model(),
            'usage' => [],
            'raw' => $json,
        ];
    }
}
