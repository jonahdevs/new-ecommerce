<?php
// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>
<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>


                            <div class="mt-3 flex items-center gap-4 flex-wrap">
                                <flux:button.group>
                                    <flux:button icon="minus" class="cursor-pointer text-zinc-500!"
                                        wire:click="decreaseCartQuantity" title="Decrease" />
                                    <flux:input readonly value="{{ $cartQuantity }}"
                                        class="max-w-9! outline-none! border-none! ring-0! text-center!" />
                                    <flux:button icon="plus" class="cursor-pointer text-zinc-500!"
                                        wire:click="increaseCartQuantity" title="Increase" />
                                    @if ($inCart)
                                        <flux:button icon="trash" class="cursor-pointer text-red-500!"
                                            wire:click="removeFromCart" title="Remove" />
                                    @endif
                                </flux:button.group>

                                @if (!$inCart)
                                    <flux:button wire:click="addToCart" variant="primary"
                                        class="uppercase cursor-pointer">
                                        Add to Cart
                                    </flux:button>
                                @endif
                            </div>
                        