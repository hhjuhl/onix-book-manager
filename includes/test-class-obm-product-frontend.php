<?php
/**
 * Class OBM_Product_Frontend_Test
 *
 * @package Onix_Book_Manager
 */

class OBM_Product_Frontend_Test extends WP_UnitTestCase {

	protected $book_product;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		// We need the main product type class for this to work
		require_once OBM_PATH . 'includes/class-obm-product.php';

		// Create a dummy book product for testing
		$this->book_product = new WC_Product_Book();
		$this->book_product->set_name( 'Test Book' );
		$this->book_product->set_regular_price( '10' );
		$this->book_product->save();

		update_post_meta( $this->book_product->get_id(), '_isbn_value', '978-3-16-148410-0' );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_delete_post( $this->book_product->get_id(), true );
		parent::tearDown();
	}

	/**
	 * Test that 'book' products are treated as variable products on the frontend.
	 */
	public function test_mark_as_variable() {
		$is_variable = OBM_Product_Frontend::mark_as_variable( false, $this->book_product );
		$this->assertTrue( $is_variable );

		// Test with a non-book product
		$simple_product = new WC_Product_Simple();
		$is_variable    = OBM_Product_Frontend::mark_as_variable( false, $simple_product );
		$this->assertFalse( $is_variable );
	}

	/**
	 * Test that book metadata is added to the product attributes display.
	 */
	public function test_display_book_metadata_frontend() {
		$attributes = OBM_Product_Frontend::display_book_metadata_frontend( array(), $this->book_product );

		$this->assertArrayHasKey( 'obm_isbn', $attributes );
		$this->assertEquals( 'ISBN', $attributes['obm_isbn']['label'] );
		$this->assertEquals( '978-3-16-148410-0', $attributes['obm_isbn']['value'] );
	}

	/**
	 * Test the inline JS injection.
	 */
	public function test_inject_frontend_js() {
		// This test just ensures the function runs without fatal errors.
		// More complex JS testing would require a different tool (e.g., Jest).
		ob_start();
		OBM_Product_Frontend::inject_frontend_js();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'show_variation', $output );
	}
}
