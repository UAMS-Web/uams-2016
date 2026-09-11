<?php

/*
 * This shortcode lists out subpages, their author if show byline on posts option is set,
 * their excerpts if excerpts are enabled, and a link to the subpage.
 *
 * [subpage-list link='link text' tilebox=boolean]
 */

class UAMS_SubpageList
{
    public function __construct()
    {
        add_shortcode( 'subpage-list', array( $this, 'list_subpages' ) );
    }

    public function list_subpages( $atts )
    {
        $attributes = (object) shortcode_atts( array(
            'link'    => 'Read more',
            'tilebox' => false,
        ), $atts, 'subpage-list' );

        $attributes->tilebox = filter_var( $attributes->tilebox, FILTER_VALIDATE_BOOLEAN );

        $subpages = get_pages( array(
            'parent'      => get_the_ID(),
            'sort_column' => 'post_title',
        ) );

        $output = '';

        if ( ! empty( $subpages ) && is_array( $subpages ) ) {
            $tiles = 0;
            foreach ( $subpages as $page ) {
                $permalink = get_permalink( $page->ID );
                $title     = esc_html( $page->post_title );

                if ( ! $attributes->tilebox ) {
                    $output .= sprintf( "<h2><a href='%s'>%s</a></h2>", esc_url( $permalink ), $title );
                    if ( get_option( 'show_byline_on_posts' ) ) {
                        $author_name = get_the_author_meta( 'display_name', $page->post_author );
                        $output     .= sprintf( "<div class='author-info'><p class='author-desc'><small>%s</small></p></div>", esc_html( $author_name ) );
                    }
                    $output .= sprintf( '<p>%s</p>', wp_kses_post( $page->post_excerpt ) );
                    if ( ! empty( $attributes->link ) ) {
                        $output .= sprintf( "<a class='uams-btn btn-sm' href='%s'>%s</a>", esc_url( $permalink ), esc_html( $attributes->link ) );
                    }
                } else {
                    if ( 0 === $tiles ) {
                        $output .= "<div class='box-outer'><div class='box two'>";
                    }
                    $output .= "<div class='tile'>";
                    $output .= sprintf( '<div>%s</div>', get_the_post_thumbnail( $page->ID, 'half' ) );
                    $output .= sprintf( "<h3><a href='%s'>%s</a></h3>", esc_url( $permalink ), $title );
                    if ( get_option( 'show_byline_on_posts' ) ) {
                        $author_name = get_the_author_meta( 'display_name', $page->post_author );
                        $output     .= sprintf( "<div class='author-info'><p class='author-desc'><small>%s</small></p></div>", esc_html( $author_name ) );
                    }
                    $output .= sprintf( '<p>%s</p>', wp_kses_post( $page->post_excerpt ) );
                    $output .= '</div>';
                    $tiles++;

                    if ( 2 === $tiles ) {
                        $output .= '</div></div>';
                        $tiles   = 0;
                    }
                }
            }

            if ( 1 === $tiles ) {
                $output .= "<div class='tile empty'></div></div></div>";
            }
        }

        return $output;
    }
}

new UAMS_SubpageList();
