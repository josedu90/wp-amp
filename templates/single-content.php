<?php
/**
 * The Template for displaying Post Page
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/single-content.php.
 *
 * @var $this AMPHTML_Template
 */
?>
<?php
$class_name = (
	is_singular( 'product' ) ? ' amphtml-single-product' :
	( is_page() ? ' amphtml-single-page' : ' amphtml-single-post' )
);
?>

<div class="amphtml-content <?php echo $class_name; ?>">
	<?php foreach ( $this->get_blocks() as $element ): ?>
		<?php if ( $name = $this->get_template_name( $element ) ): ?>
			<?php echo $this->render( $name ); ?>
		<?php endif; ?>
	<?php endforeach; ?>
</div>