<?php

/*
 *  Button shortcode allows for styled buttons to be added to content
 *  [button color='gray' type='type' url='link url' size='small']Button Text[/button]
 *  optional small attribute makes the button small.  Assume large if not present
 */

class UAMS_Button
{
    private static $types = array( 'plus', 'go', 'external', 'play' );

    public function __construct()
    {
        add_shortcode( 'button', array( $this, 'button_handler' ) );
    }

    public function button_handler( $atts, $content = null )
    {
        $atts = shortcode_atts( array(
            'color' => 'none',
            'type'  => '',
            'url'   => '#',
            'size'  => '',
            'small' => null,
            'text'  => '',
        ), $atts, 'button' );

        $classes = array( 'uams-btn' );
        $color   = 'btn-' . sanitize_html_class( $atts['color'] );

        if ( empty( $content ) && empty( $atts['text'] ) ) {
            return 'No text in this button';
        }

        if ( ! empty( $atts['type'] ) ) {
            $type = strtolower( trim( $atts['type'] ) );
            if ( in_array( $type, self::$types, true ) ) {
                $classes[] = 'btn-' . $type;
            }
        }

        $url = ! empty( $atts['url'] ) ? esc_url( $atts['url'] ) : '#';

        if ( null !== $atts['small'] ) {
            $classes[] = 'btn-sm';
        }

        if ( ! empty( $atts['size'] ) ) {
            if ( in_array( $atts['size'], array( 'small', 'sm' ), true ) ) {
                $classes[] = 'btn-sm';
            } elseif ( in_array( $atts['size'], array( 'large', 'lg' ), true ) ) {
                $classes[] = 'btn-lg';
            }
        }

        if ( ! empty( $atts['text'] ) ) {
            $content = $atts['text'];
        }

        $class_string = implode( ' ', array_unique( $classes ) );

        return sprintf(
            '<a class="%s %s" href="%s">%s</a>',
            esc_attr( $class_string ),
            esc_attr( $color ),
            $url,
            esc_html( $content )
        );
    }
}

new UAMS_Button();
