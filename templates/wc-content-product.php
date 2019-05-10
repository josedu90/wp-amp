<?php
/**
 * The Template for render AMP HTML WooCommerce  loop content for archive pages
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/wc-content-product-archive.php.
 *
 * @var $this AMPHTML_Template
 */
$post_link = $this->get_amphtml_link( get_permalink() );
?>
<div class="amphtml-content product-card">
	<?php foreach ( $this->get_blocks() as $element ): ?>
		<?php if ( $name = $this->get_template_name( $element ) ): ?>
			<?php echo $this->render( $name ); ?>
		<?php endif; ?>

		<?php if ($element == 'shop_image' || $element == 'wc_archives_image'): ?>
			<h2 class="amphtml-title">
				<a href="<?php echo $post_link; ?>"
				title="<?php echo wp_kses_data( $this->title ); ?>">
					<?php echo wp_kses_data( $this->title ); ?>
				</a>
			</h2>
		<?php endif; ?>
	<?php endforeach; ?>
</div>