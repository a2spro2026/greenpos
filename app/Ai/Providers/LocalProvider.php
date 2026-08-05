<?php

namespace App\Ai\Providers;

use App\Ai\Contracts\LlmProviderInterface;
use App\Models\AiProvider;

/**
 * Moteur local déterministe — fonctionne sans clé API.
 * Les providers cloud délèguent ici en fallback.
 */
class LocalProvider implements LlmProviderInterface
{
    public function __construct(protected ?AiProvider $config = null)
    {
    }

    public function code(): string
    {
        return 'local';
    }

    public function label(): string
    {
        return 'GreenPOS Local AI';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function chat(array $messages, array $options = []): array
    {
        // LocalProvider is orchestrated by AiAssistantService; this is a passthrough fallback.
        $lastUser = collect($messages)->reverse()->firstWhere('role', 'user');
        $content = is_array($lastUser) ? ($lastUser['content'] ?? '') : '';

        return [
            'content' => $options['precomputed'] ?? "Je suis l’assistant GreenPOS. Posez une question métier ou demandez-moi de retrouver un client, un produit, une facture ou une vente.",
            'provider' => $this->code(),
            'model' => 'greenpos-local-v1',
            'usage' => ['prompt' => mb_strlen($content), 'completion' => 0],
        ];
    }
}
