<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMPHTML_WC {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new AMPHTML_WC();
        }

        return self::$instance;
    }

    private function __clone() {
        
    }

    private function __construct() {
        // load compatibility class
        include_once 'class-amphtml-wc-api.php';
        // apply hooks
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );
        add_action( 'before_load_amphtml', array( $this, 'exclude_pages' ) );
        add_filter( 'amphtml_is_mobile_get_redirect_url', array( $this, 'add_to_cart_redirect' ) );
        add_filter( 'amphtml_admin_tab_list', array( $this, 'add_wc_options_tab' ), 10, 1 );
        add_filter( 'amphtml_template_load', array( $this, 'load_template' ), 10, 2 );
        add_filter( 'amphtml_color_fields', array( $this, 'add_colors' ), 10, 3 );
        add_filter( 'amphtml_schemaorg_tab_fields', array( $this, 'add_schema_org_type' ), 10, 2 );
        add_filter( 'amphtml_breadcrumbs', array( $this, 'update_breadcrumbs' ) );
        add_filter( 'amphtml_metadata', array( $this, 'update_schema_org' ), 10, 2 );
        add_filter( 'amphtml_featured_image_size', array( $this, 'featured_image_size' ), 10, 2 );
        add_action( 'update_6_6', array( $this, 'update_content_block_fields' ) );
        add_action( 'wp_loaded', array( $this, 'amp_add_to_cart' ) );
        add_action( 'woocommerce_loop_product_link', array( $this, 'amp_product_link' ) );
        add_action( 'wp_ajax_add_to_cart_ajax', array( $this, 'add_to_cart_ajax' ) );
        add_action( 'wp_ajax_nopriv_add_to_cart_ajax', array( $this, 'add_to_cart_ajax' ) );
        add_action( 'wp_loaded', array( $this, 'amp_cart_url' ) );
    }

    /**
     * Set variation_id from attributes set
     */
    public function amp_add_to_cart() {
        if ( empty( $_REQUEST['amp-add-to-cart'] ) || empty( $_REQUEST['add-to-cart'] ) || !is_numeric( $_REQUEST['add-to-cart'] ) ) {
            return;
        }

        $product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
        $product    = wc_get_product( $product_id );

        if ( !is_object( $product ) || !$product->get_id() ) {
            return;
        }

        $variations = $product->get_available_variations();

        foreach ( $variations as $variation ) {
            $res_arr = array_intersect_assoc( $variation['attributes'], $_REQUEST );
            if ( count( $variation['attributes'] ) == count( $res_arr ) ) {
                $_REQUEST['variation_id'] = $variation['variation_id'];
            }
        }
    }

    /**
     * Change featured image size for grid
     */
    public function featured_image_size( $size, $options ) {
        if ( ( is_shop() AND $options->get( 'shop_view' ) == 'grid' ) OR ( is_product_taxonomy() AND $options->get( 'wc_archives_view' ) == 'grid' ) ) {
            return 'amphtml-image-small';
        }

        return $size;
    }

    public function pre_get_posts( $q ) {
        if ( !$q->is_main_query() ) {
            return '';
        }

        if ( AMPHTML()->is_amp() && $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) && !isset( $q->query['pagename'] ) ) {

            $q->is_page              = true;
            $q->is_home              = false;
            $q->is_post_type_archive = true;
            $q->set( 'post_type', 'product' );

            global $wp_post_types;

            $shop_page = get_post( wc_get_page_id( 'shop' ) );

            $wp_post_types['product']->ID         = '';
            $wp_post_types['product']->post_title = $shop_page->post_title;
            $wp_post_types['product']->post_name  = $shop_page->post_name;
            $wp_post_types['product']->post_type  = $shop_page->post_type;
            $wp_post_types['product']->ancestors  = get_ancestors( $shop_page->ID, $shop_page->post_type );
        }
    }

    public function exclude_pages( $queried_object_id ) {
        // Exclude woocommerce pages which have forms        
        if ( is_checkout() || is_account_page() ) {
            update_post_meta( $queried_object_id, 'amphtml-exclude', "true" );
        } elseif ( is_cart() && get_option( AMPHTML_Options::get_field_name( 'cart_enable' ) ) ) {
            update_post_meta( $queried_object_id, 'amphtml-exclude', "false" );
        } elseif ( is_cart() && !get_option( AMPHTML_Options::get_field_name( 'cart_enable' ) ) ) {
            update_post_meta( $queried_object_id, 'amphtml-exclude', "true" );
        }
    }

    public function add_to_cart_redirect( $is_mobile ) {
        return $is_mobile && false == isset( $_GET['add-to-cart-redirect'] );
    }

    public function add_wc_options_tab( $tab_list ) {
        $tab_list['wc'] = __( 'WooCommerce', 'amphtml' );

        return $tab_list;
    }

    public function add_colors( $fields, $tab, $section ) {
        $fields[] = array(
            'id'                    => 'add_to_cart_button_color',
            'title'                 => __( 'Add to Cart Button', 'amphtml' ),
            'default'               => '#88457b',
            'display_callback'      => array( $tab, 'display_color_field' ),
            'display_callback_args' => array( 'id' => 'add_to_cart_button_color' ),
            'sanitize_callback'     => array( $tab, 'sanitize_color' ),
            'section'               => $section
        );

        return $fields;
    }

    public function is_home_shop_page() {
        global $wp_query;

        return $wp_query->is_page() && 'page' === get_option( 'show_on_front' ) && $wp_query->queried_object->ID === wc_get_page_id( 'shop' );
    }

    public function add_schema_org_type( $fields, $schema_tab ) {
        $fields[] = array(
            'id'                    => 'wc_schema_type',
            'title'                 => __( 'WooCommerce Content Type', 'amphtml' ),
            'display_callback'      => array( $schema_tab, 'display_select' ),
            'default'               => 'Product',
            'display_callback_args' => array(
                'id'             => 'wc_schema_type',
                'select_options' => array(
                    'NewsArticle' => 'NewsArticle',
                    'BlogPosting' => 'BlogPosting',
                    'Product'     => 'Product'
                )
            ),
            'description'           => '',
        );

        return $fields;
    }

    /**
     * @var $template AMPHTML_Template
     * @return bool
     */
    public function load_template( $is_loaded, $template ) {
        $social_share_script = array( // todo get_embedded_element method
            'slug' => 'amp-social-share',
            'src'  => 'https://cdn.ampproject.org/v0/amp-social-share-0.1.js'
        );
        $social_like_script  = array(
            'slug' => 'amp-facebook-like',
            'src'  => 'https://cdn.ampproject.org/v0/amp-facebook-like-0.1.js'
        );
        if ( 'add_to_cart_ajax' == $template->get_option( 'add_to_cart_behav' ) && !is_cart() ) {
            $template->add_embedded_element( array(
                'slug' => 'amp-mustache',
                'src'  => 'https://cdn.ampproject.org/v0/amp-mustache-0.2.js'
            ) );
            $template->add_embedded_element( array(
                'slug' => 'amp-form',
                'src'  => 'https://cdn.ampproject.org/v0/amp-form-0.1.js'
            ) );
        }

        switch ( true ) {
            case $this->is_home_shop_page() :
                $template->set_template_content( 'wc-product-shop' );
                $template->set_blocks( 'shop' );
                $template->title    = woocommerce_page_title( false );
                $template->metadata = $this->get_schema_metadata();
                $is_loaded          = true;
                break;
            case is_product():
                add_action( 'amphtml_schema_type', array( $this, 'set_product_schema_type' ) );
                $template->set_template_content( 'single-content' );
                $template->set_blocks( 'product' );
                $current_post_id    = get_the_ID();
                $product_factory    = new WC_Product_Factory();
                $template->product  = $product_factory->get_product( $current_post_id );
                $template->set_post( $current_post_id );
                if ( $template->get_option( 'product_social_share' ) ) {
                    $template->add_embedded_element( $social_share_script );
                }
                if ( $template->get_option( 'social_like_button' ) ) {
                    $template->add_embedded_element( $social_like_script );
                }

                if ( $this->is_product_carousel( $template->product ) ) {
                    $template->add_embedded_element( array(
                        'slug' => 'amp-carousel',
                        'src'  => 'https://cdn.ampproject.org/v0/amp-carousel-0.1.js'
                    ) );
                }
                $is_loaded          = true;
                break;
            case is_shop():
                $template->set_template_content( 'wc-product-shop' );
                $template->set_blocks( 'shop' );
                $template->title    = woocommerce_page_title( false );
                $template->metadata = $this->get_schema_metadata();
                $is_loaded          = true;
                break;
            case is_product_taxonomy():
                $template->set_template_content( 'wc-product-archive' );
                $template->set_blocks( 'wc_archives' );
                $template->title    = woocommerce_page_title( false );
                $template->metadata = $this->get_schema_metadata();
                $is_loaded          = true;
                break;
            case is_cart():
                $template->set_template_content( 'wc-cart' );
                $template->title    = woocommerce_page_title( false );
                $template->add_embedded_element( array(
                    'slug' => 'amp-form',
                    'src'  => 'https://cdn.ampproject.org/v0/amp-form-0.1.js'
                ) );
                $is_loaded          = true;
                break;
        }

        return $is_loaded;
    }

    public function get_schema_metadata() {
        global $wp_query;
        $metadata = array();

        foreach ( $wp_query->posts as $post ) {
            $tpl           = $this->get_template();
            $post_image_id = $tpl->get_post_image_id( $post->ID );
            $product       = wc_get_product( $post->ID );

            $_metadata = array(
                '@context'         => 'http://schema.org',
                '@type'            => AMPHTML()->get_template()->get_option( 'wc_schema_type' ),
                'name'             => $post->post_title,
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id'   => get_permalink( $post->ID ) ? get_permalink( $post->ID ) : get_bloginfo( 'url' ),
                ),
                'image'            => $post ? $tpl->get_schema_images( $post_image_id ) : '',
                'offers'           => array(
                    "@type"         => __( 'Offer', 'amphtml' ),
                    "price"         => $product->get_price(),
                    "priceCurrency" => get_woocommerce_currency()
                )
            );
            if ( $product->is_type( 'variable' ) ) {
                $min_price = $product->get_variation_price( 'min', false );
                $max_price = $product->get_variation_price( 'max', false );
                if ( $min_price AND $max_price ) {
                    unset( $_metadata['offers']['price'] );
                    unset( $_metadata['offers']['priceCurrency'] );
                    $_metadata['offers']['priceSpecification'] = array(
                        "price"         => $product->get_price(),
                        'minPrice'      => $min_price,
                        'maxPrice'      => $max_price,
                        "priceCurrency" => get_woocommerce_currency()
                    );
                }
            }
            $metadata[] = $_metadata;
        }

        return $metadata;
    }

    public function allow_add_to_cart_block( $is_enabled, $element ) {
        switch ( $element ) {
            case 'product_add_to_cart_block':
            case 'wc_archives_add_to_cart_block':
            case 'shop_add_to_cart_block':
                $is_enabled = true;
                break;
        }

        return $is_enabled;
    }

    public function get_add_to_cart_button( $tpl = 'simple' ) {
        global $product;

        if ( $product->get_type() === 'external' ) {
            return include( $this->get_template()->get_template_path( 'external' ) );
        }

        if ( $tpl != 'simple' && ( $product->get_type() === 'variable' && ( $this->get_template()->get_option( 'product_qty' ) || $this->get_template()->get_option( 'product_options' ) ) ) ) {
            $this->get_template()->add_embedded_element( array(
                'slug' => 'amp-form',
                'src'  => 'https://cdn.ampproject.org/v0/amp-form-0.1.js'
            ) );

            return include( $this->get_template()->get_template_path( 'options' ) );
        }

        return include( $this->get_template()->get_template_path( 'simple' ) );
    }

    public function get_product_image_links( WC_Product $product ) {

        $attachment_ids = AMPHTML_WC_API::get_gallery_attachment_ids( $product );

        if ( count( $attachment_ids ) ) {
            array_unshift( $attachment_ids, $product->get_image_id() );
            $this->get_template()->product_image_ids = $attachment_ids;
            $sanitizer                               = $this->get_template()->get_sanitize_obj();

            $sanitizer->load_content( $this->get_template()->render( 'wc-product-images' ) );

            $image_size     = $this->get_template()->get_default_image_size();
            $gallery_images = $sanitizer->get_amp_images( $image_size );

            $gallery_content = $this->get_template()->render_element( 'carousel', array(
                'width'  => $image_size['width'],
                'height' => $image_size['height'],
                'images' => $gallery_images
                    ) );

            return $gallery_content;
        }

        return $this->get_template()->render_element( 'image', $this->get_template()->featured_image );
    }

    public function get_featured_image_cart( $product_id ) {
        $featured_image    = '';
        $image_id          = get_post_meta( $product_id, 'amphtml_featured_image_id', true );
        $post_thumbnail_id = ( $image_id ) ? $image_id : get_post_thumbnail_id( $product_id );

        if ( $post_thumbnail_id ) {
            $size                  = apply_filters( 'amphtml_featured_image_size', 'amphtml-image', $this->options );
            $featured_image        = wp_get_attachment_image_src( $post_thumbnail_id, $size );
            $featured_image['alt'] = get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true );

            // Check image dimensions
            if ( empty( $featured_image[1] ) ) {
                // Check width
                $featured_image[1] = $this->get_option( 'content_width' );
            } else if ( empty( $featured_image[2] ) ) {
                // Check height
                $featured_image[2] = $this->get_option( 'element_height' );
            }
        }

        return $featured_image;
    }

    public function is_product_carousel( WC_Product $product ) {
        $attachment_ids = AMPHTML_WC_API::get_gallery_attachment_ids( $product );

        return count( $attachment_ids );
    }

    public function get_template() {
        return AMPHTML()->get_template();
    }

    public function update_content_block_fields() {
        $disabled_elements = array(
            'product_add_to_cart_block',
            'shop_add_to_cart_block',
            'wc_archives_add_to_cart_block'
        );

        foreach ( $disabled_elements as $element ) {
            update_option( 'amphtml_' . $element, 1 );
        }
    }

    public function set_product_schema_type( $type ) {
        return AMPHTML()->get_template()->get_option( 'wc_schema_type' );
    }

    public function update_breadcrumbs( $breadcrumbs ) {
        add_filter( 'woocommerce_breadcrumb_home_url', array( $this, 'update_breadcrumbs_home_link' ) );

        if ( is_woocommerce() ) {
            ob_start();
            woocommerce_breadcrumb( array(
                'delimiter'   => '',
                'wrap_before' => '<nav class="breadcrumb"><ul>',
                'wrap_after'  => '</ul></nav>',
                'before'      => '<li>',
                'after'       => '</li>',
            ) );
            $breadcrumbs = ob_get_clean();
        }

        remove_filter( 'woocommerce_breadcrumb_home_url', array( $this, 'update_breadcrumbs_home_link' ) );

        return $breadcrumbs;
    }

    public function update_breadcrumbs_home_link( $link ) {
        return AMPHTML()->get_template()->get_amphtml_link( $link );
    }

    public function update_schema_org( $metadata, $product ) {
        if ( is_woocommerce() ) {
            $product  = wc_get_product( $product->ID );
            $metadata = array(
                '@context'         => 'http://schema.org',
                '@type'            => $metadata['@type'],
                'name'             => $product->get_title(),
                'description'      => $metadata['description'],
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id'   => get_permalink( $product->get_id() ) ? get_permalink( $product->get_id() ) : get_bloginfo( 'url' ),
                ),
                'image'            => $metadata['image'],
                'offers'           => array(
                    "@type"         => __( 'Offer', 'amphtml' ),
                    "price"         => $product->get_price(),
                    "priceCurrency" => get_woocommerce_currency(),
                )
            );
            if ( $product->is_type( 'variable' ) ) {
                $min_price = $product->get_variation_price( 'min', false );
                $max_price = $product->get_variation_price( 'max', false );
                if ( $min_price AND $max_price ) {
                    unset( $metadata['offers']['price'] );
                    unset( $metadata['offers']['priceCurrency'] );
                    $metadata['offers']['priceSpecification'] = array(
                        "price"         => $product->get_price(),
                        'minPrice'      => $min_price,
                        'maxPrice'      => $max_price,
                        "priceCurrency" => get_woocommerce_currency()
                    );
                }
            }
        }

        return $metadata;
    }

    public function amp_product_link( $link ) {
        if ( AMPHTML()->is_amp() ) {
            $link = AMPHTML()->get_template()->get_amphtml_link( $link );
        }

        return $link;
    }

    public function add_to_cart_ajax() {
        $domain_url = (isset( $_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

        header( "Content-type: application/json" );
        header( "Access-Control-Allow-Credentials: true" );
        header( "Access-Control-Allow-Origin: *.ampproject.org" );
        header( "Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin" );
        header( "AMP-Access-Control-Allow-Source-Origin: " . $domain_url );

        $product_id        = absint( $_POST['product_id'] );
        $product           = wc_get_product( $product_id );
        $quantity          = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
        $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
        $product_status    = get_post_status( $product_id );
        $variation_id      = 0;
        $variation         = array();

        if ( $product && 'variation' === $product->get_type() ) {
            $variation_id = $product_id;
            $product_id   = $product->get_parent_id();
            $variation    = $product->get_variation_attributes();
        }
        if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) && 'publish' === $product_status ) {

            header( 'HTTP/1.1 200 OK' );
            $data = array(
                'status'      => 'success',
                'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
            );
        } else {

            // If there was an error adding to the cart, redirect to the product page to show any errors
            header( 'HTTP/1.1 403 Forbidden' );
            wc_clear_notices();
            $data = array(
                'errors' =>
                array(
                    'error_detail' => __( 'error text', 'amphtml' ), )
            );
        }
        wp_send_json( $data );
        die;
    }

    public function amp_cart_url() {
        if ( AMPHTML()->is_amp() ) {
            add_filter( 'woocommerce_get_cart_url', array( $this, 'get_amp_cart_url' ) );
        }
    }

    public function get_amp_cart_url( $url ) {
        if ( isset( $_REQUEST['apply_coupon'] ) || isset( $_REQUEST['remove_coupon'] ) || isset( $_REQUEST['remove_item'] ) || isset( $_REQUEST['undo_item'] ) || isset( $_REQUEST['update_cart'] ) || isset( $_REQUEST['proceed'] ) ) {
            $url = AMPHTML_Options::get_amphtml_link( $url );
        }
        return $url;
    }

}

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    AMPHTML_WC::get_instance();

    function AMPHTML_WC() {
        return AMPHTML_WC::get_instance();
    }

}