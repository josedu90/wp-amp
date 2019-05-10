<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public function for sanitizing content
 */
if ( function_exists( 'AMPHTML' ) ) {

    function esc_amphtml( $content ) {
        $amphtml = AMPHTML()->get_template()->get_sanitize_obj()->sanitize_content( $content );

        return apply_filters( 'esc_amphtml', $amphtml );
    }

}

/**
 * Shortcode for hiding content from AMP
 */
add_shortcode( 'no-amp', 'amphtml_no_amp' );

function amphtml_no_amp( $atts, $content ) {
    if ( is_wp_amp() ) {
        $content = '';
    } else {
        $content = apply_filters( 'the_content', $content );
    }

    return $content;
}

/**
 * is_post_type_viewable() for older WordPress versions
 */
if ( !function_exists( 'is_post_type_viewable' ) ) {

    function is_post_type_viewable( $post_type ) {
        if ( is_scalar( $post_type ) ) {
            $post_type = get_post_type_object( $post_type );
            if ( !$post_type ) {
                return false;
            }
        }

        return $post_type->publicly_queryable || ( $post_type->_builtin && $post_type->public );
    }

}

/**
 * Check if AMP page loaded
 * @return bool
 */
function is_wp_amp() {
    $endpoint_opt = get_option( 'amphtml_endpoint' );
    $endpoint     = ( $endpoint_opt ) ? $endpoint_opt : AMPHTML::AMP_QUERY;

    if ( '' == get_option( 'permalink_structure' ) ) {
        parse_str( $_SERVER['QUERY_STRING'], $url );

        return isset( $url[$endpoint] );
    }

    $url_parts   = explode( '?', $_SERVER["REQUEST_URI"] );
    $query_parts = explode( '/', $url_parts[0] );

    $is_amp = ( in_array( $endpoint, $query_parts ) );

    return $is_amp;
}

function amp_check_license() {
    $result = get_option( 'amphtml_license_true' );

    if ( empty( $result ) ) {
        global $license_box_api;

        $license_code = get_option( AMPHTML_Options::get_field_name( 'license_code' ) );
        $license_name = get_option( AMPHTML_Options::get_field_name( 'license_name' ) );
        if ( empty( $license_code ) )
            $license_code = ' ';
        if ( empty( $license_name ) )
            $license_name = ' ';
        $result       = $license_box_api->check_license( $license_code, $license_name );
        update_option( 'amphtml_license_true', $result );
    }
    return $result;
}

function get_cron_events() {

    $crons  = _get_cron_array();
    $events = array();

    if ( empty( $crons ) ) {
        return new WP_Error(
                'no_events', __( 'You currently have no scheduled cron events.', 'wp-crontrol' )
        );
    }

    foreach ( $crons as $time => $cron ) {
        foreach ( $cron as $hook => $dings ) {
            foreach ( $dings as $sig => $data ) {

                # This is a prime candidate for a Crontrol_Event class but I'm not bothering currently.
                $events["$hook"] = ( object ) array(
                            'hook'     => $hook,
                            'time'     => $time,
                            'sig'      => $sig,
                            'args'     => $data['args'],
                            'schedule' => $data['schedule'],
                            'interval' => isset( $data['interval'] ) ? $data['interval'] : null,
                );
            }
        }
    }

    return $events;
}

function amphtml_is_new_design(){
    $result = false;
    if( $themes = get_option( AMPHTML_Options::get_field_name( 'themes' ) ) ){
        if( $themes == 'new' ){
            $result = true;
        }
    }else{
        if( get_option( AMPHTML_Options::get_field_name( 'mobile_amp' ), 'install' ) == 'install' ){
            $result = true;
        }
    }
    return $result;
}
