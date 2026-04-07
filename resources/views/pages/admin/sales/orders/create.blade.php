<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Title('Create Order')] class extends Component {
    // =========================================================================
    // Customer
    // =========================================================================

    public ?int $customerId = null;
    public string $customerSearch = '';
    public bool $isGuest = false;
    public string $guestName = '';
    public string $guestEmail = '';
    public string $guestPhone = '';

    // =========================================================================
    // Items
    // =========================================================================

    /** @var array<int, array{product_id: int, name: string, sku: string, quantity: int, unit_price: float, total: float, image_url: string|null}> */
    public array $items = [];
    public string $productSearch = '';

    // =========================================================================
    // Shipping address
    // =========================================================================

    public ?int $selectedAddressId = null;
    public bool $useManualAddress = false;
    public string $manualFirstName = '';
    public string $manualLastName = '';
    public string $manualPhone = '';
    public string $manualAddress = '';
    public string $manualArea = '';
    public string $manualCounty = '';

    // =========================================================================
    // Financials
    // =========================================================================

    public string $shippingCost = '0';
    public string $discountAmount = '0';

    // =========================================================================
    // Payment & order
    // =========================================================================

    public string $paymentGateway = 'cod';
    public string $paymentStatus = 'paid';
    public string $initialStatus = 'confirmed';
    public string $notes = '';

    // =========================================================================
    // UI state
    // =========================================================================

    public bool $showCustomerDropdown = false;
    public bool $showProductDropdown = false;

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function mount(): void
    {
        // Defaults already set above
    }

    public function updatedCustomerSearch(): void
    {
        $this->showCustomerDropdown = strlen($this->customerSearch) >= 2;
    }

    public function updatedProductSearch(): void
    {
        $this->showProductDropdown = strlen($this->productSearch) >= 2;
    }

    public function updatedCustomerId(): void
    {
        $this->selectedAddressId = null;
        $this->useManualAddress = false;
    }

    public function updatedIsGuest(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
        $this->selectedAddressId = null;
    }

    // =========================================================================
    // Computed
    // =========================================================================

    #[Computed]
    public function customerResults(): array
    {
        if (strlen($this->customerSearch) < 2) {
            return [];
        }

        return User::query()
            ->where('is_staff', false)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('email', 'like', "%{$this->customerSearch}%")
                    ->orWhere('phone_number', 'like', "%{$this->customerSearch}%");
            })
            ->limit(6)
            ->get(['id', 'name', 'email', 'phone_number'])
            ->toArray();
    }

    #[Computed]
    public function selectedCustomer(): ?User
    {
        if (!$this->customerId) {
            return null;
        }

        return User::with(['addresses.county', 'addresses.area'])->find($this->customerId);
    }

    #[Computed]
    public function productResults(): array
    {
        if (strlen($this->productSearch) < 2) {
            return [];
        }

        return Product::query()
            ->active()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")->orWhere('sku', 'like', "%{$this->productSearch}%");
            })
            ->limit(6)
            ->get(['id', 'name', 'sku', 'price', 'sale_price', 'image_path'])
            ->toArray();
    }

    #[Computed]
    public function subtotalCents(): int
    {
        return (int) collect($this->items)->sum(fn($item) => ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1) * 100);
    }

    #[Computed]
    public function shippingCents(): int
    {
        return (int) (max(0, (float) $this->shippingCost) * 100);
    }

    #[Computed]
    public function discountCents(): int
    {
        return (int) (max(0, (float) $this->discountAmount) * 100);
    }

    #[Computed]
    public function totalCents(): int
    {
        return max(0, $this->subtotalCents + $this->shippingCents - $this->discountCents);
    }

    // =========================================================================
    // Customer actions
    // =========================================================================

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $customer = User::find($id);
        $this->customerSearch = $customer->name . ' — ' . $customer->email;
        $this->showCustomerDropdown = false;
        $this->isGuest = false;

        // Auto-select default address
        $default = Address::where('user_id', $id)->where('is_default', true)->first();
        if ($default) {
            $this->selectedAddressId = $default->id;
        }

        unset($this->selectedCustomer, $this->customerResults);
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
        $this->selectedAddressId = null;
        $this->showCustomerDropdown = false;

        unset($this->selectedCustomer, $this->customerResults);
    }

    // =========================================================================
    // Item actions
    // =========================================================================

    public function addProduct(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        // If already in list, just bump quantity
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] === $productId) {
                $this->items[$index]['quantity']++;
                $this->items[$index]['total'] = round($this->items[$index]['unit_price'] * $this->items[$index]['quantity'], 2);
                $this->productSearch = '';
                $this->showProductDropdown = false;
                unset($this->productResults, $this->subtotalCents, $this->totalCents);

                return;
            }
        }

        $unitPrice = (float) ($product->sale_price && $product->sale_price < $product->price ? $product->sale_price : $product->price);

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku ?? '—',
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total' => $unitPrice,
            'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
        ];

        $this->productSearch = '';
        $this->showProductDropdown = false;
        unset($this->productResults, $this->subtotalCents, $this->totalCents);
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        unset($this->subtotalCents, $this->totalCents);
    }

    public function updatedItems(): void
    {
        foreach ($this->items as $index => $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $price = max(0, (float) ($item['unit_price'] ?? 0));
            $this->items[$index]['quantity'] = $qty;
            $this->items[$index]['unit_price'] = $price;
            $this->items[$index]['total'] = round($price * $qty, 2);
        }

        unset($this->subtotalCents, $this->totalCents);
    }

    // =========================================================================
    // Create order
    // =========================================================================

    public function save(): void
    {
        $this->validateForm();

        $addressSnapshot = $this->resolveAddressSnapshot();

        try {
            $order = DB::transaction(function () use ($addressSnapshot) {
                $userId = $this->isGuest ? null : $this->customerId;
                $guestInfo = $this->isGuest
                    ? [
                        'name' => $this->guestName,
                        'email' => $this->guestEmail,
                        'phone' => $this->guestPhone,
                    ]
                    : null;

                $order = Order::create([
                    'user_id' => $userId,
                    'reference' => Order::generateReference(),
                    'status' => $this->initialStatus,
                    'payment_status' => PaymentStatus::PENDING->value,
                    'currency' => 'KES',
                    'subtotal_cents' => $this->subtotalCents,
                    'discount_cents' => $this->discountCents,
                    'shipping_cents' => $this->shippingCents,
                    'tax_cents' => 0,
                    'total_cents' => $this->totalCents,
                    'shipping_address' => $addressSnapshot,
                    'billing_address' => $addressSnapshot,
                    'shipping_snapshot' => [
                        'method_code' => 'admin',
                        'method_label' => 'Admin — Manual Shipping',
                        'cost' => $this->shippingCents / 100,
                        'method_id' => null,
                        'rate_id' => null,
                        'zone_id' => null,
                    ],
                    'guest_info' => $guestInfo,
                    'customer_notes' => $this->notes ?: null,
                    'sap_sync_status' => SapSyncStatus::PENDING,
                ]);

                foreach ($this->items as $item) {
                    $unitPriceCents = (int) round($item['unit_price'] * 100);
                    $totalCents = $unitPriceCents * (int) $item['quantity'];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => null,
                        'quantity' => (int) $item['quantity'],
                        'unit_price_cents' => $unitPriceCents,
                        'unit_tax_cents' => 0,
                        'discount_cents' => 0,
                        'total_cents' => $totalCents,
                        'product_snapshot' => [
                            'name' => $item['name'],
                            'sku' => $item['sku'],
                            'price' => $item['unit_price'],
                        ],
                    ]);
                }

                $isPaid = $this->paymentStatus === 'paid';

                Payment::create([
                    'order_id' => $order->id,
                    'amount_cents' => $this->totalCents,
                    'currency' => 'KES',
                    'status' => $isPaid ? PaymentStatus::PAID : PaymentStatus::PENDING,
                    'gateway' => $this->paymentGateway,
                    'paid_at' => $isPaid ? now() : null,
                    'meta' => ['created_by_admin' => true, 'admin_id' => auth()->id()],
                ]);

                if ($isPaid) {
                    $order->update(['payment_status' => PaymentStatus::PAID]);
                }

                $order->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => $this->initialStatus,
                    'changed_by_user_id' => auth()->id(),
                    'changed_by_type' => 'user',
                    'notes' => 'Order created by admin on behalf of customer.',
                ]);

                activity()
                    ->performedOn($order)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'items_count' => count($this->items),
                        'total' => $this->totalCents / 100,
                        'payment_gateway' => $this->paymentGateway,
                        'payment_status' => $this->paymentStatus,
                        'is_guest' => $this->isGuest,
                    ])
                    ->log('order_created');

                return $order;
            });

            // Trigger SAP sync for confirmed + paid orders
            if ($order->status->value === 'confirmed' && $this->paymentStatus === 'paid') {
                SyncOrderToSapJob::dispatch($order->fresh());
            }

            $this->redirect(route('admin.orders.show', $order), navigate: true);
        } catch (\Throwable $e) {
            logger()->error('Admin order creation failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function validateForm(): void
    {
        $addressRequired = $this->isGuest || $this->useManualAddress;

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'shippingCost' => 'numeric|min:0',
            'discountAmount' => 'numeric|min:0',
            'paymentGateway' => 'required|in:cod,manual',
            'paymentStatus' => 'required|in:pending,paid',
            'initialStatus' => 'required|in:pending,confirmed',
        ];

        if ($this->isGuest) {
            $rules['guestName'] = 'required|string|max:255';
            $rules['guestEmail'] = 'required|email|max:255';
        } else {
            $rules['customerId'] = 'required|integer|exists:users,id';
        }

        if ($addressRequired) {
            $rules['manualFirstName'] = 'required|string|max:255';
            $rules['manualAddress'] = 'required|string|max:500';
            $rules['manualCounty'] = 'required|string|max:255';
        } else {
            $rules['selectedAddressId'] = 'required|integer|exists:addresses,id';
        }

        $this->validate($rules);
    }

    private function resolveAddressSnapshot(): array
    {
        if ($this->isGuest || $this->useManualAddress) {
            return [
                'full_name' => trim("{$this->manualFirstName} {$this->manualLastName}"),
                'phone_number' => $this->manualPhone,
                'address' => $this->manualAddress,
                'area' => $this->manualArea,
                'county' => $this->manualCounty,
            ];
        }

        $address = Address::with(['county', 'area'])->find($this->selectedAddressId);

        return [
            'full_name' => $address->full_name,
            'phone_number' => $address->phone_number,
            'address' => $address->address,
            'area' => $address->area?->name,
            'county' => $address->county?->name,
        ];
    }
};
?>

