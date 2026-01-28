<?php

use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Defer;

new #[Defer] #[Layout('layouts.guest')] class extends Component {
    #[Computed]
    #[On('wishlist-updated')]
    public function products()
    {
        if (auth()->check()) {
            return auth()
                ->user()
                ->wishlistProducts()
                ->select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path'])
                ->withAvg('reviews', 'rating')
                ->with('brand:id,name')
                ->active()
                ->get();
        } else {
            $wishlistIds = request()->session()->get('wishlist', []);

            return Product::select(['id', 'name', 'slug', 'brand_id', 'price', 'sale_price', 'image_path'])
                ->withAvg('reviews', 'rating')
                ->with('brand:id,name')
                ->active()
                ->whereIn('id', $wishlistIds)
                ->get();
        }
    }
};
?>

@placeholder
    <div>
        <div class="bg-zinc-100">
            <div class="flex items-center gap-3 container mx-auto py-4 px-4">
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-32 h-4" />
                <flux:skeleton animate="shimmer" class="w-8 h-4" />
                <flux:skeleton animate="shimmer" class="w-44 h-4" />
            </div>
        </div>

        <section class="container mx-auto px-4 py-4 min-h-[80svh]">
            <!-- Wishlist Header -->
            <flux:skeleton class="w-48 h-4 mb-6" animate="shimmer" />

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <x-product-card-placeholder />
                @endfor
            </div>
        </section>
    </div>
