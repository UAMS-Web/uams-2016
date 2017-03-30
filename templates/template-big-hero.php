<?php
/**
  * Template Name: Big Hero
  */
?>

<?php get_header();
      $url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
      if(!$url){
        $url = get_site_url() . "/wp-content/themes/uams-2016/assets/headers/uams-pattern-grey.png";
      }
      $mobileimage = get_field('home_image_mobile');
      $hasmobileimage = '';
      if( !empty($mobileimage) && $mobileimage[0] !== "") {
        $hasmobileimage = 'hero-mobile-image';
      }
      $sidebar = get_post_meta($post->ID, "sidebar");
      $breadcrumbs = get_post_meta($post->ID, "breadcrumb");
	  $button = get_field( 'home_image_add_button' );
      $buttontext = get_field('home_image_button_text');
      $external = get_field( 'home_image_external' );
      $externalurl = get_field( 'home_image_external_url' );
      $internalurl = get_field( 'home_image_internal_url' );
      //$buttonlink = $external ? $externalurl : $internalurl; ?>

<div class="uams-hero-image hero-height <?php echo $hasmobileimage ?>" style="background-image: url(<?php echo $url ?>);">
    <?php if( !empty($mobileimage) ) { ?>
    <div class="mobile-image" style="background-image: url(<?php echo $mobileimage['url'] ?>);"></div>
    <?php } ?>
    <div id="hero-bg">
      <div id="hero-container" class="container">
        <h1 class="uams-site-title"><?php the_title(); ?></h1>
        <span class="udub-slant"><span></span></span>
      <?php if(!empty($buttontext) && $button){ ?>
        <a class="uams-btn btn-sm btn-none" href="<?php echo $external ? $externalurl : $internalurl; ?>"><?php echo $buttontext ? $buttontext : ''; ?></a>
      <?php } ?>
      </div>
    </div>
</div>
<div class="container uams-body">

  <div class="row">

    <div class="hero-content col-md-<?php echo (($sidebar[0]!="on") ? "8" : "12" ) ?> uams-content" role='main'>

      <?php //uams_page_title(); ?>
      <?php //get_template_part( 'menu', 'mobile' ); ?>
      <?php
	      if((!isset($breadcrumbs[0]) || $breadcrumbs[0]!="on")) {
	      	get_template_part( 'breadcrumbs' );
	      }
	  ?>

      <div id='main_content' class="uams-body-copy" tabindex="-1">

        <?php
          // Start the Loop.
          while ( have_posts() ) : the_post();

            /*
             * Include the post format-specific template for the content. If you want to
             * use this in a child theme, then include a file called called content-___.php
             * (where ___ is the post format) and that will be used instead.
             */

              the_content();

            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) {
              comments_template();
            }

          endwhile;

        ?>

      </div>

    </div>

    <?php
    if($sidebar[0]!="on"){ ?>
      <div id="sidebar">
      <?php get_sidebar(); ?>
      </div> <?php
    } ?>

  </div>

</div>

<?php get_footer(); ?>
