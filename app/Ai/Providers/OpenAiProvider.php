<?php

namespace App\Ai\Providers;

class OpenAiProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    protected function defaultModel(): string
    {
        return 'gpt-4o-mini';
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $base = rtrim($this->config?->base_url ?: 'https://api.openai.com/v1', '/');
        $response = $this->http()->post($base.'/chat/completions', [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
        ]);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'OpenAI indisponible ('.$response->status().'). Réponse locale activée.',
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
