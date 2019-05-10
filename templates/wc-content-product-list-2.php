<?php
/**
 * The Template for render AMP HTML WooCommerce  loop content for archive pages
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/wc-content-product-list-2.php.
 *
 * @var $this AMPHTML_Template
 */
$post_link = $this->get_amphtml_link( get_permalink() );
?>
<div class="amphtml-content product-card">
	<div class="product-card-left">
		<?php echo $this->render( 'shop_image' ); ?>
		<?php echo $this->render( 'wc_shop_rating' ); ?>
	</div>
	<div class="product-card-right">
		<h2 class="amphtml-title">
			<a href="<?php echo $post_link; ?>"
			title="<?php echo wp_kses_data( $this->title ); ?>">
				<?php echo wp_kses_data( $this->title ); ?>
			</a>
		</h2>

		<?php echo $this->render( 'wc_archives_short_desc' ); ?>
		<?php echo $this->render( 'shop_add_to_cart_block' ); ?>
	</div>
</div>

