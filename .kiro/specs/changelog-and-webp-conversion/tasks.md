# Implementation Plan: Model Changelog and WebP Image Conversion

## Overview

This implementation plan covers two independent platform improvements:

1. **Model Changelog Feature**: SAP-inspired audit trail using Spatie Activity Log with trait-based tracking and dedicated Livewire changelog pages
2. **WebP Image Conversion Feature**: Automatic WebP generation at upload time with dual storage and graceful fallback

The implementation follows an incremental approach, building core infrastructure first, then integrating with existing models and forms, and finally wiring everything together with frontend components.

## Tasks

### Part 1: Model Changelog Feature

- [x]   1. Install and configure Spatie Activity Log package
    - Verify Spatie Activity Log 4.12 is installed (already in project)
    - Publish configuration file if not present: `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"`
    - Configure retention period to 365 days in `config/activitylog.php`
    - Add `ACTIVITY_LOGGER_ENABLED=true` to `.env.example`
    - _Requirements: 1.10, 12.1, 12.3, 12.4_

- [x]   2. Create LogsModelChanges trait
    - [x] 2.1 Create trait file at `app/Concerns/LogsModelChanges.php`
        - Implement `getActivitylogOptions()` method that configures Spatie to log only dirty attributes
        - Implement abstract `getLoggedAttributes()` method for models to define tracked fields
        - Implement `getLogName()` method with default lowercase class name
        - Configure to skip empty log submissions
        - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 10.4_

    - [x] 2.2 Write unit tests for LogsModelChanges trait
        - Test `getLoggedAttributes()` returns correct field array
        - Test `getLogName()` returns correct log category
        - Test `getActivitylogOptions()` configures correct options
        - _Requirements: 2.1, 2.2, 2.3_

- [x]   3. Apply LogsModelChanges trait to critical models
    - [x] 3.1 Add trait to Product model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: name, price, sale_price, sku, stock_quantity, is_active, status, category_id, brand_id
        - _Requirements: 1.1_

    - [x] 3.2 Add trait to ProductVariant model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: sku, price, sale_price, stock_quantity, is_active
        - _Requirements: 1.2_

    - [x] 3.3 Add trait to Order model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: status, payment_status, notes
        - _Requirements: 1.3_

    - [x] 3.4 Add trait to Quote model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: status, notes
        - _Requirements: 1.4_

    - [x] 3.5 Add trait to User model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: name, email, is_active
        - _Requirements: 1.5_

    - [x] 3.6 Add trait to Category model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: name, parent_id, is_active, sort_order
        - _Requirements: 1.6_

    - [x] 3.7 Add trait to Brand model
        - Apply `LogsModelChanges` trait
        - Implement `getLoggedAttributes()` returning: name, is_active
        - _Requirements: 1.7_

    - [x] 3.8 Write integration tests for model change tracking
        - Test updating tracked field creates activity log entry
        - Test updating non-tracked field does NOT create entry
        - Test activity log captures old and new values correctly
        - Test activity log captures causer information
        - Test multiple field changes in single update
        - _Requirements: 1.8, 1.9_

- [x]   4. Create changelog Livewire pages
    - [x] 4.1 Create Product changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/product-changelog.blade.php`
        - Implement `mount(int $id)` with authorization check
        - Implement computed `activities()` property with pagination (20 per page)
        - Display activities in reverse chronological order
        - Show timestamp, causer name (or "System" if null), and field changes
        - Display "—" for null/missing values
        - Add route: `Route::get('/admin/changelog/product/{product}', ProductChangelogPage::class)->name('admin.changelog.product')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.2 Create Order changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/order-changelog.blade.php`
        - Follow same pattern as Product changelog page
        - Add route: `Route::get('/admin/changelog/order/{order}', OrderChangelogPage::class)->name('admin.changelog.order')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.3 Create Quote changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/quote-changelog.blade.php`
        - Follow same pattern as Product changelog page
        - Add route: `Route::get('/admin/changelog/quote/{quote}', QuoteChangelogPage::class)->name('admin.changelog.quote')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.4 Create User changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/user-changelog.blade.php`
        - Follow same pattern as Product changelog page
        - Add route: `Route::get('/admin/changelog/user/{user}', UserChangelogPage::class)->name('admin.changelog.user')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.5 Create Category changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/category-changelog.blade.php`
        - Follow same pattern as Product changelog page
        - Add route: `Route::get('/admin/changelog/category/{category}', CategoryChangelogPage::class)->name('admin.changelog.category')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.6 Create Brand changelog page
        - Create anonymous Livewire 4 component at `resources/views/livewire/admin/changelog/brand-changelog.blade.php`
        - Follow same pattern as Product changelog page
        - Add route: `Route::get('/admin/changelog/brand/{brand}', BrandChangelogPage::class)->name('admin.changelog.brand')`
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

    - [x] 4.7 Write integration tests for changelog pages
        - Test page displays activities in reverse chronological order
        - Test pagination works correctly (20 per page)
        - Test field changes display correctly (old → new)
        - Test null values display as "—"
        - Test authorization prevents unauthorized access
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

