<?php

namespace App\Services\Ai;

use App\Services\Ai\Tools\Tool;

/**
 * Drives a conversation through the provider, executing any tools the model
 * asks for and feeding the results back until it produces a text answer.
 */
class ChatAssistant
{
    /** Safety cap on tool-call rounds so a misbehaving model can't loop forever. */
    private const MAX_STEPS = 4;

    /** @var array<string, Tool> */
    private array $tools;

    /**
     * @param  array<int, Tool>  $tools
     */
    public function __construct(private AiChatProvider $provider, array $tools = [])
    {
        $this->tools = collect($tools)->keyBy->name()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function reply(array $messages): string
    {
        $definitions = array_values(array_map(fn (Tool $tool) => $tool->definition(), $this->tools));

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $result = $this->provider->chat($messages, $definitions);

            if (! $result->hasToolCalls()) {
                return $result->content ?? '';
            }

            // Echo the model's tool-call turn, then append each tool's output.
            $messages[] = [
                'role' => 'assistant',
                'content' => $result->content ?? '',
                'tool_calls' => $result->toolCalls,
            ];

            foreach ($result->toolCalls as $call) {
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $this->runTool($call),
                ];
            }
        }

        // Exhausted the tool budget — force a final answer without tools.
        return $this->provider->chat($messages)->content ?? '';
    }

    /**
     * @param  array{function?: array{name?: string, arguments?: string}}  $call
     */
    private function runTool(array $call): string
    {
        $name = $call['function']['name'] ?? '';
        $tool = $this->tools[$name] ?? null;

        if (! $tool) {
            return 'Tool not available.';
        }

        /** @var array<string, mixed> $arguments */
        $arguments = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

        return $tool->handle($arguments);
    }
}
