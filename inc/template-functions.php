<?php

//
// UAMS Dropdown Menus
//

if ( ! function_exists( 'uams_content_class' ) ) :
  function uams_content_class( $class = '' )
  {
    echo 'class="' . esc_attr( implode( ' ', get_uams_content_class( $class ) ) ) . '"';
  }
endif;

if ( ! function_exists( 'get_uams_content_class' ) ) :
  function get_uams_content_class( $class = '' )
  {
    $classes = array( 'uams-content' );
    if ( uams_has_sidebar() ) {
      $classes[] = 'col-md-8';
    } else {
      $classes[] = 'col-md-12';
    }

    $classes = array_map( 'esc_attr', $classes );

    return apply_filters( 'uams_content_class', $classes, $class );
  }
endif;

if ( ! function_exists( 'uams_has_sidebar' ) ) :
  function uams_has_sidebar()
  {
    global $post;

    if ( is_404() ) {
      return false;
    }

    $post_id = ( $post instanceof WP_Post ) ? $post->ID : get_the_ID();
    $format  = $post_id ? get_post_format( $post_id ) : false;

    return ( $format !== 'gallery' ) || is_archive() || is_search() || is_404();
  }
endif;

if ( ! function_exists( 'uams_dropdowns' ) ) :
  function uams_dropdowns()
  {
    $location = class_exists( 'UAMS_Dropdowns' ) ? UAMS_Dropdowns::LOCATION : 'white-bar';
    $walker   = class_exists( 'UAMS_Dropdowns_Walker_Menu' ) ? new UAMS_Dropdowns_Walker_Menu() : '';

    echo '<nav id="reddiedrops" aria-label="Main menu"><div class="reddiedrops-inner container" role="application">';

    wp_nav_menu( array(
      'theme_location' => $location,
      'container'      => false,
      'menu_class'     => 'reddiedrops-nav',
      'fallback_cb'    => '',
      'walker'         => $walker,
    ) );

    echo '</div></nav>';
  }
endif;

if ( ! function_exists( 'uams_sidebar_menu' ) ) :
  function uams_sidebar_menu()
  {
    echo sprintf( '<nav id="desktop-relative" aria-label="mobile menu that is not visible in the desktop version">%s</nav>', (string) uams_list_pages() );
  }
endif;

if ( ! function_exists( 'uams_sidebar_menu_mobile' ) ) :
  function uams_sidebar_menu_mobile()
  {
    echo sprintf( '<nav id="mobile-relative" aria-label="mobile menu">%s</nav>', (string) uams_list_pages() );
  }
endif;

if ( ! function_exists( 'uams_mobile_menu' ) ) :
  function uams_mobile_menu()
  {
    echo sprintf( '<nav id="mobile-reddiedrops" aria-label="mobile menu">%s</nav>', (string) uams_list_mobile_pages() );
  }
endif;

if ( ! function_exists( 'uams_mobile_front_page_menu' ) ) :
  function uams_mobile_front_page_menu( $class = '' )
  {
    $spacer = '';
    if ( ! empty( $class ) ) {
      $class  = ' ' . sanitize_html_class( $class );
      $spacer = '<div id="spacer"></div>';
    }
    echo sprintf( '<nav id="mobile-reddiedrops" class="frontpage%s" aria-label="relative">%s%s</nav>', $class, $spacer, (string) uams_list_front_page_menu_items() );
  }
endif;

