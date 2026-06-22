<?php

namespace App\Services\Ai;

interface AiChatProvider
{
    /**
     * Send a conversation (optionally advertising tools the model may call)
     * and return the assistant's reply.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools  OpenAI-style tool/function definitions.
     */
    public function chat(array $messages, array $tools = []): ChatResult;
}
