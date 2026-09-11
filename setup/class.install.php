<?php
/**
 * Sets up the theme
 *  - basic settings
 *  - header images
 *  - post thumbnails
 *  - automatic feed links
 */

class UAMS_Install_Theme
{
    const WIDTH = 750;

    public $DEFAULT_HEADERS = array(
        'blossoms' => array(
            'url'           => '%s/assets/headers/uams-pattern-grey.png',
            'thumbnail_url' => '%s/assets/headers/uams-pattern-grey-thumb.jpg',
            'description'   => 'UAMS Pattern',
        ),
    );

    public function __construct()
    {
        add_filter( 'embed_defaults', array( $this, 'uams_setup_embed_defaults' ), 1 );
        add_action( 'after_setup_theme', array( $this, 'uams_setup' ) );
    }

    public function uams_setup()
    {
        $defaultImage = reset( $this->DEFAULT_HEADERS );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'post-formats', array( 'gallery', 'link', 'audio' ) );

        add_theme_support( 'custom-header', array(
            'default-image'  => $defaultImage['url'],
            'random-default' => false,
            'header-text'    => false,
            'width'          => 1600,
            'height'         => 343,
            'uploads'        => true,
        ) );

        register_default_headers( $this->DEFAULT_HEADERS );
    }

    public function uams_setup_embed_defaults( $dimensions )
    {
        $dimensions = is_array( $dimensions ) ? $dimensions : array();
        $dimensions['width']  = self::WIDTH;
        $dimensions['height'] = min( ceil( self::WIDTH * 1.5 ), 1000 );
        return $dimensions;
    }
}
