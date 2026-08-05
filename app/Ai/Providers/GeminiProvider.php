<?php

namespace App\Ai\Providers;

class GeminiProvider extends AbstractHttpProvider
{
    public function code(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Google Gemini';
    }

    protected function defaultModel(): string
    {
        return 'gemini-1.5-flash';
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return app(LocalProvider::class)->chat($messages, $options);
        }

        $key = $this->config->credentials['api_key'] ?? '';
        $model = $this->model();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $contents = collect($messages)
            ->reject(fn ($m) => ($m['role'] ?? '') === 'system')
            ->map(fn ($m) => [
                'role' => ($m['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content'] ?? '']],
            ])
            ->values()
            ->all();

        $system = collect($messages)->where('role', 'system')->pluck('content')->implode("\n");

        $payload = ['contents' => $contents];
        if ($system) {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        $response = $this->http()->post($url, $payload);

        if (! $response->successful()) {
            return app(LocalProvider::class)->chat($messages, array_merge($options, [
                'precomputed' => 'Gemini indisponible. Réponse locale activée.',
            ]));
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'content' => $text,
            'provider' => $this->code(),
            'model' => $model,
            'usage' => $json['usageMetadata'] ?? [],
            'raw' => $json,
        ];
    }
}
