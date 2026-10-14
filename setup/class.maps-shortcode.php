<?php

/*
 * Maps shortcode allows for map area to be added to content
 * [map building='135' width='100%' height='480'][/map]
 * Width default is 100%. Height default is 480px.
 */

class UAMS_Map
{
    private static $buildingcode = array(
        '127', '116', '117', '118', '119', '120', '121', '122', '123', '124',
        '125', '128', '129', '126', '131', '130', '132', '133', '134', '135',
        '136', '137', '138', '139', '141', '142', '143', '144', '145', '146',
        '147', '148', '149', '150', '151', '152', '153', '154', '155', '2',
        '3', '4', '7', '6',
    );

    const URL = '//maps.uams.edu/full-screen/?markerid=';

    public function __construct()
    {
        add_shortcode( 'map', array( $this, 'map_handler' ) );
    }

    public function map_handler( $atts, $content = null )
    {
        $params = shortcode_atts(
            array(
                'building' => '',
                'width'    => '100%',
                'height'   => '480px',
            ),
            $atts,
            'map'
        );

        $building = sanitize_text_field( $params['building'] );

        if ( empty( $building ) ) {
            return 'required attribute "building" missing';
        }

        if ( ! in_array( $building, self::$buildingcode, true ) ) {
            return sprintf( 'Building "%s" is not supported', esc_html( $building ) );
        }

        $width  = esc_attr( $params['width'] );
        $height = esc_attr( $params['height'] );

        return sprintf(
            '<div class="uams-campus-map">
                <iframe width="%s" height="%s" src="%s%s" frameborder="0"></iframe>
                <a href="https://maps.uams.edu/map-mashup/?markerid=%s" target="_blank" rel="noopener noreferrer">View full map</a>
            </div>',
            $width,
            $height,
            esc_url( self::URL ),
            rawurlencode( $building ),
            rawurlencode( $building )
        );
    }
}

new UAMS_Map();
