<?php

/*
 * Shortcode for making Bootstrap grids
 */

class UAMS_TinyShortcode
{
    public $shortcodes = array(
        'grid',
        'button',
        'tilebox',
    );

    public function __construct()
    {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init()
    {
        if ( ! is_admin() ) {
            return;
        }

        wp_enqueue_style( 'bs_admin_style', get_template_directory_uri() . '/assets/admin/css/bootstrap-shortcode-admin.css', array(), null );

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( get_user_option( 'rich_editing' ) === 'true' ) {
            add_filter( 'mce_external_plugins', array( $this, 'regplugins' ) );
            add_filter( 'mce_buttons_3', array( $this, 'regbtns' ) );
        }
    }

    public function regbtns( $buttons )
    {
        $buttons = is_array( $buttons ) ? $buttons : array();
        foreach ( $this->shortcodes as $shortcode ) {
            $buttons[] = 'bs_' . $shortcode;
        }
        return $buttons;
    }

    public function regplugins( $plgs )
    {
        $plgs = is_array( $plgs ) ? $plgs : array();
        foreach ( $this->shortcodes as $shortcode ) {
            $plgs[ 'bs_' . $shortcode ] = get_template_directory_uri() . '/js/plugins/' . $shortcode . '.js';
        }
        return $plgs;
    }
}

new UAMS_TinyShortcode();
