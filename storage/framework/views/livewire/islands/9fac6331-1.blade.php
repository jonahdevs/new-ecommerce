<?php
// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>
<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>


                                <!-- Buy Now Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Actions</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">
                                            <flux:button wire:click="addToCart({{ $product->id }})" variant="primary"
                                                size="sm" icon="shopping-cart" class="cursor-pointer">Add to Cart
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Remove Row -->
                                <tr>
                                    <td class="p-4 font-medium text-zinc-900 dark:text-white text-sm ">
                                        Remove</td>
                                    @foreach ($this->products as $product)
                                        <td class="p-4 text-center border-l dark:border-zinc-700">

                                            <flux:button wire:click="removeProduct({{ $product->id }})" icon="trash"
                                                size="sm" variant="ghost" class="text-red-500! cursor-pointer">
                                            </flux:button>
                                        </td>
                                    @endforeach
                                </tr>
                            