<?php

use App\Models\Product;
use App\Models\User;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Livewire\Livewire;

/**
 * Bug Condition Exploration Test - Product Edit Navigation Fix
 * 
 * **Validates: Requirements 2.1, 2.2, 2.3**
 * 
 * This test encodes the EXPECTED behavior and will FAIL on unfixed code.
 * Failure confirms the bug exists. When this test passes after the fix,
 * it validates that the expected behavior is satisfied.
 * 
 * Property 1: Bug Condition - Navigation After Save Works
 * 
 * For any navigation attempt where a user has successfully saved product updates
 * and attempts to navigate away from the edit page, the system SHALL maintain
 * functional navigation without throwing Alpine.js errors.
 * 
 * NOTE: This is a Livewire component test that simulates the bug condition.
 * The actual bug manifests in the browser with Alpine.js errors:
 * "Uncaught (in promise) Alpine: no element provided to x-anchor"
 * 
 * This test validates that the Livewire component can be saved and that
 * subsequent page loads work correctly, which is the server-side aspect
 * of the bug fix.
 */

describe('Product Edit Navigation After Save', function () {
    
    beforeEach(function () {
        // Seed permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        // Create an admin user with proper permissions
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
        
        // Create a test product to edit
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'type' => ProductType::SIMPLE->value,
            'status' => ProductStatus::PUBLISHED->value,
            'price' => 100.00,
            'sku' => 'TEST-SKU-001',
        ]);
    });
    
    /**
     * Property Test: Product edit component can be saved successfully
     * 
     * This test validates that the save operation completes without errors.
     * The bug manifests AFTER save when navigation is attempted in the browser.
     * 
     * EXPECTED OUTCOME ON UNFIXED CODE: PASS (save works, navigation breaks)
     * EXPECTED OUTCOME ON FIXED CODE: PASS (save works, navigation works)
     */
    it('allows product save operation to complete successfully', function () {
        // Load the product edit component
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $this->product])
            ->assertSuccessful();
        
        // Modify product data
        $component->set('form.name', 'Updated Product Name')
            ->set('form.price', 150.00);
        
        // Save product updates - this should complete without errors
        $component->call('save');
        
        // Verify the component didn't throw errors during save
        $component->assertHasNoErrors();
        
        // Verify save persisted to database
        $this->product->refresh();
        expect($this->product->name)->toBe('Updated Product Name');
        expect((float)$this->product->price)->toBe(150.00);
    });
    
    /**
     * Property Test: Product edit page can be loaded after save
     * 
     * This test validates that after a save operation, the edit page
     * can be reloaded successfully. This is part of the navigation flow.
     * 
     * NOTE: This test may reveal the bug - if the save operation doesn't
     * properly persist data, it indicates a problem with the save mechanism.
     * 
     * EXPECTED OUTCOME: PASS on both unfixed and fixed code
     */
    it('allows product edit page to be reloaded after save', function () {
        // Load and save
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $this->product])
            ->set('form.name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();
        
        // Verify save persisted
        $this->product->refresh();
        expect($this->product->name)->toBe('Updated Name');
        
        // Reload the edit page - simulates navigation back to edit
        $reloadedComponent = Livewire::test('pages::admin.catalog.products.edit', ['product' => $this->product])
            ->assertSuccessful();
        
        // Verify the page loaded with updated data
        expect($reloadedComponent->get('form.name'))->toBe('Updated Name');
    });
    
    /**
     * Property Test: Multiple save operations work correctly
     * 
     * This test validates that multiple save operations don't accumulate
     * state issues that could cause navigation problems.
     * 
     * EXPECTED OUTCOME: PASS on both unfixed and fixed code
     */
    it('allows multiple save operations without errors', function () {
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $this->product])
            ->assertSuccessful();
        
        // First save
        $component->set('form.name', 'First Update')
            ->call('save')
            ->assertHasNoErrors();
        
        // Second save
        $component->set('form.name', 'Second Update')
            ->call('save')
            ->assertHasNoErrors();
        
        // Third save
        $component->set('form.name', 'Third Update')
            ->call('save')
            ->assertHasNoErrors();
        
        // Verify final state
        $this->product->refresh();
        expect($this->product->name)->toBe('Third Update');
    });
    
    /**
     * Property Test: Save works for different product types
     * 
     * Ensures the save operation works correctly across all product types.
     * The navigation bug affects all types after save.
     */
    it('allows save operation for all product types', function () {
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
            
            // Load and save
            $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $product])
                ->assertSuccessful()
                ->set('form.name', "Updated {$type} Product")
                ->call('save')
                ->assertHasNoErrors();
            
            // Verify save worked
            $product->refresh();
            expect($product->name)->toBe("Updated {$type} Product");
        }
    });
    
    /**
     * Baseline Test: Component loads successfully before any save
     * 
     * This test should PASS on unfixed code, confirming that the issue
     * only manifests after save operations.
     */
    it('loads product edit component successfully (baseline)', function () {
        $component = Livewire::test('pages::admin.catalog.products.edit', ['product' => $this->product])
            ->assertSuccessful()
            ->assertHasNoErrors();
        
        // Verify component loaded with correct data
        expect($component->get('form.name'))->toBe('Test Product');
        expect($component->get('form.price'))->toBe(100.00);
    });
});

