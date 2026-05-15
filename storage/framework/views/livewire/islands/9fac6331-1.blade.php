<?php
// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>
<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>


                                <!-- Buy Now Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-xs sm:text-sm">
                                        Actions</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            <flux:button wire:click="addToCart({{ $product->id }})"
                                                variant="customer-primary" size="customer" class="cursor-pointer">
                                                <flux:icon.shopping-cart class="w-3.5 h-3.5" />
                                                Add to Cart
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Remove Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-xs sm:text-sm">
                                        Remove</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            <flux:button wire:click="removeProduct({{ $product->id }})"
                                                variant="customer-outline" size="customer"
                                                class="text-red-500! cursor-pointer">
                                                <flux:icon.trash variant="outline" class="w-3.5 h-3.5" />
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>
                            