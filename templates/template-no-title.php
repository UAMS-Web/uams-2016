<?php
/**
 * Template Name: No title/image
 */

get_header();

$post_id     = get_the_ID();
$sidebar     = get_post_meta( $post_id, 'sidebar', true );
$breadcrumbs = get_post_meta( $post_id, 'breadcrumb', true );
$has_sidebar = ( 'on' !== $sidebar );
?>

<div class="uams-hero-image hero-blank no-title">
  <h1 class="container uams-site-title-blank"><?php the_title(); ?></h1>
</div>

<div class="container uams-body">

  <div class="row">

    <div class="col-md-<?php echo $has_sidebar ? '8' : '12'; ?> uams-content" role="main">

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
