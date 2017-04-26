<?php get_header();
   $sidebar = get_post_meta($post->ID, "sidebar");
   $breadcrumbs = get_post_meta($post->ID, "breadcrumb");
 ?>

<?php get_template_part( 'header', 'image' ); ?>

<!--<div class="col-md-12 mobile-menu"> <?php get_template_part( 'menu', 'mobile' ); ?> </div>-->
<div class="container uams-body">

  <div class="row">

    <div class="col-md-<?php echo ((!isset($sidebar[0]) || $sidebar[0]!="on") ? "8" : "12" ) ?> uams-content" role='main'>

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
          woocommerce_content();
        ?>

      </div>

    </div>

    <div id="sidebar"><?php
      if(!isset($sidebar[0]) || $sidebar[0]!="on"){
        get_sidebar();
      }
    ?></div>

  </div>

</div>

<?php get_footer(); ?>
