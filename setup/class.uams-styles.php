<?php

/**
 * Install stylesheets
 */

class UAMS_Styles
{
    public $STYLES;

    public function __construct()
    {
        $this->STYLES = array(
            'uams-master' => array(
                'id'      => 'uams-master',
                'url'     => get_template_directory_uri() . '/style' . $this->dev_stylesheet() . '.css',
                'deps'    => array(),
                'version' => '3.6',
            ),
            'uams-style'  => array(
                'id'      => 'uams-style',
                'url'     => get_stylesheet_uri(),
                'deps'    => array( 'uams-master' ),
                'version' => '3.6',
                'child'   => true,
            ),
        );

        add_action( 'wp_enqueue_scripts', array( $this, 'uams_register_default_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'uams_enqueue_default_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'uams_enqueue_admin_styles' ) );
    }

    public function uams_register_default_styles()
    {
        foreach ( $this->STYLES as $style ) {
            wp_register_style(
                $style['id'],
                $style['url'],
                $style['deps'] ?? array(),
                $style['version'] ?? false
            );
        }
    }

    public function uams_enqueue_default_styles()
    {
        wp_enqueue_style( 'uams-master' );

        foreach ( $this->STYLES as $style ) {
            if ( ! empty( $style['child'] ) && ! $this->is_child_theme() ) {
                continue;
            }

            wp_enqueue_style( $style['id'] );
        }
    }

    public function uams_enqueue_admin_styles()
    {
        if ( ! is_admin() ) {
            return;
        }

        foreach ( $this->STYLES as $style ) {
            if ( ! empty( $style['admin'] ) ) {
                wp_register_style(
                    $style['id'],
                    $style['url'],
                    $style['deps'] ?? array(),
                    $style['version'] ?? false
                );

                wp_enqueue_style( $style['id'] );
            }
        }
    }

    public function is_child_theme()
    {
        return get_template_directory() !== get_stylesheet_directory();
    }

    public function dev_stylesheet()
    {
        return is_user_logged_in() ? '.dev' : '';
    }
}