- [x]   5. Integrate changelog links into admin dropdowns
    - [x] 5.1 Add "Change Log" menu item to Product admin dropdown
        - Locate Product listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.product', $product)`
        - _Requirements: 4.1, 4.7, 4.8, 4.9_

    - [x] 5.2 Add "Change Log" menu item to Order admin dropdown
        - Locate Order listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.order', $order)`
        - _Requirements: 4.2, 4.7, 4.8, 4.9_

    - [x] 5.3 Add "Change Log" menu item to Quote admin dropdown
        - Locate Quote listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.quote', $quote)`
        - _Requirements: 4.3, 4.7, 4.8, 4.9_

    - [x] 5.4 Add "Change Log" menu item to User admin dropdown
        - Locate User listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.user', $user)`
        - _Requirements: 4.4, 4.7, 4.8, 4.9_

    - [x] 5.5 Add "Change Log" menu item to Category admin dropdown
        - Locate Category listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.category', $category)`
        - _Requirements: 4.5, 4.7, 4.8, 4.9_

    - [x] 5.6 Add "Change Log" menu item to Brand admin dropdown
        - Locate Brand listing page admin dropdown
        - Add Flux dropdown item with clock icon (outline variant)
        - Add separator before the item
        - Link to `route('admin.changelog.brand', $brand)`
        - _Requirements: 4.6, 4.7, 4.8, 4.9_

    - [x] 5.7 Write integration tests for admin dropdown links
        - Test "Change Log" menu item appears in dropdowns
        - Test menu item links to correct changelog page
        - Test menu item uses correct icon
        - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9_

- [x]   6. Checkpoint - Verify changelog feature
    - Ensure all tests pass, ask the user if questions arise.

### Part 2: WebP Image Conversion Feature

- [x]   7. Install and configure Intervention Image v3
    - Install Intervention Image v3 package: `composer require intervention/image`
    - Verify GD driver is available in PHP installation
    - _Requirements: 5.5_

- [ ]   8. Create ImageService for dual storage
    - [ ] 8.1 Create ImageService class at `app/Services/ImageService.php`
        - Implement `storeWithWebP(TemporaryUploadedFile $file, string $directory, string $disk = 'public'): array` method
        - Store original image using Laravel Storage
        - Generate WebP variant using Intervention Image v3 with 85% quality
        - Store WebP in same directory with .webp extension
        - Return array with 'original' and 'webp' keys
        - Implement error handling with fallback to original-only storage
        - Log errors for admin review
        - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

    - [ ] 8.2 Implement generateWebP helper method
        - Create protected `generateWebP(string $originalPath, string $disk = 'public'): ?string` method
        - Use Intervention Image v3 with GD driver to read original
        - Convert to WebP format with 85% quality
        - Save in same directory with .webp extension
        - Return WebP path or null on failure
        - _Requirements: 5.2, 5.3, 5.5_

    - [ ] 8.3 Write unit tests for ImageService
        - Test `storeWithWebP()` returns both original and WebP paths
        - Test WebP file is created with correct extension
        - Test WebP file is stored in same directory as original
        - Test `generateWebP()` creates WebP from existing image
        - Test service handles invalid image gracefully
        - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [ ]   9. Add WebP columns to database tables
    - [ ] 9.1 Create migration for products table
        - Add `image_webp` column (VARCHAR 255, nullable) after `image_path`
        - _Requirements: 6.1_

    - [ ] 9.2 Create migration for product_images table
        - Add `webp_path` column (VARCHAR 255, nullable) after `image_path`
        - _Requirements: 6.2_

    - [ ] 9.3 Create migration for brands table
        - Add `logo_webp` column (VARCHAR 255, nullable) after `logo_path`
        - _Requirements: 6.3_

    - [ ] 9.4 Create migration for categories table
        - Add `image_webp` column (VARCHAR 255, nullable) after `image_path`
        - Add `icon_webp` column (VARCHAR 255, nullable) after `icon_path`
        - _Requirements: 6.4, 6.5_

    - [ ] 9.5 Run migrations
        - Execute `php artisan migrate` to apply schema changes
        - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ]   10. Add WebP accessors to models
    - [ ] 10.1 Add webp_image_url accessor to Product model
        - Add `image_webp` to fillable array
        - Create `webpImageUrl()` Attribute accessor that returns full URL or null
        - _Requirements: 6.1, 6.6_

    - [ ] 10.2 Add webp_url accessor to ProductImage model
        - Add `webp_path` to fillable array
        - Create `webpUrl()` Attribute accessor that returns full URL or null
        - _Requirements: 6.2, 6.7_

    - [ ] 10.3 Add webp_logo_url accessor to Brand model
        - Add `logo_webp` to fillable array
        - Create `webpLogoUrl()` Attribute accessor that returns full URL or null
        - _Requirements: 6.3, 6.8_

    - [ ] 10.4 Add webp accessors to Category model
        - Add `image_webp` and `icon_webp` to fillable array
        - Create `webpImageUrl()` Attribute accessor that returns full URL or null
        - Create `webpIconUrl()` Attribute accessor that returns full URL or null
        - _Requirements: 6.4, 6.5, 6.9_

    - [ ] 10.5 Write unit tests for model accessors
        - Test `webp_image_url` returns correct URL when webp path exists
        - Test `webp_image_url` returns null when webp path is null
        - Test accessors work for all models (Product, Brand, Category, ProductImage)
        - _Requirements: 6.6, 6.7, 6.8, 6.9_

