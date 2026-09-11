<?php
/**
 * Template Name: Big Hero
 */

get_header();

$post_id     = get_the_ID();
$thumb_id    = get_post_thumbnail_id( $post_id );
$url         = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
if ( ! $url ) {
    $url = get_template_directory_uri() . '/assets/headers/uams-pattern-grey.png';
}

$mobileimage_raw = function_exists( 'get_field' ) ? get_field( 'home_image_mobile' ) : '';
$mobileimage_url = is_array( $mobileimage_raw ) && ! empty( $mobileimage_raw['url'] ) ? $mobileimage_raw['url'] : ( is_string( $mobileimage_raw ) ? $mobileimage_raw : '' );
$hasmobileimage  = ! empty( $mobileimage_url ) ? 'hero-mobile-image' : '';

$sidebar     = get_post_meta( $post_id, 'sidebar', true );
$breadcrumbs = get_post_meta( $post_id, 'breadcrumb', true );
$has_sidebar = ( 'on' !== $sidebar );

$button      = function_exists( 'get_field' ) ? get_field( 'home_image_add_button' ) : false;
$buttontext  = function_exists( 'get_field' ) ? get_field( 'home_image_button_text' ) : '';
$external    = function_exists( 'get_field' ) ? get_field( 'home_image_external' ) : false;
$externalurl = function_exists( 'get_field' ) ? get_field( 'home_image_external_url' ) : '';
$internalurl = function_exists( 'get_field' ) ? get_field( 'home_image_internal_url' ) : '';
$darktext    = (array) get_post_meta( $post_id, 'home_image_dark_text', true );
$hasdarktext = in_array( '1', $darktext, true ) ? ' hero-text-dark' : '';
$btn_url     = $external ? $externalurl : $internalurl;
?>

<div class="uams-hero-image hero-height <?php echo esc_attr( $hasmobileimage ); ?>" style="background-image: url('<?php echo esc_url( $url ); ?>');">
    <?php if ( ! empty( $mobileimage_url ) ) : ?>
    <div class="mobile-image" style="background-image: url('<?php echo esc_url( $mobileimage_url ); ?>');"></div>
    <?php endif; ?>
    <div id="hero-bg">
      <div id="hero-container" class="container">
        <h1 class="uams-site-title<?php echo esc_attr( $hasdarktext ); ?>"><?php the_title(); ?></h1>
        <span class="udub-slant"><span></span></span>
      <?php if ( ! empty( $buttontext ) && $button ) : ?>
        <a class="uams-btn btn-sm btn-none" href="<?php echo esc_url( $btn_url ); ?>"><?php echo esc_html( $buttontext ); ?></a>
      <?php endif; ?>
      </div>
    </div>
</div>

<div class="container uams-body">

  <div class="row">

    <div class="hero-content col-md-<?php echo $has_sidebar ? '8' : '12'; ?> uams-content" role="main">

      <?php
      if ( empty( $breadcrumbs ) || 'on' !== $breadcrumbs ) {
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
                if ( ! function_exists( 'text_cut' ) ) {
                    function text_cut( $text, $length = 27, $dots = true ) {
                        $text      = trim( preg_replace( '#[\s\n\r\t]{2,}#', ' ', (string) $text ) );
                        $text_temp = $text;

                        if ( strlen( $text ) > $length ) {
                            while ( $length > 0 && substr( $text, $length, 1 ) !== ' ' ) {
                                $length--;
                            }
                            $text = substr( $text, 0, $length );
                        }

                        return $text . ( ( $dots && $text !== '' && strlen( $text_temp ) > $length ) ? '...' : '' );
                    }
                }

                echo esc_html( text_cut( get_the_title(), 27, true ) );
                ?>

            </div>
        </button>
        <div id="mobile-sidebar-links" aria-hidden="true"><?php uams_sidebar_menu(); ?></div>
      </div>

      <div id="main_content" class="uams-body-copy" tabindex="-1">

        <?php
        while ( have_posts() ) : the_post();

            the_content();

            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }

        endwhile;
        ?>

      </div>

    </div>

    <?php if ( $has_sidebar ) : ?>
    <div id="sidebar">
      <?php get_sidebar(); ?>
    </div>
    <?php endif; ?>

  </div>

</div>

<?php get_footer(); ?>
