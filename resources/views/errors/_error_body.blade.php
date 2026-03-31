{{--
    Shared error body — included by 404, 403, 419, 429, 500, 503.
    Variables expected:
      $code    — HTTP status code string e.g. '404'
      $title   — Short error title
      $message — Descriptive message
      $isAdmin — bool, switches button color/destination
--}}

<div class="flex items-center justify-center min-h-[60vh] px-4 py-16">
    <div class="text-center max-w-lg w-full">

        {{-- Error code --}}
        <p
            class="text-8xl font-bold tracking-tight leading-none {{ $isAdmin ? 'text-brand-secondary dark:text-brand-secondary-light' : 'text-brand-primary' }}">
            {{ $code }}
        </p>

        {{-- Divider --}}
        <div
            class="w-12 h-1 rounded-full mx-auto my-5 {{ $isAdmin ? 'bg-brand-secondary dark:bg-brand-secondary-light' : 'bg-brand-primary' }}">
        </div>

        {{-- Title --}}
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100 mb-3">
            {{ $title }}
        </h1>

        {{-- Message --}}
        <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed mb-8">
            {{ $message }}
        </p>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3">

            @if ($code === '419')
                {{-- Session expired — refresh only --}}
                <button onclick="window.location.reload()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors cursor-pointer {{ $isAdmin ? 'bg-brand-secondary hover:bg-brand-secondary-dark' : 'bg-brand-primary hover:bg-brand-primary-dark' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh page
                </button>
            @elseif($code === '503')
                {{-- Maintenance — support link only --}}
                <a href="#" {{-- TODO: replace with route('contact') when available --}}
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium rounded-md transition-colors">
                    Contact support
                </a>
            @else
                {{-- All others — home/dashboard + support --}}
                @if ($isAdmin)
                    <a href="{{ route('admin.dashboard') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-secondary hover:bg-brand-secondary-dark text-white text-sm font-medium rounded-md transition-colors">
                        Back to dashboard
                    </a>
                @else
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-dark text-white text-sm font-medium rounded-md transition-colors">
                        Back to homepage
                    </a>
                @endif

                <a href="#" {{-- TODO: replace with route('contact') when available --}}
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium rounded-md transition-colors">
                    Contact support
                </a>
            @endif

        </div>

    </div>
</div>
