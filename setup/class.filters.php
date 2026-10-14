<?php
// UAMS Filters
//
// These are filters the UAMS 2016 theme adds and globally uses.

class UAMS_Filters
{
    private $REPLACE_TEMPLATE_CLASS = array( 'templatestemplate-', '-php' );

    public function __construct()
    {
        // Custom UAMS Filters
        add_filter( 'italics', array( $this, 'italicize' ) );
        add_filter( 'abbreviation', array( $this, 'abbreviate' ) );

        // Global filters
        add_filter( 'widget_text', 'do_shortcode' );
        add_filter( 'the_excerpt', 'do_shortcode' );

        // Add template body classes
        add_filter( 'body_class', array( $this, 'better_template_name_body_class' ) );

        // Category dropdown widget classes
        add_filter( 'widget_categories_dropdown_args', array( $this, 'custom_widget_classes' ) );

        // Excerpt modifiers
        add_filter( 'excerpt_more', '__return_false' );
        add_filter( 'the_excerpt', array( $this, 'excerpt_more_override' ) );

        // Add PDF filter to media library
        add_filter( 'post_mime_types', array( $this, 'modify_post_mime_types' ) );

        // Multisite filters
        if ( is_multisite() ) {
            add_filter( 'body_class', array( $this, 'add_site_title_body_class' ) );
        }

        // Custom excerpt ending
        add_filter( 'excerpt_more', array( $this, 'custom_excerpt_more' ) );

        add_filter( 'next_posts_link_attributes', array( $this, 'posts_link_attributes_right' ) );
        add_filter( 'previous_posts_link_attributes', array( $this, 'posts_link_attributes_left' ) );

        // Allow username less than 4 characters
        add_filter( 'wpmu_validate_user_signup', array( $this, 'short_user_names' ) );
    }

    public function posts_link_attributes_right()
    {
        return 'class="uams-btn btn-sm"';
    }

    public function posts_link_attributes_left()
    {
        return 'class="uams-btn btn-sm btn-left"';
    }

    public function custom_excerpt_more( $more )
    {
        return '...';
    }

    public function modify_post_mime_types( $post_mime_types )
    {
        $post_mime_types['application/pdf'] = array(
            __( 'PDF' ),
            __( 'Manage PDFs' ),
            _n_noop( 'PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>' ),
        );

        return $post_mime_types;
    }

    public function add_site_title_body_class( $classes )
    {
        $classes = is_array( $classes ) ? $classes : array();
        $blog_id = get_current_blog_id();
        $details = get_blog_details( $blog_id );

        if ( $details && ! empty( $details->path ) ) {
            $site = array_filter( explode( '/', $details->path ) );
            array_shift( $site );
            if ( ! empty( $site ) ) {
                $classes[] = 'site-' . sanitize_html_class( implode( '-', $site ) );
            }
        }

        return $classes;
    }

    public function better_template_name_body_class( $classes )
    {
        $classes = is_array( $classes ) ? $classes : array();
        if ( is_page_template() ) {
            foreach ( $classes as $index => $class ) {
                $classes[ $index ] = str_replace( $this->REPLACE_TEMPLATE_CLASS, '', $class );
            }
        }
        return $classes;
    }

    public function custom_widget_classes( $args )
    {
        $args['class'] = 'uams-select uams-select-wp';
        return $args;
    }

    public function italicize( $content )
    {
        if ( empty( $content ) || ! is_string( $content ) ) {
            return (string) $content;
        }

        $raw_words = (string) get_option( 'italicized_words', '' );
        if ( empty( $raw_words ) ) {
            return $content;
        }

        $words = array_filter( array_map( 'trim', explode( ' ', $raw_words ) ) );
        if ( empty( $words ) ) {
            return $content;
        }

        $quoted_words = array_map( 'preg_quote', $words );
        $regex        = '/\b(' . implode( '|', $quoted_words ) . ')\b/i';
        $new_content  = preg_replace( $regex, '<em>$1</em>', $content );

        if ( in_array( '&', $words, true ) ) {
            $new_content = str_replace( array( ' &#038; ', ' & ', ' &amp; ' ), ' <em>&</em> ', $new_content );
        }

        return $new_content;
    }

    public function abbreviate( $title )
    {
        $abbr = get_option( 'abbreviation' );
        return ! empty( $abbr ) ? $abbr : $title;
    }

    public function excerpt_more_override( $excerpt )
    {
        return $excerpt . '<div><a class="more" href="' . esc_url( uams_get_permalink() ) . '">Read more</a></div>';
    }

    public function short_user_names( $result )
    {
        if ( ! isset( $result['errors'] ) || ! is_wp_error( $result['errors'] ) ) {
            return $result;
        }

        $error_name = $result['errors']->get_error_message( 'user_name' );
        if ( empty( $error_name ) || $error_name !== __( 'Username must be at least 4 characters.' ) ) {
            return $result;
        }

        unset( $result['errors']->errors['user_name'] );
        return $result;
    }
}
