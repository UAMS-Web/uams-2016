<?php

//
// Adds a media caption to images in the media library
//

class UAMS_Media_Caption
{
    public function __construct()
    {
        add_filter( 'img_caption_shortcode', array( $this, 'add_media_credit_to_caption_shortcode_filter' ), 10, 3 );
    }

    public function add_media_credit_to_caption_shortcode_filter( $val, $attr, $content = null )
    {
        $atts = shortcode_atts( array(
            'id'      => '',
            'align'   => '',
            'width'   => '',
            'caption' => '',
        ), $attr, 'caption' );

        $width   = (int) $atts['width'];
        $caption = $atts['caption'];
        $id      = $atts['id'];
        $align   = $atts['align'];

        if ( 1 > $width || empty( $caption ) ) {
            return $content;
        }

        $id_attr = '';
        $credit  = '';

        if ( ! empty( $id ) ) {
            $id_attr = 'id="' . esc_attr( $id ) . '" ';
            if ( preg_match( '/\d+/', $id, $match ) ) {
                $meta_credit = get_post_meta( (int) $match[0], '_media_credit', true );
                if ( ! empty( $meta_credit ) ) {
                    $credit = '<span class="wp-media-credit">' . esc_html( $meta_credit ) . '</span>';
                }
            }
        }

        return '<div ' . $id_attr . 'class="wp-caption ' . esc_attr( $align ) . '" style="width: ' . ( 10 + $width ) . 'px">'
            . do_shortcode( (string) $content ) . '<p class="wp-caption-text">' . $caption . $credit . '</p></div>';
    }
}

new UAMS_Media_Caption();
