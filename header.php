<?php
    /**
	 * Redirect singular page to an alternate URL.
	 *
	 * @since 2.0.0
	 *
	 * @return null Return early if not a singular entry.
	 */

	if ( $url = get_field('seo_custom_redirect_url') ) {

		wp_redirect( esc_url_raw( $url ), 301 );
		exit;

	}
	$keywords = '';
	$canonical = '';
	if ( is_singular() || null !== $post_id ) {
		if ( get_field('seo_document_title') ) {
			// Title is set via custom field.
			$title = get_field('seo_document_title');
		} else {
			// Use default WP title.

		}
		if ( get_field('seo_meta_description') ) {
			// Description is set via custom field.
			$description = get_field('seo_meta_description');
		} else {
			// Use default WP description
			$description = get_bloginfo('description', 'display');
		}
		if ( get_field('seo_custom_redirect_url') ) {
			//Keywords are set via custom field.
			$keywords = get_field('seo_meta_keywords');
		}
		if ( get_field('seo_canonical_url') ) {
			//Canonical URL are set via custom field.
			$canonical = get_field('seo_canonical_url');
		}
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" class="no-js">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title> <?php echo ($title ? $title . ' | ' : wp_title(' | ',TRUE,'right')); echo str_replace('   ', ' ', get_bloginfo('name')); ?> </title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?php echo $description; ?>">
        <?php // SEO
	   	if ($keywords) {
        	echo ('<meta name="keywords" content="' . $keywords . '" />');
        }
        if ($canonical) {
        	echo ('<link rel="canonical" href="' . $canonical . '" />');
        }
        ?>

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
        <!-- Google Tag Manager -->
        <?php
	    	$gtm = get_option('google_tag_manager_id');
	    	$gtmvalue = (!empty($gtm) ? $gtm : 'GTM-NGG4P7F' );
	    ?>
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo $gtmvalue; ?>');</script>
		<!-- End Google Tag Manager -->

        <?php wp_head(); ?>

        <!--[if lt IE 9]>
            <script src="<?php bloginfo("template_directory"); ?>/assets/ie/js/html5shiv.js" type="text/javascript"></script>
            <script src="<?php bloginfo("template_directory"); ?>/assets/ie/js/respond.js" type="text/javascript"></script>
            <link rel='stylesheet' href='<?php bloginfo("template_directory"); ?>/assets/ie/css/ie.css' type='text/css' media='all' />
        <![endif]-->

        <?php
        echo get_post_meta( get_the_ID() , 'javascript' , 'true' );
        echo get_post_meta( get_the_ID() , 'css' , 'true' );
        ?>
        <?php
	    if ( get_post_meta( get_the_ID() , 'custom_header_script' , 'true' ) )
			echo get_post_meta( get_the_ID() , 'custom_header_script' , 'true' );
        ?>
    </head>
    <!--[if lt IE 9]> <body <?php body_class('lt-ie9'); ?>> <![endif]-->
    <!--[if gt IE 8]><!-->
    <body <?php body_class(); ?> >
    <!--<![endif]-->
    <!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmvalue; ?>"
	height="0" width="0" style="display:none;visibility:hidden" aria-hidden="true"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

	<!-- <a id="main-content" href="#main_content" class='screen-reader-shortcut'>Skip to main content</a> -->

    <div id="uamssearcharea" aria-hidden="true" class="uams-search-bar-container"></div>

    <a role="banner" aria-label="main_content" id="main-content" href="#main_content" class='screen-reader-shortcut'>Skip to main content</a>

    <div id="uams-container">

    <div id="uams-container-inner">


    <?php get_template_part('thinstrip'); ?>

    <?php require( get_template_directory() . '/inc/template-functions.php' );
	    if (has_nav_menu( 'white-bar' )) {
          uams_dropdowns(); ?>
    <div class="col-md-12 mobile-menu"> <?php get_template_part( 'menu', 'mobile' ); ?> </div>
	<?php } //end if nav