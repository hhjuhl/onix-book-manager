<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Book' ) ) {
	class WC_Product_Book extends WC_Product {
		/**
		 * Get product type.
		 *
		 * @return string Product type 'book'.
		 */
		public function get_type() {
			return 'book';
		}
	}
}

/**
 * Class OBM_Product_Type
 *
 * Handles the registration of the 'book' product type with WooCommerce.
 */
class OBM_Product_Type {

	/**
	 * Initialize product type registration and hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'product_type_selector', array( __CLASS__, 'add_book_type' ) );
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'set_book_class' ), 10, 2 );
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_store' ) );
		add_action( 'init', array( __CLASS__, 'register_book_taxonomy_term' ) );

		// The 'book' product type may be variable or simple. Use wrapper to decide.
		add_action( 'woocommerce_book_add_to_cart', array( __CLASS__, 'book_add_to_cart' ), 30 );
	}

	/**
	 * Render the correct add-to-cart template for a book product.
	 * Chooses variable form when the book has variations, otherwise simple form.
	 *
	 * @return void
	 */
	public static function book_add_to_cart() {
		global $product;

		if ( ! $product ) {
			return;
		}

		// If the product has variations (children), render variable add-to-cart.
		$children = method_exists( $product, 'get_children' ) ? $product->get_children() : array();
		if ( ! empty( $children ) ) {
			if ( function_exists( 'woocommerce_variable_add_to_cart' ) ) {
				woocommerce_variable_add_to_cart();
			}
			return;
		}

		// Fallback to simple add-to-cart rendering.
		if ( function_exists( 'woocommerce_simple_add_to_cart' ) ) {
			woocommerce_simple_add_to_cart();
		}
	}

	/**
	 * Add 'book' to product type selector.
	 *
	 * @param array $types Available product types.
	 * @return array Updated product types.
	 */
	public static function add_book_type( $types ) {
		$types['book'] = __( 'Book (Multi-Format)', 'onix-book-manager' );
		return $types;
	}

	/**
	 * Set WC_Product_Book class for book product type.
	 *
	 * @param string $classname The product class name.
	 * @param string $product_type The product type.
	 * @return string Product class name.
	 */
	public static function set_book_class( $classname, $product_type ) {
		return ( 'book' === $product_type ) ? 'WC_Product_Book' : $classname;
	}

	/**
	 * Register custom data store for book products.
	 *
	 * @param array $stores Available data stores.
	 * @return array Updated data stores.
	 */
	public static function register_data_store( $stores ) {
		$stores['product-book'] = 'WC_Product_Variable_Data_Store_CPT';
		return $stores;
	}

	/**
	 * Register 'book' as a product type term.
	 *
	 * @return void
	 */
	public static function register_book_taxonomy_term() {
		if ( ! term_exists( 'book', 'product_type' ) ) {
			wp_insert_term( 'book', 'product_type' );
		} }
}
OBM_Product_Type::init();
