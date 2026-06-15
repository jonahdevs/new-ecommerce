<!--
  Quote progress stepper: Requested ✓ · Quoted ✓ · Accepted (pending).

  Two stacked table-fixed tables share the same 3-up geometry: the circles row
  insets each circle to 1/6 · 1/2 · 5/6 via leading/trailing spacer cells, and
  each connector is a SINGLE cell (never two half-lines meeting) so there is no
  sub-pixel seam — the near-white "Accepted" line stays unbroken on hi-DPI
  screens. The labels table uses three equal columns so labels centre on the
  same 1/6 · 1/2 · 5/6 points, directly under each circle.
-->
<template>
  <table class="w-full table-fixed" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td style="width: 10.67%;"></td>
      <td style="width: 12%;" class="align-middle"><div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div></td>
      <td style="width: 21.33%;" class="align-middle"><div class="h-[3px] w-full bg-navy"></div></td>
      <td style="width: 12%;" class="align-middle"><div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div></td>
      <td style="width: 21.33%;" class="align-middle"><div class="h-[3px] w-full bg-slate-200"></div></td>
      <td style="width: 12%;" class="align-middle"><div class="mx-auto h-9 w-9 rounded-[9999px] border border-slate-200 bg-slate-50 text-center text-sm font-bold leading-9 text-slate-400">3</div></td>
      <td style="width: 10.67%;"></td>
    </tr>
  </table>
  <table class="w-full table-fixed" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td style="width: 33.333%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide text-navy">Requested</p></td>
      <td style="width: 33.333%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide text-navy">Quoted</p></td>
      <td style="width: 33.333%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide text-slate-400">Accepted</p></td>
    </tr>
  </table>
</template>
