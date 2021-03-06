<?php
/**
  * Template Name: Home
  */
?>
<?php get_header(); ?>


<?php
	$sidebar = get_post_meta($post->ID, "sidebar");
	$breadcrumbs = get_post_meta($post->ID, "breadcrumb");
	$first = true; // used to write class on first slide
	$i = 0;
	$slidecolor = array();
    if ( ( get_field('home_page_slider') == 'slide' ) && have_rows('home_slides') ) : //$loop->have_posts() ) : ?>
<div class="uams-homepage-slider-container" role="region" aria-label="homepage-slides">
	<?php
      while ( have_rows('home_slides') ): the_row();

				$desktopimage = get_sub_field( "home_slide_desktop" );
				$mobileimage = get_sub_field("home_slide_mobile");
				$hasmobileimage = false;
				if( !empty($mobileimage) && $mobileimage['url'] !== "") {
							$mobileimage = $mobileimage['url'];
					$hasmobileimage = true;
				}
				$buttonlink = get_sub_field( "home_slide_internal_link" ); //Default interal link
				if ( get_sub_field( "home_slide_external" ) && get_sub_field( "home_slide_external_link" ) ) { // Make it external
						$buttonlink = get_sub_field( "home_slide_external_link" );
				}
				$textcolor = get_sub_field( "home_slide_text_color" );

      ?>

    <div data-mobimg="<?php echo ($hasmobileimage ? $mobileimage : $desktopimage['url']); ?>" data-dtimg="<?php echo $desktopimage['url']; ?>" class="uams-hero-image uams-homepage-slider <?php echo ($textcolor ? $textcolor : 'lighttext' ); ?> <?php echo ($first ? 'activeslide' : '' ); ?>" style="background-position: center center; background-image:url('<?php echo $desktopimage["url"]; ?>');">
		<div>
			<h3 class="slide-title" id="slide-title-<?php echo $i; ?>"><?php the_sub_field( "home_slide_title" ); ?></a><span class="udub-slant"><span></span></span></h3>
			<p class="slide-content"><?php the_sub_field( 'home_slide_text' ); ?></p>
			<p><a class="uams-btn btn-sm btn-none" href="<?php echo $buttonlink; ?>" aria-describedby="slide-title-<?php echo $i; ?>"><?php the_sub_field( 'home_slide_button_text' ); ?></a></p>
		</div>
	</div>

<?php
	$first = false;
	$slidecolor[$i] = $textcolor;
	$i++;
	endwhile;
	?>
	<?php if ($i > 1) { ?>
	<div class="slideshow-controls <?php echo $slidecolor[0]; ?>">
		<button class="next-headline">
			<span class="uwn-slideshow-next-text">NEXT</span>
			<span class="uwn-slideshow-next-title">NEXT TITLE HERE</span>
			<span class="udub-slant" style="margin-top: 10px;"><span></span></span>
		</button>
	</div>
	<?php } ?>
</div>
	<?php
	else :

	$url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
      if(!$url){
        $url = get_site_url() . "/wp-content/themes/uams-2016/assets/headers/uams-pattern-grey.png";
      }
      $darktext = get_post_meta($post->ID, "home_image_dark_text");
      $hasdarktext =( in_array('1',$darktext) ? ' hero-text-dark' : '');
      //$mobileimage = get_post_meta($post->ID, "home_image_mobile");
      $hasmobileimage = '';
      $mobileimage = get_field('home_image_mobile');
      $hasmobileimage = ( !empty($mobileimage) ? ' hero-mobile-image' : '' );
      ?>


<div class="uams-hero-image hero-height<?php echo $hasmobileimage; ?>" style="background-image: url(<?php echo $url; ?>);">
    <?php if( get_field('home_image_mobile') ) { ?>
    <div class="mobile-image" style="background-image: url(<?php echo $mobileimage['url']; ?>);"></div>
    <?php } ?>
    <div id="hero-bg">
      <div id="hero-container" class="container">
        <h1 class="uams-site-title<?php echo $hasdarktext; ?>"><?php echo (get_field('home_image_title') ? get_field('home_image_title') : get_the_title()); ?></h1>
        <span class="udub-slant"><span></span></span>
      <?php if( get_field( 'home_image_add_button' )) { ?>
        <a class="uams-btn btn-sm btn-none" href="<?php echo ( get_field('home_image_external') ? get_field('home_image_external_url') : get_field('home_image_internal_url') ); ?>"><?php echo get_field('home_image_button_text'); ?></a>
      <?php } ?>
      </div>
    </div>
</div>

<?php
endif;
?>
<?php if( get_field( 'action_menu_active' ) && have_rows('action_menu') ) :  ?>

<div class="full-bar">
	<nav aria-label="popular links" class="container action-bar">
		<ul class="center-block">
<?php
		// Get count for class
		$rows = get_field('action_menu');
		$row_count = count($rows);
		// loop through the rows of data
		while ( have_rows('action_menu') ) : the_row();

		// vars
		$linktitle = get_sub_field('action_link_title');
		$icon = get_sub_field('action_link_icon');
		$external = get_sub_field( 'action_link' );
		$internalurl = get_sub_field( 'action_link_page');
		$externalurl = get_sub_field('action_link_url');

?>
			<li class="ab-1_<?php echo $row_count; ?>"><a href="<?php echo ($external ? $externalurl : $internalurl); ?>" title="<?php echo $linktitle; ?>"><span class="icon <?php echo $icon; ?>"></span><span><?php echo $linktitle; ?></span></a></li>
<?php
		endwhile; ?>
		</ul>
	</nav>
</div>
<?php
	endif;
?>

<div class="container uams-body">

  <div class="row">

    <div class="hero-content col-md-<?php echo (($sidebar[0]!="on") ? "8" : "12" ); ?> uams-content" role='main'>

      <?php
	      if((!isset($breadcrumbs[0]) || $breadcrumbs[0]!="on")) {
	      	get_template_part( 'breadcrumbs' );
	      }
	  ?>

  	    <div id="mobile-sidebar">

			<button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">

		    	<div aria-hidden="true" id="ham">
				    <span></span>
					<span></span>
					<span></span>
					<span></span>
			    </div>
				<div id="mobile-sidebar-title" class="page_item">

					<?php
				        //limitation of the characters
				        $text = get_the_title();
				        echo text_cut($text, 27, true);
						function text_cut($text, $length, $dots) {
						//$text =get_the_title();
						$text = trim(preg_replace('#[\s\n\r\t]{2,}#', ' ', $text));
						$text_temp = $text;
						   while (substr($text, $length, 1) != " ") {
								$length--;
							  	if ($length > strlen($text)) {
								  	break;
								}
							}
						    $text = substr($text, 0, $length);
						    return $text . ( ( $dots == true && $text != '' && strlen($text_temp) > $length ) ? '...' : '');
						}
					?>

			  	</div>
			</button>
			<div id="mobile-sidebar-links" aria-hidden="true">  <?php uams_sidebar_menu(); ?></div>
		</div>

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
    if($sidebar[0]!="on") { ?>
      <div id="sidebar">
      <?php get_sidebar(); ?>
      </div> <?php
    } ?>

  </div>

</div>


<?php
	if ( ( get_field('home_page_slider') == 'slide' ) && have_rows('home_slides') ) {
		wp_enqueue_script( 'script', get_template_directory_uri() . '/js/home-slider.js', array ( 'jquery' ), 1.1, true);
	} ?>

<?php get_footer(); ?>