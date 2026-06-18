<?php

namespace App\Notifications\Messages;

class WhatsAppMessage
{
    public string $language = 'en_US';

    /** @var array<int, array<string, mixed>> */
    public array $components = [];

    public function __construct(public string $template) {}

    public static function template(string $name): self
    {
        return new self($name);
    }

    public function language(string $code): self
    {
        $this->language = $code;

        return $this;
    }

    /**
     * Add body parameters (variable placeholders {{1}}, {{2}}, etc.).
     */
    public function body(string ...$params): self
    {
        if (! empty($params)) {
            $this->components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $p): array => ['type' => 'text', 'text' => $p],
                    $params
                ),
            ];
        }

        return $this;
    }
}
