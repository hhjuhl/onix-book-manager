<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class OBM_Export {

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'handle_export_trigger' ] );
    }

    public static function handle_export_trigger() {
        if ( ! isset( $_REQUEST['obm_generate_xml'] ) ) return;

        check_admin_referer( 'obm_export_action', 'obm_export_nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_die( esc_html__( 'You do not have permission to export data.', 'onix-book-manager' ) );
        }

        self::generate();
    }

    public static function generate() {
        $target_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        if (!$target_id) return;

        $product = wc_get_product($target_id);
        if (!$product) return;

        $parent_id = $product->get_parent_id() ?: $product->get_id();
        $parent_post = get_post($parent_id);

        $isbn = $product->get_sku() ?: get_post_meta($parent_id, '_isbn_value', true);

        if ( empty( $isbn ) ) {
            wp_safe_redirect( add_query_arg( 'obm_error', 'no_isbn', get_edit_post_link( $parent_id, 'raw' ) ) );
            exit;
        }

        $filename_identifier = sanitize_title( $isbn );
        $date_stamp = current_time('Ymd');

        if ( ob_get_length() ) ob_clean();

        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename=onix_' . $filename_identifier . '_' . $date_stamp . '.xml');

        $xml = new XMLWriter();
        $xml->openURI('php://output');
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('ONIXMessage');
        $xml->writeAttribute('release', '3.1'); 
        $xml->writeAttribute('xmlns', 'http://ns.editeur.org/onix/3.1/reference');

        $xml->startElement('Header');
        $xml->startElement('Sender');
        $xml->writeElement('SenderName', get_bloginfo('name'));
        $xml->endElement();
        $xml->writeElement('SentDateTime', gmdate('Ymd\THis\Z'));
        $xml->endElement();

        if ( $product->is_type('variation') ) {
            self::write_product_record($xml, $product, $parent_post);
        } else {
            $children = $product->get_children();
            if ( ! empty($children) ) {
                foreach ( $children as $child_id ) {
                    $child_obj = wc_get_product($child_id);
                    if ($child_obj) self::write_product_record($xml, $child_obj, $parent_post);
                }
            } else {
                self::write_product_record($xml, $product, $parent_post);
            }
        }

        $xml->endElement(); 
        $xml->endDocument();
        $xml->flush();
        exit;
    }

    private static function write_product_record($xml, $product, $parent_post) {
        $isbn = $product->get_sku() ?: get_post_meta($parent_post->ID, '_isbn_value', true);
        
        // Configuration for allowed XHTML
        $allowed_onix_tags = array('p', 'br', 'em', 'strong', 'i', 'b', 'sub', 'sup', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'ruby', 'rp', 'rb', 'rt', 'cite');
        $allowed_onix_html = array_fill_keys($allowed_onix_tags, array());

        $xml->startElement('Product');
        $xml->writeElement('RecordReference', $isbn);
        $xml->writeElement('NotificationType', '03');

        $xml->startElement('ProductIdentifier');
        $xml->writeElement('ProductIDType', '15');
        $xml->writeElement('IDValue', $isbn);
        $xml->endElement();

        $xml->startElement('DescriptiveDetail');

        $format = strtolower($product->get_attribute('Format'));
        $onix_form = '00'; $onix_detail = '';

        if (stripos($format, 'hardback') !== false) {
            $onix_form = 'BB';
        } elseif (stripos($format, 'paperback') !== false) {
            $onix_form = 'BC'; $onix_detail = 'B113';
        } elseif (stripos($format, 'e-book') !== false || stripos($format, 'ebook') !== false) {
            $onix_form = 'ED'; $onix_detail = 'E101';
        }

        $xml->writeElement('ProductComposition', '00');
        $xml->writeElement('ProductForm', $onix_form);
        if ($onix_detail) $xml->writeElement('ProductFormDetail', $onix_detail);
        
        $xml->writeElement('PrimaryContentType', '10');
        $xml->writeElement('EpubTechnicalProtection', '00');

        $xml->writeElement('NoCollection', '');
        $xml->startElement('TitleDetail');
        $xml->writeElement('TitleType', '01');
        $xml->startElement('TitleElement');
            $xml->writeElement('TitleElementLevel', '01');
            $xml->writeElement('TitleText', $parent_post->post_title);
            $xml->writeElement('Subtitle', ''); 
        $xml->endElement();
        $xml->endElement();

        // Contributor block
        $authors = explode(', ', $product->get_attribute('Author') ?: '');
        foreach ($authors as $sequenceIndex => $contributor) {
            if (empty($contributor)) continue;

            $contributorname = trim(wp_strip_all_tags($contributor));

            // Determine NamesBeforeKey and KeyNames
            if (strpos($contributorname, ' ') === false) {
                // Single-word name → treat entire value as KeyNames
                $namesbeforekey = '';
                $keynames = $contributorname;
            } else {
                // Multi-word name → split at last space
                $lastSpacePosition = strrpos($contributorname, ' ');
                $namesbeforekey = substr($contributorname, 0, $lastSpacePosition);
                $keynames = substr($contributorname, $lastSpacePosition + 1);
            }

            $xml->startElement('Contributor');
                $xml->writeElement('SequenceNumber', $sequenceIndex + 1);
                $xml->writeElement('ContributorRole', 'A01');

                if ($namesbeforekey !== '') {
                    $xml->writeElement('NamesBeforeKey', $namesbeforekey);
                }

                $xml->writeElement('KeyNames', $keynames);
            $xml->endElement();
        } // End Contributor block
        
        $xml->writeElement('EditionNumber', '1');
        
        $xml->startElement('Language');
            $xml->writeElement('LanguageRole', '01');
            $xml->writeElement('LanguageCode', 'dan');
        $xml->endElement();

        $xml->startElement('Language');
            $xml->writeElement('LanguageRole', '02');
            $xml->writeElement('LanguageCode', 'dan');
        $xml->endElement();

        $pages = get_post_meta($parent_post->ID, '_book_page_count', true);
        if ($pages) {
            $xml->startElement('Extent');
                $xml->writeElement('ExtentType', '00'); 
                $xml->writeElement('ExtentValue', $pages);
                $xml->writeElement('ExtentUnit', '03');
            $xml->endElement();
        }

        $thema = get_post_meta($parent_post->ID, '_book_thema_code', true);
        if ($thema) {
            $xml->startElement('Subject');
                $xml->writeElement('SubjectSchemeIdentifier', '93');
                $xml->writeElement('SubjectCode', $thema);
                $xml->writeElement('MainSubject', '');
            $xml->endElement();
        }
        $xml->endElement(); // DescriptiveDetail
        
        // CollateralDetail
        $xml->startElement('CollateralDetail');
            
            // Short Description
            $xml->startElement('TextContent');
                $xml->writeElement('TextType', '02');
                $xml->writeAttribute('textformat', '05');
                $xml->writeElement('ContentAudience', '00');
                $xml->startElement('Text');
                    $xml->startCData();
                    $xml->text(trim(wp_kses($parent_post->post_excerpt, $allowed_onix_html)));
                    $xml->endCData();
                $xml->endElement();
            $xml->endElement();

            // Long Description
            $xml->startElement('TextContent');
                $xml->writeElement('TextType', '03');
                $xml->writeAttribute('textformat', '05');
                $xml->writeElement('ContentAudience', '00');
                $xml->startElement('Text');
                    $xml->startCData();
                    $xml->text(trim(wp_kses($parent_post->post_content, $allowed_onix_html)));
                    $xml->endCData();
                $xml->endElement();
            $xml->endElement();

            // Supporting Resource (Cover Image)
            $cover_image_id  = $product->get_image_id();
            $cover_image_url = $cover_image_id ? wp_get_attachment_url($cover_image_id) : '';
            
            if ($cover_image_url) {
                $xml->startElement('SupportingResource');
                    $xml->writeElement('ResourceContentType', '01');
                    $xml->writeElement('ContentAudience', '00');
                    $xml->writeElement('ResourceMode', '03');
                    $xml->startElement('ResourceVersion');
                        $xml->writeElement('ResourceForm', '02');
                        $xml->writeElement('ResourceLink', $cover_image_url);
                    $xml->endElement(); 
                $xml->endElement();
            }

        $xml->endElement(); // CollateralDetail

        // PublishingDetail
        $xml->startElement('PublishingDetail');
            $publisher = get_post_meta($parent_post->ID, '_book_publisher_name', true) ?: get_bloginfo('name');
            $publisherlink = get_bloginfo('url'); 
            $xml->startElement('Publisher');
                $xml->writeElement('PublishingRole', '01');
                $xml->writeElement('PublisherName', $publisher);
                $xml->startElement('Website');
                    $xml->writeElement('WebsiteRole', '01');
                    $xml->writeElement('WebsiteLink', $publisherlink);
                $xml->endElement();
            $xml->endElement();

            $pub_date = get_post_meta($parent_post->ID, '_book_publication_date', true);
            if ($pub_date) {
                $xml->startElement('PublishingDate');
                    $xml->writeElement('PublishingDateRole', '01');
                    $xml->startElement('Date');
                        $xml->writeAttribute('dateformat', '00');
                        $xml->text(gmdate('Ymd', strtotime($pub_date)));
                    $xml->endElement(); 
                $xml->endElement(); 
            }
        $xml->endElement(); // PublishingDetail

        // ProductSupply
        $xml->startElement('ProductSupply');
            $xml->startElement('Market');
                $xml->startElement('Territory');
                    $xml->writeElement('RegionsIncluded', 'WORLD');
                $xml->endElement();
            $xml->endElement();
            $xml->startElement('MarketPublishingDetail');
                $xml->writeElement('MarketPublishingStatus', '00');
            $xml->endElement();
            $xml->startElement('SupplyDetail');
                $xml->startElement('Supplier');
                    $xml->writeElement('SupplierRole', '00'); 
                    $xml->writeElement('SupplierName', $publisher);
                $xml->endElement();
                $xml->writeElement('ProductAvailability', '20');
                $xml->startElement('Price');
                    $xml->writeElement('PriceType', '42');
                    $xml->writeElement('PriceAmount', $product->get_price());
                    $xml->writeElement('CurrencyCode', get_woocommerce_currency());
                $xml->endElement();
            $xml->endElement();
        $xml->endElement(); 

        $xml->endElement(); // Product
    }
}
OBM_Export::init();