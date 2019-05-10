<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMPHTML_Tab_License extends AMPHTML_Tab_Abstract {

    public function get_fields() {
        return array(
            array(
                'id'                    => 'license_name',
                'title'                 => __( "Codecanyon user name", 'amphtml' ),
                'display_callback'      => array( $this, 'display_text_field' ),
                'display_callback_args' => array( 'license_name' ),
                'placeholder'           => 'johnsmith123',
            ),
            array(
                'id'                    => 'license_code',
                'title'                 => __( 'Enter your License', 'amphtml' ),
                'display_callback'      => array( $this, 'display_text_field' ),
                'display_callback_args' => array( 'license_code' ),
                'placeholder'           => 'a1b2c3d4-e5f6-g7h8-i9g0-k1l2m3n4o5p6',
            ),
        );
    }

    public function get_submit() {
        $check = amp_check_license();
        if ( empty( $check['status'] ) ) {
            ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary"
                       value="<?php echo __( 'Save Changes', 'amphtml' ); ?>">
                       <?php do_action( 'get_tab_submit_button', $this ); ?>
            </p>
            <?php
        } elseif ( $this->is_update() ) {
            ?>
            <input type="submit" value="<?php echo __( 'Update', 'amphtml' ); ?>" class="button button-primary" id="update_license"/>
            <?php
        }
    }

    public function is_update() {
        global $license_box_api;
        $result = $license_box_api->check_update();
        return $result['status'];
    }

}
