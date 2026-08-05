<?php

namespace App\Ai\Contracts;

interface LlmProviderInterface
{
    public function code(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * @param  list<array{role:string,content:string}>  $messages
     * @return array{content:string,provider:string,model:?string,usage?:array,raw?:mixed}
     */
    public function chat(array $messages, array $options = []): array;
}
