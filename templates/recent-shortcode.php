<?php
/**
 * @var $this AMPHTML_Template
 */
$posts = $this->get_recent_posts( $this->recent_atts['count'] );
$show_thumbnail = $this->recent_atts['image'] != 'hide';
if ( $posts && $posts->have_posts() ) : ?>
	<aside class="amphtml-recent-posts">
		<h2><?php echo $this->recent_atts['title'] ?></h2>
		<ul class="amphtml-recent-posts-list <?php echo $show_thumbnail ? ' show-thumbs' : '' ?>">
			<?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
				<?php $link = get_permalink( get_the_ID() ); ?>
				<li>
                    <?php if ( $show_thumbnail && has_post_thumbnail( get_the_ID() ) ) : ?>
						<a href="<?php echo $this->get_amphtml_link( $link ); ?>"
						   title="<?php the_title_attribute(); ?>">
							<?php $this->the_post_thumbnail_tpl( get_the_ID() ); ?>
						</a>
					<?php endif; ?>
                    <a href="<?php echo $this->get_amphtml_link( $link ); ?>"
				       title="<?php the_title_attribute(); ?>"><?php the_title( '<h4>', '</h4>' ); ?></a>
                </li>
			<?php endwhile; ?>
		</ul>
	</aside>
	<?php
endif;
wp_reset_query();