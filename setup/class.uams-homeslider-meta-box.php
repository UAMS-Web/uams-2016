<?php

class UAMS_Home_Slider_Meta_Box
{
    public function __construct()
    {
        add_action( 'add_meta_boxes', array( $this, 'hs_add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save' ) );
    }

    public function hs_add_meta_box( $post_type )
    {
        $post_types = array( 'home_slider' );

        if ( in_array( $post_type, $post_types, true ) ) {
            add_meta_box(
                'hs-meta',
                'Home Slider Options',
                array( $this, 'hs_meta_box_function' ),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function hs_meta_box_function( $post )
    {
        wp_nonce_field( 'mobileimage_nonce', 'mobileimage_name' );

        $mobileimage = get_post_meta( $post->ID, 'mobileimage', true );
        $slidelink   = get_post_meta( $post->ID, 'slidelink', true );

        echo '<div>';
        echo "<p><b>Slide Link</b><br /><input type='text' class='text' name='slidelink' value='" . esc_attr( $slidelink ) . "'></p>";
        echo "<p><b>Mobile Slider Image</b><br /><input type='text' class='meta-image' name='mobileimagetext' value='" . esc_attr( $mobileimage ) . "'><input type='button' class='button image-upload' value='Browse'></p>";
        echo "<div class='image-preview'><img src='" . esc_url( $mobileimage ) . "' style='max-width: 140px;'></div>";
        echo '</div>';
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var meta_image_frame;
                $('.image-upload').click(function (e) {
                    e.preventDefault();
                    var meta_image = $(this).parent().children('.meta-image');

                    if (meta_image_frame) {
                        meta_image_frame.open();
                        return;
                    }
                    meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
                        title: meta_image.attr('title') || 'Select Image',
                        button: {
                            text: 'Use this image'
                        }
                    });
                    meta_image_frame.on('select', function () {
                        var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
                        meta_image.val(media_attachment.url);
                    });
                    meta_image_frame.open();
                });
            });
        </script>
        <?php
    }

    public function save( $post_id )
    {
        if ( ! isset( $_POST['mobileimage_name'] ) ) {
            return $post_id;
        }

        if ( ! wp_verify_nonce( $_POST['mobileimage_name'], 'mobileimage_nonce' ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( isset( $_POST['post_type'] ) && 'home_slider' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        if ( isset( $_POST['mobileimagetext'] ) ) {
            update_post_meta( $post_id, 'mobileimage', sanitize_text_field( $_POST['mobileimagetext'] ) );
        }

        if ( isset( $_POST['slidelink'] ) ) {
            update_post_meta( $post_id, 'slidelink', sanitize_text_field( $_POST['slidelink'] ) );
        }
    }
}

new UAMS_Home_Slider_Meta_Box();
