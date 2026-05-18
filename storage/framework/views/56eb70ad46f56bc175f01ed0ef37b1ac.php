<div class="p-8 max-w-2xl mx-auto font-mono text-sm">
    <h1 class="text-xl font-bold mb-6">Echo / Pusher Test Page</h1>

    <div class="mb-6 p-4 bg-gray-100 rounded">
        <p class="font-bold mb-2">SEND (triggers real broadcast)</p>
        <div class="flex gap-3 items-center">
            <label>Order ID:</label>
            <input type="number" wire:model="orderId" class="border px-2 py-1 w-24 rounded" />
            <button wire:click="send" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">
                Dispatch OrderUpdated
            </button>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log): ?>
            <pre class="mt-3 text-green-700"><?php echo e($log); ?></pre>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="p-4 bg-gray-100 rounded">
        <p class="font-bold mb-2">RECEIVE (Echo listener log)</p>
        <pre id="echo-log" class="text-xs bg-white border p-3 rounded min-h-[120px] whitespace-pre-wrap">Waiting for events...</pre>
    </div>
</div><?php /**PATH C:\Users\jonah.wakahiu\Desktop\ecommerce\sheffield_ecommerce\storage\framework\views/2c3e10ff8a781db402db630a5321d4cd.blade.php ENDPATH**/ ?>