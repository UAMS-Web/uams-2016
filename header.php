<?php
    /**
	 * Redirect singular page to an alternate URL.
	 *
	 * @since 2.0.0
	 *
	 * @return null Return early if not a singular entry.
	 */

	if ( function_exists( 'get_field' ) && ( $url = get_field( 'seo_custom_redirect_url' ) ) ) {
		wp_redirect( esc_url_raw( $url ), 301 );
		exit;
	}

	$title       = '';
	$description = get_bloginfo( 'description', 'display' );
	$keywords    = '';
	$canonical   = '';

	if ( is_singular() || ! empty( $post_id ) ) {
		if ( function_exists( 'get_field' ) ) {
			if ( get_field( 'seo_document_title' ) ) {
				$title = get_field( 'seo_document_title' );
			}
			if ( get_field( 'seo_meta_description' ) ) {
				$description = get_field( 'seo_meta_description' );
			}
			if ( get_field( 'seo_custom_redirect_url' ) ) {
				$keywords = get_field( 'seo_meta_keywords' );
			}
			if ( get_field( 'seo_canonical_url' ) ) {
				$canonical = get_field( 'seo_canonical_url' );
			}
		}
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?> class="no-js">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo esc_html( ! empty( $title ) ? $title . ' | ' : wp_title( ' | ', false, 'right' ) ); echo esc_html( str_replace( '   ', ' ', get_bloginfo( 'name' ) ) ); ?></title>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?php echo esc_attr( $description ); ?>">
        <?php
	   	if ( ! empty( $keywords ) ) {
        	echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
        }
        if ( ! empty( $canonical ) ) {
        	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }
        ?>

        <!-- Google Tag Manager -->
        <?php
	    	$gtm      = get_option( 'google_tag_manager_id' );
	    	$gtmvalue = ! empty( $gtm ) ? esc_attr( $gtm ) : 'GTM-NGG4P7F';
	    ?>
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo $gtmvalue; ?>');</script>
		<!-- End Google Tag Manager -->

        <?php wp_head(); ?>

        <!--[if lt IE 9]>
            <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/ie/js/html5shiv.js" type="text/javascript"></script>
            <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/ie/js/respond.js" type="text/javascript"></script>
            <link rel='stylesheet' href='<?php echo esc_url( get_template_directory_uri() ); ?>/assets/ie/css/ie.css' type='text/css' media='all' />
        <![endif]-->

        <?php
        $page_js  = get_post_meta( get_the_ID(), 'javascript', true );
        $page_css = get_post_meta( get_the_ID(), 'css', true );
        if ( ! empty( $page_js ) )  { echo $page_js; }
        if ( ! empty( $page_css ) ) { echo $page_css; }

	    $custom_header_script = get_post_meta( get_the_ID(), 'custom_header_script', true );
	    if ( ! empty( $custom_header_script ) ) {
			echo $custom_header_script;
		}
        ?>
    </head>
    <!--[if lt IE 9]> <body <?php body_class( 'lt-ie9' ); ?>> <![endif]-->
    <!--[if gt IE 8]><!-->
    <body <?php body_class(); ?>>
    <!--<![endif]-->
    <!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmvalue; ?>"
	height="0" width="0" style="display:none;visibility:hidden" aria-hidden="true"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

    <div id="uamssearcharea" aria-hidden="true" class="uams-search-bar-container"></div>

    <a role="banner" aria-label="main_content" id="main-content" href="#main_content" class='screen-reader-shortcut'>Skip to main content</a>

    <div id="uams-container">

    <div id="uams-container-inner">

    <?php get_template_part( 'thinstrip' ); ?>

    <?php 
        require_once get_template_directory() . '/inc/template-functions.php';
	    if ( has_nav_menu( 'white-bar' ) ) {
            uams_dropdowns(); ?>
            <div class="col-md-12 mobile-menu"><?php get_template_part( 'menu', 'mobile' ); ?></div>
	<?php } ?>
