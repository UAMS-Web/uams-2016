<?php
/**
 * Adds a media credit to images in the media library
 */

class UAMS_Media_Credit
{
    public function __construct()
    {
        add_filter( 'mce_external_plugins', array( $this, 'add_media_credit_shortcode_to_tinymce' ) );
        add_filter( 'image_send_to_editor', array( $this, 'mediacredit_tinymce_html' ), 10, 7 );
        add_shortcode( 'mediacredit', array( $this, 'mediacredit_html' ) );
        add_filter( 'attachment_fields_to_edit', array( $this, 'image_attachment_fields_to_edit' ), 100, 2 );
        add_filter( 'attachment_fields_to_save', array( $this, 'custom_image_attachment_fields_to_save' ), 10, 2 );
    }

    public function add_media_credit_shortcode_to_tinymce( $plugins )
    {
        $plugins = is_array( $plugins ) ? $plugins : array();
        $plugins['mediacredit'] = get_template_directory_uri() . '/assets/admin/js/media-credit.js';
        return $plugins;
    }

    public function mediacredit_tinymce_html( $html, $id, $caption, $title, $align, $url, $size )
    {
        if ( ! empty( $caption ) ) {
            return $html;
        }

        $credit = get_post_meta( (int) $id, '_media_credit', true );
        $img    = wp_get_attachment_image_src( (int) $id, $size );
        $width  = isset( $img[1] ) ? (int) $img[1] : 0;

        return ! empty( $credit ) ?
            "<dl class='mediacredit align" . esc_attr( $align ) . "' data-credit='" . esc_attr( $credit ) . "' data-align='align" . esc_attr( $align ) . "' data-size='" . esc_attr( $size ) . "' style='width:{$width}px'>
                <dt class='mediacredit-dt'>{$html}</dt>
                <dd class='wp-caption-dd'>" . esc_html( $credit ) . "</dd>
            </dl>" : $html;
    }

    public function mediacredit_html( $attrs, $content = null )
    {
        $atts = shortcode_atts( array(
            'id'     => '',
            'align'  => '',
            'size'   => 'full',
            'credit' => '',
        ), $attrs, 'mediacredit' );

        $post_id = (int) $atts['id'];
        if ( 1 > $post_id ) {
            return '';
        }

        $img   = wp_get_attachment_image_src( $post_id, $atts['size'] );
        $width = isset( $img[1] ) ? (int) $img[1] : 0;

        return '<div class="wp-caption ' . esc_attr( $atts['align'] ) . '" style="width:' . ( 10 + $width ) . 'px">' .
            do_shortcode( (string) $content ) . '<p class="wp-media-credit">Image by ' . esc_html( $atts['credit'] ) . '</p></div>';
    }

    public function image_attachment_fields_to_edit( $form_fields, $post )
    {
        $post_id = ( $post instanceof WP_Post ) ? $post->ID : (int) $post;
        $context = (array) get_post_meta( $post_id, '_wp_attachment_context' );

        if ( ! in_array( 'custom-header', $context, true ) && wp_attachment_is_image( $post_id ) ) {
            $form_fields['media_credit'] = array(
                'label' => __( 'Image Credit' ),
                'input' => 'text',
                'value' => get_post_meta( $post_id, '_media_credit', true ),
            );
        }

        return $form_fields;
    }

    public function custom_image_attachment_fields_to_save( $post, $attachment )
    {
        if ( isset( $attachment['media_credit'], $post['ID'] ) ) {
            update_post_meta( (int) $post['ID'], '_media_credit', sanitize_text_field( $attachment['media_credit'] ) );
        }
        return $post;
    }
}

new UAMS_Media_Credit();
