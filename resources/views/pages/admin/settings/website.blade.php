<?php

use App\Settings\AnalyticsSettings;
use App\Settings\BrandingSettings;
use App\Settings\BusinessSettings;
use App\Settings\LegalSettings;
use App\Settings\LocalizationSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Website settings — Admin')] class extends Component
{
    use WithFileUploads;

    #[Url]
    public string $section = 'business';

    // ─── Business info ───────────────────────────────────────────────────────────
    public string $legal_name = '';

    public string $trading_name = '';

    public string $registration_number = '';

    public string $tax_pin = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $address = '';

    public string $business_hours = '';

    public string $store_name = '';

    public string $tagline = '';

    public ?string $logo_path = null;

    public ?string $favicon_path = null;

    public string $brand_color = '#b91c1c';

    public $pendingLogo = null;

    public $pendingFavicon = null;

    // ─── Localization ──────────────────────────────────────────────────────────
    public string $country = 'KE';

    public string $language = 'en';

    public string $currency = 'KES';

    public string $timezone = 'Africa/Nairobi';

    public string $date_format = 'd M Y';

    public string $weight_unit = 'g';

    public string $dimension_unit = 'mm';

    // ─── SEO ───────────────────────────────────────────────────────────────────
    public string $meta_title_pattern = '';

    public string $default_meta_description = '';

    public string $default_meta_keywords = '';

    public bool $index_site = true;

    public bool $generate_sitemap = true;

    // ─── Social ────────────────────────────────────────────────────────────────
    public ?string $og_image_path = null;

    public $pendingOgImage = null;

    public string $twitter_handle = '';

    public string $facebook_url = '';

    public string $instagram_url = '';

    public string $x_url = '';

    public string $linkedin_url = '';

    public string $youtube_url = '';

    public string $tiktok_url = '';

    public string $whatsapp_number = '';

    // ─── Analytics ─────────────────────────────────────────────────────────────
    public string $ga4_id = '';

    public string $gtm_id = '';

    public string $meta_pixel_id = '';

    public string $tiktok_pixel_id = '';

    // ─── Legal ─────────────────────────────────────────────────────────────────
    public string $terms_conditions = '';

    public string $privacy_policy = '';

    public string $returns_policy = '';

    public string $shipping_policy = '';

    public bool $cookie_consent_enabled = false;

    public function mount(
        BusinessSettings $business,
        BrandingSettings $branding,
        LocalizationSettings $localization,
        SeoSettings $seo,
        SocialSettings $social,
        AnalyticsSettings $analytics,
        LegalSettings $legal,
    ): void {
        $this->legal_name = $business->legal_name;
        $this->trading_name = $business->trading_name;
        $this->registration_number = $business->registration_number;
        $this->tax_pin = $business->tax_pin;
        $this->contact_email = $business->contact_email;
        $this->contact_phone = $business->contact_phone;
        $this->address = $business->address;
        $this->business_hours = $business->business_hours;

        $this->store_name = $branding->store_name;
        $this->tagline = $branding->tagline;
        $this->logo_path = $branding->logo_path;
        $this->favicon_path = $branding->favicon_path;
        $this->brand_color = $branding->brand_color;

        $this->country = $localization->country;
        $this->language = $localization->language;
        $this->currency = $localization->currency;
        $this->timezone = $localization->timezone;
        $this->date_format = $localization->date_format;
        $this->weight_unit = $localization->weight_unit;
        $this->dimension_unit = $localization->dimension_unit;

        $this->meta_title_pattern = $seo->meta_title_pattern;
        $this->default_meta_description = $seo->default_meta_description;
        $this->default_meta_keywords = $seo->default_meta_keywords;
        $this->index_site = $seo->index_site;
        $this->generate_sitemap = $seo->generate_sitemap;

        $this->og_image_path = $social->og_image_path;
        $this->twitter_handle = $social->twitter_handle;
        $this->facebook_url = $social->facebook_url;
        $this->instagram_url = $social->instagram_url;
        $this->x_url = $social->x_url;
        $this->linkedin_url = $social->linkedin_url;
        $this->youtube_url = $social->youtube_url;
        $this->tiktok_url = $social->tiktok_url;
        $this->whatsapp_number = $social->whatsapp_number;

        $this->ga4_id = $analytics->ga4_id;
        $this->gtm_id = $analytics->gtm_id;
        $this->meta_pixel_id = $analytics->meta_pixel_id;
        $this->tiktok_pixel_id = $analytics->tiktok_pixel_id;

        $this->terms_conditions = $legal->terms_conditions;
        $this->privacy_policy = $legal->privacy_policy;
        $this->returns_policy = $legal->returns_policy;
        $this->shipping_policy = $legal->shipping_policy;
        $this->cookie_consent_enabled = $legal->cookie_consent_enabled;
    }

    public function saveBusiness(BusinessSettings $business, BrandingSettings $branding): void
    {
        $this->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'trading_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_pin' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'business_hours' => ['nullable', 'string', 'max:255'],
            'store_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'brand_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pendingLogo' => ['nullable', 'image', 'max:2048'],
            'pendingFavicon' => ['nullable', 'image', 'max:512'],
        ]);

        if ($this->pendingLogo) {
            if ($this->logo_path) {
                Storage::disk('public')->delete($this->logo_path);
            }
            $this->logo_path = $this->pendingLogo->store('branding', 'public');
            $this->pendingLogo = null;
        }

        if ($this->pendingFavicon) {
            if ($this->favicon_path) {
                Storage::disk('public')->delete($this->favicon_path);
            }
            $this->favicon_path = $this->pendingFavicon->store('branding', 'public');
            $this->pendingFavicon = null;
        }

        $business->fill([
            'legal_name' => $this->legal_name,
            'trading_name' => $this->trading_name,
            'registration_number' => $this->registration_number,
            'tax_pin' => $this->tax_pin,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'business_hours' => $this->business_hours,
        ])->save();

        $branding->fill([
            'store_name' => $this->store_name,
            'tagline' => $this->tagline,
            'logo_path' => $this->logo_path,
            'favicon_path' => $this->favicon_path,
            'brand_color' => $this->brand_color,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Business info updated.', variant: 'success');
    }

    public function removeLogo(BrandingSettings $settings): void
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }
        $this->logo_path = null;
        $this->pendingLogo = null;
        $settings->logo_path = null;
        $settings->save();
    }

    public function saveLocalization(LocalizationSettings $settings): void
    {
        $this->validate([
            'country' => ['required', 'string', 'size:2'],
            'language' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'timezone'],
            'date_format' => ['required', 'string', 'max:20'],
            'weight_unit' => ['required', 'string', 'in:kg,g,lb'],
            'dimension_unit' => ['required', 'string', 'in:cm,mm,in'],
        ]);

        $settings->fill([
            'country' => strtoupper($this->country),
            'language' => $this->language,
            'currency' => strtoupper($this->currency),
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
            'weight_unit' => $this->weight_unit,
            'dimension_unit' => $this->dimension_unit,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Localization updated.', variant: 'success');
    }

    public function saveSeo(SeoSettings $settings): void
    {
        $this->validate([
            'meta_title_pattern' => ['required', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:500'],
            'default_meta_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        $settings->fill([
            'meta_title_pattern' => $this->meta_title_pattern,
            'default_meta_description' => $this->default_meta_description,
            'default_meta_keywords' => $this->default_meta_keywords,
            'index_site' => $this->index_site,
            'generate_sitemap' => $this->generate_sitemap,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'SEO settings updated.', variant: 'success');
    }

    public function saveSocial(SocialSettings $settings): void
    {
        $this->validate([
            'twitter_handle' => ['nullable', 'string', 'max:50'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'pendingOgImage' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->pendingOgImage) {
            if ($this->og_image_path) {
                Storage::disk('public')->delete($this->og_image_path);
            }
            $this->og_image_path = $this->pendingOgImage->store('branding', 'public');
            $this->pendingOgImage = null;
        }

        $settings->fill([
            'og_image_path' => $this->og_image_path,
            'twitter_handle' => ltrim($this->twitter_handle, '@'),
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'x_url' => $this->x_url,
            'linkedin_url' => $this->linkedin_url,
            'youtube_url' => $this->youtube_url,
            'tiktok_url' => $this->tiktok_url,
            'whatsapp_number' => $this->whatsapp_number,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Social links updated.', variant: 'success');
    }

    public function saveAnalytics(AnalyticsSettings $settings): void
    {
        $this->validate([
            'ga4_id' => ['nullable', 'string', 'max:50'],
            'gtm_id' => ['nullable', 'string', 'max:50'],
            'meta_pixel_id' => ['nullable', 'string', 'max:50'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:50'],
        ]);

        $settings->fill([
            'ga4_id' => $this->ga4_id,
            'gtm_id' => $this->gtm_id,
            'meta_pixel_id' => $this->meta_pixel_id,
            'tiktok_pixel_id' => $this->tiktok_pixel_id,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Analytics updated.', variant: 'success');
    }

    public function saveLegal(LegalSettings $settings): void
    {
        $this->validate([
            'terms_conditions' => ['nullable', 'string', 'max:50000'],
            'privacy_policy' => ['nullable', 'string', 'max:50000'],
            'returns_policy' => ['nullable', 'string', 'max:50000'],
            'shipping_policy' => ['nullable', 'string', 'max:50000'],
        ]);

        $settings->fill([
            'terms_conditions' => $this->terms_conditions,
            'privacy_policy' => $this->privacy_policy,
            'returns_policy' => $this->returns_policy,
            'shipping_policy' => $this->shipping_policy,
            'cookie_consent_enabled' => $this->cookie_consent_enabled,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Legal pages updated.', variant: 'success');
    }

    /** @return array<int, string> */
    #[Computed]
    public function timezones(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function getLogoPreview(): ?string
    {
        if ($this->pendingLogo) {
            return $this->pendingLogo->temporaryUrl();
        }

        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getOgPreview(): ?string
    {
        if ($this->pendingOgImage) {
            return $this->pendingOgImage->temporaryUrl();
        }

        return $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null;
    }
}; ?>

<x-admin.settings-shell tab="website" :section="$section">

    {{-- Business info (company details + branding) --}}
    @if ($section === 'business')
        <flux:card>
            <flux:heading>Business info</flux:heading>
            <flux:subheading>Legal, contact and brand details for your company.</flux:subheading>

            <form wire:submit="saveBusiness" class="mt-6 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="legal_name" label="Legal name" required />
                    <flux:input wire:model="trading_name" label="Trading name" required />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="registration_number" label="Registration no." placeholder="e.g. PVT-XXXXXX" />
                    <flux:input wire:model="tax_pin" label="Tax PIN / VAT no." placeholder="e.g. P05XXXXXXX" />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="contact_email" type="email" label="Contact email" placeholder="hello@store.com" />
                    <flux:input wire:model="contact_phone" label="Contact phone" placeholder="+254 700 000 000" />
                </div>
                <flux:textarea wire:model="address" label="Address" rows="3" placeholder="123 Main St, Nairobi, Kenya" />
                <flux:input wire:model="business_hours" label="Business hours" placeholder="Mon–Fri 8am–5pm, Sat 9am–1pm" />

                <flux:separator />
                <flux:text size="sm" class="font-medium text-zinc-500">Branding</flux:text>

                <flux:input wire:model="store_name" label="Store name" required />
                <flux:input wire:model="tagline" label="Tagline" placeholder="Quality products, delivered fast" />

                {{-- Logo --}}
                <div>
                    <flux:label>Logo</flux:label>
                    <div class="mt-2 flex items-center gap-4">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            @if ($this->getLogoPreview())
                                <img src="{{ $this->getLogoPreview() }}" alt="Logo" class="h-full w-full object-contain" />
                            @else
                                <flux:icon.photo class="size-7 text-zinc-300 dark:text-zinc-600" />
                            @endif
                        </div>
                        <div class="space-y-2">
                            <input type="file" wire:model="pendingLogo" accept="image/*" class="block text-sm text-zinc-500 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
                            @if ($logo_path || $pendingLogo)
                                <flux:button size="xs" variant="ghost" type="button" wire:click="removeLogo" class="text-red-500!">Remove</flux:button>
                            @endif
                        </div>
                    </div>
                    @error('pendingLogo') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                {{-- Favicon --}}
                <div>
                    <flux:label>Favicon</flux:label>
                    <input type="file" wire:model="pendingFavicon" accept="image/*" class="mt-2 block text-sm text-zinc-500 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
                    @error('pendingFavicon') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                {{-- Brand color --}}
                <div>
                    <flux:label>Brand color</flux:label>
                    <div class="mt-2 flex items-center gap-3">
                        <input type="color" wire:model="brand_color" class="h-10 w-14 cursor-pointer rounded-md border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" />
                        <flux:input wire:model="brand_color" class="w-36 font-mono" />
                    </div>
                    @error('brand_color') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Localization --}}
    @if ($section === 'localization')
        <flux:card>
            <flux:heading>Localization</flux:heading>
            <flux:subheading>Regional formats, currency and units.</flux:subheading>

            <form wire:submit="saveLocalization" class="mt-6 space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="country" label="Country code" placeholder="KE" maxlength="2" class="uppercase" />
                    <flux:select wire:model="language" label="Language">
                        <flux:select.option value="en">English</flux:select.option>
                        <flux:select.option value="sw">Swahili</flux:select.option>
                        <flux:select.option value="fr">French</flux:select.option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="currency" label="Currency">
                        <flux:select.option value="KES">KES — Kenyan Shilling</flux:select.option>
                        <flux:select.option value="USD">USD — US Dollar</flux:select.option>
                        <flux:select.option value="EUR">EUR — Euro</flux:select.option>
                        <flux:select.option value="GBP">GBP — British Pound</flux:select.option>
                        <flux:select.option value="UGX">UGX — Ugandan Shilling</flux:select.option>
                        <flux:select.option value="TZS">TZS — Tanzanian Shilling</flux:select.option>
                        <flux:select.option value="ZAR">ZAR — South African Rand</flux:select.option>
                    </flux:select>
                    <flux:select wire:model="timezone" label="Timezone" searchable>
                        @foreach ($this->timezones as $tz)
                            <flux:select.option :value="$tz">{{ $tz }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="date_format" label="Date format">
                    <flux:select.option value="d M Y">{{ now()->format('d M Y') }} (d M Y)</flux:select.option>
                    <flux:select.option value="d/m/Y">{{ now()->format('d/m/Y') }} (d/m/Y)</flux:select.option>
                    <flux:select.option value="m/d/Y">{{ now()->format('m/d/Y') }} (m/d/Y)</flux:select.option>
                    <flux:select.option value="Y-m-d">{{ now()->format('Y-m-d') }} (Y-m-d)</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="weight_unit" label="Weight unit">
                        <flux:select.option value="kg">Kilograms (kg)</flux:select.option>
                        <flux:select.option value="g">Grams (g)</flux:select.option>
                        <flux:select.option value="lb">Pounds (lb)</flux:select.option>
                    </flux:select>
                    <flux:select wire:model="dimension_unit" label="Dimension unit">
                        <flux:select.option value="cm">Centimeters (cm)</flux:select.option>
                        <flux:select.option value="mm">Millimeters (mm)</flux:select.option>
                        <flux:select.option value="in">Inches (in)</flux:select.option>
                    </flux:select>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- SEO --}}
    @if ($section === 'seo')
        <flux:card>
            <flux:heading>SEO</flux:heading>
            <flux:subheading>Defaults for search engine results.</flux:subheading>

            <form wire:submit="saveSeo" class="mt-6 space-y-5">
                <flux:input wire:model="meta_title_pattern" label="Meta title pattern"
                    description="Use {page} and {site} as placeholders." required />
                <flux:textarea wire:model="default_meta_description" label="Default meta description" rows="3"
                    placeholder="Shown when a page has no description of its own." />
                <flux:input wire:model="default_meta_keywords" label="Default meta keywords" placeholder="comma, separated, keywords" />

                <flux:separator />

                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Allow search engines to index the site</flux:label>
                        <flux:text size="sm" class="text-xs">Turn off to add a noindex directive site-wide.</flux:text>
                    </div>
                    <flux:switch wire:model="index_site" />
                </div>
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Generate XML sitemap</flux:label>
                    <flux:switch wire:model="generate_sitemap" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Social & sharing --}}
    @if ($section === 'social')
        <flux:card>
            <flux:heading>Social & sharing</flux:heading>
            <flux:subheading>Share-card image and the profiles shown in your footer.</flux:subheading>

            <form wire:submit="saveSocial" class="mt-6 space-y-5">
                <div>
                    <flux:label>Default share image (Open Graph)</flux:label>
                    <div class="mt-2 flex items-center gap-4">
                        <div class="flex h-20 w-36 items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            @if ($this->getOgPreview())
                                <img src="{{ $this->getOgPreview() }}" alt="OG image" class="h-full w-full object-cover" />
                            @else
                                <flux:icon.photo class="size-7 text-zinc-300 dark:text-zinc-600" />
                            @endif
                        </div>
                        <input type="file" wire:model="pendingOgImage" accept="image/*" class="block text-sm text-zinc-500 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
                    </div>
                    @error('pendingOgImage') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <flux:input wire:model="twitter_handle" label="X / Twitter handle" placeholder="@yourstore" />

                <flux:separator />
                <flux:text size="sm" class="font-medium text-zinc-500">Footer profile links</flux:text>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="facebook_url" label="Facebook" icon="link" placeholder="https://facebook.com/…" />
                    <flux:input wire:model="instagram_url" label="Instagram" icon="link" placeholder="https://instagram.com/…" />
                    <flux:input wire:model="x_url" label="X / Twitter" icon="link" placeholder="https://x.com/…" />
                    <flux:input wire:model="linkedin_url" label="LinkedIn" icon="link" placeholder="https://linkedin.com/…" />
                    <flux:input wire:model="youtube_url" label="YouTube" icon="link" placeholder="https://youtube.com/…" />
                    <flux:input wire:model="tiktok_url" label="TikTok" icon="link" placeholder="https://tiktok.com/@…" />
                    <flux:input wire:model="whatsapp_number" label="WhatsApp number" placeholder="+254 700 000 000" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Analytics --}}
    @if ($section === 'analytics')
        <flux:card>
            <flux:heading>Analytics & tracking</flux:heading>
            <flux:subheading>Drop in your tracking IDs — scripts load on the storefront.</flux:subheading>

            <form wire:submit="saveAnalytics" class="mt-6 space-y-5">
                <flux:input wire:model="ga4_id" label="Google Analytics 4 (Measurement ID)" placeholder="G-XXXXXXXXXX" />
                <flux:input wire:model="gtm_id" label="Google Tag Manager ID" placeholder="GTM-XXXXXXX" />
                <flux:input wire:model="meta_pixel_id" label="Meta (Facebook) Pixel ID" placeholder="000000000000000" />
                <flux:input wire:model="tiktok_pixel_id" label="TikTok Pixel ID" placeholder="XXXXXXXXXXXXXXXXXXXX" />

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- Legal pages --}}
    @if ($section === 'legal')
        <flux:card>
            <flux:heading>Legal pages</flux:heading>
            <flux:subheading>Policy content shown across your storefront.</flux:subheading>

            <form wire:submit="saveLegal" class="mt-6 space-y-5">
                <flux:textarea wire:model="terms_conditions" label="Terms & conditions" rows="5" />
                <flux:textarea wire:model="privacy_policy" label="Privacy policy" rows="5" />
                <flux:textarea wire:model="returns_policy" label="Returns policy" rows="5" />
                <flux:textarea wire:model="shipping_policy" label="Shipping policy" rows="5" />

                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Cookie consent banner</flux:label>
                        <flux:text size="sm" class="text-xs">Show a cookie consent notice to visitors.</flux:text>
                    </div>
                    <flux:switch wire:model="cookie_consent_enabled" />
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

</x-admin.settings-shell>
