<div class="px-10 py-10 mt-auto bg-gray-900 text-white rounded-t-[40px] relative overflow-hidden">
    {{-- Decorative accent --}}
    <div class="absolute top-0 right-0 w-64 h-64 bg-[#c02434] opacity-10 rounded-full -mr-32 -mt-32"></div>

    <div class="grid grid-cols-5 gap-8 relative z-10">
        <div class="col-span-3">
            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4 flex items-center gap-2">
                <span class="w-8 h-px bg-gray-700"></span>
                Official Bank Details
            </h4>
            <div class="grid grid-cols-2 gap-x-8 gap-y-5">
                <div>
                    <span class="block text-gray-500 uppercase font-bold text-[8px] mb-1 tracking-widest">Primary Bank (KES)</span>
                    <div class="text-[11px] font-bold text-white border-l-2 border-[#c02434] pl-3">
                        Standard Chartered Bank<br>
                        <span class="text-gray-400 font-medium font-normal">A/C No:</span> 01020304050607<br>
                        <span class="text-gray-400 font-medium font-normal">Branch:</span> Westlands
                    </div>
                </div>
                <div>
                    <span class="block text-gray-500 uppercase font-bold text-[8px] mb-1 tracking-widest">MPESA Paybill</span>
                    <div class="text-[11px] font-bold text-white border-l-2 border-pink-600 pl-3">
                        Business No: <span class="text-white">522522</span><br>
                        Account No: <span class="text-white">1128266994</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-2 text-right">
            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4 flex items-center gap-2 justify-end">
                Direct Support
                <span class="w-8 h-px bg-gray-700"></span>
            </h4>
            <div class="text-[11px] text-gray-300 space-y-2">
                <div class="flex flex-col">
                    <span class="text-[8px] text-gray-500 uppercase font-bold">Hotline</span>
                    <span class="text-white font-bold">+254 713 444 000</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[8px] text-gray-500 uppercase font-bold">Email</span>
                    <span class="text-white font-bold italic underline decoration-[#c02434] underline-offset-4">control@sheffieldafrica.com</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-12 pt-8 border-t border-gray-800 flex justify-between items-center">
        <div class="max-w-md">
            <div class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">Legal Notice</div>
            <div class="text-[9px] text-gray-400 leading-relaxed font-medium">
                Goods remain the property of <span class="text-gray-300 font-bold">Sheffield Steel Systems Limited</span> until full payment is confirmed.
                Interest at market rates may be charged on overdue accounts. Claims regarding discrepancies must be submitted in writing within 24 hours.
            </div>
        </div>

        <div class="text-right flex items-center gap-4">
            {{-- QR Code placeholder --}}
            <div class="w-16 h-16 bg-white p-1 rounded-lg">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('customer.orders.show', $order)) }}" alt="QR Code" class="w-full h-full">
            </div>
            <div class="inline-block text-center border-2 border-gray-800 p-3 rounded-xl">
                <div class="text-[14px] font-black text-white italic tracking-tighter leading-none mb-1">Sheffield Africa</div>
                <div class="text-[8px] text-[#c02434] uppercase font-black tracking-[0.3em]">Driven. Trusted.</div>
            </div>
        </div>
    </div>
</div>