<div>
    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="mb-6">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" wire:navigate icon-variant="outline" />
            <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Create Order</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl" class="font-bold! tracking-tight">Create Order</flux:heading>
        <flux:subheading class="mt-1">Place an order on behalf of a customer.</flux:subheading>
    </div>

    <flux:callout color="indigo" variant="info" icon="information-circle" class="mb-6">
        <flux:callout.heading>ERP sync is automatic</flux:callout.heading>
        <flux:callout.text>Orders set to <strong>Confirmed</strong> with payment <strong>Mark as Paid</strong> will be
            synced to SAP and a KRA receipt generated automatically.</flux:callout.text>
    </flux:callout>

    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-4 gap-5">

            {{-- ── Left: Main (3 cols) ── --}}
            <div class="col-span-3 space-y-5">

                {{-- ============================================================ --}}
                {{-- CUSTOMER                                                      --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div
                        class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
                        <flux:heading size="lg" class="font-semibold!">Customer</flux:heading>
                        <flux:switch wire:model.live="isGuest" label="Guest order" />
                    </div>

                    <div class="p-6 space-y-4">
                        @if ($isGuest)
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model="guestName" label="Full Name" placeholder="John Doe" required />
                                <flux:input wire:model="guestEmail" label="Email" type="email"
                                    placeholder="john@example.com" required />
                            </div>
                            <flux:input wire:model="guestPhone" label="Phone" placeholder="+254 7XX XXX XXX" />
                        @else
                            {{-- Customer search --}}
                            <div class="relative" x-data x-on:click.outside="$wire.showCustomerDropdown = false">
                                @if ($customerId && $selectedCustomer)
                                    <div
                                        class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50">
                                        <div class="flex items-center gap-3">
                                            <flux:avatar circle size="sm" name="{{ $selectedCustomer->name }}" />
                                            <div>
                                                <flux:heading size="sm" class="font-medium!">
                                                    {{ $selectedCustomer->name }}</flux:heading>
                                                <flux:subheading class="text-xs!">{{ $selectedCustomer->email }}
                                                </flux:subheading>
                                            </div>
                                        </div>
                                        <flux:button size="sm" variant="ghost" icon="x-mark"
                                            wire:click="clearCustomer" class="cursor-pointer" />
                                    </div>
                                @else
                                    <flux:input wire:model.live="customerSearch" icon="magnifying-glass"
                                        placeholder="Search by name, email or phone..." autocomplete="off"
                                        x-on:focus="$wire.showCustomerDropdown = $wire.customerSearch.length >= 2" />

                                    @if ($showCustomerDropdown && count($this->customerResults))
                                        <div
                                            class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg overflow-hidden">
                                            @foreach ($this->customerResults as $c)
                                                <button wire:click="selectCustomer({{ $c['id'] }})"
                                                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-left border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                                                    <flux:avatar circle size="sm" name="{{ $c['name'] }}" />
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-zinc-800 dark:text-white">
                                                            {{ $c['name'] }}</p>
                                                        <p class="text-xs text-zinc-500 truncate">{{ $c['email'] }}
                                                        </p>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @elseif ($showCustomerDropdown && strlen($customerSearch) >= 2)
                                        <div
                                            class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg px-4 py-3">
                                            <flux:subheading class="text-xs!">No customers found for
                                                "{{ $customerSearch }}"</flux:subheading>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @error('customerId')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        @endif
                    </div>
                </flux:card>

                {{-- ============================================================ --}}
                {{-- SHIPPING ADDRESS                                              --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading size="lg" class="font-semibold!">Shipping Address</flux:heading>
                    </div>
                    <div class="p-6 space-y-4">

                        @if (!$isGuest && $customerId && $selectedCustomer && $selectedCustomer->addresses->isNotEmpty() && !$useManualAddress)
                            {{-- Customer's saved addresses --}}
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($selectedCustomer->addresses as $addr)
                                    <label wire:key="addr-{{ $addr->id }}"
                                        class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors {{ $selectedAddressId === $addr->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                                        <input type="radio" wire:model.live="selectedAddressId"
                                            value="{{ $addr->id }}" class="mt-0.5 shrink-0" />
                                        <div class="text-sm">
                                            <p class="font-medium text-zinc-800 dark:text-white">{{ $addr->full_name }}
                                            </p>
                                            <p class="text-zinc-500 text-xs leading-relaxed">
                                                {{ $addr->address }}<br>
                                                {{ $addr->area?->name ? $addr->area->name . ', ' : '' }}{{ $addr->county?->name }}
                                            </p>
                                            @if ($addr->is_default)
                                                <flux:badge size="sm" color="blue" class="mt-1">Default
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedAddressId')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror

                            <flux:button size="sm" variant="ghost" wire:click="$set('useManualAddress', true)"
                                class="cursor-pointer">
                                Enter a different address
                            </flux:button>
                        @else
                            {{-- Manual address entry --}}
                            @if (!$isGuest && $useManualAddress)
                                <flux:button size="sm" variant="ghost" icon="arrow-left"
                                    wire:click="$set('useManualAddress', false)" class="cursor-pointer">
                                    Use saved address
                                </flux:button>
                            @endif

                            <div class="grid grid-cols-3 gap-4">
                                <flux:input wire:model="manualFirstName" label="First Name" required />
                                <flux:input wire:model="manualLastName" label="Last Name" />
                                <flux:input wire:model="manualPhone" label="Phone" />
                            </div>
                            <flux:input wire:model="manualAddress" label="Street Address" required />
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model="manualArea" label="Area / Estate" />
                                <flux:input wire:model="manualCounty" label="County" required />
                            </div>
                            @error('manualFirstName')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                            @error('manualAddress')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                            @error('manualCounty')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        @endif
                    </div>
                </flux:card>

                {{-- ============================================================ --}}
                {{-- ITEMS                                                         --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading size="lg" class="font-semibold!">Items</flux:heading>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Product search --}}
                        <div class="relative" x-data x-on:click.outside="$wire.showProductDropdown = false">
                            <flux:input wire:model.live="productSearch" icon="magnifying-glass"
                                placeholder="Search products by name or SKU..." autocomplete="off"
                                x-on:focus="$wire.showProductDropdown = $wire.productSearch.length >= 2" />

                            @if ($showProductDropdown && count($this->productResults))
                                <div
                                    class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg overflow-hidden">
                                    @foreach ($this->productResults as $p)
                                        <button wire:click="addProduct({{ $p['id'] }})"
                                            class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-left border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                                            <div class="shrink-0 w-10 h-10 rounded border overflow-hidden bg-zinc-50">
                                                @if ($p['image_path'])
                                                    <img src="{{ asset('storage/' . $p['image_path']) }}"
                                                        class="w-full h-full object-cover" />
                                                @else
                                                    <flux:icon name="photo"
                                                        class="w-full h-full p-2 text-zinc-300" />
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-zinc-800 dark:text-white truncate">
                                                    {{ $p['name'] }}</p>
                                                <p class="text-xs text-zinc-500">{{ $p['sku'] ?? '—' }} ·
                                                    {{ format_currency($p['sale_price'] && $p['sale_price'] < $p['price'] ? $p['sale_price'] : $p['price']) }}
                                                </p>
                                            </div>
                                            <flux:icon name="plus-circle" class="size-5 text-zinc-400 shrink-0" />
                                        </button>
                                    @endforeach
                                </div>
                            @elseif ($showProductDropdown && strlen($productSearch) >= 2)
                                <div
                                    class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg px-4 py-3">
                                    <flux:subheading class="text-xs!">No products found.</flux:subheading>
                                </div>
                            @endif
                        </div>
                        @error('items')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror

                        {{-- Items table --}}
                        @if (count($items))
                            <div class="border border-zinc-200 dark:border-zinc-600 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th
                                                class="text-left px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                                Product</th>
                                            <th
                                                class="text-center px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider w-24">
                                                Qty</th>
                                            <th
                                                class="text-right px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider w-36">
                                                Unit Price</th>
                                            <th
                                                class="text-right px-4 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider w-28">
                                                Total</th>
                                            <th class="w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                        @foreach ($items as $index => $item)
                                            <tr wire:key="item-{{ $index }}"
                                                class="bg-white dark:bg-zinc-900">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        @if ($item['image_url'])
                                                            <img src="{{ $item['image_url'] }}"
                                                                class="w-8 h-8 rounded object-cover border shrink-0" />
                                                        @endif
                                                        <div>
                                                            <p class="font-medium text-zinc-800 dark:text-white">
                                                                {{ $item['name'] }}</p>
                                                            <p class="text-xs text-zinc-500">{{ $item['sku'] }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number"
                                                        wire:model.blur="items.{{ $index }}.quantity"
                                                        min="1"
                                                        class="w-full text-center border border-zinc-200 dark:border-zinc-600 rounded-md px-2 py-1 text-sm bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-zinc-400 text-xs">KES</span>
                                                        <input type="number"
                                                            wire:model.blur="items.{{ $index }}.unit_price"
                                                            min="0" step="0.01"
                                                            class="w-full text-right border border-zinc-200 dark:border-zinc-600 rounded-md ps-8 pe-2 py-1 text-sm bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right font-medium text-zinc-800 dark:text-white">
                                                    {{ format_currency($item['total']) }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button wire:click="removeItem({{ $index }})"
                                                        class="text-zinc-400 hover:text-red-500 transition-colors">
                                                        <flux:icon name="x-mark" class="size-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div
                                class="flex flex-col items-center gap-2 py-8 text-zinc-400 text-center border border-dashed border-zinc-200 dark:border-zinc-600 rounded-lg">
                                <flux:icon.shopping-bag class="size-8 opacity-40" />
                                <flux:subheading class="text-sm!">No items added yet. Search for products above.
                                </flux:subheading>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- ============================================================ --}}
                {{-- NOTES                                                         --}}
                {{-- ============================================================ --}}
                <flux:card>
                    <flux:heading class="mb-3">Order Notes</flux:heading>
                    <flux:textarea wire:model="notes" placeholder="Internal or customer notes for this order..."
                        rows="3" />
                </flux:card>

            </div>

            {{-- ── Right: Sidebar (1 col) ── --}}
            <div class="col-span-1 space-y-5">

                {{-- ============================================================ --}}
                {{-- PAYMENT                                                       --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Payment</flux:heading>
                    </div>
                    <div class="p-5 space-y-3">
                        <flux:select wire:model="paymentGateway" label="Method">
                            <flux:select.option value="cod">Cash on Delivery</flux:select.option>
                            <flux:select.option value="manual">Manual / Bank Transfer</flux:select.option>
                        </flux:select>

                        <flux:select wire:model="paymentStatus" label="Status">
                            <flux:select.option value="paid">Mark as Paid</flux:select.option>
                            <flux:select.option value="pending">Pending Payment</flux:select.option>
                        </flux:select>
                    </div>
                </flux:card>

                {{-- ============================================================ --}}
                {{-- ORDER STATUS                                                  --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Order Status</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:select wire:model="initialStatus">
                            <flux:select.option value="confirmed">Confirmed</flux:select.option>
                            <flux:select.option value="pending">Pending</flux:select.option>
                        </flux:select>
                    </div>
                </flux:card>

                {{-- ============================================================ --}}
                {{-- ORDER TOTALS                                                  --}}
                {{-- ============================================================ --}}
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Order Totals</flux:heading>
                    </div>
                    <div class="p-5 space-y-3">
                        <flux:input wire:model.blur="shippingCost" label="Shipping Cost (KES)" type="number"
                            min="0" step="0.01" />
                        <flux:input wire:model.blur="discountAmount" label="Discount (KES)" type="number"
                            min="0" step="0.01" />

                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 space-y-2">
                            <div class="flex justify-between text-sm">
                                <flux:text>Subtotal</flux:text>
                                <flux:text class="font-medium">{{ format_currency($this->subtotalCents / 100) }}
                                </flux:text>
                            </div>
                            @if ($this->shippingCents > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Shipping</flux:text>
                                    <flux:text>{{ format_currency($this->shippingCents / 100) }}</flux:text>
                                </div>
                            @endif
                            @if ($this->discountCents > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Discount</flux:text>
                                    <flux:text class="text-green-600">−
                                        {{ format_currency($this->discountCents / 100) }}</flux:text>
                                </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-600">
                                <flux:heading size="sm">Total</flux:heading>
                                <flux:heading size="sm" class="font-bold!">
                                    {{ format_currency($this->totalCents / 100) }}</flux:heading>
                            </div>
                        </div>

                    </div>
                </flux:card>

            </div>
        </div>

        <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.orders.index')" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Create Order</span>
                <span wire:loading wire:target="save">Creating...</span>
            </flux:button>
        </flux:card>

    </form>
</div>
