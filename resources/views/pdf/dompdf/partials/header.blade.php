@php
    $logoPath = public_path('logo.png');
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }
@endphp

<div class="px-10 py-8 flex justify-between items-start">
    <div class="flex flex-col gap-4">
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo" class="h-12 w-auto">
        @else
            <div class="text-2xl font-bold text-[#c02434] tracking-tight uppercase">SHEFFIELD</div>
        @endif

        <div class="mt-4">
            <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Tax Invoice</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-gray-500 font-medium">Invoice No:</span>
                <span class="text-gray-900 font-bold">#{{ $order->reference }}</span>
            </div>
        </div>
    </div>

    <div class="text-right">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 text-right">Supplier</div>
        <div class="text-base font-extrabold text-[#c02434] uppercase">Sheffield Steel Systems Limited</div>
        <div class="text-gray-600 space-y-0.5 mt-1">
            <div>Off Old Mombasa Road</div>
            <div>Opposite Hilton Garden Inn</div>
            <div>P.O. Box 48670-00100</div>
            <div>Nairobi, Kenya</div>
            <div class="pt-1 font-medium text-gray-700 font-bold">PIN: P051148391Z</div>
        </div>
    </div>
</div>
