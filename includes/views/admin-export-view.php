<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$books = get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'product_type' => 'book']);
$ready_count = 0;
?>

<div class="wrap obm-export-wrap">
    <h1><span class="dashicons dashicons-book-alt" style="font-size: 1em; width: auto;"></span> <?php esc_html_e( 'ONIX Export Manager', 'onix-book-manager' ); ?></h1>

    <table class="wp-list-table widefat fixed striped obm-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Title & Format', 'onix-book-manager' ); ?></th>
                <th><?php esc_html_e( 'ISBN', 'onix-book-manager' ); ?></th>
                <th><?php esc_html_e( 'Validation', 'onix-book-manager' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'onix-book-manager' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $books ) : ?>
                <?php foreach ( $books as $post ) :
                    $product = wc_get_product($post->ID);
                    $items = $product->is_type('variable') ? $product->get_available_variations('objects') : [$product];

                    foreach ( $items as $item ) :
                        $errors = OBM_Admin::validate_book( $item );
                        if ( empty($errors) ) $ready_count++;
                        $is_var = $item->is_type('variation');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $post->post_title ); ?></strong>
                            <?php if ( $is_var ) : ?>
                                <span class="obm-format-tag"><?php echo esc_html(implode( ', ', $item->get_attributes()) ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($item->get_sku()) ?: '—'; ?></code></td>
                        <td>
                            <?php if ( empty($errors) ) : ?>
                                <span class="obm-status-ready">✔ <?php esc_html_e( 'Valid', 'onix-book-manager' ); ?></span>
                            <?php else : ?>
                                <?php foreach ( $errors as $err ) : ?>
                                    <div class="obm-error-pill"><?php echo esc_html( $err ); ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'onix-book-manager' ); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4"><?php esc_html_e( 'No books found.', 'onix-book-manager' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="obm-bulk-box">
        <h2><?php esc_html_e( 'Bulk Export', 'onix-book-manager' ); ?></h2>
        <p><?php printf( esc_html_x( 'Items ready for export: %d', '%d is amount of books ready for export', 'onix-book-manager' ), esc_html($ready_count )); ?></p>
        <a href="<?php echo esc_url(admin_url( 'tools.php?page=onix-export&action=download_onix' )); ?>"
           class="button button-primary button-large <?php echo ($ready_count === 0) ? 'disabled' : ''; ?>">
            <?php esc_html_e( 'Download Full ONIX Feed', 'onix-book-manager' ); ?>
        </a>
    </div>
</div>
