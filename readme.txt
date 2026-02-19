=== ONIX Book Manager for WooCommerce ===
Contributors: hhjuhl
Tags: woocommerce, books, onix, isbn
Requires at least: 6.8
Tested up to: 6.9
Stable tag: 0.1.1
Requires PHP: 8.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrate book-specific data into WooCommerce using a dedicated 'Book' product type, with support for ONIX metadata and XML export.

== Description ==

ONIX Book Manager enhances WooCommerce for booksellers by introducing a new **Book (Multi-Format)** product type. This powerful feature allows you to manage books and their various formats (like Hardback, Paperback, E-book) as variations of a single product.

The plugin adds a dedicated "Book Metadata" tab to the product editor, where you can easily input essential bibliographic information based on the ONIX standard, including:

*   ISBN-13
*   Publisher
*   Thema Code
*   Publication Date
*   Number of Pages

It also provides a simple one-click tool to **export product data as an ONIX 3.0 XML file**, making it easy to share your book data with distributors and partners.

**Key Features:**

*   **New 'Book' Product Type:** Manages different book formats as product variations.
*   **ONIX Metadata Fields:** Easily add and manage book-specific data.
*   **Automatic Attribute Creation:** Sets up 'Format' and 'Author' attributes for you.
*   **Frontend Display:** Shows book metadata cleanly on the product page.
*   **ONIX XML Export:** Generate ONIX 3.0 XML for individual products or variations.
*   **ISBN-SKU Sync:** Automatically uses the book's main ISBN as the parent product's SKU.

== Installation ==

1.  Upload the `onix-book-manager` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Products > Add New** and select **Book (Multi-Format)** from the 'Product data' dropdown to start adding your books.

== Frequently Asked Questions ==

= How do I set the Author and Format for a book? =

The plugin automatically creates 'Author' (Forfatter) and 'Format' product attributes. You can set these for your book in the **Product Data > Attributes** tab. Use the 'Format' attribute to create variations like 'Hardback' and 'Paperback'.

= Where do I find the ONIX export button? =

For the main book product, the "Export as ONIX" button is located in the "Publish" meta box on the product edit screen. For individual variations (e.g., the Paperback version), the export button can be found within that variation's settings in the "Variations" tab.

= What is a Thema code? =

Thema is an international subject category scheme for books. You can find the appropriate code for your book by visiting the official Thema browser. The plugin provides a link to this browser directly from the "Book Metadata" tab.

== Screenshots ==

1.  The "Book (Multi-Format)" option in the product type dropdown.
2.  The "Book Metadata" tab for entering ONIX data.
3.  Setting the 'Format' and 'Author' in the Attributes tab.
4.  The frontend display of book metadata in the "Additional Information" tab.
5.  The "Export as ONIX" button in the sidebar.

== Changelog ==

= 0.1.1 =
*   Fix: Synchronize readme.txt header with main plugin file.
*   Fix: Load all required class files correctly.
*   Refactor: Split large OBM_Product class into OBM_Product_Type, OBM_Product_Admin, and OBM_Product_Frontend.
*   Feature: Add comprehensive unit tests for new classes.

= 0.1.0 =
*   Initial release.

== Upgrade Notice ==

= 0.1.1 =
This version includes major code refactoring for improved stability and maintainability. Please test after updating.