if ( ! function_exists( 'uams_list_pages' ) ) :
  function uams_list_pages( $mobile = false )
  {
    global $UAMS, $post;

    if ( ! ( $post instanceof WP_Post ) ) {
      return '';
    }

    $parent = ! empty( $post->post_parent ) ? get_post( $post->post_parent ) : false;
    $parent_id = ( $parent instanceof WP_Post ) ? $parent->ID : 0;
    $parent_post_parent = ( $parent instanceof WP_Post ) ? $parent->post_parent : 0;

    if ( ! $mobile && ! get_children( array( 'post_parent' => $post->ID, 'post_status' => 'publish' ) ) && $parent_id === $post->ID ) {
      return '';
    }

    $toggle = $mobile ? '<button class="uams-mobile-menu-toggle">Menu</button>' : '';
    $class  = $mobile ? 'uams-mobile-menu' : 'uams-sidebar-menu';

    $siblings = get_pages( array(
      'parent'    => $parent_post_parent,
      'post_type' => 'page',
      'exclude'   => $parent_id ? array( $parent_id ) : array(),
    ) );

    $ids = ( ! is_front_page() && is_array( $siblings ) ) ? array_map( function( $sibling ) { return $sibling->ID; }, $siblings ) : array();

    $walker = ( isset( $UAMS ) && is_object( $UAMS ) && isset( $UAMS->SidebarMenuWalker ) ) ? $UAMS->SidebarMenuWalker : '';

    $pages = wp_list_pages( array(
      'title_li'     => '<a href="' . esc_url( home_url( '/' ) ) . '" title="Home" class="homelink">Home</a>',
      'child_of'     => $parent_post_parent,
      'exclude_tree' => $ids,
      'depth'        => 3,
      'echo'         => 0,
      'walker'       => $walker,
    ) );

    if ( empty( $pages ) || ! is_string( $pages ) ) {
      return '';
    }

    $bool = false !== strpos( $pages, 'child-page-existance-tester' );

    return ( $bool && ! is_search() ) ? sprintf( '%s<ul class="%s first-level">%s</ul>', $toggle, esc_attr( $class ), $pages ) : '';
  }
endif;

if ( ! function_exists( 'uams_list_mobile_pages' ) ) :
  function uams_list_mobile_pages()
  {
    if ( ! is_front_page() ) {
      $isMenuEmpty  = uams_list_pages( true );
      $alwaysMobile = get_option( 'use_main_menu_on_mobile' );
      if ( empty( $isMenuEmpty ) && $alwaysMobile ) {
        return uams_list_front_page_menu_items();
      }
      return $isMenuEmpty;
    }

    $locations = get_nav_menu_locations();
    $location  = class_exists( 'UAMS_Dropdowns' ) ? UAMS_Dropdowns::LOCATION : 'white-bar';

    if ( empty( $locations[ $location ] ) ) {
      return '';
    }

    $menu = wp_get_nav_menu_object( $locations[ $location ] );
    if ( ! $menu ) {
      return '';
    }

    $items = wp_get_nav_menu_items( $menu->term_id );
    if ( empty( $items ) || ! is_array( $items ) ) {
      return '';
    }

    $toggle = '<button class="uams-mobile-menu-toggle">Menu</button>';
    $ids    = array();

    foreach ( $items as $item ) {
      if ( (int) $item->menu_item_parent !== 0 ) {
        continue;
      }
      $ids[] = $item->object_id;
    }

    if ( empty( $ids ) ) {
      return '';
    }

    global $UAMS;
    $walker = ( isset( $UAMS ) && is_object( $UAMS ) && isset( $UAMS->SidebarMenuWalker ) ) ? $UAMS->SidebarMenuWalker : '';

    $pages = wp_list_pages( array(
      'title_li'   => '<a href="' . esc_url( home_url( '/' ) ) . '" title="Home" class="homelink">Home</a>',
      'include'    => implode( ',', $ids ),
      'sort_order' => 'menu_order',
      'depth'      => 1,
      'echo'       => 0,
      'walker'     => $walker,
    ) );

    return $pages ? sprintf( '%s<ul class="uams-mobile-menu first-level">%s</ul>', $toggle, $pages ) : '';
  }
endif;

if ( ! function_exists( 'uams_list_front_page_menu_items' ) ) :
  function uams_list_front_page_menu_items()
  {
    $location = class_exists( 'UAMS_Dropdowns' ) ? UAMS_Dropdowns::LOCATION : 'white-bar';
    $toggle   = '<button class="uams-mobile-menu-toggle">Menu</button>';

    $items = wp_nav_menu( array(
      'title_li'        => '<a href="' . esc_url( home_url( '/' ) ) . '" title="Home" class="homelink">Home</a>',
      'theme_location' => $location,
      'depth'           => 3,
      'container_class' => '',
      'menu_class'      => '',
      'fallback_cb'     => '',
      'echo'            => false,
    ) );

    return $items ? sprintf( '%s<ul class="uams-mobile-menu first-level">%s</ul>', $toggle, $items ) : '';
  }
endif;

