<?php

use Livewire\Component;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Computed;
use App\Services\ProductService;

new #[Defer] class extends Component {
    public string $type;
    public array $context = [];
    public bool $slider = true;
    public int $limit = 8;

    #[Computed]
    public function products()
    {
        return app(ProductService::class)->recommendedProducts($this->type, $this->context, $this->limit);
    }
};
?>

<div>
    {{-- Simplicity is the essence of happiness. - Cedric Bledsoe --}}
</div>
