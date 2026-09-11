<?php

/*
 *  YouTube shortcode allows for YouTube video and/or playlist to be added to content
 *  [youtube type='single' id='xxxx' max-results='5']
 */

class UAMS_YouTube
{
    public static $types = array( 'playlist', 'single' );

    public function __construct()
    {
        add_shortcode( 'youtube', array( $this, 'youtube_handler' ) );
    }

    public function youtube_handler( $atts )
    {
        $params = shortcode_atts( array(
            'type'        => '',
            'id'          => '',
            'max-results' => 0,
            'max_results' => 0,
        ), $atts, 'youtube' );

        $type = strtolower( trim( $params['type'] ) );

        if ( empty( $type ) ) {
            return 'required attribute "type" missing';
        }

        if ( ! in_array( $type, self::$types, true ) ) {
            return sprintf( 'youtube type "%s" not supported', esc_html( $type ) );
        }

        $id = sanitize_text_field( $params['id'] );
        if ( empty( $id ) ) {
            return 'required attribute "id" missing';
        }

        $max_results = (int) ( $params['max-results'] ?: $params['max_results'] );
        $el_id       = 'uams-youtube-' . wp_rand( 0, 1000 );

        if ( $max_results > 0 ) {
            return sprintf(
                '<div id="%s" class="uams-youtube" data-uams-youtube-type="%s" data-uams-youtube="%s" data-max-results="%d"></div>',
                esc_attr( $el_id ),
                esc_attr( $type ),
                esc_attr( $id ),
                $max_results
            );
        }

        return sprintf(
            '<div id="%s" class="uams-youtube" data-uams-youtube-type="%s" data-uams-youtube="%s"></div>',
            esc_attr( $el_id ),
            esc_attr( $type ),
            esc_attr( $id )
        );
    }
}

new UAMS_YouTube();
