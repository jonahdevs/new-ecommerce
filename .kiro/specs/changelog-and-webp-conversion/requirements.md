# Requirements Document

## Introduction

This document specifies requirements for two platform improvements to a Laravel e-commerce application:

1. **Model Changelog Feature** — An SAP-inspired audit trail system that tracks changes to critical business models (Product, ProductVariant, Order, Quote, User, Category, Brand) using the Spatie Activity Log package. Changes are accessible through dedicated changelog pages linked from admin action dropdowns.

2. **WebP Image Conversion Feature** — Automatic WebP image generation at upload time using Intervention Image v3, storing both original and WebP formats with graceful fallback support for legacy images.

## Glossary

- **Changelog_System**: The audit trail subsystem that records and displays model changes
- **Activity_Log**: The Spatie Activity Log package and its underlying database table
- **LogsModelChanges_Trait**: A reusable PHP trait that wraps Spatie functionality for model tracking
- **Changelog_Page**: A Livewire 4 anonymous class component that displays change history for a specific model instance
- **Admin_Dropdown**: The Flux UI dropdown menu in admin listing pages containing action items
- **ImageService**: A service class that handles dual storage of original and WebP image formats
- **WebP_Image_Component**: A reusable Blade component (x-webp-image) that renders images with WebP support and fallback
- **Critical_Model**: One of the seven business models requiring changelog tracking (Product, ProductVariant, Order, Quote, User, Category, Brand)
- **Tracked_Field**: A model attribute that triggers changelog entries when modified
- **Image_Upload_Form**: Any Livewire form component that handles file uploads
- **Legacy_Image**: An image uploaded before WebP conversion was implemented (has no WebP variant)

## Requirements

### Requirement 1: Model Change Tracking

**User Story:** As an administrator, I want changes to critical business models automatically tracked, so that I can audit who changed what and when for compliance and troubleshooting purposes.

#### Acceptance Criteria

1. THE Changelog_System SHALL track changes to Product models for the following Tracked_Fields: name, price, sale_price, sku, stock_quantity, is_active, status, category_id, brand_id
2. THE Changelog_System SHALL track changes to ProductVariant models for the following Tracked_Fields: sku, price, sale_price, stock_quantity, is_active
3. THE Changelog_System SHALL track changes to Order models for the following Tracked_Fields: status, payment_status, notes
4. THE Changelog_System SHALL track changes to Quote models for the following Tracked_Fields: status, notes
5. THE Changelog_System SHALL track changes to User models for the following Tracked_Fields: name, email, is_active
6. THE Changelog_System SHALL track changes to Category models for the following Tracked_Fields: name, parent_id, is_active, sort_order
7. THE Changelog_System SHALL track changes to Brand models for the following Tracked_Fields: name, is_active
8. WHEN a Tracked_Field value changes, THE Changelog_System SHALL record the old value, new value, timestamp, and causer (authenticated user)
9. WHEN a model update occurs but no Tracked_Fields change, THE Changelog_System SHALL NOT create a changelog entry
10. THE Activity_Log SHALL retain changelog entries for 365 days

### Requirement 2: LogsModelChanges Trait

**User Story:** As a developer, I want a reusable trait to enable changelog tracking on any model, so that I can consistently add audit trails without duplicating code.

#### Acceptance Criteria

1. THE LogsModelChanges_Trait SHALL provide a getLoggedAttributes method that returns an array of Tracked_Fields
2. THE LogsModelChanges_Trait SHALL provide a getLogName method that returns a string identifier for the log category
3. WHEN the LogsModelChanges_Trait is applied to a model, THE Changelog_System SHALL automatically track changes to fields returned by getLoggedAttributes
4. THE LogsModelChanges_Trait SHALL configure the Activity_Log to log only dirty attributes (changed values)
5. THE LogsModelChanges_Trait SHALL configure the Activity_Log to skip empty log submissions

### Requirement 3: Changelog Page Display

**User Story:** As an administrator, I want to view a chronological list of changes for any model instance, so that I can understand its modification history.

#### Acceptance Criteria

