<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OBM_Admin {

    /**
     * Initialize admin page and hooks.
     *
     * @return void
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'post_row_actions', array( __CLASS__, 'add_row_action' ), 10, 2 );
    }

    /**
     * Add ONIX Export submenu to Products menu.
     *
     * @return void
     */
    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=product',
            __( 'ONIX Export', 'onix-book-manager' ),
            __( 'ONIX Export', 'onix-book-manager' ),
            'manage_options',
            'onix-export',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Enqueue admin assets on export page.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public static function enqueue_assets( $hook ) {

        // Correct hook names for submenu under Products
        if ( $hook !== 'product_page_onix-export' ) {
            return;
        }

        wp_enqueue_style( 'obm-admin-css', OBM_URL . 'assets/css/obm-admin.css', array(), OBM_VERSION );
    }

    /**
     * Render the ONIX Export admin page.
     *
     * @return void
     */
    public static function render_page() {

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'download_onix' ) {
            OBM_Export::generate();
        }

        include OBM_PATH . 'includes/views/admin-export-view.php';
    }

    /**
     * Validate book product has required ONIX data.
     *
     * @param WC_Product $product The product to validate.
     * @return array List of validation errors, empty if valid.
     */
    public static function validate_book( $product ) {
        $errors = array();

        if ( ! $product->get_sku() ) {
            $errors[] = __( 'Missing ISBN.', 'onix-book-manager' );
        }

        $format = $product->get_attribute( 'Format' );
        if ( empty( $format ) ) {
            $errors[] = __( 'Missing Format attribute.', 'onix-book-manager' );
        }

        $contributor = $product->get_attribute( 'forfatter' );
        if ( empty( $contributor ) ) {
            $errors[] = __( 'Missing Author attribute.', 'onix-book-manager' );
        }

        return $errors;
    }

    /**
     * Add ONIX export action to product row actions.
     *
     * @param array   $actions The post row actions.
     * @param WP_Post $post The post object.
     * @return array Updated row actions.
     */
    public static function add_row_action( $actions, $post ) {

        if ( 'product' === $post->post_type ) {

            $product = wc_get_product( $post->ID );

            if ( $product && $product->is_type( 'book' ) ) {

                $url = wp_nonce_url(
                    add_query_arg(
                        array(
                            'action'     => 'download_onix',
                            'product_id' => $post->ID,
                        ),
                        admin_url( 'edit.php?post_type=product&page=onix-export' )
                    ),
                    'obm_export_action',
                    'obm_export_nonce'
                );

                $actions['onix'] = '<a href="' . esc_url( $url ) . '">' . __( 'Export as ONIX', 'onix-book-manager' ) . '</a>';
            }
        }

        return $actions;
    }
}

OBM_Admin::init();
