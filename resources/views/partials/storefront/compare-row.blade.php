{{-- props: $label, $cells (collection|array of strings), $empty (bool — render trailing empty cell) --}}
<tr>
    <td class="sticky left-0 z-10 w-[200px] border-b border-zinc-200 bg-surface-sunken px-4 py-3 align-top text-[13px] font-semibold text-ink-2">
        {{ $label }}
    </td>
    @foreach ($cells as $cell)
        <td class="border-b border-zinc-200 px-4 py-3 align-top text-[13.5px] text-ink">
            {{ $cell }}
        </td>
    @endforeach
    @if (! empty($empty))
        <td class="border-b border-zinc-200 px-4 py-3"></td>
    @endif
</tr>