1. THE Changelog_Page SHALL display changelog entries in reverse chronological order (newest first)
2. THE Changelog_Page SHALL paginate results with 20 entries per page
3. FOR EACH changelog entry, THE Changelog_Page SHALL display the timestamp, causer name, and field changes
4. FOR EACH field change, THE Changelog_Page SHALL display the field name, old value, and new value
5. WHEN a field was added (no old value exists), THE Changelog_Page SHALL display "—" for the old value
6. WHEN a field was removed (no new value exists), THE Changelog_Page SHALL display "—" for the new value
7. THE Changelog_Page SHALL use Livewire 4 anonymous class component architecture
8. WHEN a user lacks view permission for a model, THE Changelog_Page SHALL return an authorization error

### Requirement 4: Admin Dropdown Integration

**User Story:** As an administrator, I want to access changelog pages from the admin listing page action dropdown, so that I can quickly view change history without navigating away from my workflow.

#### Acceptance Criteria

1. THE Admin_Dropdown for Product listings SHALL include a "Change Log" menu item
2. THE Admin_Dropdown for Order listings SHALL include a "Change Log" menu item
3. THE Admin_Dropdown for Quote listings SHALL include a "Change Log" menu item
4. THE Admin_Dropdown for User listings SHALL include a "Change Log" menu item
5. THE Admin_Dropdown for Category listings SHALL include a "Change Log" menu item
6. THE Admin_Dropdown for Brand listings SHALL include a "Change Log" menu item
7. WHEN a "Change Log" menu item is clicked, THE Admin_Dropdown SHALL navigate to the corresponding Changelog_Page for that model instance
8. THE "Change Log" menu item SHALL use a clock icon with outline variant
9. THE "Change Log" menu item SHALL be separated from other menu items by a visual separator

### Requirement 5: WebP Image Generation

**User Story:** As a developer, I want images automatically converted to WebP format at upload time, so that the application serves optimized images without manual conversion.

#### Acceptance Criteria

1. WHEN an image is uploaded through an Image_Upload_Form, THE ImageService SHALL store the original image file
2. WHEN an image is uploaded through an Image_Upload_Form, THE ImageService SHALL generate a WebP variant with 85% quality
3. THE ImageService SHALL store the WebP variant in the same directory as the original with a .webp extension
4. THE ImageService SHALL return both the original file path and WebP file path
5. THE ImageService SHALL use Intervention Image v3 with the GD driver for conversion
6. THE ImageService SHALL accept a TemporaryUploadedFile, directory path, and optional disk name as parameters

### Requirement 6: WebP Database Storage

**User Story:** As a developer, I want WebP file paths stored alongside original image paths, so that the application can serve WebP images when available.

#### Acceptance Criteria

1. THE Product model SHALL have an image_webp column to store the WebP variant path
2. THE ProductImage model SHALL have a webp_path column to store the WebP variant path
3. THE Brand model SHALL have a logo_webp column to store the WebP variant path
4. THE Category model SHALL have an image_webp column to store the WebP variant path
5. THE Category model SHALL have an icon_webp column to store the WebP variant path
6. THE Product model SHALL provide a webp_image_url accessor that returns the full URL or null
7. THE ProductImage model SHALL provide a webp_url accessor that returns the full URL or null
8. THE Brand model SHALL provide a webp_logo_url accessor that returns the full URL or null
9. THE Category model SHALL provide webp_image_url and webp_icon_url accessors that return full URLs or null

### Requirement 7: WebP Image Component

**User Story:** As a developer, I want a reusable Blade component for rendering images with WebP support, so that I can consistently implement WebP delivery with fallback across the application.

#### Acceptance Criteria

1. THE WebP_Image_Component SHALL accept src, webp, alt, and class parameters
2. WHEN a webp parameter is provided, THE WebP_Image_Component SHALL render a picture element with a WebP source
3. WHEN a webp parameter is provided, THE WebP_Image_Component SHALL render an img element as fallback within the picture element
4. WHEN a webp parameter is null or not provided, THE WebP_Image_Component SHALL render a plain img element
5. THE WebP_Image_Component SHALL merge additional HTML attributes onto the img element
6. THE WebP_Image_Component SHALL support Legacy_Images by gracefully degrading to img-only rendering

### Requirement 8: Image Upload Form Integration

**User Story:** As a developer, I want existing image upload forms updated to use the ImageService, so that new uploads automatically generate WebP variants.

