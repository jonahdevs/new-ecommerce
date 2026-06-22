<?php

namespace App\Services\Ai;

use App\Settings\ChatbotSettings;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Resolves the configured AI chat provider. The active provider is chosen in
 * admin (ChatbotSettings), falling back to config/ai.php when settings are
 * unavailable (e.g. mid-migration). Add a new API shape by mapping its
 * `driver` here.
 */
class AiManager
{
    /**
     * Build the provider named (or the admin-selected default).
     */
    public function provider(?string $name = null): AiChatProvider
    {
        $name ??= $this->defaultProvider();

        /** @var array<string, mixed>|null $config */
        $config = config("ai.providers.{$name}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("AI provider [{$name}] is not configured.");
        }

        return match ($config['driver'] ?? 'openai') {
            'openai' => new OpenAiCompatibleProvider($config),
            default => throw new InvalidArgumentException(
                "Unsupported AI driver [{$config['driver']}] for provider [{$name}]."
            ),
        };
    }

    /**
     * Admin-selected provider, or the config default if settings aren't ready.
     */
    private function defaultProvider(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $provider = app(ChatbotSettings::class)->provider;

                if ($provider !== '') {
                    return $provider;
                }
            }
        } catch (\Throwable) {
            // Settings unavailable — fall through to the config default.
        }

        return (string) config('ai.default');
    }
}
