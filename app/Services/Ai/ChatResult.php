<?php

namespace App\Services\Ai;

/**
 * One assistant turn: either free-text content, or a request to call tools
 * (OpenAI-style `tool_calls`), or both.
 */
class ChatResult
{
    /**
     * @param  array<int, array{id?: string, type?: string, function?: array{name?: string, arguments?: string}}>  $toolCalls
     */
    public function __construct(
        public ?string $content = null,
        public array $toolCalls = [],
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
