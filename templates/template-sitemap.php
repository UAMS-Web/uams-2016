<?php
/**
 * Template Name: Sitemap
 */

get_header();

$post_id     = get_the_ID();
$sidebar     = get_post_meta( $post_id, 'sidebar', true );
$breadcrumbs = get_post_meta( $post_id, 'breadcrumb', true );
$has_sidebar = ( 'on' !== $sidebar );

get_template_part( 'header', 'image' ); ?>

<div class="container uams-body">

  <div class="row">

    <div class="col-md-<?php echo $has_sidebar ? '8' : '12'; ?> uams-content" role="main">

      <?php
      if ( empty( $breadcrumbs ) || 'on' !== $breadcrumbs ) {
          get_template_part( 'breadcrumbs' );
      }
      ?>

      <div id="main_content" class="uams-body-copy" tabindex="-1">

        <div id="mobile-sidebar">

          <button id="mobile-sidebar-menu" class="visible-xs" aria-hidden="true" tabindex="1">

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

        <h2 id="pages">Pages</h2>
        <ul>
          <?php
          wp_list_pages( array(
              'exclude'  => '',
              'title_li' => '',
          ) );
          ?>
        </ul>

        <h2 id="posts">Posts</h2>
        <ul>
          <?php
          $cats = get_categories( array( 'hide_empty' => true ) );
          foreach ( $cats as $cat ) {
              echo '<li><h3>' . esc_html( $cat->name ) . '</h3>';
              echo '<ul>';

              $cat_posts = new WP_Query( array(
                  'posts_per_page'      => -1,
                  'cat'                 => $cat->term_id,
                  'ignore_sticky_posts' => true,
                  'no_found_rows'       => true,
              ) );

              if ( $cat_posts->have_posts() ) {
                  while ( $cat_posts->have_posts() ) {
                      $cat_posts->the_post();
                      $categories = get_the_category();
                      if ( ! empty( $categories ) && (int) $categories[0]->term_id === (int) $cat->term_id ) {
                          echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
                      }
                  }
                  wp_reset_postdata();
              }

              echo '</ul>';
              echo '</li>';
          }
          ?>
        </ul>

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
