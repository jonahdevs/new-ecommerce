@if($order->customer_note)
<div class="px-10 py-4 border-t border-gray-100">
    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Order Notes</div>
    <div class="text-xs text-gray-600 italic leading-relaxed">
        "{{ $order->customer_note }}"
    </div>
</div>
@endif
