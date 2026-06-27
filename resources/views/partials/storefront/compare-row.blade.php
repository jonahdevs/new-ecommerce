{{-- props: $label, $cells (collection|array of strings), $emptyCount (int — number of trailing empty cells) --}}
<tr>
    <td class="sticky left-0 z-10 w-36 border-b border-zinc-200 bg-white px-4 py-3 text-center align-top text-[13px] font-semibold text-ink-2 lg:w-50">
        {{ $label }}
    </td>
    @foreach ($cells as $cell)
        <td class="border-b border-l border-zinc-200 px-4 py-3 text-center align-top text-[13.5px] text-ink">
            {{ $cell }}
        </td>
    @endforeach
    @for ($i = 0; $i < ($emptyCount ?? 0); $i++)
        <td class="hidden border-b border-l border-zinc-200 px-4 py-3 lg:table-cell"></td>
    @endfor
</tr>
