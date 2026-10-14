<?php

/* box shortcode:
 * meant for front page
 * boxes contain tiles. Boxes support only tiles inside and only between 1 and 12 tiles.
 *
 * structure: [box alignment="centered" shadow="none" padding="none"][tile][/tile][tile][/tile][/box]
 */

class TileBox
{
    const MAXTILES = 12;
    private $count = 0;
    private $NumbersArray = array( 'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve' );

    public function __construct()
    {
        add_filter( 'the_content', array( $this, 'wpex_fix_shortcodes' ) );
        add_shortcode( 'box', array( $this, 'box_handler' ) );
        add_shortcode( 'tile', array( $this, 'tile_handler' ) );
    }

    public function wpex_fix_shortcodes( $content )
    {
        if ( empty( $content ) || ! is_string( $content ) ) {
            return (string) $content;
        }

        $array = array(
            '<p>['    => '[',
            ']</p>'   => ']',
            ']<br />' => ']',
        );

        return strtr( $content, $array );
    }

    public function box_handler( $atts, $content = null )
    {
        $boxCenter = shortcode_atts( array(
            'alignment' => 'none',
            'color'     => '',
            'padding'   => '',
            'shadow'    => '',
            'custom'    => '',
        ), $atts, 'box' );

        $color   = ! empty( $boxCenter['color'] ) ? ' box-' . sanitize_html_class( $boxCenter['color'] ) : '';
        $padding = ! empty( $boxCenter['padding'] ) ? ' nopad' : '';
        $shadow  = ! empty( $boxCenter['shadow'] ) ? ' noshadow' : '';
        $custom  = ! empty( $boxCenter['custom'] ) ? ' ' . sanitize_html_class( $boxCenter['custom'] ) : '';
        $center  = 'box-' . sanitize_html_class( $boxCenter['alignment'] );

        $this->count = 0;

        if ( empty( $content ) ) {
            return 'No content inside the box element. Make sure you close your box element. Required structure: [box][tile]content[/tile][/box]';
        }

        $output    = do_shortcode( $content );
        $count_key = min( $this->count, self::MAXTILES );
        $number    = isset( $this->NumbersArray[ $count_key ] ) ? $this->NumbersArray[ $count_key ] : 'twelve';

        return sprintf(
            '<div class="box-outer"><div class="box %s %s%s%s%s%s">%s</div></div>',
            esc_attr( $number ),
            esc_attr( $center ),
            esc_attr( $color ),
            esc_attr( $padding ),
            esc_attr( $shadow ),
            esc_attr( $custom ),
            $output
        );
    }

    public function tile_handler( $atts, $content = null )
    {
        $this->count++;
        $tile_atts = shortcode_atts( array(
            'empty' => 'false',
        ), $atts, 'tile' );

        $classes = 'tile';

        if ( $this->count > self::MAXTILES ) {
            $content = 'Too many [tile]s. Only up to 12 are supported.';
        }

        if ( filter_var( $tile_atts['empty'], FILTER_VALIDATE_BOOLEAN ) ) {
            $classes .= ' empty';
        } elseif ( empty( $content ) ) {
            $content = 'No content for this tile. Make sure you wrap your content like this: [tile]Content here[/tile]';
        }

        return sprintf( '<div class="%s">%s</div>', esc_attr( $classes ), apply_filters( 'the_content', (string) $content ) );
    }
}

new TileBox();
