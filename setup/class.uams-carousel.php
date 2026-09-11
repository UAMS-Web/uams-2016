<?php

/*
Plugin Name: UAMS Carousel
Plugin URL: http://uw.edu
Description: Transform your standard image galleries into an immersive full-screen experience.
Version: 0.1
Author: Automattic
*/

class UAMS_Carousel {

    const COLUMNS = 5;
    const LINK    = 'none';

    public $prebuilt_widths = array( 370, 700, 1000, 1200, 1400, 2000 );
    public $first_run       = true;

    public function __construct()
    {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init()
    {
        if ( $this->disable() ) {
            return;
        }

        $this->prebuilt_widths = apply_filters( 'jp_carousel_widths', $this->prebuilt_widths );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'gallery_style', array( $this, 'add_data_to_container' ) );
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_data_to_images' ), 10, 2 );
    }

    public function disable()
    {
        return apply_filters( 'uams_carousel_disable', false );
    }

    public function asset_version( $version )
    {
        return apply_filters( 'jp_carousel_asset_version', $version );
    }

    public function enqueue_assets( $output = '' )
    {
        if ( ! empty( $output ) && ! apply_filters( 'jp_carousel_force_enable', false ) ) {
            remove_filter( 'gallery_style', array( $this, 'add_data_to_container' ) );
            remove_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_data_to_images' ) );
            return $output;
        }

        do_action( 'jp_carousel_thumbnails_shown' );

        $is_logged_in        = is_user_logged_in();
        $require_name_email  = (int) get_option( 'require_name_email' );

        $localize_strings = array(
            'widths'             => $this->prebuilt_widths,
            'is_logged_in'       => $is_logged_in,
            'lang'               => strtolower( substr( (string) get_locale(), 0, 2 ) ),
            'ajaxurl'            => set_url_scheme( admin_url( 'admin-ajax.php' ) ),
            'nonce'              => wp_create_nonce( 'carousel_nonce' ),
            'display_exif'       => true,
            'display_geo'        => false,
            'background_color'   => 'white',
            'download_original'  => sprintf( __( 'View full size <span class="photo-size">%1$s<span class="photo-size-times">&times;</span>%2$s</span>', 'jetpack' ), '{0}', '{1}' ),
            'camera'             => __( 'Camera', 'jetpack' ),
            'aperture'           => __( 'Aperture', 'jetpack' ),
            'shutter_speed'      => __( 'Shutter Speed', 'jetpack' ),
            'focal_length'       => __( 'Focal Length', 'jetpack' ),
            'require_name_email' => $require_name_email,
            'login_url'          => wp_login_url( apply_filters( 'the_permalink', get_permalink() ) ),
        );

        $localize_strings = apply_filters( 'jp_carousel_localize_strings', $localize_strings );
        wp_localize_script( 'site', 'jetpackCarouselStrings', $localize_strings );

        do_action( 'jp_carousel_enqueue_assets', $this->first_run, $localize_strings );

        $this->first_run = false;

        return $output;
    }

    public function add_data_to_images( $attr, $attachment = null )
    {
        if ( $this->first_run || empty( $attachment ) ) {
            return $attr;
        }

        $attachment_id   = (int) $attachment->ID;
        $orig_file       = wp_get_attachment_image_src( $attachment_id, 'original' );
        $orig_file       = isset( $orig_file[0] ) ? $orig_file[0] : wp_get_attachment_url( $attachment_id );
        $meta            = wp_get_attachment_metadata( $attachment_id );
        $size            = isset( $meta['width'], $meta['height'] ) ? (int) $meta['width'] . ',' . (int) $meta['height'] : '';
        $img_meta        = ! empty( $meta['image_meta'] ) ? (array) $meta['image_meta'] : array();

        $medium_file_info = wp_get_attachment_image_src( $attachment_id, 'medium' );
        $medium_file      = isset( $medium_file_info[0] ) ? $medium_file_info[0] : '';

        $large_file_info  = wp_get_attachment_image_src( $attachment_id, 'large' );
        $large_file       = isset( $large_file_info[0] ) ? $large_file_info[0] : '';

        $post_obj         = get_post( $attachment_id );
        $attachment_title = $post_obj ? wptexturize( $post_obj->post_title ) : '';
        $attachment_desc  = $post_obj ? wpautop( wptexturize( $post_obj->post_content ) ) : '';

        if ( ! empty( $img_meta ) ) {
            unset( $img_meta['latitude'], $img_meta['longitude'] );
        }

        $json_img_meta = json_encode( array_map( 'strval', $img_meta ) );

        $attr['data-attachment-id']     = $attachment_id;
        $attr['data-orig-file']         = esc_attr( $orig_file );
        $attr['data-orig-size']         = $size;
        $attr['data-comments-opened']   = '0';
        $attr['data-image-meta']        = esc_attr( $json_img_meta );
        $attr['data-image-title']       = esc_attr( $attachment_title );
        $attr['data-image-description'] = esc_attr( $attachment_desc );
        $attr['data-medium-file']       = esc_attr( $medium_file );
        $attr['data-large-file']        = esc_attr( $large_file );

        return $attr;
    }

    public function add_data_to_container( $html )
    {
        global $post;

        if ( isset( $post ) && $post instanceof WP_Post ) {
            $blog_id = (int) get_current_blog_id();

            $extra_data = array(
                'data-carousel-extra' => array(
                    'blog_id'   => $blog_id,
                    'permalink' => get_permalink( $post->ID ),
                ),
            );

            $extra_data = apply_filters( 'jp_carousel_add_data_to_container', $extra_data );

            foreach ( (array) $extra_data as $data_key => $data_values ) {
                $html = str_replace( '<div ', '<div ' . esc_attr( $data_key ) . "='" . esc_attr( json_encode( $data_values ) ) . "' ", $html );
            }
        }

        return $html;
    }
}
