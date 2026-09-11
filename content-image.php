<div class="uams-image-content">

  <?php echo wp_get_attachment_image( get_the_ID(), 'full', false, array( 'class' => 'attachment-full center-block' ) ); ?>

  <h1 class="entry-title"><?php the_title(); ?></h1>

  <?php echo get_the_excerpt(); ?>
  <?php
  	$credit = get_post_meta( get_the_ID(), '_media_credit', true );
  	if ( $credit ) {
		echo '<br> Photo Credit: ' . esc_html( $credit );
	}
  ?>

  <div>
    <a href="<?php echo esc_url( wp_get_attachment_url( get_the_ID() ) ); ?>" title="<?php the_title_attribute(); ?>" target="_blank" download="<?php the_title_attribute(); ?>">Download</a>
  </div>

</div>
