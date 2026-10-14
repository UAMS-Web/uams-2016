<?php if ( get_post_meta( get_the_ID(), 'post_custom_link', true ) && is_single() ) { ?>
  <h1 class="post-link-title"><i class="far fa-link fa-sm post-link-icon"></i> <a href="<?php echo esc_url( get_post_meta( get_the_ID(), 'post_custom_link', true ) ); ?>"><?php the_title(); ?></a></h1>
  <p><em>This is an external link and will take you to a new page.</em></p>
  <?php the_content(); ?>
<?php } else { ?>
  <article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
      <h1 class="screen-reader-text"><?php the_title(); ?></h1>
      <?php the_content(); ?>
  </article> <!-- .entry-->
<?php } ?>
