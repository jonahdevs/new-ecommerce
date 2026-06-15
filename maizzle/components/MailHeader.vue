<!--
  Invoice-Pro hero band (navy). Carries the whole opening: a top bar with the
  reversed logo + a "Shop" link, a centred outlined check/icon chip, the big
  title, a greeting row with a reference pill, a divider, then the intro.

  Slots (Blade echoes in any slot must be wrapped in a Raw tag by the caller):
    icon     — inline SVG, white stroke, sits in the outlined circle
    title    — white headline
    greeting — bold white line + muted sub-line (an account avatar sits opposite)
    intro    — white paragraph

  Logo: reversed white logo bare on navy via $emailLogoInverseUrl; a custom
  merchant logo (no reversed variant) falls back to the colour logo on a white
  pill. NB: never write a literal Raw open tag in a comment in this file — the
  rawExtract plugin would pair it with a real closing tag and corrupt the build.
-->
<template>
  <Section class="bg-navy px-9 pt-6 pb-8">
    <!-- top bar: logo + shop link -->
    <Raw>
      <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td class="align-middle">@if (! empty($emailLogoInverseUrl))<img src="{{ $emailLogoInverseUrl }}" width="150" alt="Sheffield" class="block" />@endif@if (empty($emailLogoInverseUrl))<span class="inline-block rounded-md bg-white px-3 py-2"><img src="{{ $emailLogoUrl }}" width="104" alt="Sheffield" class="block" /></span>@endif</td>
          <td class="text-right align-middle"><a href="{{ route('catalog') }}" class="text-[13px] font-semibold text-white/80 no-underline">Shop &rsaquo;</a></td>
        </tr>
      </table>
    </Raw>

    <div class="my-[18px] h-px bg-white/20"></div>

    <!-- check + title -->
    <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="pt-2 text-center">
          <div class="mx-auto mb-4 h-16 w-16 rounded-[9999px] border-2 border-white/40 text-center leading-[60px]">
            <slot name="icon" />
          </div>
          <Heading class="m-0 text-center text-[25px] font-extrabold leading-tight text-white"><slot name="title" /></Heading>
        </td>
      </tr>
    </table>

    <!-- greeting + account avatar -->
    <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="pt-6 align-middle"><slot name="greeting" /></td>
        <td class="pt-6 text-right align-middle">
          <div class="inline-block h-11 w-11 rounded-[9999px] bg-white/15 text-center leading-[44px]">
            <svg viewBox="0 0 24 24" fill="#ffffff" class="inline-block h-6 w-6 align-middle"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2.5c-3.34 0-10 1.67-10 5V21h20v-1.5c0-3.33-6.66-5-10-5Z" /></svg>
          </div>
        </td>
      </tr>
    </table>

    <div class="my-[18px] h-px bg-white/20"></div>

    <slot name="intro" />
  </Section>
</template>
