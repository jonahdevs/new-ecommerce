<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Change Log')] class extends Component
{
    use WithPagination;

    public string $modelType;

    public int $modelId;

    private Model $subject;

    public function mount(string $modelType, int $id): void
    {
        abort_unless(array_key_exists($modelType, $this->registry()), 404);

        $class = $this->registry()[$modelType]['class'];
        $this->subject = $class::findOrFail($id);
        $this->modelType = $modelType;
        $this->modelId = $id;

        $authorizeCallback = $this->registry()[$modelType]['authorize'] ?? null;

        if ($authorizeCallback !== null) {
            $authorizeCallback();
        }
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        $class = $this->registry()[$this->modelType]['class'];

        return Activity::query()
            ->where('subject_type', $class)
            ->where('subject_id', $this->modelId)
            ->with('causer')
            ->latest()
            ->paginate(20);
    }

    public function render(): View
    {
        $config = $this->registry()[$this->modelType];
        $subject = $this->resolveSubject();

        return view('pages.admin.changelog.model-changelog', [
            'config' => $config,
            'subject' => $subject,
            'pageTitle' => $config['label'].' Change Log',
            'subjectLabel' => ($config['subjectLabel'])($subject),
            'backUrl' => ($config['backRoute'])($subject),
            'crumbUrl' => ($config['crumbRoute'])(),
        ]);
    }

    public function getFieldLabel(string $field): string
    {
        return match ($field) {
            'sku' => 'SKU',
            'sale_price' => 'Sale Price',
            'stock_quantity' => 'Stock Quantity',
            'is_active' => 'Active Status',
            'category_id' => 'Category',
            'brand_id' => 'Brand',
            'parent_id' => 'Parent Category',
            'payment_status' => 'Payment Status',
            'customer_notes', 'admin_notes' => 'Notes',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    public function formatValue(mixed $value, string $field): string
    {
        if (is_null($value)) {
            return '—';
        }

        return match ($field) {
            'price', 'sale_price' => format_currency($value),
            'is_active' => $value ? 'Active' : 'Inactive',
            'status' => $this->formatStatus((string) $value),
            'payment_status' => PaymentStatus::tryFrom($value)?->label() ?? ucfirst($value),
            'category_id', 'parent_id' => $this->lookupCategoryName((int) $value),
            'brand_id' => $this->lookupBrandName((int) $value),
            default => (string) $value,
        };
    }

    private function formatStatus(string $value): string
    {
        return match ($this->modelType) {
            'product' => ProductStatus::tryFrom($value)?->label() ?? ucfirst($value),
            'order' => OrderStatus::tryFrom($value)?->label() ?? ucfirst($value),
            'quote' => QuoteStatus::tryFrom($value)?->label() ?? ucfirst($value),
            'user' => UserStatus::tryFrom($value)?->label() ?? ucfirst($value),
            default => ucfirst($value),
        };
    }

    private function lookupCategoryName(int $id): string
    {
        $category = Category::find($id);

        return $category ? $category->name : "Category #{$id}";
    }

    private function lookupBrandName(int $id): string
    {
        $brand = Brand::find($id);

        return $brand ? $brand->name : "Brand #{$id}";
    }

    private function resolveSubject(): Model
    {
        if (! isset($this->subject)) {
            $class = $this->registry()[$this->modelType]['class'];
            $this->subject = $class::findOrFail($this->modelId);
        }

        return $this->subject;
    }

    private function registry(): array
    {
        return [
            'product' => [
                'class' => Product::class,
                'label' => 'Product',
                'authorize' => fn () => $this->authorize('update', $this->subject),
                'subjectLabel' => fn (Model $m) => $m->name,
                'backRoute' => fn (Model $m) => route('admin.catalog.products.edit', $m),
                'backLabel' => 'Back to Product',
                'crumbRoute' => fn () => route('admin.catalog.products.index'),
                'crumbLabel' => 'Products',
            ],
            'order' => [
                'class' => Order::class,
                'label' => 'Order',
                'authorize' => fn () => abort_unless(auth()->user()?->is_staff, 403),
                'subjectLabel' => fn (Model $m) => "Order #{$m->reference}",
                'backRoute' => fn (Model $m) => route('admin.sales.orders.show', $m),
                'backLabel' => 'Back to Order',
                'crumbRoute' => fn () => route('admin.sales.orders.index'),
                'crumbLabel' => 'Orders',
            ],
            'quote' => [
                'class' => Quote::class,
                'label' => 'Quote',
                'authorize' => fn () => $this->authorize('update', $this->subject),
                'subjectLabel' => fn (Model $m) => "Quote #{$m->reference}",
                'backRoute' => fn (Model $m) => route('admin.quotations.show', $m),
                'backLabel' => 'Back to Quote',
                'crumbRoute' => fn () => route('admin.quotations.index'),
                'crumbLabel' => 'Quotes',
            ],
            'user' => [
                'class' => User::class,
                'label' => 'User',
                'authorize' => null,
                'subjectLabel' => fn (Model $m) => $m->name,
                'backRoute' => fn (Model $m) => route('admin.access-control.users.edit', $m),
                'backLabel' => 'Back to User',
                'crumbRoute' => fn () => route('admin.access-control.roles.index'),
                'crumbLabel' => 'Roles',
            ],
            'category' => [
                'class' => Category::class,
                'label' => 'Category',
                'authorize' => fn () => $this->authorize('update', $this->subject),
                'subjectLabel' => fn (Model $m) => $m->name,
                'backRoute' => fn (Model $m) => route('admin.catalog.categories.edit', $m),
                'backLabel' => 'Back to Category',
                'crumbRoute' => fn () => route('admin.catalog.categories.index'),
                'crumbLabel' => 'Categories',
            ],
            'brand' => [
                'class' => Brand::class,
                'label' => 'Brand',
                'authorize' => fn () => $this->authorize('update', $this->subject),
                'subjectLabel' => fn (Model $m) => $m->name,
                'backRoute' => fn (Model $m) => route('admin.catalog.brands.edit', $m),
                'backLabel' => 'Back to Brand',
                'crumbRoute' => fn () => route('admin.catalog.brands.index'),
                'crumbLabel' => 'Brands',
            ],
        ];
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="$crumbUrl" wire:navigate>{{ $config['crumbLabel'] }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Change Log</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ $pageTitle }}</flux:heading>
            <flux:subheading>Change history for {{ $subjectLabel }}</flux:subheading>
        </div>
        <flux:button :href="$backUrl" wire:navigate icon="arrow-left" variant="ghost">
            {{ $config['backLabel'] }}
        </flux:button>
    </div>

    <flux:card class="p-0">
        @if ($this->activities->isEmpty())
            <div class="py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                    <flux:icon.clock class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <div>
                        <flux:heading size="sm">No changes recorded</flux:heading>
                        <flux:subheading class="mt-0.5">
                            Changes to this {{ strtolower($config['label']) }} will appear here.
                        </flux:subheading>
                    </div>
                </div>
            </div>
        @else
            <flux:table :paginate="$this->activities">
                <flux:table.columns>
                    <flux:table.column class="ps-5! w-48">Timestamp</flux:table.column>
                    <flux:table.column class="w-48">Changed By</flux:table.column>
                    <flux:table.column>Changes</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->activities as $activity)
                        <flux:table.row :key="$activity->id">
                            {{-- Timestamp --}}
                            <flux:table.cell class="ps-5! align-top">
                                <div class="text-sm text-zinc-800 dark:text-zinc-100">
                                    {{ $activity->created_at->format('M j, Y') }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $activity->created_at->format('g:i A') }}
                                </div>
                            </flux:table.cell>

                            {{-- Changed By --}}
                            <flux:table.cell class="align-top">
                                @if ($activity->causer)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" circle :name="$activity->causer->name" />
                                        <div>
                                            <div class="text-sm text-zinc-800 dark:text-zinc-100">
                                                {{ $activity->causer->name }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $activity->causer->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="cog-6-tooth" class="size-4" />
                                        <span class="text-sm">System</span>
                                    </div>
                                @endif
                            </flux:table.cell>

                            {{-- Changes --}}
                            <flux:table.cell class="align-top">
                                <div class="space-y-2">
                                    @php
                                        $oldValues = $activity->properties['old'] ?? [];
                                        $newValues = $activity->properties['attributes'] ?? [];
                                        $changedFields = array_unique(
                                            array_merge(array_keys($oldValues), array_keys($newValues)),
                                        );
                                    @endphp

                                    @if ($activity->event === 'created')
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400 italic">
                                            Record created
                                        </span>
                                    @else
                                        @foreach ($changedFields as $field)
                                            <div class="text-sm">
                                                <span
                                                    class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->getFieldLabel($field) }}:</span>
                                                <span class="text-zinc-600 dark:text-zinc-400">
                                                    {{ $this->formatValue($oldValues[$field] ?? null, $field) }}
                                                </span>
                                                <span class="text-zinc-400 dark:text-zinc-500 mx-1">→</span>
                                                <span class="text-zinc-800 dark:text-zinc-100 font-medium">
                                                    {{ $this->formatValue($newValues[$field] ?? null, $field) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
