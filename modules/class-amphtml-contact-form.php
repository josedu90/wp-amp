<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMPHTML_Contact_Form {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new AMPHTML_Contact_Form();
        }

        return self::$instance;
    }

    private function __clone() {

    }

    public function __construct() {
        if ( is_wp_amp() ) {
            add_filter( 'wpcf7_form_action_url', array( $this, 'cf7_form_action_url' ) );
            add_filter( 'wpcf7_form_elements', array( $this, 'cf7_form_elements' ) );
        }
        add_action( 'wp_ajax_cf7_submit_form', array( $this, 'cf7_submit_form' ) );
        add_action( 'wp_ajax_nopriv_cf7_submit_form', array( $this, 'cf7_submit_form' ) );
    }

    /**
     * Filter action url
     */
    function cf7_form_action_url( $url ) {
        return admin_url( 'admin-ajax.php', 'relative' ) . '?action=cf7_submit_form';
    }

    /**
     * Append status element
     */
    function cf7_form_elements( $elements ) {
        ob_start();
        ?>
        <div submitting>
            <template type="amp-mustache">
                <div class="ampcf7-loading"></div>
            </template>
        </div>
        <div submit-success>
            <template type="amp-mustache">
                <div class="ampcf7-success">{{msg}}</div>
            </template>
        </div>
        <div submit-error>
            <template type="amp-mustache">{{msg}}{{#verifyErrors}}<p>{{name}}: {{message}}</p>{{/verifyErrors}}</template>
        </div>
        <?php
        $elements .= ob_get_clean();
        return $elements;
    }

    /**
     * Ajax handling
     * */
    function cf7_submit_form() {

        $domain_url = (isset( $_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

        header( "Content-type: application/json" );
        header( "Access-Control-Allow-Credentials: true" );
        header( "Access-Control-Allow-Origin: *.ampproject.org" );
        header( "Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin" );
        header( "AMP-Access-Control-Allow-Source-Origin: " . $domain_url );


        $form_id    = isset( $_POST['_wpcf7'] ) ? $_POST['_wpcf7'] : '';
        $form       = WPCF7_ContactForm::get_instance( $form_id );
        $submission = WPCF7_Submission::get_instance( $form );

        $response = $submission->get_response();
        $status   = $submission->get_status();
        // $output     = array( 'message' =>  $response );

        switch ( $status ) {
            case 'validation_failed':
                header( 'HTTP/1.1 403 Forbidden' );
                break;
            case 'acceptance_missing':
                header( 'HTTP/1.1 403 Forbidden' );
                break;
            case 'spam':
                header( 'HTTP/1.1 403 Forbidden' );
                break;
            case 'aborted':
                header( 'HTTP/1.1 403 Forbidden' );
                break;
            case 'mail_sent':
                header( 'HTTP/1.1 200 OK' );
                break;
            case 'mail_failed':
                header( 'HTTP/1.1 403 Forbidden' );
                break;
            default:
                break;
        }
        $verifyErrors = array();
        foreach ( $submission->get_invalid_fields() as $key => $val ) {

            $key            = str_replace( '-', ' ', $key );
            $key            = ucfirst( $key );
            $verifyErrors[] = array(
                'name'      => $key,
                'message'   => $val['reason']
            );
        }

        $output['msg'] = $response;

        if ( $status == 'validation_failed' )
            $output['msg'] = '';


        $output['verifyErrors'] = apply_filters( 'ampcf7_verify_errors', $verifyErrors );

        echo json_encode( $output );
        die;
    }

}

AMPHTML_Contact_Form::get_instance();
