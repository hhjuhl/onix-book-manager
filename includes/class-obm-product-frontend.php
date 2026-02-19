<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OBM_Product_Frontend
 *
 * Handles the frontend display and behavior of the 'book' product type.
 */
class OBM_Product_Frontend {

	/**
	 * Initialize frontend hooks for book product type.
	 *
	 * @return void
	 */
	public static function init() {
		// Frontend & Script Injections
		add_action( 'wp_head', array( __CLASS__, 'inject_frontend_styles' ) );
		add_action( 'wp_footer', array( __CLASS__, 'inject_frontend_js' ) );

		// Frontend Logic
		add_filter( 'woocommerce_is_variable_product', array( __CLASS__, 'mark_as_variable' ), 10, 2 );
		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'display_book_metadata_frontend' ), 10, 2 );
	}

	/**
	 * Inject frontend styles to hide SKU wrapper.
	 *
	 * @return void
	 */
	public static function inject_frontend_styles() {
		if ( is_product() ) {
			echo '<style>.product_meta .sku_wrapper { display: none !important; }</style>';
		}
	}

	/**
	 * Inject JavaScript to update ISBN when variation changes.
	 *
	 * @return void
	 */
	public static function inject_frontend_js() {
		if ( ! is_product() ) {
			return;
		}
		$parent_isbn = get_post_meta( get_the_ID(), '_isbn_value', true );
		?>
		<script type="text/javascript">
			jQuery(function($){
				const originalIsbn = "<?php echo esc_js( $parent_isbn ); ?>";
				const isbnValueSelector = '.woocommerce-product-attributes-item--obm_isbn .woocommerce-product-attributes-item__value';

				$('form.variations_form').on('show_variation', function(event, variation) {
					if (variation.sku) {
						$(isbnValueSelector).text(variation.sku);
					}
				});
				$('form.variations_form').on('reset_data', function() {
					$(isbnValueSelector).text(originalIsbn);
				});
			});
		</script>
		<?php
	}

	/**
	 * Display book-specific metadata on product page.
	 *
	 * @param array      $product_attributes Product attributes for display.
	 * @param WC_Product $product The product object.
	 * @return array Updated product attributes.
	 */
	public static function display_book_metadata_frontend( $product_attributes, $product ) {
		$parent_id      = $product->get_parent_id() ?: $product->get_id();
		$parent_product = wc_get_product( $parent_id );

		if ( ! $parent_product || ! $parent_product->is_type( 'book' ) ) {
			return $product_attributes;
		}

		$isbn_value = $product->get_sku() ?: get_post_meta( $parent_id, '_isbn_value', true );
		$publisher  = get_post_meta( $parent_id, '_book_publisher_name', true ) ?: get_bloginfo( 'name' );

		$raw_date          = get_post_meta( $parent_id, '_book_publication_date', true );
		$formatted_pubdate = ! empty( $raw_date ) ? date_i18n( get_option( 'date_format' ), strtotime( $raw_date ) ) : '';

		$themacode    = get_post_meta( $parent_id, '_book_thema_code', true );
		$themasubject = '';
		if ( ! empty( $themacode ) ) {
			$themasubject = OBM_Thema::subject( $themacode ) ?: $themacode;
		}

		$meta_to_display = array(
			'isbn'                   => array(
				'label' => __( 'ISBN', 'onix-book-manager' ),
				'value' => $isbn_value,
			),
			'_book_publisher_name'   => array(
				'label' => __( 'Publisher', 'onix-book-manager' ),
				'value' => $publisher,
			),
			'_book_publication_date' => array(
				'label' => __( 'Publication Date', 'onix-book-manager' ),
				'value' => $formatted_pubdate,
			),
			'_book_page_count'       => array(
				'label' => __( 'Number of Pages', 'onix-book-manager' ),
				'value' => get_post_meta( $parent_id, '_book_page_count', true ),
			),
			'_book_thema_code'       => array(
				'label' => __( 'Thema Category', 'onix-book-manager' ),
				'value' => $themasubject,
			),
		);

		foreach ( $meta_to_display as $key => $data ) {
			if ( ! empty( $data['value'] ) ) {
				$product_attributes[ 'obm_' . $key ] = array(
					'label' => $data['label'],
					'value' => esc_html( $data['value'] ),
				);
			}
		}
		return $product_attributes;
	}

	/**
	 * Mark book products as variable to show variation dropdowns.
	 *
	 * @param bool       $is_variable Whether product is variable.
	 * @param WC_Product $product The product object.
	 * @return bool True for book products, otherwise $is_variable.
	 */
	public static function mark_as_variable( $is_variable, $product ) {
		if ( is_a( $product, 'WC_Product' ) && 'book' === $product->get_type() ) {
			// Only mark as variable when the book product actually has variations.
			$children = method_exists( $product, 'get_children' ) ? $product->get_children() : array();
			return ! empty( $children );
		}
		return $is_variable;
	}
}
OBM_Product_Frontend::init();