if ( ! function_exists( 'get_uams_breadcrumbs' ) ) :
  function get_uams_breadcrumbs()
  {
    global $post;

    $post_id   = ( $post instanceof WP_Post ) ? $post->ID : get_the_ID();
    $ancestors = $post_id ? array_reverse( get_post_ancestors( $post_id ) ) : array();

    $site_title = get_bloginfo( 'name' );
    $html       = '<li><a href="http://www.uams.edu" title="University of Arkansas for Medical Sciences">Home</a></li>';
    $html      .= '<li' . ( is_front_page() ? ' class="current"' : '' ) . '><a href="' . esc_url( home_url( '/' ) ) . '" title="' . esc_attr( $site_title ) . '">' . esc_html( $site_title ) . '</a></li>';

    if ( is_404() ) {
      $html .= '<li class="current"><span>Ooops!</span></li>';
    } elseif ( is_search() ) {
      $html .= '<li class="current"><span>Search results for ' . esc_html( get_search_query() ) . '</span></li>';
    } elseif ( is_author() ) {
      $author = get_queried_object();
      $name   = ( $author && isset( $author->display_name ) ) ? $author->display_name : '';
      $html  .= '<li class="current"><span> Author: ' . esc_html( $name ) . '</span></li>';
    } elseif ( get_queried_object_id() === (int) get_option( 'page_for_posts' ) && get_option( 'page_for_posts' ) ) {
      $html .= '<li class="current"><span> ' . esc_html( get_the_title( get_queried_object_id() ) ) . ' </span></li>';
    }

    if ( is_category() || is_tax() || is_single() || is_post_type_archive() ) {
      if ( is_post_type_archive() && ! is_tax() ) {
        $posttype = get_post_type_object( get_post_type() );
        if ( $posttype ) {
          $html .= '<li class="current"><span>' . esc_html( $posttype->labels->menu_name ) . '</span></li>';
        }
      }

      if ( is_category() ) {
        $category = get_category( get_query_var( 'cat' ) );
        if ( $category && ! is_wp_error( $category ) ) {
          $html .= '<li class="current"><span>' . esc_html( get_cat_name( $category->term_id ) ) . '</span></li>';
        }
      }

      if ( is_tax() && ! is_post_type_archive() ) {
        $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
        $tax  = get_taxonomy( get_query_var( 'taxonomy' ) );
        if ( $tax && $term && ! is_wp_error( $term ) ) {
          $html .= '<li class="current"><span>' . esc_html( $tax->labels->name ) . ': ' . esc_html( $term->name ) . '</span></li>';
        }
      }

      if ( is_tax() && is_post_type_archive() ) {
        $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
        if ( $term && ! is_wp_error( $term ) ) {
          $html .= '<li class="current"><span>' . esc_html( $term->name ) . '</span></li>';
        }
      }

      if ( is_single() ) {
        if ( has_category() && $post_id ) {
          $thecat = get_the_category( $post_id );
          if ( ! empty( $thecat ) ) {
            $category = array_shift( $thecat );
            $html    .= '<li><a href="' . esc_url( get_category_link( $category->term_id ) ) . '" title="' . esc_attr( get_cat_name( $category->term_id ) ) . '">' . esc_html( get_cat_name( $category->term_id ) ) . '</a></li>';
          }
        }
        if ( uams_is_custom_post_type() ) {
          $posttype = get_post_type_object( get_post_type() );
          if ( $posttype ) {
            $archive_link = ! empty( $posttype->query_var ) ? get_post_type_archive_link( $posttype->query_var ) : '';
            if ( ! empty( $archive_link ) ) {
              $html .= '<li><a href="' . esc_url( $archive_link ) . '" title="' . esc_attr( $posttype->labels->menu_name ) . '">' . esc_html( $posttype->labels->menu_name ) . '</a></li>';
            } elseif ( is_array( $posttype->rewrite ) && ! empty( $posttype->rewrite['slug'] ) ) {
              $html .= '<li><a href="' . esc_url( site_url( '/' . $posttype->rewrite['slug'] . '/' ) ) . '" title="' . esc_attr( $posttype->labels->menu_name ) . '">' . esc_html( $posttype->labels->menu_name ) . '</a></li>';
            }
          }
        }
        if ( $post_id ) {
          $html .= '<li class="current"><span>' . esc_html( get_the_title( $post_id ) ) . '</span></li>';
        }
      }
    } elseif ( is_page() ) {
      if ( ( ! is_home() || ! is_front_page() ) && $post_id ) {
        $ancestors[] = $post_id;
      }

      if ( ! is_front_page() ) {
        $filtered = array_values( array_filter( $ancestors ) );
        $count    = count( $filtered );
        foreach ( $filtered as $index => $ancestor ) {
          $page = get_post( $ancestor );
          if ( ! ( $page instanceof WP_Post ) ) {
            continue;
          }
          $is_last    = ( $index + 1 === $count );
          $url        = get_permalink( $page->ID );
          $title_attr = esc_attr( $page->post_title );

          if ( $is_last ) {
            $html .= '<li class="current"><span>' . esc_html( $page->post_title ) . '</span></li>';
          } else {
            $html .= '<li><a href="' . esc_url( $url ) . '" title="' . $title_attr . '">' . esc_html( $page->post_title ) . '</a></li>';
          }
        }
      }
    }

    return "<nav class='uams-breadcrumbs' aria-label='breadcrumbs'><ul>$html</ul></nav>";
  }
