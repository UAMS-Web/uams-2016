<?php

/**
 * UAMS Dropdowns
 * This installs the default dropdowns for the UAMS Theme
 */

class UAMS_Dropdowns
{
    const NAME           = 'White Bar';
    const LOCATION       = 'white-bar';
    const DISPLAY_NAME   = 'Dropdowns';
    const DEFAULT_STATUS = 'publish';

    public $menu_items = array();
    public $MENU_ID;

    public function __construct()
    {
        $this->menu_items = array();
        add_action( 'after_setup_theme', array( $this, 'register_white_bar_menu' ) );
        add_action( 'after_setup_theme', array( $this, 'install_default_white_bar_menu' ) );
        add_action( 'wp_update_nav_menu', array( $this, 'save_white_bar' ) );
    }

    public function register_white_bar_menu()
    {
        register_nav_menu( self::LOCATION, __( self::NAME ) );
    }

    public function install_default_white_bar_menu()
    {
        $this->generate_menu_list();
        $this->MENU_ID = wp_create_nav_menu( self::DISPLAY_NAME );

        // wp_create_nav_menu returns a WP_Error if the menu already exists
        if ( is_wp_error( $this->MENU_ID ) ) {
            return;
        }

        foreach ( $this->menu_items as $menu_name => $menu_attributes ) {
            $children = isset( $menu_attributes['children'] ) ? $menu_attributes['children'] : array();
            unset( $menu_attributes['children'] );

            $parent_id = wp_update_nav_menu_item( $this->MENU_ID, 0, $menu_attributes );

            if ( ! empty( $children ) && ! is_wp_error( $parent_id ) ) {
                foreach ( $children as $submenu ) {
                    $submenu['menu-item-parent-id'] = $parent_id;
                    wp_update_nav_menu_item( $this->MENU_ID, 0, $submenu );
                }
            }
        }

        $this->set_uams_menu_location();
    }

    public function set_uams_menu_location()
    {
        $locations = (array) get_theme_mod( 'nav_menu_locations' );
        $locations['white-bar'] = $this->MENU_ID;
        set_theme_mod( 'nav_menu_locations', $locations );
    }

    public function generate_menu_list()
    {
        // The default About dropdown.
        $this->add_menu_item( 'About', 'http://web.uams.edu/about/' );
        $this->add_menu_item( 'About UAMS', 'http://web.uams.edu/about/', 'About' );
        $this->add_menu_item( 'Vision, Mission & Core Values', 'http://web.uams.edu/about/vision-mission-core-values/', 'About' );
        $this->add_menu_item( 'Leadership', 'http://web.uams.edu/about/leadership/', 'About' );
        $this->add_menu_item( 'Fast Facts', 'http://web.uams.edu/about/fast-facts/', 'About' );
        $this->add_menu_item( 'History', 'http://web.uams.edu/about/uams-history/', 'About' );
        $this->add_menu_item( 'Contact Us', 'http://web.uams.edu/about/contact-information/', 'About' );

        // The default Academics dropdown.
        $this->add_menu_item( 'Academics', 'http://web.uams.edu/for-faculty-staff-and-students/' );
        $this->add_menu_item( 'Departments & Divisions', 'http://web.uams.edu/departments-and-divisions/', 'Academics' );
        $this->add_menu_item( 'Colleges', 'http://web.uams.edu/educational-programs-at-uams/', 'Academics' );
        $this->add_menu_item( 'Students', 'http://students.uams.edu', 'Academics' );
        $this->add_menu_item( 'Faculty & Administration', 'http://web.uams.edu/for-faculty-staff-and-students/', 'Academics' );

        // The default Admissions dropdown.
        $this->add_menu_item( 'Apply', 'http://students.uams.edu/apply/' );

        // The default News dropdown.
        $this->add_menu_item( 'News & Events', 'http://uamshealth.com/news/' );

        // The default Research dropdown.
        $this->add_menu_item( 'Research', 'http://research.uams.edu' );
    }

    public function add_menu_item( $name, $url, $parent = null )
    {
        $item = array(
            'menu-item-title'  => $name,
            'menu-item-url'    => $url,
            'menu-item-status' => self::DEFAULT_STATUS,
        );

        if ( $parent ) {
            $this->menu_items[ $parent ]['children'][ $name ] = $item;
        } else {
            $this->menu_items[ $name ] = $item;
        }
    }

    public function save_white_bar( $menu_id )
    {
        $menu_object = wp_get_nav_menu_object( $menu_id );
        if ( $menu_object && isset( $menu_object->slug ) && $menu_object->slug === 'dropdowns' ) {
            if ( ! current_user_can( 'manage_network' ) && ! current_user_can( 'administrator' ) ) {
                wp_die( 'Insufficient permission: cannot edit the default dropdowns menu.' );
            }
        }
    }
}
