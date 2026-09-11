<?php

/*
Shortcode for using the Links list found in the dashboard

Example:
[bookmark category_name=pfw categorize="0" title_li=""]
[row]
    [col class='col-md-12']Text[/col]
[/row]
*/

class UAMS_GridShortcode
{
    public function __construct()
    {
        add_shortcode( 'row', array( $this, 'bs_row' ) );
        add_shortcode( 'col', array( $this, 'bs_span' ) );
    }

    public function bs_row( $params, $content = '' )
    {
        $atts = shortcode_atts( array(
            'class' => 'row',
        ), $params, 'row' );

        $content = ! empty( $content ) ? preg_replace( '/<br class="nc".\/>/', '', (string) $content ) : '';
        $result  = '<div class="' . esc_attr( $atts['class'] ) . '">';
        $result .= do_shortcode( $content );
        $result .= '</div>';

        return force_balance_tags( $result );
    }

    public function bs_span( $params, $content = '' )
    {
        $atts = shortcode_atts( array(
            'class' => 'col-sm-1',
        ), $params, 'col' );

        $result  = '<div class="' . esc_attr( $atts['class'] ) . '">';
        $result .= do_shortcode( (string) $content );
        $result .= '</div>';

        return force_balance_tags( $result );
    }
}

new UAMS_GridShortcode();
