<?php

class UAMS_Sidebar_Menu_Walker extends Walker_Page
{
    public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 )
    {
        $indent = $depth ? str_repeat( "\t", (int) $depth ) : '';
        $args   = (array) $args;

        $css_class = array( 'page_item', 'page-item-' . $page->ID );

        if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
            $css_class[] = 'page_item_has_children';
        }

        $is_current = false;

        if ( ! empty( $current_page ) ) {
            $_current_page = get_post( $current_page );

            if ( $_current_page instanceof WP_Post ) {
                if ( ! empty( $_current_page->ancestors ) && is_array( $_current_page->ancestors ) && in_array( $page->ID, $_current_page->ancestors, true ) ) {
                    $css_class[] = 'current_page_ancestor';
                }

                if ( (int) $page->ID === (int) $current_page ) {
                    $is_current  = true;
                    $css_class[] = 'current_page_item';
                } elseif ( (int) $page->ID === (int) $_current_page->post_parent ) {
                    $css_class[] = 'current_page_parent';
                }
            }
        } elseif ( (int) $page->ID === (int) get_option( 'page_for_posts' ) ) {
            $css_class[] = 'current_page_parent';
        }

        $css_classes = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

        if ( '' === $page->post_title ) {
            $page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
        }

        $link_before = ! empty( $args['link_before'] ) ? $args['link_before'] : '';
        $link_after  = ! empty( $args['link_after'] ) ? $args['link_after'] : '';

        $parent = get_post_meta( $page->ID, 'parent', true );

        if ( $is_current ) {
            $output .= $indent . sprintf(
                '<li class="%s"><span>%s%s%s</span>',
                esc_attr( $css_classes ),
                $link_before,
                apply_filters( 'the_title', $page->post_title, $page->ID ),
                $link_after
            );
        } elseif ( ( 'on' === $parent ) && 0 !== $depth ) {
            // Hidden from child navigation
        } else {
            $output .= $indent . sprintf(
                '<li class="%s"><a href="%s">%s%s%s</a>',
                esc_attr( $css_classes . ' child-page-existance-tester' ),
                esc_url( get_permalink( $page->ID ) ),
                $link_before,
                apply_filters( 'the_title', $page->post_title, $page->ID ),
                $link_after
            );
        }

        if ( ! empty( $args['show_date'] ) ) {
            $time        = ( 'modified' === $args['show_date'] ) ? $page->post_modified : $page->post_date;
            $date_format = ! empty( $args['date_format'] ) ? $args['date_format'] : '';
            $output     .= ' ' . mysql2date( $date_format, $time );
        }
    }
}
