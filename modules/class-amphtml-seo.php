<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMPHTML_SEO {

    private static $instance = null;
    private $amp             = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new AMPHTML_SEO();
        }

        return self::$instance;
    }

    private function __clone() {
        
    }

    public function __construct() {
        if ( is_wp_amp() ) {
            $this->amp = AMPHTML();
            if ( $this->is_aiosp() ) {
                add_action( 'amphtml_template_head', array( $this, 'aisop_enable' ) );
                add_action( 'wp', array( $this, 'aisop_disable_ga' ) );
            }
            if ( $this->is_yoast_seo() ) {
                add_action( 'amphtml_template_head', array( $this, 'yoast_enable' ) );
                add_action( 'wp', array( $this, 'yoast_fix_home' ) );

                // Remove comments
                $instance = WPSEO_Frontend::get_instance();
                remove_action( 'wpseo_head', array( $instance, 'debug_marker' ), 2 );
            }

            // Other SEO plugins integration
            add_action( 'amphtml_template_head', array( $this, 'add_seo' ) );
        }
    }

    public function yoast_fix_home() {
        if ( is_home() ) {
            add_filter( 'wpseo_canonical', array( $this, 'fix_canonical_url' ) );
            add_filter( 'wpseo_opengraph_title', array( $this, 'fix_og_title' ) );
        }
    }

    public function fix_canonical_url() {
        return $this->amp->get_canonical_url();
    }

    public function fix_og_title() {
        return $this->amp->template->doc_title;
    }

    public function aisop_disable_ga() {
        global $aiosp;
        remove_action( 'aioseop_modules_wp_head', array( $aiosp, 'aiosp_google_analytics' ) );
        remove_action( 'wp_head', array( $aiosp, 'aiosp_google_analytics' ) );
        add_filter( 'aiosp_google_analytics', '__return_false' );
    }

    public function yoast_enable() {
        add_filter( 'wpseo_canonical', '__return_false' );
        add_filter( 'wpseo_opengraph_url', array( $this, 'fix_canonical_url' ) );
        wpseo_frontend_head_init();
        do_action( 'wpseo_head' );
    }

    public function aisop_enable() {
        add_filter( 'aioseop_canonical_url', '__return_empty_string' );
        add_filter( 'aiosp_opengraph_meta', array( $this, 'aisop_facebook_url' ), 10, 3 );
        $aiosp = new All_in_One_SEO_Pack();
        $aiosp->wp_head();
    }

    public function aisop_facebook_url( $filtered_value, $social, $tag ) {
        if ( 'facebook' == $social && 'url' == $tag ) {
            $filtered_value = $this->fix_canonical_url();
        }
        return $filtered_value;
    }

    public function is_yoast_seo() {
        if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) OR is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
            return true;
        }

        return false;
    }

    public function is_aiosp() {
        if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
            return true;
        }

        return false;
    }

    public function is_wp_meta_seo() {
        if ( is_plugin_active( 'wp-meta-seo/wp-meta-seo.php' ) ) {
            return true;
        }

        return false;
    }

    public function add_seo() {
        global $wp_filter;

        // https://wordpress.org/plugins/autodescription/
        $seo_framework_hook = 'html_output';
        add_filter( 'the_seo_framework_rel_canonical_output', '__return_false' );
        add_filter( 'the_seo_framework_ldjson_scripts', '__return_empty_string' );

        // https://wordpress.org/plugins/seo-ultimate/
        $seo_ultimate_hook = 'template_head';

        // https://wordpress.org/plugins/wp-seopress/
        $seo_seopress           = 'seopress';
        $seo_seopress_canonical = 'seopress_titles_canonical';

        $priority = 1;

        if ( isset( $wp_filter['su_head'] ) AND class_exists( 'WP_Hook' ) ) {
            foreach ( $wp_filter['su_head']->callbacks[10] as $name => $callback ) {
                if ( strpos( $name, 'link_rel_canonical_tag' ) ) {
                    unset( $wp_filter['su_head']->callbacks[10][$name] );
                }
            }
        }

        // https://wordpress.org/plugins/wp-seopress/
        if ( isset( $wp_filter['wp_head'] ) AND class_exists( 'WP_Hook' ) ) {
            foreach ( $wp_filter['wp_head']->callbacks[0] as $name => $callback ) {
                if ( stristr( $name, $seo_seopress ) ) {
                    call_user_func( $callback['function'] );
                }
            }
        }

        if ( isset( $wp_filter['wp_head'] ) AND class_exists( 'WP_Hook' ) ) {
            foreach ( $wp_filter['wp_head']->callbacks[$priority] as $name => $callback ) {
                if ( strpos( $name, $seo_ultimate_hook ) || strpos( $name, $seo_framework_hook ) || ( stristr( $name, $seo_seopress ) && !stristr( $name, $seo_seopress_canonical ) ) ) {
                    call_user_func( $callback['function'] );
                }
            }
        }

        // https://wordpress.org/plugins/wp-meta-seo/
        if ( $this->is_wp_meta_seo() ) {
            do_action( 'wpmsseo_head' );
        }
    }

}

AMPHTML_SEO::get_instance();
