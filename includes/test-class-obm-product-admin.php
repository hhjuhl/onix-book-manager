<?php
/**
 * Class OBM_Product_Admin_Test
 *
 * @package Onix_Book_Manager
 */

class OBM_Product_Admin_Test extends WP_UnitTestCase {

	/**
	 * Test that the 'Format' and 'Forfatter' attributes are created.
	 */
	public function test_setup_book_attributes() {
		// Ensure WooCommerce functions are available
		if ( ! function_exists( 'wc_create_attribute' ) ) {
			$this->markTestSkipped( 'WooCommerce functions not available.' );
		}

		OBM_Product_Admin::setup_book_attributes();

		$this->assertNotEquals( 0, taxonomy_exists( 'pa_format' ) );
		$this->assertNotEquals( 0, taxonomy_exists( 'pa_forfatter' ) );

		// Check if a term was created for 'Format'
		$term = term_exists( 'Hardback', 'pa_format' );
		$this->assertIsArray( $term );
	}

	/**
	 * Test saving book metadata.
	 */
	public function test_save_book_meta() {
		// Create a dummy post
		$post_id = $this->factory->post->create( array( 'post_type' => 'product' ) );

		// Simulate the $_POST data
		$_POST['_isbn_value']      = '978-1-23-456789-0';
		$_POST['_book_page_count'] = '300';

		OBM_Product_Admin::save_book_meta( $post_id );

		// Verify the data was saved correctly
		$this->assertEquals( '978-1-23-456789-0', get_post_meta( $post_id, '_isbn_value', true ) );
		$this->assertEquals( '300', get_post_meta( $post_id, '_book_page_count', true ) );

		// Clean up global
		unset( $_POST['_isbn_value'], $_POST['_book_page_count'] );
	}
}
