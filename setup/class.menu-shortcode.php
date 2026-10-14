<?php

/*
 * Shortcode for embedding a menu on page
 * [custommenu menu=Menu-name-here class=class-name]
 */

class UAMS_MenuShortcode
{
    public function __construct()
    {
        add_shortcode( 'custommenu', array( $this, 'vo_custom_menu_shortcode' ) );
    }

    public function vo_custom_menu_shortcode( $atts, $content = null )
    {
        $params = shortcode_atts(
            array(
                'menu'            => '',
                'container'       => 'div',
                'container_class' => 'icon-menu',
                'container_id'    => '',
                'menu_class'      => 'menu',
                'menu_id'         => '',
                'fallback_cb'     => 'wp_page_menu',
                'before'          => '',
                'after'           => '',
                'link_before'     => '',
                'link_after'      => '',
                'depth'           => 0,
                'walker'          => '',
                'theme_location'  => '',
            ),
            $atts,
            'custommenu'
        );

        return wp_nav_menu(
            array(
                'menu'            => $params['menu'],
                'container'       => $params['container'],
                'container_class' => $params['container_class'],
                'container_id'    => $params['container_id'],
                'menu_class'      => $params['menu_class'],
                'menu_id'         => $params['menu_id'],
                'echo'            => false,
                'fallback_cb'     => $params['fallback_cb'],
                'before'          => $params['before'],
                'after'           => $params['after'],
                'link_before'     => $params['link_before'],
                'link_after'      => $params['link_after'],
                'depth'           => (int) $params['depth'],
                'walker'          => ! empty( $params['walker'] ) && class_exists( $params['walker'] ) ? new $params['walker']() : '',
                'theme_location'  => $params['theme_location'],
            )
        );
    }
}

new UAMS_MenuShortcode();
