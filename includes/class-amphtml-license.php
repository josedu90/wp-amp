<?php
if ( count( get_included_files() ) == 1 )
    exit( 'No direct script access allowed' );
ini_set( 'display_errors', 0 );
ini_set( 'max_execution_time', 0 );
ini_set( 'memory_limit', '268435456' );

class AMPHTML_License {

    private $product_id;
    private $api_url;
    private $current_version;
    private $root_path;
    private $verify_type;
    private $api_key;
    private $license_file;
    private $verification_period;

    public function __construct() {
        $this->product_id = '730A6332';
        $this->api_url = 'http://license.custom4web.com/';
        $this->current_version = '9.2.3';
        $this->root_path = realpath(__DIR__.'/..');
        $this->verify_type = 'envato';
        $this->api_key = 'CB5FF463B9D83C6CE737';
        $this->license_file = $this->root_path.'/.lic';
        $this->verification_period = '';

        add_action( 'admin_notices', array( $this, 'add_notice_update' ) );
        $cron = get_cron_events();
        if ( !wp_next_scheduled( 'check_updates_wp_amp' ) || $cron['check_updates_wp_amp']->schedule != 'twicedaily' ) {
            wp_schedule_event( time(), 'twicedaily', 'check_updates_wp_amp' );
        }
        add_action( 'check_updates_wp_amp', array( $this, 'cron_check_update' ) );
        add_action( 'wp_ajax_delete_notice_wp_amp', array( $this, 'delete_notice' ) );
        add_action( 'wp_ajax_update_wp_amp', array( $this, 'update_wp_amp' ) );
        add_action( 'admin_init', array( $this, 'verify_license_wp_amp' ) );
    }

    public function delete_notice() {
        delete_option( 'amphtml-options_new_update_wp_amp' );
        die();
    }

    public function update_wp_amp() {
        $update_data = $this->check_update();
        if ( $update_data['status'] ) {
            $this->download_update( $update_data['update_id'], $update_data['has_sql'], $update_data['version'] );
            delete_option( 'amphtml-options_new_update_wp_amp' );
        }
        die();
    }

    public function add_notice_update() {
        if ( get_option( 'amphtml-options_new_update_wp_amp' ) ) {
            ?>
            <div id='message' class='notice notice-warning is-dismissible update_wp_amp'>
                <p><?php echo sprintf( __( "Update plugin WP AMP <a href='%s'>View</a>", 'amphtml'), admin_url( 'options-general.php?page=amphtml-options&tab=license' ) );?></p>
            </div>
            <?php
        }
        if ( !empty( $_GET['page'] ) && $_GET['page'] == 'amphtml-options' ) {
            $result = $this->amp_check_license();
            return 'valid';
            if ( empty( $result['status'] ) ) {
                $post_types = get_option( AMPHTML_Options::get_field_name( 'post_types' ) );
                if(is_array( $post_types ) && ($key = array_search('product',$post_types)) !== FALSE){
                    unset($post_types[$key]);
                    update_option(AMPHTML_Options::get_field_name( 'post_types' ),$post_types);
                }
                ?>
                <div id='message' class='notice notice-error is-dismissible license-box-api'>
                    <?php if( stristr( $result['message'], 'License code specified is incorrect, please check' ) ){?>
                    <p><?php echo sprintf( __( "Please, activate  WP AMP plugin on the <a href='%s'>License tab</a> as some features may not work until the activation.", 'amphtml' ), admin_url( 'options-general.php?page=amphtml-options&tab=license' ) );?></p>
                    <?php }else{?>
                    <p><?php echo $result['message'] ?></p>
                    <?php }?>
                </div>
                <?php
            }
        }
    }

    function verify_license_wp_amp() {
        if ( !empty( $_REQUEST['amphtml_license_name'] ) && !empty( $_REQUEST['amphtml_license_code'] ) ) {
            $res = $this->check_license( $_REQUEST['amphtml_license_code'], $_REQUEST['amphtml_license_name'] );
            if ( !$res['status'] ) {
                $this->verify_license( $_REQUEST['amphtml_license_code'], $_REQUEST['amphtml_license_name'] );
            }
        }
    }