- [ ]   11. Create x-webp-image Blade component
    - [ ] 11.1 Create component file at `resources/views/components/webp-image.blade.php`
        - Accept props: src (required), webp (optional), alt (required), class (optional)
        - Render picture element with WebP source when webp prop is provided
        - Render plain img element when webp prop is null
        - Merge additional attributes onto img element
        - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

    - [ ] 11.2 Write component tests for x-webp-image
        - Test component renders picture element when webp provided
        - Test component renders img element when webp is null
        - Test component merges attributes correctly
        - Test component handles legacy images (no webp)
        - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [ ]   12. Update image upload forms to use ImageService
    - [ ] 12.1 Update Product image upload form
        - Locate Product Livewire form component
        - Replace direct file storage with ImageService->storeWithWebP()
        - Update both `image_path` and `image_webp` columns in database
        - _Requirements: 8.1, 8.6_

    - [ ] 12.2 Update ProductImage upload form
        - Locate ProductImage Livewire form component
        - Replace direct file storage with ImageService->storeWithWebP()
        - Update both `image_path` and `webp_path` columns in database
        - _Requirements: 8.2, 8.6_

    - [ ] 12.3 Update Brand logo upload form
        - Locate Brand Livewire form component
        - Replace direct file storage with ImageService->storeWithWebP()
        - Update both `logo_path` and `logo_webp` columns in database
        - _Requirements: 8.3, 8.6_

    - [ ] 12.4 Update Category image upload form
        - Locate Category Livewire form component
        - Replace direct file storage with ImageService->storeWithWebP()
        - Update both `image_path` and `image_webp` columns in database
        - _Requirements: 8.4, 8.6_

    - [ ] 12.5 Update Category icon upload form
        - Locate Category Livewire form component (icon field)
        - Replace direct file storage with ImageService->storeWithWebP()
        - Update both `icon_path` and `icon_webp` columns in database
        - _Requirements: 8.5, 8.6_

    - [ ] 12.6 Write integration tests for upload forms
        - Test product image upload stores both formats
        - Test brand logo upload stores both formats
        - Test category image/icon upload stores both formats
        - Test database columns are updated correctly
        - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

- [ ]   13. Update frontend to use x-webp-image component
    - [ ] 13.1 Update product card component
        - Locate product card Blade component
        - Replace img tags with x-webp-image component
        - Pass `src="{{ $product->image_url }}"` and `webp="{{ $product->webp_image_url }}"`
        - _Requirements: 9.1, 9.4, 9.5, 9.6_

    - [ ] 13.2 Update product details hero section
        - Locate product details page hero section
        - Replace img tags with x-webp-image component
        - Pass `src="{{ $product->image_url }}"` and `webp="{{ $product->webp_image_url }}"`
        - _Requirements: 9.2, 9.4, 9.5, 9.6_

    - [ ] 13.3 Update grouped product hero section
        - Locate grouped product page hero section
        - Replace img tags with x-webp-image component
        - Pass `src="{{ $product->image_url }}"` and `webp="{{ $product->webp_image_url }}"`
        - _Requirements: 9.3, 9.4, 9.5, 9.6_

- [ ]   14. Checkpoint - Verify WebP feature
    - Ensure all tests pass, ask the user if questions arise.

### Part 3: Final Integration and Documentation

- [ ]   15. Final integration verification
    - [ ] 15.1 Test changelog feature end-to-end
        - Update a Product and verify changelog entry appears
        - Access changelog page from admin dropdown
        - Verify all tracked fields are logged correctly
        - _Requirements: 1.1-1.10, 3.1-3.8, 4.1-4.9_

    - [ ] 15.2 Test WebP feature end-to-end
        - Upload a new product image and verify both formats are stored
        - View product on frontend and verify WebP is served
        - Test with legacy product (no WebP) and verify fallback works
        - _Requirements: 5.1-5.6, 6.1-6.9, 7.1-7.6, 8.1-8.6, 9.1-9.6_

    - [ ] 15.3 Verify extensibility patterns
        - Document how to add changelog tracking to new models
        - Document how to add WebP conversion to new upload forms
        - _Requirements: 10.1-10.5, 11.1-11.5_

- [ ]   16. Final checkpoint - Complete implementation
    - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The two features are independent and can be implemented in parallel if desired
- All changelog pages follow the same pattern for consistency
- WebP conversion includes comprehensive error handling with fallback to original images
- Legacy images (uploaded before WebP feature) are supported through graceful degradation
