<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/add-to-cart/simple.php.
 *
 * @var $this AMPHTML_WC
 */
global $product;

if ( 'add_to_cart_ajax' == $this->get_template()->get_option( 'add_to_cart_behav' ) && 'simple' == $product->get_type() ) {
    $action     = '/wp-admin/admin-ajax.php?action=add_to_cart_ajax';
    $action_url = esc_url_raw( '//' . $_SERVER[ 'HTTP_HOST' ] . $action );
    ?>
    <form method="POST" action-xhr="<?php echo $action_url; ?>" target="_top">
        <div class="cart-field">            
            <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
            <?php if ( $this->get_template()->get_option( 'product_qty' ) && is_single() ): ?>
                <div class="amp-product-options amp-product-qty">
                    <input type="number" name="quantity" value="1"/>
                </div>
            <?php endif; ?>
            <div class="amphtml-add-to">
                <div submit-success class="amphtml-form-alert">
                    <template type="amp-mustache">
                        <div class="amphtml-form-status-success">
                            <a class="a-button amphtml-view-cart-btn" href="<?php echo $this->get_template()->get_amphtml_link( wc_get_cart_url() ); ?>">
                                <?php _e( 'View Cart', 'amphtml' ); ?>
                            </a>
                        </div>
                    </template>
                </div>
                <input type="submit" class="a-button" name="add-to-cart-text" value="<?php echo $this->get_template()->get_option( 'add_to_cart_text' ) ?>">
            </div>
        </div>
        <div submit-error class="amphtml-form-alert">
            <template type="amp-mustache">
                <div class="amphtml-form-status-error">
                    <div class="amphtml-form-errors-list">
                        {{#errors}}
                        <div class="amphtml-form-error">{{error_detail}}</div>
                        {{/errors}}
                    </div>
                </div>
            </template>
        </div>
    </form>
    <?php
} else {

    if ( 'add_to_cart_cart' == $this->get_template()->get_option( 'add_to_cart_behav' ) ) {
        global $woocommerce;
        $add_to_cart_url = wc_get_cart_url();
        $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $add_to_cart_url );
    } else if ( 'add_to_cart_checkout' == $this->get_template()->get_option( 'add_to_cart_behav' ) ) {
        global $woocommerce;
        $add_to_cart_url = $woocommerce->cart->get_checkout_url();
        $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $add_to_cart_url );
    } else if ( 'add_to_cart' == $this->get_template()->get_option( 'add_to_cart_behav' ) ) {
        $add_to_cart_url = get_permalink( $product->get_id() );
        $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $add_to_cart_url );
        $add_to_cart_url = add_query_arg( 'add-to-cart-redirect', '1', $add_to_cart_url );
    } else if ( 'add_to_cart_ajax' == $this->get_template()->get_option( 'add_to_cart_behav' ) && $product->get_type() === 'variable' ) {
        $add_to_cart_url = $this->get_template()->get_amphtml_link( get_permalink( $product->get_id() ) );
        $add_to_cart_url = add_query_arg( 'add-to-cart-redirect', '1', $add_to_cart_url );
    } else {
        $add_to_cart_url = get_permalink( $product->get_id() );
        $add_to_cart_url = add_query_arg( 'add-to-cart-redirect', '1', $add_to_cart_url );
    }

    if ( $product->is_in_stock() ) :
        ?>
        <?php do_action( 'amp_before_add_to_cart_button' ); ?>
        <p class="amphtml-add-to">
            <a class="a-button"
               href="<?php echo $add_to_cart_url ?>"><?php echo $this->get_template()->get_option( 'add_to_cart_text' ) ?></a>
        </p>
        <?php do_action( 'amp_after_add_to_cart_button' ); ?>
        <?php
    endif;
}