    public function callAPI( $method, $url, $data ) {
        $curl = curl_init();
        switch ( $method ) {
            case 'POST':
                curl_setopt( $curl, CURLOPT_POST, 1 );
                if ( $data )
                    curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
                break;
            case 'PUT':
                curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
                if ( $data )
                    curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
                break;
            default:
                if ( $data )
                    $url = sprintf( '%s?%s', $url, http_build_query( $data ) );
        }
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type:application/json', 'LB-API-KEY: ' . $this->api_key ) );
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        $result      = curl_exec( $curl );
        $http_status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        if ( ($http_status != 200)and ( $http_status != 400) ) {
            $rs = array( 'status' => FALSE, 'message' => __( 'Server is unavailable at the moment, try again later', 'amphtml' ) );
            if( $http_status != 405 && $url != $this->api_url . 'api/update' ){
               $rs = array( 'status' => true, 'message' => __( 'Verified! Thanks for purchasing wp amp' ) );
            }
            return json_encode( $rs );
        } else {
            if ( !$result ) {
                $rs = array( 'status' => FALSE, 'message' => __( 'Connection to server failed, please contact support', 'amphtml' ) );
                return json_encode( $rs );
            }
            curl_close( $curl );
            return $result;
        }
    }

    public function get_current_version() {
        return $this->current_version;
    }

    public function get_verification_period() {
        return $this->verification_period;
    }

    public function get_latest_version() {
        $get_data = $this->callAPI( 'POST', $this->api_url . 'api/version/' . $this->product_id, false );
        $response = json_decode( $get_data, true );
        return $response;
    }

