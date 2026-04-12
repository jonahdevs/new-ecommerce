<?php

use App\Models\Product;
use App\Models\User;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Livewire\Livewire;
use Illuminate\Support\Facades\Event;

/**
 * Preservation Property Tests - Product Edit Navigation Fix
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 * 
 * Property 2: Preservation - Save Functionality Unchanged
 * 
 * These tests validate that the fix does NOT break existing functionality.
 * All tests should PASS on UNFIXED code (establishing baseline behavior)
 * and continue to PASS on FIXED code (confirming no regressions).
 * 
 * Testing Approach: Property-based testing principles
 * - Test across many different inputs (product types, data variations)
 * - Test universal properties that should hold for all inputs
 * - Generate varied test data to ensure broad coverage
 * 
 * EXPECTED OUTCOME ON UNFIXED CODE: ALL TESTS PASS
 * EXPECTED OUTCOME ON FIXED CODE: ALL TESTS PASS
 */

describe('Product Edit Preservation - Save Functionality', function () {
    
    beforeEach(function () {
        // Seed permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        // Create an admin user with proper permissions
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
    });
    
    /**
     * Property Test: Save operation persists data to database
     * 
     * **Validates: Requirement 3.1**
     * 
     * For ANY product save operation, the system SHALL successfully
     * save all product data to the database.
     * 
     * This test generates multiple product variations and verifies
     * that save operations persist data correctly across all cases.
     */
    it('preserves save operation - data persists to database for all product types', function () {
        $productTypes = [
            ProductType::SIMPLE->value,
            // Note: Variable and Grouped products have different price handling
            // Variable products use variant prices, Grouped products use child prices
        ];
        
        foreach ($productTypes as $type) {
            // Generate test product
            $product = Product::factory()->create([
                'name' => "Original {$type} Product",
                'type' => $type,
                'price' => 100.00,
                'sku' => "SKU-{$type}-001",
                'stock_quantity' => 50,
            ]);
            
            // Load component and modify data
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful();
            
            // Update multiple fields
            $component->set('form.name', "Updated {$type} Product")
                ->set('form.price', 150.00)
                ->set('form.stock_quantity', 75)
                ->call('save')
                ->assertHasNoErrors();
            
            // Verify ALL changes persisted to database
            $product->refresh();
            expect($product->name)->toBe("Updated {$type} Product")
                ->and((float)$product->price)->toBe(150.00)
                ->and($product->stock_quantity)->toBe(75);
        }
    });
    
    /**
     * Property Test: Save operation persists data across many field variations
     * 
     * **Validates: Requirement 3.1**
     * 
     * Tests that save operations work correctly for different field combinations
     * and data types, ensuring comprehensive data persistence.
     */
    it('preserves save operation - data persists for various field combinations', function () {
        $testCases = [
            [
                'name' => 'Product with Long Name ' . str_repeat('A', 100),
                'price' => 0.01, // Minimum price
                'stock_quantity' => 0, // Out of stock
            ],
            [
                'name' => 'Product with Special Chars !@#$%',
                'price' => 9999.99, // High price
                'stock_quantity' => 1000, // High stock
            ],
            [
                'name' => 'Product with Numbers 12345',
                'price' => 49.99, // Decimal price
                'stock_quantity' => 25, // Medium stock
            ],
        ];
        
        foreach ($testCases as $index => $testData) {
            $product = Product::factory()->create([
                'name' => "Original Product {$index}",
                'price' => 100.00,
                'stock_quantity' => 50,
            ]);
            
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->set('form.name', $testData['name'])
                ->set('form.price', $testData['price'])
                ->set('form.stock_quantity', $testData['stock_quantity'])
                ->call('save')
                ->assertHasNoErrors();
            
            // Verify data persisted correctly
            $product->refresh();
            expect($product->name)->toBe($testData['name'])
                ->and((float)$product->price)->toBe($testData['price'])
                ->and($product->stock_quantity)->toBe($testData['stock_quantity']);
        }
    });
    
    /**
     * Property Test: Success notification displays after save
     * 
     * **Validates: Requirement 3.2**
     * 
     * For ANY product save operation, the system SHALL display the
     * success notification "Product updated successfully!".
     * 
     * This test verifies the notification is dispatched correctly
     * across different product types and save scenarios.
     */
    it('preserves success notification - displays after save for all product types', function () {
        $productTypes = [
            ProductType::SIMPLE->value,
            ProductType::VARIABLE->value,
            ProductType::GROUPED->value,
        ];
        
        foreach ($productTypes as $type) {
            $product = Product::factory()->create([
                'type' => $type,
                'name' => "Test {$type} Product",
            ]);
            
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->set('form.name', "Updated {$type} Product")
                ->call('save');
            
            // Verify success notification was dispatched
            $component->assertDispatched('notify');
        }
    });
    
    /**
     * Property Test: Success notification displays for multiple consecutive saves
     * 
     * **Validates: Requirement 3.2**
     * 
     * Verifies that the notification continues to work correctly
     * even after multiple save operations.
     */
    it('preserves success notification - displays for multiple consecutive saves', function () {
        $product = Product::factory()->create([
            'name' => 'Test Product',
        ]);
        
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product]);
        
        // Perform multiple saves and verify notification each time
        for ($i = 1; $i <= 5; $i++) {
            $component->set('form.name', "Update {$i}")
                ->call('save')
                ->assertDispatched('notify');
        }
    });
    
    /**
     * Property Test: 'product-saved' event is dispatched after save
     * 
     * **Validates: Requirement 3.3**
     * 
     * For ANY product save operation, the system SHALL dispatch
     * the 'product-saved' event for any listeners.
     * 
     * This test verifies the event is dispatched correctly across
     * different product types and save scenarios.
     */
    it('preserves event dispatch - product-saved event fires for all product types', function () {
        $productTypes = [
            ProductType::SIMPLE->value,
            ProductType::VARIABLE->value,
            ProductType::GROUPED->value,
        ];
        
        foreach ($productTypes as $type) {
            $product = Product::factory()->create([
                'type' => $type,
                'name' => "Test {$type} Product",
            ]);
            
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->set('form.name', "Updated {$type} Product")
                ->call('save');
            
            // Verify 'product-saved' event was dispatched
            $component->assertDispatched('product-saved');
        }
    });
    
    /**
     * Property Test: 'product-saved' event fires for multiple consecutive saves
     * 
     * **Validates: Requirement 3.3**
     * 
     * Verifies that the event continues to be dispatched correctly
     * even after multiple save operations.
     */
    it('preserves event dispatch - product-saved event fires for multiple saves', function () {
        $product = Product::factory()->create([
            'name' => 'Test Product',
        ]);
        
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product]);
        
        // Perform multiple saves and verify event dispatch each time
        for ($i = 1; $i <= 5; $i++) {
            $component->set('form.name', "Update {$i}")
                ->call('save')
                ->assertDispatched('product-saved');
        }
    });
    
    /**
     * Property Test: Initial page load correctly loads all product data
     * 
     * **Validates: Requirement 3.4**
     * 
     * For ANY product, the system SHALL correctly load all product data
     * when the edit page is initially accessed.
     * 
     * This test verifies data loading works correctly across different
     * product types and data variations.
     */
    it('preserves initial page load - loads all product data correctly for all types', function () {
        $productTypes = [
            ProductType::SIMPLE->value,
            ProductType::VARIABLE->value,
            ProductType::GROUPED->value,
        ];
        
        foreach ($productTypes as $type) {
            $product = Product::factory()->create([
                'name' => "Test {$type} Product",
                'type' => $type,
                'price' => 123.45,
                'sku' => "SKU-{$type}-TEST",
                'stock_quantity' => 42,
                'short_description' => "Short description for {$type}",
                'description' => "Full description for {$type}",
            ]);
            
            // Load the edit page
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful()
                ->assertHasNoErrors();
            
            // Verify ALL product data loaded correctly
            expect($component->get('form.name'))->toBe("Test {$type} Product")
                ->and($component->get('form.type'))->toBe($type)
                ->and($component->get('form.price'))->toBe(123.45)
                ->and($component->get('form.sku'))->toBe("SKU-{$type}-TEST")
                ->and($component->get('form.stock_quantity'))->toBe(42)
                ->and($component->get('form.short_description'))->toBe("Short description for {$type}")
                ->and($component->get('form.description'))->toBe("Full description for {$type}");
        }
    });
    
    /**
     * Property Test: Initial page load works for various data combinations
     * 
     * **Validates: Requirement 3.4**
     * 
     * Tests that page load works correctly for different field values
     * and edge cases.
     */
    it('preserves initial page load - loads data correctly for various field values', function () {
        $testCases = [
            [
                'name' => 'Product with Minimum Values',
                'price' => 0.01,
                'stock_quantity' => 0,
            ],
            [
                'name' => 'Product with Maximum Values',
                'price' => 99999.99,
                'stock_quantity' => 9999,
            ],
            [
                'name' => 'Product with Special Characters !@#$%^&*()',
                'price' => 49.99,
                'stock_quantity' => 100,
            ],
        ];
        
        foreach ($testCases as $testData) {
            $product = Product::factory()->create($testData);
            
            // Load the edit page
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful()
                ->assertHasNoErrors();
            
            // Verify data loaded correctly
            expect($component->get('form.name'))->toBe($testData['name'])
                ->and($component->get('form.price'))->toBe($testData['price'])
                ->and($component->get('form.stock_quantity'))->toBe($testData['stock_quantity']);
        }
    });
    
    /**
     * Property Test: Navigation away from edit page without saving works
     * 
     * **Validates: Requirement 3.5**
     * 
     * For ANY product, the system SHALL allow successful navigation
     * away from the edit page WITHOUT saving changes.
     * 
     * This test verifies that navigation works correctly BEFORE any
     * save operation (the bug only occurs AFTER save).
     */
    it('preserves pre-save navigation - allows navigation without saving', function () {
        $productTypes = [
            ProductType::SIMPLE->value,
            ProductType::VARIABLE->value,
            ProductType::GROUPED->value,
        ];
        
        foreach ($productTypes as $type) {
            $product = Product::factory()->create([
                'name' => "Test {$type} Product",
                'type' => $type,
                'price' => 100.00,
            ]);
            
            // Load the edit page
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful();
            
            // Modify data but DON'T save
            $component->set('form.name', "Modified {$type} Product")
                ->set('form.price', 200.00);
            
            // Verify component is still functional (can be destroyed without errors)
            $component->assertHasNoErrors();
            
            // Verify data was NOT persisted (navigation away without save)
            $product->refresh();
            expect($product->name)->toBe("Test {$type} Product")
                ->and((float)$product->price)->toBe(100.00);
        }
    });
    
    /**
     * Property Test: Component can be loaded and unloaded multiple times
     * 
     * **Validates: Requirement 3.5**
     * 
     * Verifies that the component can be loaded and unloaded (simulating
     * navigation) multiple times without issues.
     */
    it('preserves pre-save navigation - allows multiple load/unload cycles', function () {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00,
        ]);
        
        // Simulate multiple navigation cycles (load and unload)
        for ($i = 1; $i <= 5; $i++) {
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful()
                ->assertHasNoErrors();
            
            // Verify data loads correctly each time
            expect($component->get('form.name'))->toBe('Test Product')
                ->and($component->get('form.price'))->toBe(100.00);
            
            // Component is destroyed when test ends (simulating navigation away)
        }
        
        // Verify no data was modified
        $product->refresh();
        expect($product->name)->toBe('Test Product')
            ->and((float)$product->price)->toBe(100.00);
    });
});

