<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiManager;
use App\Services\Ai\ChatAssistant;
use App\Services\Ai\OpenAiCompatibleProvider;
use App\Services\Ai\Tools\OrderStatusTool;
use App\Services\Ai\Tools\ProductSearchTool;
use App\Settings\ChatbotSettings;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** Build an OpenAI-style chat/completions response with plain text. */
function fakeChatCompletion(string $content): array
{
    return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
}

/** Build an OpenAI-style response where the model asks to call a tool. */
function fakeToolCall(string $name, array $arguments): array
{
    return ['choices' => [['message' => [
        'role' => 'assistant',
        'content' => null,
        'tool_calls' => [[
            'id' => 'call_1',
            'type' => 'function',
            'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]],
    ]]]];
}

it('resolves the provider named in config', function () {
    config(['ai.default' => 'groq']);

    expect(app(AiManager::class)->provider())->toBeInstanceOf(OpenAiCompatibleProvider::class);
});

it('throws for an unknown provider', function () {
    app(AiManager::class)->provider('does-not-exist');
})->throws(InvalidArgumentException::class);

it('sends the conversation to the configured endpoint and returns the reply', function () {
    config([
        'ai.default' => 'groq',
        'ai.providers.groq.base_url' => 'https://api.groq.com/openai/v1',
        'ai.providers.groq.key' => 'test-key',
    ]);

    Http::fake([
        'api.groq.com/*' => Http::response(fakeChatCompletion('Our combi ovens are a great fit.')),
    ]);

    $result = app(AiChatProvider::class)->chat([
        ['role' => 'user', 'content' => 'Recommend an oven'],
    ]);

    expect($result->content)->toBe('Our combi ovens are a great fit.')
        ->and($result->hasToolCalls())->toBeFalse();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com')
        && $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === config('ai.providers.groq.model'));
});

it('flips provider based on the admin chatbot setting', function () {
    app(ChatbotSettings::class)->fill(['provider' => 'openai'])->save();

    config([
        'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
        'ai.providers.openai.key' => 'openai-key',
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response(fakeChatCompletion('Hello from OpenAI.')),
        'api.groq.com/*' => Http::response(fakeChatCompletion('Hello from Groq.')),
    ]);

    expect(app(AiChatProvider::class)->chat([['role' => 'user', 'content' => 'hi']])->content)
        ->toBe('Hello from OpenAI.');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
});

it('searches only visible, published products', function () {
    Product::factory()->published()->create(['name' => 'Rational iCombi Pro Oven']);
    Product::factory()->create(['name' => 'Rational Draft Oven']); // draft → excluded

    $output = json_decode(app(ProductSearchTool::class)->handle(['query' => 'Rational']), true);

    expect($output['products'])->toHaveCount(1)
        ->and($output['products'][0]['name'])->toBe('Rational iCombi Pro Oven')
        ->and($output['products'][0]['url'])->toContain('/product/');
});

it('marks products as buy-online or quote-only with a link', function () {
    Product::factory()->published()->create([
        'name' => 'Countertop Fryer Buyable',
        'price' => 4500000,
        'requires_quotation' => false,
    ]);
    Product::factory()->published()->create([
        'name' => 'Custom Cold Room Quote',
        'requires_quotation' => true,
    ]);

    $rows = collect(json_decode(app(ProductSearchTool::class)->handle(['query' => 'Buyable']), true)['products']);
    $buyable = $rows->firstWhere('name', 'Countertop Fryer Buyable');

    expect($buyable['price'])->not->toBeNull()
        ->and($buyable['purchase'])->toContain('online')
        ->and($buyable['url'])->toContain('/product/');

    $quoteOnly = collect(json_decode(app(ProductSearchTool::class)->handle(['query' => 'Custom Cold Room Quote']), true)['products'])->first();

    expect($quoteOnly['price'])->toBeNull()
        ->and($quoteOnly['purchase'])->toContain('Quote only');
});

it('reports no matches without inventing products', function () {
    $output = json_decode(app(ProductSearchTool::class)->handle(['query' => 'nonexistent-xyz']), true);

    expect($output['products'])->toBe([])
        ->and($output['note'])->toContain('No matching products');
});

it('runs the product tool then answers from the results', function () {
    config(['ai.default' => 'groq', 'ai.providers.groq.key' => 'test-key']);

    Product::factory()->published()->create(['name' => 'Blast Chiller 20-Tray']);

    // First call: model asks to search. Second call: model answers using the results.
    Http::fake([
        'api.groq.com/*' => Http::sequence()
            ->push(fakeToolCall('search_products', ['query' => 'blast chiller']))
            ->push(fakeChatCompletion('We stock the Blast Chiller 20-Tray — want a quote?')),
    ]);

    $answer = app(ChatAssistant::class)->reply([
        ['role' => 'user', 'content' => 'Do you have a blast chiller?'],
    ]);

    expect($answer)->toBe('We stock the Blast Chiller 20-Tray — want a quote?');

    // The second request must include the tool result feeding the catalogue data back.
    Http::assertSent(function ($request) {
        $roles = collect($request['messages'] ?? [])->pluck('role');

        return $roles->contains('tool')
            && str_contains((string) collect($request['messages'])->firstWhere('role', 'tool')['content'], 'Blast Chiller 20-Tray');
    });
});

it('tells guests to sign in and returns no order data', function () {
    $output = json_decode(app(OrderStatusTool::class)->handle([]), true);

    expect($output['authenticated'])->toBeFalse()
        ->and($output)->not->toHaveKey('orders')
        ->and($output['note'])->toContain('sign in');
});

it('returns only the signed-in customer own orders and quotes', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    $myOrder = Order::factory()->for($me)->create(['order_number' => 'SHF-MINE-001']);
    Order::factory()->for($someoneElse)->create(['order_number' => 'SHF-THEIRS-999']);
    Quote::factory()->for($me)->create(['quote_number' => 'RFQ-MINE-001']);

    $this->actingAs($me);

    $output = json_decode(app(OrderStatusTool::class)->handle([]), true);

    expect($output['authenticated'])->toBeTrue()
        ->and(collect($output['orders'])->pluck('number'))->toContain('SHF-MINE-001')
        ->and(collect($output['orders'])->pluck('number'))->not->toContain('SHF-THEIRS-999')
        ->and(collect($output['quotes'])->pluck('number'))->toContain('RFQ-MINE-001')
        ->and($output['orders'][0]['url'])->toContain('/account/orders/');
});

