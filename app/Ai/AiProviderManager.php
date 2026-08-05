<?php

namespace App\Ai;

use App\Ai\Contracts\LlmProviderInterface;
use App\Ai\Providers\AzureOpenAiProvider;
use App\Ai\Providers\ClaudeProvider;
use App\Ai\Providers\GeminiProvider;
use App\Ai\Providers\LocalProvider;
use App\Ai\Providers\MistralProvider;
use App\Ai\Providers\OllamaProvider;
use App\Ai\Providers\OpenAiProvider;
use App\Models\AiProvider;
use InvalidArgumentException;

class AiProviderManager
{
    /** @var array<string, class-string<LlmProviderInterface>> */
    protected array $drivers = [
        'local' => LocalProvider::class,
        'openai' => OpenAiProvider::class,
        'azure_openai' => AzureOpenAiProvider::class,
        'claude' => ClaudeProvider::class,
        'gemini' => GeminiProvider::class,
        'mistral' => MistralProvider::class,
        'ollama' => OllamaProvider::class,
    ];

    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function driver(?string $code = null): LlmProviderInterface
    {
        $code = $code ?: $this->defaultCode();

        if (! isset($this->drivers[$code])) {
            throw new InvalidArgumentException("Provider AI inconnu : {$code}");
        }

        $config = AiProvider::query()->where('code', $code)->first();

        return new $this->drivers[$code]($config);
    }

    public function defaultCode(): string
    {
        $default = AiProvider::query()->where('is_default', true)->where('is_enabled', true)->value('code');

        return $default ?: 'local';
    }

    public function codes(): array
    {
        return array_keys($this->drivers);
    }

    public function ensureDefaults(): void
    {
        $defs = [
            'local' => ['name' => 'GreenPOS Local AI', 'is_enabled' => true, 'is_default' => true, 'model' => 'greenpos-local-v1', 'status' => 'connected'],
            'openai' => ['name' => 'OpenAI', 'is_enabled' => false, 'model' => 'gpt-4o-mini', 'base_url' => 'https://api.openai.com/v1'],
            'azure_openai' => ['name' => 'Azure OpenAI', 'is_enabled' => false, 'model' => 'gpt-4o-mini'],
            'claude' => ['name' => 'Anthropic Claude', 'is_enabled' => false, 'model' => 'claude-3-5-haiku-latest', 'base_url' => 'https://api.anthropic.com'],
            'gemini' => ['name' => 'Google Gemini', 'is_enabled' => false, 'model' => 'gemini-1.5-flash'],
            'mistral' => ['name' => 'Mistral AI', 'is_enabled' => false, 'model' => 'mistral-small-latest', 'base_url' => 'https://api.mistral.ai/v1'],
            'ollama' => ['name' => 'Ollama (Local)', 'is_enabled' => false, 'model' => 'llama3.2', 'base_url' => 'http://127.0.0.1:11434'],
        ];

        foreach ($defs as $code => $attrs) {
            AiProvider::query()->firstOrCreate(
                ['code' => $code],
                array_merge($attrs, [
                    'credentials' => [],
                    'settings' => [],
                    'status_message' => $code === 'local'
                        ? 'Moteur embarqué prêt — aucune clé API requise.'
                        : 'Connecteur prêt — renseigner la clé API pour activer.',
                ])
            );
        }
    }
}