#### Acceptance Criteria

1. WHEN a Product image is uploaded, THE Image_Upload_Form SHALL use ImageService to store both original and WebP variants
2. WHEN a ProductImage is uploaded, THE Image_Upload_Form SHALL use ImageService to store both original and WebP variants
3. WHEN a Brand logo is uploaded, THE Image_Upload_Form SHALL use ImageService to store both original and WebP variants
4. WHEN a Category image is uploaded, THE Image_Upload_Form SHALL use ImageService to store both original and WebP variants
5. WHEN a Category icon is uploaded, THE Image_Upload_Form SHALL use ImageService to store both original and WebP variants
6. THE Image_Upload_Form SHALL update both the original path column and the webp path column in the database

### Requirement 9: Frontend WebP Display

**User Story:** As a user, I want the application to serve WebP images when available, so that pages load faster with optimized image formats.

#### Acceptance Criteria

1. THE product card component SHALL use the WebP_Image_Component to display product thumbnails
2. THE product details hero section SHALL use the WebP_Image_Component to display product images
3. THE grouped product hero section SHALL use the WebP_Image_Component to display product images
4. WHEN a product has a WebP variant, THE WebP_Image_Component SHALL serve the WebP image to supporting browsers
5. WHEN a product has no WebP variant (Legacy_Image), THE WebP_Image_Component SHALL serve the original image
6. WHEN a browser does not support WebP, THE WebP_Image_Component SHALL serve the original image as fallback

### Requirement 10: Changelog Extensibility

**User Story:** As a developer, I want to add changelog tracking to new models in the future, so that the audit trail system can grow with the application.

#### Acceptance Criteria

1. WHEN a developer applies the LogsModelChanges_Trait to a new model, THE Changelog_System SHALL automatically track changes to fields specified in getLoggedAttributes
2. WHEN a developer creates a new Changelog_Page following the established pattern, THE Changelog_Page SHALL display changes from the Activity_Log
3. WHEN a developer adds a "Change Log" menu item to an Admin_Dropdown, THE menu item SHALL navigate to the corresponding Changelog_Page
4. THE LogsModelChanges_Trait SHALL provide a default getLogName implementation that returns the lowercase class name
5. THE LogsModelChanges_Trait SHALL allow models to override getLogName for custom log categorization

### Requirement 11: WebP Extensibility

**User Story:** As a developer, I want to add WebP conversion to new image upload forms in the future, so that all images benefit from optimization.

#### Acceptance Criteria

1. WHEN a developer adds a new Image_Upload_Form, THE developer SHALL be able to use ImageService without modification
2. THE ImageService SHALL support any storage disk supported by Laravel's Storage facade
3. THE ImageService SHALL support any image format supported by Intervention Image v3
4. THE WebP_Image_Component SHALL work with any image URL without requiring specific path structures
5. WHEN a new model requires WebP support, THE developer SHALL add a nullable string column for the WebP path

### Requirement 12: Activity Log Configuration

**User Story:** As a system administrator, I want to control whether activity logging is enabled, so that I can disable it in development or testing environments if needed.

#### Acceptance Criteria

1. THE Activity_Log SHALL be enabled or disabled via the ACTIVITY_LOGGER_ENABLED environment variable
2. WHEN ACTIVITY_LOGGER_ENABLED is false, THE Changelog_System SHALL NOT create changelog entries
3. THE Activity_Log SHALL use a retention period of 365 days as configured in config/activitylog.php
4. THE Activity_Log SHALL store entries in the activity_log database table

### Requirement 13: Parser and Serializer Requirements

**User Story:** As a developer, I want the Activity_Log properties column to correctly serialize and deserialize field changes, so that changelog data is accurately stored and retrieved.

#### Acceptance Criteria

1. WHEN the Activity_Log stores field changes, THE Activity_Log SHALL serialize old and new values as JSON in the properties column
2. WHEN the Changelog_Page retrieves field changes, THE Activity_Log SHALL deserialize the properties column into a PHP array
3. THE Activity_Log SHALL use Laravel's JSON casting for the properties column
4. FOR ALL valid field change data, serializing then deserializing SHALL produce equivalent data (round-trip property)
