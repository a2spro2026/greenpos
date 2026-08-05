<?php

namespace App\Ai\Providers;

class MistralProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'mistral';
    }

    public function label(): string
    {
        return 'Mistral AI';
    }

    protected function defaultModel(): string
    {
        return 'mistral-small-latest';
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $base = rtrim($this->config?->base_url ?: 'https://api.mistral.ai/v1', '/');
        $response = $this->http()->post($base.'/chat/completions', [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
        ]);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'Mistral indisponible. Réponse locale activée.',
            ]));
        }

        $json = $response->json();

        return [
            'content' => $json['choices'][0]['message']['content'] ?? '',
            'provider' => $this->code(),
            'model' => $json['model'] ?? $this->model(),
            'usage' => $json['usage'] ?? [],
            'raw' => $json,
        ];
    }
}
