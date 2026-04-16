<div class="px-10 py-8">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b-2 border-gray-900">
                <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter w-8">#</th>
                <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter">Description / Item Code</th>
                <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-center w-16">Qty</th>
                <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-24">Unit Price</th>
                <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-32">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($order->items as $index => $item)
                @php
                    $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                    $sku = $item->product_snapshot['sku'] ?? '—';
                @endphp
                <tr>
                    <td class="py-4 text-gray-500 font-medium align-top text-xs">{{ $index + 1 }}</td>
                    <td class="py-4 align-top">
                        <div class="font-bold text-gray-900 text-xs">{{ $name }}</div>
                        @if($sku)
                            <div class="text-[10px] text-[#c02434] font-semibold mt-0.5 tracking-wider">{{ $sku }}</div>
                        @endif
                    </td>
                    <td class="py-4 text-center text-gray-900 font-medium align-top text-xs">{{ $item->quantity }}</td>
                    <td class="py-4 text-right text-gray-600 align-top text-xs">
                        {{ number_format($item->unit_price_cents / 100, 2) }}
                    </td>
                    <td class="py-4 text-right font-bold text-gray-900 align-top text-xs">
                        {{ number_format($item->total_cents / 100, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
