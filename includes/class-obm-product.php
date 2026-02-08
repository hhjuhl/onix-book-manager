<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Book' ) ) {
	class WC_Product_Book extends WC_Product_Variable {
		public function get_type() {
			return 'book'; }
	}
}

class OBM_Product {

	public static function init() {
		// Registration
		add_filter( 'product_type_selector', array( __CLASS__, 'add_book_type' ) );
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'set_book_class' ), 10, 2 );
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_store' ) );
		add_action( 'init', array( __CLASS__, 'register_book_taxonomy_term' ) );

		// Admin UI
		add_action( 'admin_init', array( __CLASS__, 'setup_book_attributes' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'add_sidebar_button' ) );
		add_action( 'admin_footer', array( __CLASS__, 'lock_sku_js' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_metadata_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_metadata_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_book_meta' ) );
		add_action( 'woocommerce_variation_options_dimensions', array( __CLASS__, 'add_variant_export_button' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'display_export_notices' ) );

		// Frontend & Script Injections
		add_action( 'admin_footer', array( __CLASS__, 'inject_admin_js' ) );
		add_action( 'wp_head', array( __CLASS__, 'inject_frontend_styles' ) );
		add_action( 'wp_footer', array( __CLASS__, 'inject_frontend_js' ) );

		// Frontend Logic
		add_filter( 'woocommerce_is_variable_product', array( __CLASS__, 'mark_as_variable' ), 10, 2 );
		add_action( 'woocommerce_book_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
		add_filter( 'woocommerce_display_product_attributes', array( __CLASS__, 'display_book_metadata_frontend' ), 10, 2 );
	}

	public static function inject_frontend_styles() {
		if ( is_product() ) {
			echo '<style>.product_meta .sku_wrapper { display: none !important; }</style>';
		}
	}

	public static function inject_frontend_js() {
		if ( ! is_product() ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(function($){
				$('form.variations_form').on('show_variation', function(event, variation) {
					if (variation.sku) {
						$('.woocommerce-product-attributes-item--obm_isbn .woocommerce-product-attributes-item__value').text(variation.sku);
					}
				});
				$('form.variations_form').on('reset_data', function() {
					var parentIsbn = "<?php echo esc_js( get_post_meta( get_the_ID(), '_isbn_value', true ) ); ?>";
					$('.woocommerce-product-attributes-item--obm_isbn .woocommerce-product-attributes-item__value').text(parentIsbn);
				});
			});
		</script>
		<?php
	}

	public static function display_book_metadata_frontend( $product_attributes, $product ) {
		$parent_id      = $product->get_parent_id() ?: $product->get_id();
		$parent_product = wc_get_product( $parent_id );

		if ( $parent_product && $parent_product->is_type( 'book' ) ) {
			$isbn_value = $product->get_sku() ?: get_post_meta( $parent_id, '_isbn_value', true );
			$publisher  = get_post_meta( $parent_id, '_book_publisher_name', true ) ?: get_bloginfo( 'name' );

			$raw_date          = get_post_meta( $parent_id, '_book_publication_date', true );
			$formatted_pubdate = ! empty( $raw_date ) ? date_i18n( get_option( 'date_format' ), strtotime( $raw_date ) ) : '';

			// --- JUST-IN-TIME LOADING ---
			$themacode    = get_post_meta( $parent_id, '_book_thema_code', true );
			$themasubject = $themacode; // Fallback to code if class fails

			if ( ! empty( $themacode ) ) {
				// Load the helper class file only here
				$class_path = OBM_PATH . 'includes/class-obm-thema.php';
				if ( file_exists( $class_path ) ) {
					require_once $class_path;
					if ( class_exists( 'OBM_Thema' ) ) {
						$themasubject = OBM_Thema::subject( $themacode );
					}
				}
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
					'label' => __( 'Page Count', 'onix-book-manager' ),
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
		}
		return $product_attributes;
	}

	public static function inject_admin_js() {
		if ( 'product' != get_post_type() ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(function($){
				$('.show_if_variable').addClass('show_if_book');
				$('.enable_variation').addClass('show_if_book');
				$('select#product-type').trigger('change');
			});
		</script>
		<?php
	}

	public static function add_variant_export_button( $loop, $variation_data, $variation ) {
		// Correctly generate the export link with nonce
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

	public static function display_export_notices() {
		if ( 'no_isbn' === isset( $_GET['obm_error'] ) && $_GET['obm_error'] ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Export failed: Missing ISBN/SKU.', 'onix-book-manager' ) . '</p></div>';
		}
	}

	public static function mark_as_variable( $is_variable, $product ) {
		return ( is_a( $product, 'WC_Product' ) && $product->get_type() === 'book' ) ? true : $is_variable;
	}

	public static function setup_book_attributes() {
		if ( ! function_exists( 'wc_create_attribute' ) ) {
			return;
		}
		$formatname    = 'Format';
		$formatslug    = sanitize_title( $formatname );
		$forfattername = 'Forfatter';
		$forfatterslug = sanitize_title( $forfattername );
		if ( ! taxonomy_exists( 'pa_' . $formatslug ) ) {
			wc_create_attribute(
				array(
					'name'         => $formatname,
					'slug'         => $formatslug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
			register_taxonomy( 'pa_' . $formatslug, array( 'product' ) );
		}
		if ( ! taxonomy_exists( 'pa_' . $forfatterslug ) ) {
			wc_create_attribute(
				array(
					'name'         => $forfattername,
					'slug'         => $forfatterslug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => true,
				)
			);
			register_taxonomy( 'pa_' . $forfatterslug, array( 'product' ) );
		}
		$terms = array( __( 'Hardback', 'onix-book-manager' ), __( 'Paperback', 'onix-book-manager' ), __( 'E-book', 'onix-book-manager' ) );
		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, 'pa_' . $formatslug ) ) {
				wp_insert_term( $term, 'pa_' . $formatslug );
			}
		}
	}

	public static function add_book_type( $types ) {
		$types['book'] = __( 'Book (Multi-Format)', 'onix-book-manager' );
		return $types; }
	public static function set_book_class( $classname, $product_type ) {
		return ( $product_type === 'book' ) ? 'WC_Product_Book' : $classname; }
	public static function register_data_store( $stores ) {
		$stores['product-book'] = 'WC_Product_Variable_Data_Store_CPT';
		return $stores; }
	public static function register_book_taxonomy_term() {
		if ( ! term_exists( 'book', 'product_type' ) ) {
			wp_insert_term( 'book', 'product_type' );
		} }

	public static function add_sidebar_button() {
		global $post;
		if ( $post && $post->post_type === 'product' ) {
			$product = wc_get_product( $post->ID );
			if ( $product && $product->is_type( 'book' ) ) {
				// Corrected Link for the Parent Product Export
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

	public static function lock_sku_js() {
		if ( get_post_type() !== 'product' ) {
			return;
		}
		?>
	<script>
		jQuery(document).ready(function($) {
			const $isbnField = $('#_isbn_value');
			
			// Function to sync ISBN to SKU and Global Unique ID
			const syncISBN = function() {
				const val = $isbnField.val();
				// Target both the main SKU field and any variation SKU fields
				const $targets = $('input[name^="_sku"], input[name^="_global_unique_id"], #_sku, #_global_unique_id');
				
				$targets.val(val);
			};

			const lockAndLabel = () => {
				const $f = $('input[name^="_sku"], input[name^="_global_unique_id"], #_sku, #_global_unique_id');
				$f.prop('readonly', true).css({'background-color': '#eee', 'cursor': 'not-allowed'});
				
				$f.each(function() {
					if (!$(this).next('.obm-note').length) {
						$(this).after('<small class="obm-note" style="display:block;color:#777;font-style:italic;"><?php esc_html_e( 'Inherited from book metadata tab', 'onix-book-manager' ); ?></small>');
					}
				});
			};

			// Run sync whenever the user types in the ISBN field
			$isbnField.on('input change', syncISBN);

			// Periodically check/lock fields (especially after AJAX variation loads)
			setInterval(function() {
				lockAndLabel();
	
				// Check if the source ISBN has a value
				const isbnVal = $isbnField.val();
				if (isbnVal) {
					const $sku = $('#_sku');
					const $guid = $('#_global_unique_id');

					// If either the SKU or Global Unique ID is empty, force a sync
					if ($sku.val() === '' || $guid.val() === '') {
						syncISBN();
					}
				}
			}, 1000);

			// Initial run
			setTimeout(function() {
				syncISBN();
				lockAndLabel();
			}, 500);
		});
	</script>
		<?php
	}

	public static function add_metadata_tab( $tabs ) {
		$tabs['obm_metadata'] = array(
			'label'    => __( 'Book Metadata', 'onix-book-manager' ),
			'target'   => 'obm_metadata_panel',
			'class'    => array( 'show_if_book' ),
			'priority' => 11,
		);
		return $tabs;
	}

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

				// Added a helper link for Thema code lookup
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

	public static function save_book_meta( $post_id ) {
		$fields = array( '_isbn_value', '_book_publisher_name', '_book_thema_code', '_book_publication_date', '_book_page_count' );
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
OBM_Product::init();
