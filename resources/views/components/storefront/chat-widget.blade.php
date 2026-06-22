<?php

use App\Services\Ai\ChatAssistant;
use App\Settings\ChatbotSettings;
use Livewire\Component;

new class extends Component {
    /** How many recent turns to send back as context (keeps tokens in check). */
    private const CONTEXT_TURNS = 10;

    public string $draft = '';

    public bool $thinking = false;

    /** Admin master switch — when false the widget renders nothing. */
    public bool $enabled = true;

    /** Admin-configured intro line shown in the empty panel. */
    public string $greeting = '';

    /** Admin-configured system prompt for this conversation. */
    public string $systemPrompt = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public function mount(ChatbotSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->greeting = $settings->greeting ?: 'Hi! How can I help you today?';
        $this->systemPrompt = $settings->system_prompt ?: (string) config('ai.system_prompt');
    }

    /**
     * Append the visitor's message, then trigger reply() in a follow-up request
     * so the message (and a "typing" indicator) paint immediately.
     */
    public function send(): void
    {
        $text = trim($this->draft);

        if ($text === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $text];
        $this->draft = '';
        $this->thinking = true;
        $this->dispatch('chat-scroll');

        $this->js('$wire.reply()');
    }

    /**
     * Scripted entry points (the "buttons" half of the hybrid). Each seeds a
     * question and lets the AI answer it.
     */
    public function ask(string $prompt): void
    {
        $this->draft = $prompt;
        $this->send();
    }

    /**
     * Call the configured provider for a reply. Never throws to the UI — on
     * failure it shows a friendly fallback so the widget stays usable.
     */
    public function reply(ChatAssistant $assistant): void
    {
        try {
            $answer = $assistant->reply($this->payload());
        } catch (\Throwable $e) {
            report($e);
            $answer = 'Sorry — I had trouble responding just now. Please try again, or reach our team on +254 713 777 111.';
        }

        $this->messages[] = ['role' => 'assistant', 'content' => $answer !== '' ? $answer : 'Sorry, I did not catch that — could you rephrase?'];
        $this->thinking = false;
        $this->dispatch('chat-scroll');
    }

    /**
     * System prompt + the trailing slice of the conversation.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function payload(): array
    {
        return array_merge([['role' => 'system', 'content' => $this->systemPrompt]], array_slice($this->messages, -self::CONTEXT_TURNS));
    }
}; ?>

<div>
@if ($enabled)
<div x-data="{ open: false }"
    x-on:chat-scroll.window="$nextTick(() => { if ($refs.log) $refs.log.scrollTop = $refs.log.scrollHeight })"
    class="fixed right-4 bottom-4 z-50 print:hidden">

    {{-- Launcher --}}
    <button type="button" x-show="!open" x-on:click="open = true" aria-label="Open chat assistant"
        class="inline-flex size-14 cursor-pointer items-center justify-center rounded-full bg-brand-blue-500 text-white shadow-lg transition hover:bg-brand-blue-600">
        <flux:icon.chat-bubble-left-right variant="outline" class="size-6" />
    </button>

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition.origin.bottom.right
        class="flex h-[32rem] w-[22rem] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl">

        {{-- Header --}}
        <div class="flex items-center justify-between bg-brand-blue-500 px-4 py-3 text-white">
            <div class="flex items-center gap-2">
                <flux:icon.sparkles variant="micro" class="size-4" />
                <span class="text-sm font-semibold">Ask Sheffield</span>
            </div>
            <button type="button" x-on:click="open = false" aria-label="Close chat"
                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-white/80 transition hover:bg-white/10 hover:text-white">
                <flux:icon.x-mark variant="micro" class="size-4" />
            </button>
        </div>

        {{-- Message log --}}
        <div x-ref="log" class="scrollbar-thin flex-1 space-y-3 overflow-y-auto bg-surface-sunken p-4">
            @if (empty($messages))
                <div class="text-[13px] text-ink-3">
                    <p class="mb-3">{{ $greeting }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="ask('Help me choose the right equipment for my kitchen.')"
                            class="cursor-pointer rounded-full border border-line bg-white px-3 py-1.5 text-[12.5px] font-medium text-ink-2 transition hover:border-brand-blue-500 hover:text-brand-blue-500">Find
                            equipment</button>
                        <button type="button" wire:click="ask('How do delivery and installation work?')"
                            class="cursor-pointer rounded-full border border-line bg-white px-3 py-1.5 text-[12.5px] font-medium text-ink-2 transition hover:border-brand-blue-500 hover:text-brand-blue-500">Delivery
                            &amp; install</button>
                        <a href="{{ route('quote.request') }}" wire:navigate
                            class="rounded-full border border-line bg-white px-3 py-1.5 text-[12.5px] font-medium text-ink-2 transition hover:border-brand-blue-500 hover:text-brand-blue-500">Request
                            a quote</a>
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                <div @class([
                    'flex',
                    'justify-end' => $message['role'] === 'user',
                    'justify-start' => $message['role'] !== 'user',
                ])>
                    <div @class([
                        'max-w-[85%] rounded-2xl px-3.5 py-2 text-[13px] leading-relaxed whitespace-pre-line',
                        'bg-brand-blue-500 text-white' => $message['role'] === 'user',
                        'bg-white text-ink border border-line' => $message['role'] !== 'user',
                    ])>{{ $message['content'] }}</div>
                </div>
            @endforeach

            {{-- Typing indicator --}}
            <div wire:show="thinking" class="flex justify-start">
                <div class="inline-flex items-center gap-1 rounded-2xl border border-line bg-white px-3.5 py-2.5">
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-4 [animation-delay:-0.3s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-4 [animation-delay:-0.15s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-4"></span>
                </div>
            </div>
        </div>

        {{-- Composer --}}
        <form wire:submit="send" class="flex items-center gap-2 border-t border-zinc-200 bg-white p-3">
            <input type="text" wire:model="draft" wire:loading.attr="disabled" placeholder="Type your message…"
                autocomplete="off"
                class="min-w-0 flex-1 rounded-full border border-line bg-surface-sunken px-3.5 py-2 text-[13px] text-ink placeholder:text-ink-4 focus:border-brand-blue-500 focus:outline-none" />
            <button type="submit" wire:loading.attr="disabled" aria-label="Send message"
                class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full bg-brand-blue-500 text-white transition hover:bg-brand-blue-600 disabled:opacity-50">
                <flux:icon.paper-airplane variant="micro" class="size-4" />
            </button>
        </form>
    </div>
</div>
@endif
</div>
