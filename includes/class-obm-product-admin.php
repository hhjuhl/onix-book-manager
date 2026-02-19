<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OBM_Product_Admin
 *
 * Handles all admin-facing UI and functionality for the 'book' product type.
 */
class OBM_Product_Admin {

	/**
	 * Initialize admin-facing hooks for book product type.
	 *
	 * @return void
	 */
	public static function init() {
		// Admin UI
		add_action( 'admin_init', array( __CLASS__, 'setup_book_attributes' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'add_sidebar_button' ) );
		add_action( 'admin_footer', array( __CLASS__, 'lock_sku_js' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_metadata_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_metadata_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_book_meta' ) );
		add_action( 'woocommerce_variation_options_dimensions', array( __CLASS__, 'add_variant_export_button' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'display_export_notices' ) );
		add_action( 'admin_footer', array( __CLASS__, 'inject_admin_js' ) );
	}

	/**
	 * Inject admin JavaScript to show book product fields.
	 *
	 * @return void
	 */
	public static function inject_admin_js() {
		if ( 'product' !== get_post_type() ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(function($){
				// Ensure book type shows fields normally shown for both simple and variable products.
				$('.show_if_variable, .show_if_simple').addClass('show_if_book');
				$('.enable_variation').addClass('show_if_book');
				$('select#product-type').trigger('change');
			});
		</script>
		<?php
	}

	/**
	 * Add ONIX export button to variation edit form.
	 *
	 * @param int   $loop The variation loop index.
	 * @param array $variation_data Variation data.
	 * @param mixed $variation The variation object.
	 * @return void
	 */
	public static function add_variant_export_button( $loop, $variation_data, $variation ) {
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'obm_generate_xml' => 1,
					'product_id'       => $variation->ID,
				),
				admin_url( 'post.php' )
			),
			'obm_export_action',
			'obm_export_nonce'
		);
		echo '<div class="form-row form-row-full obm-variant-export" style="padding:12px; background:#f9f9f9; border:1px solid #ccd0d4; border-radius:4px; margin-top:10px;">';
		echo '<strong>' . esc_html__( 'ONIX XML Export for this variant', 'onix-book-manager' ) . '</strong><br>';
		echo '<a href="' . esc_url( $url ) . '" class="button button-secondary" style="margin-top:5px;">' . esc_html__( 'Download ONIX XML', 'onix-book-manager' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Display export error notices in admin.
	 *
	 * @return void
	 */
	public static function display_export_notices() {
		if ( isset( $_GET['obm_error'] ) && 'no_isbn' === $_GET['obm_error'] ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Export failed: Missing ISBN/SKU.', 'onix-book-manager' ) . '</p></div>';
		}
	}

	/**
	 * Create and register Format and Forfatter attributes.
	 *
	 * @return void
	 */
	public static function setup_book_attributes() {
		if ( ! function_exists( 'wc_create_attribute' ) ) {
			return;
		}
		$attributes = array(
			'Format'    => array(
				'slug'         => 'format',
				'has_archives' => false,
				'terms'        => array( __( 'Hardback', 'onix-book-manager' ), __( 'Paperback', 'onix-book-manager' ), __( 'E-book', 'onix-book-manager' ) ),
			),
			'Forfatter' => array(
				'slug'         => 'forfatter',
				'has_archives' => true,
				'terms'        => array(),
			),
		);

		foreach ( $attributes as $name => $data ) {
			if ( ! taxonomy_exists( 'pa_' . $data['slug'] ) ) {
				wc_create_attribute(
					array(
						'name'         => $name,
						'slug'         => $data['slug'],
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => $data['has_archives'],
					)
				);
				register_taxonomy( 'pa_' . $data['slug'], array( 'product' ) );
			}
			foreach ( $data['terms'] as $term ) {
				if ( ! term_exists( $term, 'pa_' . $data['slug'] ) ) {
					wp_insert_term( $term, 'pa_' . $data['slug'] );
				}
			}
		}
	}

	/**
	 * Add ONIX export button to product edit sidebar.
	 *
	 * @return void
	 */
	public static function add_sidebar_button() {
		global $post;
		if ( $post && 'product' === $post->post_type ) {
			$product = wc_get_product( $post->ID );
			if ( $product && $product->is_type( 'book' ) ) {
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'obm_generate_xml' => 1,
							'product_id'       => $post->ID,
						),
						admin_url( 'post.php' )
					),
					'obm_export_action',
					'obm_export_nonce'
				);

				echo '<div class="misc-pub-section" style="border-top:1px solid #eee;">';
				echo '<a href="' . esc_url( $url ) . '" class="button"><span class="dashicons dashicons-external" style="margin-top:4px; font-size:16px;"></span> ' . esc_html__( 'Export as ONIX', 'onix-book-manager' ) . '</a>';
				echo '</div>';
			}
		}
	}

	/**
	 * Inject JavaScript to lock SKU field and sync with ISBN.
	 *
	 * @return void
	 */
	public static function lock_sku_js() {
		if ( 'product' !== get_post_type() ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready(function($) {
				// Only run this for the 'book' product type.
				if ($('select#product-type').val() !== 'book') {
					return;
				}

				const $isbnField = $('#_isbn_value');
				const syncISBN = () => $isbnField.val() && $('#_sku').val($isbnField.val());
				const lockSkuField = () => {
					const $sku = $('#_sku');
					if (!$sku.prop('readonly')) {
						$sku.prop('readonly', true).css({'background-color': '#eee', 'cursor': 'not-allowed'});
						$sku.after('<small class="obm-note" style="display:block;color:#777;font-style:italic;"><?php esc_html_e( 'Inherited from Book Metadata tab', 'onix-book-manager' ); ?></small>');
					}
				};

				$isbnField.on('input change', syncISBN);

				// Initial run and sync for 'book' type.
				syncISBN();
				lockSkuField();

				// When variations are loaded, lock their SKU fields if they are empty.
				$('#woocommerce-product-data').on('woocommerce_variations_loaded', function() {
					$('input.variable_sku').each(function() {
						if ($(this).val() === '') {
							$(this).prop('readonly', true).css({'background-color': '#eee', 'cursor': 'not-allowed'});
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Add "Book Metadata" tab to product data tabs.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array Updated tabs.
	 */
	public static function add_metadata_tab( $tabs ) {
		$tabs['obm_metadata'] = array(
			'label'    => __( 'Book Metadata', 'onix-book-manager' ),
			'target'   => 'obm_metadata_panel',
			'class'    => array( 'show_if_book' ),
			'priority' => 11,
		);
		return $tabs;
	}

	/**
	 * Render the Book Metadata panel content.
	 *
	 * @return void
	 */
	public static function render_metadata_panel() {
		?>
		<div id="obm_metadata_panel" class="panel woocommerce_options_panel show_if_book">
			<div class="options_group">
				<h3 style="padding-left:12px;"><strong><?php esc_html_e( 'ONIX Bibliographic Data', 'onix-book-manager' ); ?></strong></h3>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'    => '_isbn_value',
						'label' => __( 'ISBN-13', 'onix-book-manager' ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'          => '_book_publisher_name',
						'label'       => __( 'Publisher', 'onix-book-manager' ),
						'placeholder' => get_bloginfo( 'name' ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'          => '_book_thema_code',
						'label'       => __( 'Thema Code', 'onix-book-manager' ),
						'description' => '<a href="https://ns.editeur.org/thema" target="_blank">' . __( 'Browse Thema Categories', 'onix-book-manager' ) . '</a>',
						'desc_tip'    => false,
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'    => '_book_publication_date',
						'label' => __( 'Publication Date', 'onix-book-manager' ),
						'type'  => 'date',
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'    => '_book_page_count',
						'label' => __( 'Number of Pages', 'onix-book-manager' ),
						'type'  => 'number',
					)
				);
				?>
				<p class="help" style="padding-left:12px;"><strong><?php esc_html_e( 'Note: »Author« and »Format« is set using the Attributes panel', 'onix-book-manager' ); ?></strong></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save book metadata from product editor.
	 *
	 * @param int $post_id The product post ID.
	 * @return void
	 */
	public static function save_book_meta( $post_id ) {
		$fields = array( '_isbn_value', '_book_publisher_name', '_book_thema_code', '_book_publication_date', '_book_page_count' );
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
OBM_Product_Admin::init();