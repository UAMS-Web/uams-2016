<?php

/**
 * This shortcode allows iFrames for editors.
 */
class UAMS_Iframes
{
    public $ALLOWED_IFRAMES = array();

    public function __construct()
    {
        $this->ALLOWED_IFRAMES = $this->get_iframe_domains();
        add_shortcode( 'iframe', array( $this, 'add_iframe' ) );
    }

    public function add_iframe( $atts )
    {
        $params = shortcode_atts( array(
            'src'    => '',
            'height' => get_option( 'embed_size_h' ),
            'width'  => get_option( 'embed_size_w' ),
        ), $atts, 'iframe' );

        $params['src'] = esc_url( $params['src'], array( 'http', 'https' ) );
        if ( empty( $params['src'] ) ) {
            return '';
        }

        $parsed = parse_url( $params['src'] );
        if ( isset( $parsed['host'] ) && ! in_array( $parsed['host'], $this->ALLOWED_IFRAMES, true ) ) {
            return '';
        }

        $iframeSrc          = html_entity_decode( $params['src'] );
        $iframeQueryString  = parse_url( $iframeSrc, PHP_URL_QUERY );
        $parentQueryString  = ! empty( $_GET ) ? http_build_query( $_GET ) : '';

        if ( ! empty( $iframeQueryString ) && ! empty( $parentQueryString ) ) {
            parse_str( (string) $iframeQueryString, $iframeQueryParams );
            parse_str( (string) $parentQueryString, $parentQueryParams );
            $query_merged = array_merge( (array) $iframeQueryParams, (array) $parentQueryParams );
            $iframeSrc    = str_replace( $iframeQueryString, http_build_query( $query_merged ), $iframeSrc );
        } elseif ( ! empty( $parentQueryString ) ) {
            $iframeSrc .= '?' . $parentQueryString;
        }

        $iframeSrc = esc_url( $iframeSrc, array( 'http', 'https' ) );

        return sprintf(
            '<iframe src="%s" width="%s" height="%s" style="border:0"></iframe>',
            $iframeSrc,
            esc_attr( $params['width'] ),
            esc_attr( $params['height'] )
        );
    }

    public function get_iframe_domains()
    {
        return array(
            'uams.edu',
            'www.uams.edu',
            'uamshealth.com',
            'uamsonlinedev.com',
            'google.com',
            'docs.google.com',
            'youtube.com',
            'www.googletagmanager.com',
            'www.google.com',
            'www.youtube.com',
            'pgcalc.com',
            'www.pgcalc.com',
            'storify.com',
            'api.soundcloud.com',
            'flickr.com',
            'vimeo.com',
            'player.vimeo.com',
            'www.facebook.com',
            'facebook.com',
            'monday.com',
            'forms.monday.com',
        );
    }
}

new UAMS_Iframes();
