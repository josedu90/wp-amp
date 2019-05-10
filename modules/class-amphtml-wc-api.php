<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Compatibility class for WooCommerce API
 */
class AMPHTML_WC_API {

	public static $wc_3 = false;

	public static function init() {
		if ( class_exists( 'WooCommerce' ) ) {
			global $woocommerce;
			if ( version_compare( $woocommerce->version, '3.0', ">=" ) ) {
				self::$wc_3 = true;
			}
		}
	}

	public static function get_gallery_attachment_ids( $product ) {
		if ( self::$wc_3 ) {
			return $product->get_gallery_image_ids();
		} else {
			return $product->get_gallery_attachment_ids();
		}
	}

	public static function get_related( $product, $posts_per_page ) {
		if ( self::$wc_3 ) {
			return wc_get_related_products( $product->get_id(), $posts_per_page );
		} else {
			return $product->get_related( $posts_per_page );
		}
	}

}

AMPHTML_WC_API::init();