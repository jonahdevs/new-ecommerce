<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            // Legal
            ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions'],
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
            ['slug' => 'returns-policy', 'title' => 'Returns Policy'],
            ['slug' => 'shipping-policy', 'title' => 'Shipping Policy'],
            ['slug' => 'cookie-policy', 'title' => 'Cookie Policy'],
            // Business / content
            ['slug' => 'trade-accounts', 'title' => 'Trade Accounts'],
            ['slug' => 'installation', 'title' => 'Installation'],
            ['slug' => 'service-contracts', 'title' => 'Service Contracts'],
            ['slug' => 'spec-sheets', 'title' => 'Spec Sheets'],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'body' => 'This content is managed in the admin under Content → Pages. Replace this placeholder with the real copy.',
                    'meta_description' => null,
                    'is_published' => true,
                ],
            );
        }
    }
}
