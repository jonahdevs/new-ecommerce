<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The provider the chatbot talks to. Every provider below speaks the
    | OpenAI-compatible "chat/completions" dialect, so flipping this value
    | (or AI_PROVIDER in .env) is all it takes to switch between Groq,
    | OpenAI, Google Gemini, OpenRouter, or a local Ollama instance.
    |
    */

    'default' => env('AI_PROVIDER', 'groq'),

    /*
    |--------------------------------------------------------------------------
    | Generation Defaults
    |--------------------------------------------------------------------------
    */

    'max_tokens' => (int) env('AI_MAX_TOKENS', 1024),
    'temperature' => (float) env('AI_TEMPERATURE', 0.4),
    'timeout' => (int) env('AI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | The standing instructions prepended to every conversation. Keep it
    | grounded: never invent prices or stock, and steer buyers toward the
    | catalogue search and the Request-a-Quote flow.
    |
    */

    'system_prompt' => env('AI_SYSTEM_PROMPT', <<<'PROMPT'
        You are the online assistant for Sheffield Steel Systems (Sheffield Africa),
        East Africa's leading supplier of commercial kitchen, cold room, laundry and
        healthcare equipment since 2003.

        This is an e-commerce store. Customers can either buy products directly at
        checkout or submit a Request a Quote — offer whichever fits. Help visitors
        discover the right equipment, understand delivery, installation, warranty and
        spares. Keep replies short, friendly and practical.

        Rules:
        - Both buying online (checkout) and requesting a quote are available. Some
          products can be bought directly; others are quote-only. Use the search tool to
          tell which, and present the matching option(s) — never imply quotes are the
          only way to buy.
        - Only recommend products returned by the product search tool — these are the
          live, published catalogue. Never mention or invent a product that is not in the
          tool results, and ALWAYS include the product's link when you mention it.
        - Never invent prices, stock levels, lead times or model numbers. Only state a
          price if the search tool provides one; otherwise invite a quote for pricing.
        - If you are unsure or the question needs a human, suggest contacting the team on
          +254 713 777 111.
        - Stay on topic: commercial equipment, orders, quotes and support for this store.
        PROMPT),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Each provider shares the "openai" driver (the OpenAI-compatible REST
    | shape). To add a provider with a different API shape later (e.g. the
    | Anthropic Messages API), give it its own driver string and register a
    | matching adapter in App\Services\Ai\AiManager.
    |
    */

    'providers' => [

        'groq' => [
            'driver' => 'openai',
            'key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],

        'gemini' => [
            'driver' => 'openai',
            'key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],

        'openrouter' => [
            'driver' => 'openai',
            'key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct'),
        ],

        'ollama' => [
            'driver' => 'openai',
            'key' => env('OLLAMA_API_KEY', 'ollama'),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
            'model' => env('OLLAMA_MODEL', 'llama3.1'),
        ],

    ],

];
