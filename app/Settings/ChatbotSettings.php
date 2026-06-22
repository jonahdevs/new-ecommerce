<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ChatbotSettings extends Settings
{
    /** Master switch — when false the storefront chat widget is not rendered. */
    public bool $enabled = true;

    /** Active AI provider key (must exist in config/ai.php providers). */
    public string $provider = 'groq';

    /** Standing instructions sent with every conversation (tone + rules). */
    public string $system_prompt = '';

    /** First line shown in the empty chat panel. */
    public string $greeting = '';

    /** Allow the assistant to search the live catalogue. */
    public bool $product_search_enabled = true;

    /** Allow signed-in customers to look up their own orders and quotes. */
    public bool $order_lookup_enabled = true;

    public static function group(): string
    {
        return 'chatbot';
    }
}
