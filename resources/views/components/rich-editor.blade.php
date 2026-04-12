@props([
    'model' => 'content',
    'placeholder' => 'Start writing...',
    'value' => '',
    'label' => null,
    'error' => null,
])

<div x-data="richEditor('{{ $model }}', '{{ $placeholder }}', @js($value ?? ''))" class="w-full">

    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
        </label>
    @endif

    <div
        class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">

        {{-- ── Toolbar ── --}}
        <div
            class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-gray-50 dark:bg-zinc-800 border-b border-gray-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200">

            {{-- Bold / Italic / Underline --}}
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Bold" @click="toggleBold()"
                    :class="isActive('bold') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm font-bold">B</button>

                <button type="button" title="Italic" @click="toggleItalic()"
                    :class="isActive('italic') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm italic">I</button>

                <button type="button" title="Underline" @click="toggleUnderline()"
                    :class="isActive('underline') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded text-sm underline">U</button>
            </div>

            {{-- Headings --}}
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                @foreach ([1, 2, 3] as $level)
                    <button type="button" title="Heading {{ $level }}"
                        @click="toggleHeading({{ $level }})"
                        :class="isActive('heading', { level: {{ $level }} }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                        class="w-8 h-7 flex items-center justify-center rounded text-xs font-semibold">H{{ $level }}</button>
                @endforeach
            </div>

            {{-- Lists & Blockquote --}}
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Bullet list" @click="toggleBulletList()"
                    :class="isActive('bulletList') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="9" y1="6" x2="20" y2="6" />
                        <line x1="9" y1="12" x2="20" y2="12" />
                        <line x1="9" y1="18" x2="20" y2="18" />
                        <circle cx="4" cy="6" r="1.5" fill="currentColor" stroke="none" />
                        <circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none" />
                        <circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none" />
                    </svg>
                </button>

                <button type="button" title="Numbered list" @click="toggleOrderedList()"
                    :class="isActive('orderedList') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="10" y1="6" x2="21" y2="6" />
                        <line x1="10" y1="12" x2="21" y2="12" />
                        <line x1="10" y1="18" x2="21" y2="18" />
                        <path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" />
                    </svg>
                </button>

                <button type="button" title="Blockquote" @click="toggleBlockquote()"
                    :class="isActive('blockquote') ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z" />
                    </svg>
                </button>
            </div>

            {{-- Alignment --}}
            <div class="flex items-center gap-0.5 pr-2 mr-1 border-r border-gray-300 dark:border-zinc-600">
                <button type="button" title="Align left" @click="alignLeft()"
                    :class="isActive({ textAlign: 'left' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                        <line x1="17" y1="18" x2="3" y2="18" />
                    </svg>
                </button>

                <button type="button" title="Align center" @click="alignCenter()"
                    :class="isActive({ textAlign: 'center' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="17" y1="12" x2="7" y2="12" />
                        <line x1="19" y1="18" x2="5" y2="18" />
                    </svg>
                </button>

                <button type="button" title="Align right" @click="alignRight()"
                    :class="isActive({ textAlign: 'right' }) ? 'bg-gray-200 dark:bg-zinc-600' : 'hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="w-7 h-7 flex items-center justify-center rounded">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                        <line x1="21" y1="18" x2="7" y2="18" />
                    </svg>
                </button>
            </div>

            {{-- Undo / Redo --}}
            <div class="flex items-center gap-0.5">
                <button type="button" title="Undo" @click="undo()"
                    class="w-7 h-7 flex items-center justify-center rounded hover:bg-gray-200 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 7v6h6" />
                        <path d="M21 17A9 9 0 006 5.7L3 8" />
                    </svg>
                </button>
                <button type="button" title="Redo" @click="redo()"
                    class="w-7 h-7 flex items-center justify-center rounded hover:bg-gray-200 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 7v6h-6" />
                        <path d="M3 17a9 9 0 0015-3.7L21 8" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Editor Area ── --}}
        <div x-ref="editor" wire:ignore class="bg-white dark:bg-zinc-900"></div>
    </div>

    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif

</div>