endif;

if ( ! function_exists( 'uams_breadcrumbs' ) ) :
  function uams_breadcrumbs()
  {
    echo get_uams_breadcrumbs();
  }
endif;

if ( ! function_exists( 'uams_thumbnail_url' ) ) :
  function uams_thumbnail_url( $size = 'original' )
  {
    echo esc_url( uams_get_thumbnail_url( $size ) );
  }
endif;

if ( ! function_exists( 'uams_get_thumbnail_url' ) ) :
  function uams_get_thumbnail_url( $size = 'original' )
  {
    $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), $size, true );
    return is_array( $thumbnail ) ? $thumbnail[0] : '';
  }
endif;

if ( ! function_exists( 'is_pdf' ) ) :
  function is_pdf()
  {
    return get_post_mime_type() === 'application/pdf';
  }
endif;

if ( ! function_exists( 'uams_get_sticky_posts' ) ) :
  function uams_get_sticky_posts( $args = array() )
  {
    $stickyposts = get_option( 'sticky_posts' );
    $defaults    = array( 'post__in' => ! empty( $stickyposts ) ? $stickyposts : array( 0 ) );
    $options     = wp_parse_args( $args, $defaults );

    return get_posts( $options );
  }
endif;

if ( ! function_exists( 'uams_is_custom_post_type' ) ) :
  function uams_is_custom_post_type()
  {
    $type = get_post_type();
    return $type ? array_key_exists( $type, get_post_types( array( '_builtin' => false ) ) ) : false;
  }
endif;

if ( ! function_exists( 'uams_site_title' ) ) :
  function uams_site_title()
  {
    $classes = '';
    if ( get_option( 'overly_long_title' ) ) {
      $classes .= ' long-title';
    }
    $blog_name = get_bloginfo( 'name' );
    echo '<a href="' . esc_url( home_url( '/' ) ) . '" title="' . esc_attr( str_replace( '   ', ' ', $blog_name ) ) . '" tabindex="-1" aria-hidden="true" class="uams-site-title' . esc_attr( $classes ) . '">' . str_replace( '   ', ' <br/>', esc_html( $blog_name ) ) . '</a>';
  }
endif;

if ( ! function_exists( 'uams_page_title' ) ) :
  function uams_page_title()
  {
    $classes = 'uams-page-title';
    if ( get_option( 'overly_long_title' ) ) {
      $classes .= ' long-title';
    }
    $title = get_the_title();
    echo '<a href="" title="' . esc_attr( $title ) . '"><div class="' . esc_attr( $classes ) . '">' . esc_html( $title ) . '</div></a>';
  }
endif;

if ( ! function_exists( 'uams_get_permalink' ) ) :
  function uams_get_permalink( $post = null )
  {
    if ( empty( $post ) ) {
      $postID = get_the_ID();
    } else {
      $post_obj = get_post( $post );
      $postID   = ( $post_obj instanceof WP_Post ) ? $post_obj->ID : 0;
    }

    if ( ! $postID ) {
      return '';
    }

    $external_url = get_post_meta( $postID, 'post_custom_link', true );
    $post_format  = get_post_format( $postID );

    if ( ! empty( $external_url ) && ( $post_format === 'link' ) ) {
      return $external_url;
    }

    return get_permalink( $postID );
  }
endif;
