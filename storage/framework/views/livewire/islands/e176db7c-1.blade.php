<?php
// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>
<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>


                    <div class="mt-3 flex items-center gap-4">
                        <flux:button.group>
                            <flux:button icon="minus" class="cursor-pointer text-zinc-500!" title="Decrease Quantity"
                                wire:click="decreaseCartQuantity"></flux:button>

                            <flux:input readonly value="{{ $cartQuantity }}"
                                class="max-w-9! outline-none! border-none! ring-0 focus:outline-none! focus:border-none!"
                                style="outline: none; padding-left: 0 !important; padding-right: 0 !important; text-align: center !important;" />

                            <flux:button icon="plus" class="cursor-pointer text-zinc-500!" title="Increase Quantity"
                                wire:click="increaseCartQuantity"></flux:button>

                            @if ($inCart)
                                <flux:button icon="trash" class="cursor-pointer text-red-500!"
                                    wire:click="removeFromCart" title="Remove Item from Cart">
                                </flux:button>
                            @endif
                        </flux:button.group>

                        @if (!$inCart)
                            <flux:button wire:click="addToCart" class="uppercase" variant="primary">
                                Add to Cart
                            </flux:button>
                        @endif
                    </div>
                