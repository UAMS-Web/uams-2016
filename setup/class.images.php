<?php

//
// Installs the custom image sizes
//

class UAMS_Images
{
    // If `$show` is true it will appear in the image dropdown menu
    public $IMAGE_SIZES = array(
        'mug-shot' => array(
            'name'   => 'Mug Shot',
            'width'  => 150,
            'height' => 250,
            'crop'   => true,
            'show'   => true,
        ),
        'sidebar' => array(
            'name'   => 'Sidebar',
            'width'  => 375,
            'height' => 9999,
            'crop'   => false,
            'show'   => true,
        ),
        'half' => array(
            'name'   => 'Half width',
            'width'  => 375,
            'height' => 9999,
            'crop'   => false,
            'show'   => true,
        ),
        'full-content' => array(
            'name'   => 'Content area',
            'width'  => 750,
            'height' => 9999,
            'crop'   => false,
            'show'   => true,
        ),
        'page' => array(
            'name'   => 'Full page',
            'width'  => 1140,
            'height' => 9999,
            'crop'   => false,
            'show'   => true,
        ),
        'thimble' => array(
            'name'   => 'Thimble',
            'width'  => 50,
            'height' => 50,
            'crop'   => true,
            'show'   => false,
        ),
        'thumbnail-large' => array(
            'name'   => 'Thumbnail large',
            'width'  => 300,
            'height' => 300,
            'crop'   => true,
            'show'   => false,
        ),
        'rss' => array(
            'name'   => 'RSS',
            'width'  => 108,
            'height' => 81,
            'crop'   => true,
            'show'   => false,
        ),
    );

    public function __construct()
    {
        add_action( 'after_setup_theme', array( $this, 'add_uams_image_sizes' ) );
        add_filter( 'image_size_names_choose', array( $this, 'show_image_sizes' ) );
    }

    public function add_uams_image_sizes()
    {
        foreach ( $this->IMAGE_SIZES as $name => $image ) {
            add_image_size(
                $name,
                $image['width'],
                $image['height'],
                $image['crop']
            );
        }
    }

    public function show_image_sizes( $defaultSizes )
    {
        $defaultSizes = is_array( $defaultSizes ) ? $defaultSizes : array();
        $imagesToShow = array_filter( $this->IMAGE_SIZES, function( $image ) {
            return ! empty( $image['show'] );
        } );

        foreach ( $imagesToShow as $id => $image ) {
            $imagesToShow[ $id ] = $image['name'];
        }

        return array_merge( $imagesToShow, $defaultSizes );
    }
}
