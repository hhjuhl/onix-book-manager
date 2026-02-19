<?php
/**
 * Class OBM_Product_Type_Test
 *
 * @package Onix_Book_Manager
 */

class OBM_Product_Type_Test extends WP_UnitTestCase {

	/**
	 * Test if the 'book' product type is added correctly.
	 */
	public function test_add_book_type() {
		$types     = array( 'simple' => 'Simple' );
		$new_types = OBM_Product_Type::add_book_type( $types );

		$this->assertArrayHasKey( 'book', $new_types );
		$this->assertEquals( 'Book (Multi-Format)', $new_types['book'] );
	}

	/**
	 * Test if the correct class is set for the 'book' product type.
	 */
	public function test_set_book_class() {
		// Test for 'book' type
		$classname = OBM_Product_Type::set_book_class( 'WC_Product_Simple', 'book' );
		$this->assertEquals( 'WC_Product_Book', $classname );

		// Test for another type
		$classname = OBM_Product_Type::set_book_class( 'WC_Product_Simple', 'simple' );
		$this->assertEquals( 'WC_Product_Simple', $classname );
	}

	/**
	 * Test if the correct data store is registered.
	 */
	public function test_register_data_store() {
		$stores     = array( 'product-simple' => 'WC_Product_Data_Store_CPT' );
		$new_stores = OBM_Product_Type::register_data_store( $stores );

		$this->assertArrayHasKey( 'product-book', $new_stores );
		$this->assertEquals( 'WC_Product_Variable_Data_Store_CPT', $new_stores['product-book'] );
	}

	/**
	 * Test if the 'book' product type term is created.
	 */
	public function test_register_book_taxonomy_term() {
		OBM_Product_Type::register_book_taxonomy_term();
		$term = term_exists( 'book', 'product_type' );
		$this->assertNotFalse( $term );
		$this->assertIsArray( $term );
	}
}
