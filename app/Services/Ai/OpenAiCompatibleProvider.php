<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Talks to any provider that exposes the OpenAI "chat/completions" REST shape:
 * Groq, OpenAI, Google Gemini (OpenAI endpoint), OpenRouter and Ollama all do.
 * Swapping provider is just a different base URL, key and model — see config/ai.php.
 */
class OpenAiCompatibleProvider implements AiChatProvider
{
    /**
     * @param  array{key: ?string, base_url: string, model: string, max_tokens?: int, temperature?: float, timeout?: int}  $config
     */
    public function __construct(private array $config) {}

    public function chat(array $messages, array $tools = []): ChatResult
    {
        $payload = [
            'model' => $this->config['model'],
            'messages' => $messages,
            'max_tokens' => $this->config['max_tokens'] ?? config('ai.max_tokens', 1024),
            'temperature' => $this->config['temperature'] ?? config('ai.temperature', 0.4),
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $message = Http::baseUrl(rtrim($this->config['base_url'], '/'))
            ->withToken((string) ($this->config['key'] ?? ''))
            ->timeout($this->config['timeout'] ?? config('ai.timeout', 30))
            ->acceptJson()
            ->post('/chat/completions', $payload)
            ->throw()
            ->json('choices.0.message', []);

        $content = $message['content'] ?? null;

        return new ChatResult(
            is_string($content) ? trim($content) : null,
            $message['tool_calls'] ?? [],
        );
    }
}
