<?php

namespace App\Ai\Providers;

class AzureOpenAiProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'azure_openai';
    }

    public function label(): string
    {
        return 'Azure OpenAI';
    }

    protected function defaultModel(): string
    {
        return 'gpt-4o-mini';
    }

    public function isConfigured(): bool
    {
        if (! parent::isConfigured()) {
            return false;
        }

        return ! empty($this->config?->base_url);
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $deployment = $this->config?->settings['deployment'] ?? $this->model();
        $apiVersion = $this->config?->settings['api_version'] ?? '2024-08-01-preview';
        $base = rtrim($this->config->base_url, '/');
        $url = "{$base}/openai/deployments/{$deployment}/chat/completions?api-version={$apiVersion}";

        $response = $this->http()
            ->withHeaders(['api-key' => $this->config->credentials['api_key'] ?? ''])
            ->post($url, [
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.3,
            ]);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'Azure OpenAI indisponible. Réponse locale activée.',
            ]));
        }

        $json = $response->json();

        return [
            'content' => $json['choices'][0]['message']['content'] ?? '',
            'provider' => $this->code(),
            'model' => $deployment,
            'usage' => $json['usage'] ?? [],
            'raw' => $json,
        ];
    }
}
