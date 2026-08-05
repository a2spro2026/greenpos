<?php

namespace App\Ai\Providers;

use App\Ai\Contracts\LlmProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;

abstract class AbstractHttpProvider implements LlmProviderInterface
{
    public function __construct(protected ?AiProvider $config = null)
    {
    }

    public function isConfigured(): bool
    {
        if (! $this->config || ! $this->config->is_enabled) {
            return false;
        }

        $creds = $this->config->credentials ?? [];

        return ! empty($creds['api_key'] ?? null);
    }

    protected function http()
    {
        $key = $this->config?->credentials['api_key'] ?? '';
        $timeout = (int) ($this->config?->settings['timeout'] ?? 45);

        return Http::timeout($timeout)
            ->withToken($key)
            ->acceptJson()
            ->asJson();
    }

    protected function model(): string
    {
        return $this->config?->model ?: $this->defaultModel();
    }

    abstract protected function defaultModel(): string;
}
