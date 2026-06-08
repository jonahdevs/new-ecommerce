<?php

namespace App\Support;

class AdminSettingsNav
{
    /**
     * The settings information architecture: top-level tabs, each with a subnav
     * of sections. Drives both the routes and the shared settings shell.
     *
     * @return array<string, array{label: string, icon: string, sections: array<string, array{label: string, icon: string}>}>
     */
    public static function tabs(): array
    {
        return [
            'general' => ['label' => 'General', 'icon' => 'building-storefront', 'sections' => [
                'profile' => ['label' => 'Profile', 'icon' => 'user'],
                'security' => ['label' => 'Security', 'icon' => 'shield-check'],
                'appearance' => ['label' => 'Appearance', 'icon' => 'swatch'],
                'notifications' => ['label' => 'My notifications', 'icon' => 'bell'],
            ]],
            'website' => ['label' => 'Website', 'icon' => 'window', 'sections' => [
                'business' => ['label' => 'Business info', 'icon' => 'identification'],
                'localization' => ['label' => 'Localization', 'icon' => 'globe-alt'],
                'seo' => ['label' => 'SEO', 'icon' => 'magnifying-glass'],
                'social' => ['label' => 'Social & sharing', 'icon' => 'share'],
                'analytics' => ['label' => 'Analytics', 'icon' => 'chart-bar'],
                'legal' => ['label' => 'Legal pages', 'icon' => 'document-text'],
            ]],
            'app' => ['label' => 'App', 'icon' => 'squares-2x2', 'sections' => [
                'inventory' => ['label' => 'Inventory', 'icon' => 'archive-box'],
                'reviews' => ['label' => 'Reviews', 'icon' => 'star'],
                'checkout' => ['label' => 'Checkout & cart', 'icon' => 'shopping-cart'],
                'quotations' => ['label' => 'Quotations', 'icon' => 'document-text'],
                'shipping' => ['label' => 'Shipping & delivery', 'icon' => 'truck'],
                'notifications' => ['label' => 'Notifications', 'icon' => 'bell'],
            ]],
            'system' => ['label' => 'System', 'icon' => 'server', 'sections' => [
                'email' => ['label' => 'Email & SMS', 'icon' => 'envelope'],
                'integrations' => ['label' => 'Integrations', 'icon' => 'puzzle-piece'],
                'security' => ['label' => 'Security', 'icon' => 'shield-check'],
                'maintenance' => ['label' => 'Maintenance', 'icon' => 'wrench-screwdriver'],
            ]],
            'financial' => ['label' => 'Financial', 'icon' => 'banknotes', 'sections' => [
                'payments' => ['label' => 'Payments', 'icon' => 'credit-card'],
                'tax' => ['label' => 'Tax', 'icon' => 'receipt-percent'],
                'currency' => ['label' => 'Currency & pricing', 'icon' => 'currency-dollar'],
            ]],
            'other' => ['label' => 'Other', 'icon' => 'settings', 'sections' => [
                'banned-ips' => ['label' => 'Banned IPs', 'icon' => 'no-symbol'],
                'backup' => ['label' => 'Backup', 'icon' => 'circle-stack'],
                'cache' => ['label' => 'Cache', 'icon' => 'bolt'],
            ]],
        ];
    }

    /** @return array<string, array{label: string, icon: string}> */
    public static function sectionsFor(string $tab): array
    {
        return self::tabs()[$tab]['sections'] ?? [];
    }
}
