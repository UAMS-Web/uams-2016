<?php

class UAMS_GoogleApps
{
    public function __construct()
    {
        // Turns an iframe into a shortcode for parsing
        add_filter( 'pre_kses', array( $this, 'uams_google_calendar_embed_to_shortcode' ) );
        // GoogleApps shortcode
        add_shortcode( 'googleapps', array( $this, 'uams_google_calendar_shortcode' ) );
    }

    public function uams_google_calendar_shortcode( $atts )
    {
        $params = shortcode_atts( array(
            'query'  => '',
            'dir'    => '',
            'domain' => 'www',
            'width'  => 620,
            'height' => 500,
            'app'    => 'calendar',
        ), $atts, 'googleapps' );

        if ( 'calendar/embed' === $params['dir'] ) {
            return sprintf(
                '<div class="googleapps-%s"><iframe width="%s" height="%s" style="border:0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.google.com/calendar/embed?%s"></iframe></div>',
                esc_attr( $params['app'] ),
                esc_attr( $params['width'] ),
                esc_attr( $params['height'] ),
                esc_attr( $params['query'] )
            );
        }

        return '';
    }

    public function uams_google_calendar_embed_to_shortcode( $content )
    {
        if ( empty( $content ) || ! is_string( $content ) ) {
            return $content;
        }

        if ( false === strpos( $content, '<iframe ' ) && false === strpos( $content, 'google.com/calendar' ) ) {
            return $content;
        }

        $content = preg_replace_callback( '#&lt;iframe\s[^&]*?(?:&(?!gt;)[^&]*?)*?src="https?://.*?\.google\.(.*?)/(.*?)\?(.+?)"[^&]*?(?:&(?!gt;)[^&]*?)*?&gt;\s*&lt;/iframe&gt;\s*(?:&lt;br\s*/?&gt;)?\s*#i', array( $this, 'uams_google_calendar_embed_to_shortcode_callback' ), $content );

        $content = preg_replace_callback( '!\<iframe\s[^>]*?src="https?://.*?\.google\.(.*?)/(.*?)\?(.+?)"[^>]*?\>\s*\</iframe\>\s*!i', array( $this, 'uams_google_calendar_embed_to_shortcode_callback' ), $content );

        return $content;
    }

    public function uams_google_calendar_embed_to_shortcode_callback( $match )
    {
        if ( preg_match( '/\bwidth=[\'"](\d+)/', $match[0], $width ) ) {
            $width = min( array( (int) $width[1], 630 ) );
        } else {
            $width = 630;
        }

        if ( preg_match( '/\bheight=[\'"](\d+)/', $match[0], $height ) ) {
            $height = (int) $height[1];
        } else {
            $height = 500;
        }

        $url = isset( $match[3] ) ? $match[3] : '';

        return sprintf(
            '[googleapps domain="www" dir="calendar/embed" query="%s" width="%d" height="%d"]',
            esc_attr( $url ),
            (int) $width,
            (int) $height
        );
    }
}

new UAMS_GoogleApps();
