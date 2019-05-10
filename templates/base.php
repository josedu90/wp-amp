<?php
/**
 * The Base Template for displaying AMP HTML page.
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/base.php.
 *
 * @var $this AMPHTML_Template
 */

ob_start();
do_action( 'amphtml_template_content' );
$content = ob_get_clean();

ob_start();
echo $this->get_footer();
$footer = ob_get_clean();

$rtl = $this->options->get( 'rtl_enable' );

$content_class_name = is_front_page() ? ' home-page' : '';

do_action( 'amphtml_before_header' );
?>
<!doctype html>
<html amp <?php echo $this->get_language_attributes() ?>>
<head>
	<?php echo $this->render( 'header' ) ?>
	<!--WP AMP plugin ver.<?php echo AMPHTML()->version ?>-->
</head>
<body<?php echo $rtl ? ' class="rtl"' : ''; ?>>
<?php if ( $this->options->get( 'header_menu' ) ): ?>
	<?php if ( $this->options->get( 'header_menu_type' ) == 'sidebar' ): ?>
		<amp-sidebar id="amp-sidebar" layout="nodisplay" side="<?php echo $rtl ? 'left' : 'right'; ?>">
			<button class="amp-close-button" on="tap:amp-sidebar.close" role="button" tabindex="0">
				<span class="close-line"></span>
				<span class="close-line"></span>
			</button>
			<?php echo $this->nav_menu(); ?>
		</amp-sidebar>
	<?php endif; ?>
<?php endif; ?>
<div class="wrapper" id="top">
	<nav class="amphtml-title">
		<?php echo $this->render( 'nav' ) ?>
	</nav>
	<div id="main">
		<div class="inner<?php echo $content_class_name; ?>">
			<?php echo $content; ?>
		</div>
	</div>
	<div class="footer">
		<div class="inner">
			<?php do_action( 'amphtml_footer_logo' ); ?>
            <?php if ( $this->options->get( 'footer_menu' ) ): ?>
            <?php echo $this->nav_menu_footer(); ?>
            <?php endif; ?>
			<?php if ( $footer ): ?>
				<div class="footer_content"><?php echo $footer; ?></div>
			<?php endif; ?>

			<?php if ( $scroll = $this->get_scrolltop() ): ?>
				<div class="scrolltop-btn"><a href="#top"><?php _e( $scroll, 'amphtml' ); ?></a></div>
			<?php endif; ?>

			<?php do_action( 'amphtml_footer_bottom' ); ?>
		</div>
	</div>
</div>
<?php do_action( 'amphtml_after_footer' ); ?>
</body>
</html>