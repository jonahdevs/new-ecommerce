<!--
  Brand utility classes — Sheffield's two brand colours (red + navy).

  The framework Layout hardcodes `@import "@maizzle/tailwindcss"`, which never
  loads our `tailwind.css` `@theme`, so `bg-brand`/`text-navy` would compile to
  nothing. Declaring them as plain CSS here lets the inliner apply them once
  (no duplicate compilation) while Layout's style tag handles every other class.
  Keep these hex values in sync with `tailwind.css`.

  Convention: navy = structure (header band, stepper, references, info boxes,
  links); red = action + urgency (CTA, headline total, expiry callout).
-->
<template>
  <style>
    .bg-brand { background-color: #c02434; }
    .bg-brand-dark { background-color: #9c1c29; }
    .bg-brand-tint { background-color: #fbeaec; }
    .text-brand { color: #c02434; }
    .border-brand { border-color: #c02434; }
    .bg-navy { background-color: #2f4196; }
    .bg-navy-dark { background-color: #243270; }
    .bg-navy-tint { background-color: #eef1fb; }
    .text-navy { color: #2f4196; }
    .border-navy { border-color: #2f4196; }
  </style>
</template>
