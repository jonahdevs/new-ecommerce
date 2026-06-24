<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // CHATBOT
        // ==================================================
        $this->migrator->add('chatbot.enabled', true);
        $this->migrator->add('chatbot.provider', (string) config('ai.default', 'groq'));
        $this->migrator->add('chatbot.system_prompt', (string) config('ai.system_prompt', ''));
        $this->migrator->add('chatbot.greeting', 'Hi! I can help you find equipment, explain delivery & installation, or get you a quote.');
        $this->migrator->add('chatbot.product_search_enabled', true);
        $this->migrator->add('chatbot.order_lookup_enabled', true);
    }
};
