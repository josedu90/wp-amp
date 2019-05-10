<?php
/**
 * The Template for including AMP HTML extended components
 *
 * This template can be overridden by copying it to yourtheme/wp-amp/carousel-image.php.
 *
 * @var $this AMPHTML_Template
 */
if ( isset( $element['slug'] ) && $element['slug'] ): 
if( 'amp-mustache' == $element['slug'] ){
    ?>
    <script async custom-template="<?php echo $element['slug'] ?>" src="<?php echo $element['src'] ?>"></script>
<?php }else{ ?>
	<script async custom-element="<?php echo $element['slug'] ?>" src="<?php echo $element['src'] ?>"></script>
<?php }?>
<?php endif; ?>