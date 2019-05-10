<?php
/**
 * The Template for render AMP HTML WooCommerce  loop content for archive pages
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/wc-cart.php.
 *
 * @var $this AMPHTML_Template
 */
?>
<header class="page-header">
    <?php if ( $this->options->get( 'cart_breadcrumbs' ) ): ?>
        <?php echo $this->render( 'breadcrumb' ); ?>
    <?php endif; ?>
    <h1 class="amphtml-title"><?php the_title() ?></h1>
    <?php if ( $this->options->get( 'cart_original_btn_block' ) ): ?>
        <?php echo $this->render( 'cart_original_btn_block' ); ?>
    <?php endif; ?>
</header>
<div class="amphtml-content cart">
    <?php wc_print_notices(); ?>
    <form class="woocommerce-cart-form" action-xhr="<?php echo $this->get_amphtml_link( wc_get_cart_url() ); ?>" method="post">
        <div class="cart-products">
            <?php
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product        = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_id      = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
                $product_factory = new WC_Product_Factory();
                $this->product   = $product_factory->get_product( $product_id );
                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                    $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                    ?>
                    <div class="cart-product">
                        <?php if ( $this->options->get( 'cart_image' ) ): ?>
                        <div class="cart-product-image">
                            <?php echo $this->render_element( 'image', AMPHTML_WC()->get_featured_image_cart( $product_id ) ); ?>
                        </div>
                        <?php endif; ?>
                        <div class="cart-product-meta">
                            <h3 class="product-title"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' ); ?></h3>
                            <small class="product-sku"><?php echo $this->render( 'product_sku' ); ?></small>
                            <p class="product-price"><?php echo $this->product->get_price_html(); ?></p>
                            <div class="product-total-quantity">
                                <?php
                                if ( $_product->is_sold_individually() ) {
                                    $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
                                } else if ( !$this->options->get( 'cart_update' ) ) {
                                    $product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
                                } else {
                                    $product_quantity = woocommerce_quantity_input( array(
                                        'input_name'   => "cart[{$cart_item_key}][qty]",
                                        'input_value'  => $cart_item['quantity'],
                                        'max_value'    => $_product->get_max_purchase_quantity(),
                                        'min_value'    => '0',
                                        'product_name' => $_product->get_name(),
                                            ), $_product, false );
                                }
                                echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok
                                ?>
                                <p class="product-total-price">
                                    <?php
                                    echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
            </div>
            <div class="cart-actions">
                <?php if ( wc_coupons_enabled() && $this->options->get( 'cart_coupon' ) ) { ?>
                    <div class="coupon">
                        <label for="coupon_code"><?php esc_html_e( 'Coupon:', 'amphtml' ); ?></label>
                        <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'amphtml' ); ?>" />
                        <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'amphtml' ); ?>"><?php esc_attr_e( 'Apply coupon', 'amphtml' ); ?></button>
                        <?php do_action( 'woocommerce_cart_coupon' ); ?>
                    </div>
                <?php } ?>
                <?php if ( $this->options->get( 'cart_update' ) ): ?>
                <button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'amphtml' ); ?>"><?php esc_html_e( 'Update cart', 'amphtml' ); ?></button>
                <?php endif; ?>
                <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
            </div>
    </form>
    <div class="cart-totals">
        <h3><?php _e( 'Cart totals', 'amphtml' ); ?></h3>
        <div class="cart-totals-row">
            <p class="cart-totals-column-head"><?php _e( 'Subtotal', 'amphtml' ); ?></p>
            <p class="cart-totals-column-price"><?php wc_cart_totals_subtotal_html(); ?></p>
        </div>
        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
            <div class="cart-totals-row">
                <p class="cart-totals-column-head"><?php wc_cart_totals_coupon_label( $coupon ); ?></p>
                <p class="cart-totals-column-price"><?php wc_cart_totals_coupon_html( $coupon ); ?></p>
            </div>
        <?php endforeach; ?>
        <div class="cart-totals-row">
            <p class="cart-totals-column-head"><?php _e( 'Total', 'amphtml' ); ?></p>
            <p class="cart-totals-column-price"><?php wc_cart_totals_order_total_html(); ?></p>
        </div>

        <div class="amphtml-add-to">
            <a href="<?php echo $this->get_amphtml_link( wc_get_checkout_url() ); ?>" class="a-button"><?php _e( 'Proceed to checkout', 'amphtml' ); ?></a>
        </div>
    </div>
</div>
