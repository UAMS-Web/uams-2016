<?php

/**
 * UAMS Quicklinks
 * This will register the UAMS Quicklinks navigation and provide a JSON feed for the current quicklinks menu
 */
class UAMS_QuickLinks
{
    const NAME         = 'Quick Links';
    const LOCATION     = 'quick-links';
    const ALLOWED_BLOG = 1;

    public $MULTISITE;
    public $items = array();

    public function __construct()
    {
        $this->MULTISITE = is_multisite();

        if ( ! $this->MULTISITE || ( $this->MULTISITE && get_current_blog_id() === self::ALLOWED_BLOG ) ) {
            add_action( 'after_setup_theme', array( $this, 'register_quick_links_menu' ) );
        }

        add_action( 'wp_ajax_quicklinks', array( $this, 'uams_quicklinks_feed' ) );
        add_action( 'wp_ajax_nopriv_quicklinks', array( $this, 'uams_quicklinks_feed' ) );
    }

    public function register_quick_links_menu()
    {
        register_nav_menu( self::LOCATION, __( self::NAME ) );
    }

    public function uams_quicklinks_feed()
    {
        if ( $this->MULTISITE ) {
            switch_to_blog( self::ALLOWED_BLOG );
        }

        $locations = get_nav_menu_locations();
        if ( isset( $locations[ self::LOCATION ] ) ) {
            $this->items = wp_get_nav_menu_items( $locations[ self::LOCATION ] );
        } elseif ( $location = wp_get_nav_menu_object( self::LOCATION ) ) {
            $this->items = wp_get_nav_menu_items( $location->term_id );
        }

        if ( $this->MULTISITE ) {
            restore_current_blog();
        }

        wp_send_json( $this->parse_menu() );
    }

    public function parse_menu()
    {
        $menu = array();

        if ( ! empty( $this->items ) && is_array( $this->items ) ) {
            foreach ( $this->items as $item ) {
                $item_array = array_intersect_key(
                    (array) $item,
                    array_fill_keys( array( 'ID', 'title', 'url', 'classes', 'menu_item_parent' ), null )
                );

                if ( empty( $item_array['classes'] ) || empty( $item_array['classes'][0] ) ) {
                    $item_array['classes'] = false;
                }

                $menu[] = $item_array;
            }
        }

        return $menu;
    }
}

new UAMS_QuickLinks();
