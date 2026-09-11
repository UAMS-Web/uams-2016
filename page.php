<?php 
get_header();

$post_id     = get_the_ID();
$sidebar     = get_post_meta( $post_id, 'sidebar' );
$breadcrumbs = get_post_meta( $post_id, 'breadcrumb' );
?>

<?php get_template_part( 'header', 'image' ); ?>

<div class="container uams-body">

  <div class="row">

    <div class="col-md-<?php echo ( ! isset( $sidebar[0] ) || $sidebar[0] !== 'on' ) ? '8' : '12'; ?> uams-content" role='main'>

      <?php
      if ( ! isset( $breadcrumbs[0] ) || $breadcrumbs[0] !== 'on' ) {
          get_template_part( 'breadcrumbs' );
      }
      ?>

      <div id='main_content' class="uams-body-copy" tabindex="-1">

        <?php
          // Start the Loop.
          while ( have_posts() ) : the_post();

            /*
             * Include the post format-specific template for the content. If you want to
             * use this in a child theme, then include a file called content-___.php
             * (where ___ is the post format) and that will be used instead.
             */
            get_template_part( 'content', 'page' );

            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) {
              comments_template();
            }

          endwhile;
        ?>

      </div>

    </div>

    <div id="sidebar">
      <?php
      if ( ! isset( $sidebar[0] ) || $sidebar[0] !== 'on' ) {
        get_sidebar();
      }
      ?>
    </div>

  </div>

</div>

<?php get_footer(); ?>