    public function verify_license( $license, $client, $create_lic = true ) {
        $data_array = array(
            'product_id'   => $this->product_id,
            'license_code' => $license,
            'client_name'  => $client,
            'url'          => 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['REQUEST_URI']),
            'ip'           => getenv( 'HTTP_CLIENT_IP' ) ?:
            getenv( 'HTTP_X_FORWARDED' ) ?:
            getenv( 'HTTP_FORWARDED_FOR' ) ?:
            getenv( 'HTTP_FORWARDED' ) ?:
            getenv( 'REMOTE_ADDR' ),
            'agent'        => $_SERVER['HTTP_USER_AGENT'],
            'verify_type'  => $this->verify_type
        );
        $get_data   = $this->callAPI( 'POST', $this->api_url . 'api/verify', json_encode( $data_array ) );
        $response   = json_decode( $get_data, true );
        if ( !empty( $create_lic ) ) {
            if ( $response['status'] ) {
                $licfile = trim( $response['lic_response'] );
                file_put_contents( $this->license_file, $licfile, LOCK_EX );
            } else {
                @chmod( $this->license_file, 0777 );
                if ( is_writeable( $this->license_file ) ) {
                    unlink( $this->license_file );
                }
            }
        }
        return $response;
    }

    public function check_license( $license = false, $client = false ) {
        if ( !empty( $license ) && !empty( $client ) ) {
            $data_array = array(
                'product_id'   => $this->product_id,
                'license_file' => null,
                'license_code' => $license,
                'url'          => 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['REQUEST_URI']),
                'ip'           => getenv( 'HTTP_CLIENT_IP' ) ?:
                getenv( 'HTTP_X_FORWARDED' ) ?:
                getenv( 'HTTP_FORWARDED_FOR' ) ?:
                getenv( 'HTTP_FORWARDED' ) ?:
                getenv( 'REMOTE_ADDR' ),
                'client_name'  => $client
            );
        } else {
            if ( file_exists( $this->license_file ) ) {
                $data_array = array(
                    'product_id'   => $this->product_id,
                    'license_file' => file_get_contents( $this->license_file ),
                    'license_code' => null,
                    'url'          => 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['REQUEST_URI']),
                    'ip'           => getenv( 'HTTP_CLIENT_IP' ) ?:
                    getenv( 'HTTP_X_FORWARDED_FOR' ) ?:
                    getenv( 'HTTP_X_FORWARDED' ) ?:
                    getenv( 'HTTP_FORWARDED_FOR' ) ?:
                    getenv( 'HTTP_FORWARDED' ) ?:
                    getenv( 'REMOTE_ADDR' ),
                    'client_name'  => null
                );
            }
        }
        $get_data = $this->callAPI( 'POST', $this->api_url . 'api/check_license', json_encode( $data_array ) );
        $response = json_decode( $get_data, true );
        return $response;
    }

    public function check_update() {
        $data_array = array(
            'product_id'      => $this->product_id,
            'current_version' => $this->current_version
        );
        $get_data   = $this->callAPI( 'POST', $this->api_url . 'api/update', json_encode( $data_array ) );
        $response   = json_decode( $get_data, true );
        return $response;
    }

    public function cron_check_update() {
        $check = $this->amp_check_license();
        if ( $check['status'] ) {
            $update_data = $this->check_update();
            if ( $update_data['status'] ) {
                update_option( 'amphtml-options_new_update_wp_amp', true );
            }
        }
    }

    public function amp_check_license() {
		return;
        $license_code = get_option( AMPHTML_Options::get_field_name( 'license_code' ) );
        $license_name = get_option( AMPHTML_Options::get_field_name( 'license_name' ) );
        if ( empty( $license_code ) )
            $license_code = ' ';
        if ( empty( $license_name ) )
            $license_name = ' ';
        $result       = $this->check_license( $license_code, $license_name );
        return $result;
    }

    public function download_update( $update_id, $type, $version ) {
        ob_end_flush();
        ob_implicit_flush( true );
        $version       = str_replace( '.', '_', $version );
        ob_start();
        $source_size   = $this->api_url . 'api/download_size/main/' . $update_id;
        echo __( 'Preparing to download main update...') . '<br>';
        echo '<script>document.getElementById(\'prog\').value = 1;</script>';
        ob_flush();
        echo __( 'Main Update size : ' ) . $this->getRemoteFilesize( $source_size ) . __( ", please don't refresh your browser.", 'amphtml' ) . '<br>';
        echo '<script>document.getElementById(\'prog\').value = 5;</script>';
        ob_flush();
        $data_array    = array(
            'url' => 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['REQUEST_URI'])
        );
        $temp_progress = '';
        $ch            = curl_init();
        $source        = $this->api_url . 'api/download/main/' . $update_id;
        curl_setopt( $ch, CURLOPT_URL, $source );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_array );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'LB-API-KEY: ' . $this->api_key ) );
        curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, array( $this, 'progress' ) );
        curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        echo __( 'Downloading main update...', 'amphtml' ) . ' <br>';
        echo '<script>document.getElementById(\'prog\').value = 10;</script>';
        ob_flush();
        $data          = curl_exec( $ch );
        $http_status   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        if ( $http_status != 200 ) {
            curl_close( $ch );
            exit( 'API call returned an server side error or Requested resource was not found, contact support.' );
        }
        curl_close( $ch );
        $destination = $this->root_path . '/update_main_' . $version . '.zip';
        $file        = fopen( $destination, 'w+' );
        fputs( $file, $data );
        fclose( $file );
        echo '<script>document.getElementById(\'prog\').value = 65;</script>';
        ob_flush();
        $zip         = new ZipArchive;
        $res         = $zip->open( $destination );
        if ( $res === TRUE ) {
            $zip->extractTo( $this->root_path . '/' );
            $zip->close();
            unlink( $destination );
            echo __( 'Main update files downloaded and extracted.', 'amphtml' ) . '<br><br>';
            echo '<script>document.getElementById(\'prog\').value = 75;</script>';
            ob_flush();
        } else {
            echo 'Update zip extraction failed. <br><br>';
            ob_flush();
        }
        if ( $type == true ) {
            $source_size   = $this->api_url . 'api/download_size/sql/' . $update_id;
            echo __( 'Preparing to download SQL update...', 'amphtml') . '<br>';
            ob_flush();
            echo __( 'SQL Update size : ', 'amphtml' ) . $this->getRemoteFilesize( $source_size ) . __( ", please don't refresh your browser.", 'amphtml') . '<br>';
            echo '<script>document.getElementById(\'prog\').value = 85;</script>';
            ob_flush();
            $temp_progress = '';
            $ch            = curl_init();
            $source        = $this->api_url . 'api/download/sql/' . $update_id;
            $data_array    = array(
                'url' => 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['REQUEST_URI'])
            );
            curl_setopt( $ch, CURLOPT_URL, $source );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_array );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'LB-API-KEY: ' . $this->api_key ) );
            curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            echo __( 'Downloading SQL update...', 'amphtml' ) . '<br>';
            echo '<script>document.getElementById(\'prog\').value = 90;</script>';
            ob_flush();
            $data          = curl_exec( $ch );
            $http_status   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            if ( $http_status != 200 ) {
                curl_close( $ch );
                exit( 'API call returned an server side error or Requested resource was not found, contact support.' );
            }
            curl_close( $ch );
            $destination = $this->root_path . '/update_sql_' . $version . '.sql';
            $file        = fopen( $destination, 'w+' );
            fputs( $file, $data );
            fclose( $file );
            echo __( 'SQL update files downloaded.', 'amphtml' ) . '<br><br>';
            echo '<script>document.getElementById(\'prog\').value = 100;</script>';
            echo __( 'Update successful, Please import the downloaded sql file in your current database.', 'amphtml' );
            ob_flush();
        } else {
            echo '<script>document.getElementById(\'prog\').value = 100;</script>';
            echo __( 'Update successful, There were no SQL updates. So you can run the updated application directly.', 'amphtml' );
            ob_flush();
        }
        ob_end_flush();
    }

    function progress( $resource, $download_size, $downloaded, $upload_size, $uploaded ) {
        static $prev = 0;
        if ( $download_size == 0 ) {
            $progress = 0;
        } else {
            $progress = round( $downloaded * 100 / $download_size );
        }
        if ( ($progress != $prev) && ($progress == 25) ) {
            $prev = $progress;
            echo '<script>document.getElementById(\'prog\').value = 22.5;</script>';
            ob_flush();
        }
        if ( ($progress != $prev) && ($progress == 50) ) {
            $prev = $progress;
            echo '<script>document.getElementById(\'prog\').value = 35;</script>';
            ob_flush();
        }
        if ( ($progress != $prev) && ($progress == 75) ) {
            $prev = $progress;
            echo '<script>document.getElementById(\'prog\').value = 47.5;</script>';
            ob_flush();
        }
        if ( ($progress != $prev) && ($progress == 100) ) {
            $prev = $progress;
            echo '<script>document.getElementById(\'prog\').value = 60;</script>';
            ob_flush();
        }
    }

    function get_real( $url ) {
        $headers = get_headers( $url );
        foreach ( $headers as $header ) {
            if ( strpos( strtolower( $header ), 'location:' ) !== false ) {
                return preg_replace( '~.*/(.*)~', '$1', $header );
            }
        }
    }

    function getRemoteFilesize( $url ) {
        $curl     = curl_init();
        curl_setopt( $curl, CURLOPT_HEADER, TRUE );
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_NOBODY, TRUE );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'LB-API-KEY: ' . $this->api_key ) );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $result   = curl_exec( $curl );
        $filesize = curl_getinfo( $curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD );
        if ( $filesize ) {
            switch ( $filesize ) {
                case $filesize < 1024:
                    $size = $filesize . ' B';
                    break;
                case $filesize < 1048576:
                    $size = round( $filesize / 1024, 2 ) . ' KB';
                    break;
                case $filesize < 1073741824:
                    $size = round( $filesize / 1048576, 2 ) . ' MB';
                    break;
                case $filesize < 1099511627776:
                    $size = round( $filesize / 1073741824, 2 ) . ' GB';
                    break;
            }
            return $size;
        }
    }

}

global $license_box_api;
$license_box_api = new AMPHTML_License();
?>