it('cannot reach another customer order even with their reference', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();
    Order::factory()->for($someoneElse)->create(['order_number' => 'SHF-THEIRS-999']);

    $this->actingAs($me);

    $output = json_decode(app(OrderStatusTool::class)->handle(['reference' => 'SHF-THEIRS-999']), true);

    expect($output['orders'])->toBe([]);
});

it('answers an order-status question using the order tool', function () {
    config(['ai.default' => 'groq', 'ai.providers.groq.key' => 'test-key']);

    $me = User::factory()->create();
    Order::factory()->for($me)->create(['order_number' => 'SHF-2026-01234', 'status' => OrderStatus::OUT_FOR_DELIVERY]);
    $this->actingAs($me);

    Http::fake([
        'api.groq.com/*' => Http::sequence()
            ->push(fakeToolCall('check_my_orders', []))
            ->push(fakeChatCompletion('Your order SHF-2026-01234 is out for delivery.')),
    ]);

    $answer = app(ChatAssistant::class)->reply([
        ['role' => 'user', 'content' => 'Where is my order?'],
    ]);

    expect($answer)->toBe('Your order SHF-2026-01234 is out for delivery.');

    Http::assertSent(function ($request) {
        $toolMessage = collect($request['messages'] ?? [])->firstWhere('role', 'tool');

        return $toolMessage !== null && str_contains((string) $toolMessage['content'], 'SHF-2026-01234');
    });
});

it('appends the visitor message and clears the draft on send', function () {
    Livewire::test('storefront.chat-widget')
        ->set('draft', 'Do you deliver to Mombasa?')
        ->call('send')
        ->assertSet('draft', '')
        ->assertSet('thinking', true)
        ->assertCount('messages', 1)
        ->assertSet('messages.0.role', 'user')
        ->assertSet('messages.0.content', 'Do you deliver to Mombasa?');
});

it('appends the assistant reply from the provider', function () {
    config(['ai.default' => 'groq', 'ai.providers.groq.key' => 'test-key']);

    Http::fake([
        'api.groq.com/*' => Http::response(fakeChatCompletion('Yes, we deliver countrywide.')),
    ]);

    Livewire::test('storefront.chat-widget')
        ->set('draft', 'Do you deliver to Mombasa?')
        ->call('send')
        ->call('reply')
        ->assertSet('thinking', false)
        ->assertCount('messages', 2)
        ->assertSet('messages.1.role', 'assistant')
        ->assertSet('messages.1.content', 'Yes, we deliver countrywide.');
});

it('shows a friendly fallback when the provider fails', function () {
    config(['ai.default' => 'groq', 'ai.providers.groq.key' => 'test-key']);

    Http::fake([
        'api.groq.com/*' => Http::response('server error', 500),
    ]);

    $component = Livewire::test('storefront.chat-widget')
        ->set('draft', 'hello')
        ->call('send')
        ->call('reply')
        ->assertSet('thinking', false)
        ->assertSet('messages.1.role', 'assistant');

    expect($component->get('messages')[1]['content'])->toContain('trouble responding');
});

it('does not render the widget when disabled in settings', function () {
    app(ChatbotSettings::class)->fill(['enabled' => false])->save();

    Livewire::test('storefront.chat-widget')
        ->assertSet('enabled', false)
        ->assertDontSee('Open chat assistant');
});

it('uses the admin-configured greeting and system prompt', function () {
    app(ChatbotSettings::class)->fill([
        'greeting' => 'Karibu! Ask us anything.',
        'system_prompt' => 'You are a test bot.',
    ])->save();

    Livewire::test('storefront.chat-widget')
        ->assertSet('greeting', 'Karibu! Ask us anything.')
        ->assertSet('systemPrompt', 'You are a test bot.')
        ->assertSee('Karibu! Ask us anything.');
});

it('omits tools from the request when both abilities are disabled', function () {
    app(ChatbotSettings::class)->fill([
        'product_search_enabled' => false,
        'order_lookup_enabled' => false,
    ])->save();
    config(['ai.providers.groq.key' => 'test-key']);

    Http::fake(['api.groq.com/*' => Http::response(fakeChatCompletion('Hello.'))]);

    app(ChatAssistant::class)->reply([['role' => 'user', 'content' => 'hi']]);

    Http::assertSent(fn ($request) => ! array_key_exists('tools', $request->data()));
});

it('lets an admin edit chatbot settings from the system settings page', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.settings.system', ['section' => 'chatbot']));

    $response->assertOk();
    $response->assertSee('System prompt');
    $response->assertSee('Product search');
});
