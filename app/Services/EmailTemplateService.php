<?php

namespace App\Services;

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;

class EmailTemplateService
{
    public function __construct() {}

    /**
     * Resolve subject + HTML for a given template type with variable substitution.
     * Returns null if no active DB template exists (caller should fall back to Blade).
     *
     * @param  array<string, string>  $variables
     * @return array{subject: string, html: string}|null
     */
    public function render(EmailTemplateType|string $type, array $variables = []): ?array
    {
        $template = EmailTemplate::query()
            ->active()
            ->byType($type)
            ->first();

        if (! $template || ! $template->body_html) {
            return null;
        }

        $subject = $this->interpolate($template->subject, $variables);
        $html    = $this->interpolate($template->body_html, $variables);

        return compact('subject', 'html');
    }

    /** Replace {{token}} placeholders with actual values. */
    private function interpolate(string $content, array $variables): string
    {
        foreach ($variables as $token => $value) {
            $placeholder = '{{' . ltrim(rtrim($token, '}}'), '{{') . '}}';
            $content = str_replace($placeholder, (string) $value, $content);
        }

        return $content;
    }
}
