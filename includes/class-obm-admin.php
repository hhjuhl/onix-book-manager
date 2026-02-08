<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class OBM_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_filter( 'post_row_actions', [ __CLASS__, 'add_row_action' ], 10, 2 );
    }

    public static function add_menu() {
        add_management_page(
            __( 'ONIX Export', 'onix-book-manager' ),
            __( 'ONIX Export', 'onix-book-manager' ),
            'manage_options',
            'onix-export',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'tools_page_onix-export' !== $hook ) return;
        wp_enqueue_style( 'obm-admin-css', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/obm-admin.css', [], '1.7.0' );
    }

    public static function render_page() {
        if ( isset($_GET['action']) && $_GET['action'] == 'download_onix' ) {
            OBM_Export::generate();
        }
        include OBM_PATH . 'includes/views/admin-export-view.php';
    }

    /**
     * Pre-flight validation for ONIX data
     */
    public static function validate_book( $product ) {
        $errors = [];

        // Check ISBN (SKU)
        if ( ! $product->get_sku() ) {
            $errors[] = __( 'Missing ISBN/SKU.', 'onix-book-manager' );
        }

        // Check Format Attribute
        $format = $product->get_attribute('Format');
        if ( empty( $format ) ) {
            $errors[] = __( 'Missing "Format" attribute.', 'onix-book-manager' );
        }

        // Check Forfatter Attribute
        $format = $product->get_attribute('Forfatter');
        if ( empty( $format ) ) {
            $errors[] = __( 'Missing "Forfatter" attribute.', 'onix-book-manager' );
        }

        return $errors;
    }

    public static function add_row_action( $actions, $post ) {
        if ( $post->post_type === 'product' ) {
            $product = wc_get_product($post->ID);
            if ( $product && $product->is_type('book') ) {
                $url = admin_url( 'tools.php?page=onix-export&action=download_onix&product_id=' . $post->ID );
                $actions['onix'] = '<a href="' . $url . '">' . __( 'Export as ONIX', 'onix-book-manager' ) . '</a>';
            }
        }
        return $actions;
    }


}
OBM_Admin::init();
