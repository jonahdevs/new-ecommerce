<!--
  Invoice-Pro footer — a navy bar that closes the card. Optional white social
  icons (only those configured in SocialSettings), then copyright on the left
  and contact / website on the right. Pulls live values from SocialSettings /
  BusinessSettings. Transactional: no unsubscribe.
-->
<template>
  <Section class="bg-navy-dark px-9 py-6">
    <Raw>
      @php
          $social = app(\App\Settings\SocialSettings::class);
          $business = app(\App\Settings\BusinessSettings::class);
          $siteUrl = preg_replace('#^https?://#', '', rtrim(config('app.url'), '/'));
          $socials = array_filter([
              'facebook' => $social->facebook_url,
              'instagram' => $social->instagram_url,
              'x' => $social->x_url,
              'linkedin' => $social->linkedin_url,
              'youtube' => $social->youtube_url,
          ]);
      @endphp
      @if (! empty($socials))
        <table class="mx-auto mb-4" cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            @if ($social->facebook_url)
              <td class="px-[5px]"><a href="{{ $social->facebook_url }}" class="inline-block h-8 w-8 rounded-[9999px] bg-white/10 text-center leading-8"><svg viewBox="0 0 24 24" class="inline-block h-4 w-4 align-middle"><path fill="#ffffff" d="M13.5 21v-7h2.3l.4-2.7h-2.7V9.6c0-.8.3-1.3 1.4-1.3h1.4V5.9c-.7-.1-1.4-.1-2-.1-2 0-3.5 1.2-3.5 3.5v1.9H8.5V14h2.3v7h2.7z"/></svg></a></td>
            @endif
            @if ($social->instagram_url)
              <td class="px-[5px]"><a href="{{ $social->instagram_url }}" class="inline-block h-8 w-8 rounded-[9999px] bg-white/10 text-center leading-8"><svg viewBox="0 0 24 24" class="inline-block h-4 w-4 align-middle"><path fill="#ffffff" d="M12 8.1A3.9 3.9 0 1 0 12 15.9 3.9 3.9 0 0 0 12 8.1zm0 6.4a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm4-6.6a.9.9 0 1 1-1.8 0 .9.9 0 0 1 1.8 0zM17.7 9c-.1-1-.3-2-1.1-2.7-.7-.8-1.7-1-2.7-1.1-1.1-.1-4.3-.1-5.4 0-1 .1-2 .3-2.7 1.1-.8.7-1 1.7-1.1 2.7-.1 1.1-.1 4.3 0 5.4.1 1 .3 2 1.1 2.7.7.8 1.7 1 2.7 1.1 1.1.1 4.3.1 5.4 0 1-.1 2-.3 2.7-1.1.8-.7 1-1.7 1.1-2.7.1-1.1.1-4.3 0-5.4zm-1.5 6.5c-.2.6-.7 1-1.3 1.3-.9.4-3 .3-4 .3s-3.1.1-4-.3c-.6-.2-1-.7-1.3-1.3-.4-.9-.3-3-.3-4s-.1-3.1.3-4c.2-.6.7-1 1.3-1.3.9-.4 3-.3 4-.3s3.1-.1 4 .3c.6.2 1 .7 1.3 1.3.4.9.3 3 .3 4s.1 3.1-.3 4z"/></svg></a></td>
            @endif
            @if ($social->x_url)
              <td class="px-[5px]"><a href="{{ $social->x_url }}" class="inline-block h-8 w-8 rounded-[9999px] bg-white/10 text-center leading-8"><svg viewBox="0 0 24 24" class="inline-block h-[14px] w-[14px] align-middle"><path fill="#ffffff" d="M17.5 5h-2l-3.1 4L9.6 5H5.5l4.6 6.3L5.7 19h2l3.4-4.4L14.4 19h4.1l-4.8-6.6L17.5 5zm-2.6 12.5L7.9 6.4h1.2l7 11.1h-1.2z"/></svg></a></td>
            @endif
            @if ($social->linkedin_url)
              <td class="px-[5px]"><a href="{{ $social->linkedin_url }}" class="inline-block h-8 w-8 rounded-[9999px] bg-white/10 text-center leading-8"><svg viewBox="0 0 24 24" class="inline-block h-4 w-4 align-middle"><path fill="#ffffff" d="M8 9.6H5.7V18H8V9.6zM6.85 8.5a1.35 1.35 0 1 0 0-2.7 1.35 1.35 0 0 0 0 2.7zM18.3 18H16v-4.2c0-1-.4-1.7-1.3-1.7-.7 0-1.1.5-1.3 1-.1.2-.1.4-.1.6V18H11s.03-7.4 0-8.4h2.3v1.2c.3-.5.85-1.2 2.1-1.2 1.5 0 2.7 1 2.7 3.1V18z"/></svg></a></td>
            @endif
            @if ($social->youtube_url)
              <td class="px-[5px]"><a href="{{ $social->youtube_url }}" class="inline-block h-8 w-8 rounded-[9999px] bg-white/10 text-center leading-8"><svg viewBox="0 0 24 24" class="inline-block h-4 w-4 align-middle"><path fill="#ffffff" d="M20 8.6c-.2-.9-.8-1.5-1.6-1.7C16.9 6.5 12 6.5 12 6.5s-4.9 0-6.4.4c-.8.2-1.4.8-1.6 1.7C3.6 10.1 3.6 12 3.6 12s0 1.9.4 3.4c.2.9.8 1.5 1.6 1.7 1.5.4 6.4.4 6.4.4s4.9 0 6.4-.4c.8-.2 1.4-.8 1.6-1.7.4-1.5.4-3.4.4-3.4s0-1.9-.4-3.4zM10.5 14.5v-5l4.3 2.5-4.3 2.5z"/></svg></a></td>
            @endif
          </tr>
        </table>
      @endif
      <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td class="align-middle text-[11px] text-white/60">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</td>
          <td class="text-right align-middle text-[11px] text-white/70">
            @if ($business->contact_email)<a href="mailto:{{ $business->contact_email }}" class="text-white/80 no-underline">{{ $business->contact_email }}</a> &nbsp;&middot;&nbsp; @endif<a href="{{ config('app.url') }}" class="text-white/80 no-underline">{{ $siteUrl }}</a>
          </td>
        </tr>
      </table>
    </Raw>
  </Section>
</template>
