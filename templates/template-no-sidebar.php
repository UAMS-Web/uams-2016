<?php
/**
 * Template Name: No Sidebar
 */

get_header();

$post_id     = get_the_ID();
$breadcrumbs = get_post_meta( $post_id, 'breadcrumb', true );

get_template_part( 'header', 'image' ); ?>

<div class="container uams-body">

  <div class="row">

    <div class="col-md-12 uams-content" role="main">

      <?php get_template_part( 'menu', 'mobile' ); ?>

      <?php
      if ( empty( $breadcrumbs ) || 'on' !== $breadcrumbs ) {
          get_template_part( 'breadcrumbs' );
      }
      ?>

      <div id="main_content" class="uams-body-copy" tabindex="-1">

        <?php
        while ( have_posts() ) : the_post();

            get_template_part( 'content', 'page' );

            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }

        endwhile;
        ?>

      </div>

    </div>

  </div>

</div>

<?php get_footer(); ?>
