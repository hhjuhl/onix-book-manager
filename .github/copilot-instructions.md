# ONIX Book Manager - AI Coding Agent Instructions

## Project Overview
ONIX Book Manager is a WooCommerce plugin (PHP 8.0+, WC 10.5.0+) that adds a custom "book" product type with ONIX 3.1 export functionality. The plugin extends WooCommerce's variable product system and manages book-specific metadata (ISBN, format, language, THEMA subject codes, etc.).

## Architecture & Key Components

### Plugin Initialization Flow (`onix-book-manager.php`)
1. Constants defined: `OBM_VERSION`, `OBM_PATH`, `OBM_URL`
2. Textdomain loaded on `init` hook
3. **Critical**: Classes loaded on `plugins_loaded` hook (ensures WooCommerce active first)
4. Activation hook for first-time setup
5. WooCommerce HPOS compatibility declared on `before_woocommerce_init`

### Class Structure (All static methods in `includes/`)
- **`OBM_Product_Type`**: Registers custom book product type and WooCommerce integration
- **`OBM_Product_Admin`**: Manages admin UI (metadata tabs, SKU locking, variant export buttons)
- **`OBM_Product_Frontend`**: Handles customer-facing display and JavaScript behavior
- **`OBM_Admin`**: Handles admin menu (Products > ONIX Export), export page rendering, asset enqueueing
- **`OBM_Export`**: Generates ONIX 3.1 XML with nonce validation and permission checks
- **`OBM_Thema`**: Loads/caches THEMA classification from `assets/thema_da.json` using transients

### Key Hooks & Patterns
- **Product Type Registration**: `product_type_selector`, `woocommerce_product_class`, `woocommerce_data_stores` filters
- **Admin Data Panels**: Custom tab added via `woocommerce_product_data_tabs` → `woocommerce_product_data_panels` → `woocommerce_process_product_meta`
- **Frontend Display**: Variable product attributes filtered via `woocommerce_display_product_attributes`
- **Export Trigger**: Both AJAX (variant buttons) and form-based (Tools menu) with `obm_export_nonce` nonce validation

## Data Model & Metadata

### Product SKU Strategy
- **SKU = ISBN**: Used as product identifier for ONIX export
- SKU-locking JS injected to prevent accidental changes
- Export falls back to `_isbn_value` post meta if SKU unavailable

### Book Metadata (Post Meta Keys)
Standard prefixes: `_isbn_value`, `_format_attribute`, `_language_attribute`, etc.
- Attributes stored as WooCommerce product attributes (filterable via `pa_*` taxonomy)
- Format/Language stored in `WC_Product_Book` variable attributes

### Thema Classification
- Source: `assets/thema_da.json` (Danish THEMA vocabulary)
- Structure: JSON array with `CodeValue` and `CodeDescription` keys
- Quirk: Single JSON entry returns object; multiple return array (handled in `load()`)
- Cached via WordPress transients with file modification time check

## Development Workflows

### Testing ONIX Export
1. Create/edit book product → Set ISBN (SKU), Format, Language required
2. Admin menu: Tools > ONIX Export → Select product → Generate XML
3. OR: Single-product export via admin row action or variant button
4. Nonce validation required: `obm_export_nonce` ← ensure included in forms

### Code Standards
- WordPress & WooCommerce coding standards enforced via phpcs/WPCS
- Vendor: `dealerdirect/phpcodesniffer-composer-installer`, `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`
- Check locally: `./vendor/bin/phpcs` (or use phpcompatibility sniffs for PHP 8.0 compat)
- Code should follow the Single Responsibility Principle
- Frontend code should not be loaded on backend and backend code should not be loaded on frontend.
- Additionally the best practice guidelines should be followed: https://developer.woocommerce.com/docs/extensions/best-practices-extensions/extension-development-best-practices/

### Internationalization (i18n)
- Textdomain: `onix-book-manager`
- Translation files: `/languages/*.po`, `*.mo`, `*.l10n.php`
- Use `__()` and `_e()` for all user-facing strings

## Common Customization Points

### Adding New Book Metadata Fields
1. Register attribute in `OBM_Product::setup_book_attributes()` (admin UI)
2. Add form field in `render_metadata_panel()` view
3. Save via `woocommerce_process_product_meta` hook in `save_book_meta()`
4. Display frontend via `display_book_metadata_frontend()` filter

### Extending ONIX Export
- Main generation: `OBM_Export::generate()` — generates XML, headers, sanitizes filename
- Extends `WC_Product_Book` class if additional product properties needed
- ONIX file naming: `ONIX-{isbn}_{date}.xml` (e.g., `ONIX-9788723456789_20260211.xml`)

### Roadmap & Future Enhancements

#### High priority enhancements for next release (v1.1.0):
1. Support for simple book products (non-variable)
2. Remove custom ISBN field and rely on built-in GTIN-field. Make SKU inherit from GTIN. Rename GTIN to ISBN-13.

#### Medium-term enhancements (v1.2.0+):
- Support for additional ONIX fields (e.g., product measurements, multiple contributors)
- Admin export validation (required fields, XML schema validation)
- Support for multiple languages in THEMA classification
- Improved export performance for large catalogs (batch processing, WP CLI command)
- UI/UX improvements in admin (bulk export, export history/log)
- Integration with external ONIX validation services or APIs
- Add support for product measurement fields in ONIX export.
- Add validation in ONIX admin to ensure all fields relevant to the exporter are filled out, and refuse to export if fields are missing.
- Implement XML schema validation for the generated ONIX files to ensure compliance with ONIX 3.1 standards.
- Add support for exporting product variants (e.g., different formats) as separate ONIX records with appropriate relationships.
- Document the missing ONIX features and how users can implement them manually if needed, while we work on adding them to the plugin.

#### Long-term vision (v2.0+):
- Full ONIX 3.1 compliance with all relevant fields and relationships
- Support for other product types (e.g., audiobooks, eBooks) with appropriate metadata

## Critical Files Reference
- **Main plugin**: [../onix-book-manager.php](../onix-book-manager.php)
- **Product type**: [../includes/class-obm-product.php](../includes/class-obm-product.php)
- **Product admin**: [../includes/class-obm-product-admin.php](../includes/class-obm-product-admin.php)
- **Product frontend**: [../includes/class-obm-product-frontend.php](../includes/class-obm-product-frontend.php)
- **Admin page**: [../includes/class-obm-admin.php](../includes/class-obm-admin.php)
- **Export engine**: [../includes/class-obm-export.php](../includes/class-obm-export.php)
- **THEMA vocab**: [../assets/thema_da.json](../assets/thema_da.json)
- **Admin template**: [../includes/views/admin-export-view.php](../includes/views/admin-export-view.php)

## Important Notes
- **Standards**: the plugin should rely as much as possible on the WooCommerce way of doing things and the features that are built into primarily WooCommerce core and secondarily Wordpress Core
- **No namespaces**: Classes prefixed with `OBM_` for WordPress compatibility
- **WooCommerce dependency**: Plugin deactivates gracefully if WC not active
- **Security**: All export endpoints check `current_user_can('edit_products')` and validate nonces
