<?php
$html = apply_filters( 'woocommerce_short_description', $this->post->post_excerpt );
?>
<div class="product-short-desc"><?php echo $this->get_sanitize_obj()->sanitize_content( $html ); ?></div>