@endplaceholder

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto px-4 py-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item href="{{ route('products') }}" wire:navigate>Products</flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Wishlist</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-4 min-h-[80svh]">
        <!-- Wishlist Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900">Wishlist</h1>
            </div>
        </div>

        <div @class([
            'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4' => !$this->products->isEmpty(),
        ])>
            @forelse ($this->products as $product)
                <livewire:product-card :product="$product" />
            @empty
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
                    <!-- Illustration -->
                    <div class="mb-8">
                        <svg class="size-44 text-zinc-400" version="1.1" x="0px" y="0px" viewBox="0 0 512 520"
                            fill="currentColor" style="enable-background:new 0 0 512 512;" xml:space="preserve">
                            <g>
                                <g>
                                    <g>
                                        <g>
                                            <path
                                                d="M76.49,247.591c-1.394,0-2.644-0.977-2.936-2.396c-0.334-1.623,0.711-3.209,2.334-3.543l94.507-19.448      c1.621-0.335,3.209,0.711,3.543,2.334c0.334,1.623-0.711,3.209-2.334,3.543l-94.507,19.448      C76.894,247.571,76.69,247.591,76.49,247.591z" />
                                        </g>
                                        <g>
                                            <path
                                                d="M437.704,247.592c-0.262,0-0.526-0.035-0.79-0.106l-86.702-23.619c-1.599-0.436-2.542-2.084-2.106-3.683      c0.436-1.6,2.09-2.54,3.683-2.106l86.702,23.619c1.599,0.436,2.542,2.084,2.106,3.683      C440.233,246.714,439.023,247.592,437.704,247.592z" />
                                        </g>
                                        <g>
                                            <path
                                                d="M257.15,483.306c-0.159,0-0.318-0.013-0.477-0.038L74.425,453.934c-1.454-0.234-2.523-1.489-2.523-2.962V343.965      c0-1.657,1.343-3,3-3s3,1.343,3,3v104.451l176.249,28.368V278.308c0-1.657,1.343-3,3-3c1.657,0,3,1.343,3,3v201.998      c0,0.879-0.386,1.714-1.055,2.283C258.549,483.055,257.858,483.306,257.15,483.306z" />
                                        </g>
                                        <g>
                                            <path
                                                d="M257.15,483.306c-0.707,0-1.397-0.25-1.943-0.715c-0.67-0.569-1.057-1.405-1.057-2.285V278.308c0-1.657,1.343-3,3-3      c1.657,0,3,1.343,3,3v198.47l173.947-28.356V343.965c0-1.657,1.343-3,3-3s3,1.343,3,3v107.007c0,1.471-1.066,2.725-2.518,2.961      l-179.947,29.334C257.473,483.293,257.311,483.306,257.15,483.306z" />
                                        </g>
                                        <g>
                                            <path
                                                d="M316.366,375.81c-1.017,0-1.985-0.519-2.542-1.407L254.608,279.9c-0.527-0.841-0.604-1.889-0.206-2.798      c0.398-0.908,1.221-1.562,2.197-1.744l180.552-33.717c1.325-0.246,2.655,0.421,3.248,1.635l43.298,88.885      c0.398,0.817,0.404,1.772,0.015,2.596c-0.389,0.822-1.13,1.424-2.015,1.636l-164.634,39.334      C316.831,375.783,316.598,375.81,316.366,375.81z M262.032,280.448l55.732,88.942l158.9-37.964l-40.659-83.467L262.032,280.448z      " />
                                        </g>
                                        <g>
                                            <path
                                                d="M195.634,375.81c-0.231,0-0.465-0.026-0.697-0.082L30.303,336.394c-0.895-0.214-1.643-0.827-2.028-1.663      c-0.385-0.836-0.365-1.803,0.055-2.622l45.493-88.884c0.605-1.183,1.919-1.824,3.221-1.583l180.658,33.717      c0.984,0.184,1.813,0.848,2.207,1.769s0.303,1.978-0.243,2.817l-61.517,94.502C197.586,375.31,196.633,375.81,195.634,375.81z       M35.41,331.444L194.273,369.4l57.914-88.967L78.144,247.951L35.41,331.444z" />
                                        </g>
                                        <g>
                                            <g>
                                                <g>
                                                    <path
                                                        d="M293.952,197.132c-0.422,0-0.842-0.089-1.233-0.266c-0.726-0.327-1.291-0.929-1.572-1.673        c-2.3-6.079-4.778-11.669-7.175-17.076c-7.373-16.632-13.74-30.995-7.299-45.274c3.547-7.862,11.416-12.943,20.048-12.943        c3.122,0,6.158,0.655,9.022,1.947c8.501,3.835,11.654,9.936,12.75,17.876c3.729-2.479,7.968-4.417,12.726-4.417        c2.934,0,5.917,0.703,9.121,2.148c11.057,4.988,15.999,18.032,11.016,29.079c-6.439,14.273-21.42,19.001-38.766,24.475        c-5.648,1.782-11.489,3.625-17.576,5.928C294.671,197.068,294.312,197.132,293.952,197.132z M296.721,125.901        c-6.277,0-12,3.694-14.579,9.411c-5.338,11.833,0.246,24.428,7.315,40.375c2.064,4.657,4.188,9.448,6.227,14.608        c5.227-1.89,10.234-3.47,15.101-5.006c16.631-5.248,29.767-9.394,35.103-21.22c3.622-8.03,0.027-17.515-8.014-21.142        c-2.413-1.089-4.59-1.618-6.654-1.618c-3.932,0-7.805,1.848-13.369,6.379c-0.883,0.718-2.094,0.875-3.128,0.408        c-1.036-0.467-1.719-1.479-1.765-2.615c-0.419-10.503-2.861-15.086-9.681-18.163        C301.192,126.377,298.986,125.901,296.721,125.901z" />
                                                </g>
                                            </g>
                                            <g>
                                                <g>
                                                    <path
                                                        d="M204.933,125.901c-5.453,0-10.468-1.067-15.332-3.261c-6.477-2.922-11.43-8.193-13.945-14.844        c-2.515-6.65-2.291-13.88,0.631-20.358c4.823-10.69,12.643-14.4,22.866-15.588c-5.875-8.45-8.269-16.767-3.446-27.458        c4.301-9.536,13.836-15.697,24.291-15.697c3.778,0,7.453,0.793,10.921,2.358c17.46,7.876,23.296,26.371,30.055,47.786        c2.221,7.038,4.517,14.315,7.389,21.908c0.587,1.55-0.194,3.281-1.744,3.868c-7.582,2.868-14.547,5.956-21.284,8.942        C230.393,120.18,217.489,125.901,204.933,125.901z M219.998,34.694c-8.101,0-15.488,4.774-18.822,12.165        c-3.968,8.794-2.447,15.274,6.024,25.678c0.718,0.881,0.875,2.092,0.408,3.128c-0.467,1.036-1.479,1.718-2.615,1.764        c-13.407,0.535-19.271,3.683-23.238,12.477c-2.263,5.017-2.437,10.616-0.488,15.768c1.948,5.151,5.784,9.234,10.8,11.497        c4.072,1.837,8.28,2.73,12.865,2.73c11.285,0,23.652-5.482,37.971-11.831c5.986-2.654,12.151-5.387,18.81-7.997        c-2.454-6.731-4.487-13.174-6.461-19.429c-6.585-20.867-11.787-37.35-26.801-44.123        C225.764,35.309,222.92,34.694,219.998,34.694z" />
                                                </g>
                                            </g>
                                            <g>
                                                <g>
                                                    <path
                                                        d="M218.109,234.675c-3.633,0-6.975-0.711-10.219-2.175c-4.393-1.982-7.751-5.557-9.457-10.067        c-1.706-4.51-1.554-9.413,0.428-13.806c3.001-6.654,7.666-9.315,13.702-10.327c-3.236-5.194-4.328-10.453-1.326-17.106        c2.917-6.466,9.382-10.645,16.472-10.645c2.563,0,5.056,0.538,7.408,1.6c11.57,5.22,15.358,17.221,19.744,31.117        c1.411,4.471,2.87,9.094,4.689,13.904c0.282,0.744,0.256,1.57-0.071,2.295c-0.327,0.725-0.929,1.291-1.673,1.572        c-4.804,1.817-9.229,3.778-13.509,5.676C234.662,230.986,226.34,234.675,218.109,234.675z M227.709,176.55        c-4.735,0-9.054,2.792-11.002,7.112c-2.351,5.21-1.454,8.911,3.69,15.23c0.718,0.881,0.875,2.092,0.408,3.128        c-0.467,1.036-1.479,1.718-2.615,1.764c-8.142,0.325-11.51,2.101-13.86,7.312c-1.323,2.932-1.424,6.205-0.286,9.216        s3.381,5.397,6.313,6.721c2.451,1.105,4.987,1.644,7.751,1.644c6.961,0,14.744-3.451,23.756-7.446        c3.53-1.565,7.157-3.173,11.047-4.725c-1.413-3.951-2.609-7.742-3.774-11.432c-4.115-13.039-7.365-23.338-16.489-27.454        C231.078,176.91,229.416,176.55,227.709,176.55z" />
                                                </g>
                                            </g>
                                            <circle cx="327" cy="72.901" r="10" />
                                            <circle cx="307" cy="232.901" r="10" />
                                            <circle cx="157" cy="152.901" r="10" />
                                            <circle cx="397" cy="112.901" r="10" />
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>

                    <!-- Heading -->
                    <h2 class="text-2xl font-bold text-zinc-900 mb-3">
                        Your wishlist is empty
                    </h2>

                    <!-- Description -->
                    <p class="text-zinc-600 mb-8 max-w-md">
                        Save your favorite products here to keep track of items you love. Start browsing and add
                        products to
                        your wishlist!
                    </p>

                    <!-- Primary CTA -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <flux:button href="{{ route('products') }}" wire:navigate variant="primary"
                            class="w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Browse Products
                        </flux:button>

                        <flux:button href="{{ route('home') }}" wire:navigate variant="ghost" class="w-full sm:w-auto">
                            Back to Home
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</div>
