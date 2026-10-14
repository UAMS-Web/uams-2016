<?php
/**
 * Adds an enclosure to the RSS feed for post items that have a featured image.
 */

class UAMS_Enclosure
{
    public function __construct()
    {
        add_action( 'rss2_item', array( $this, 'add_post_featured_image_as_rss_item_enclosure' ) );
    }

    public function add_post_featured_image_as_rss_item_enclosure()
    {
        if ( ! has_post_thumbnail() ) {
            return;
        }

        $thumbnail_size = apply_filters( 'rss_enclosure_image_size', 'thumbnail' );
        $thumbnail_id   = get_post_thumbnail_id( get_the_ID() );
        $thumbnail      = image_get_intermediate_size( $thumbnail_id, $thumbnail_size );

        if ( empty( $thumbnail ) || empty( $thumbnail['url'] ) || empty( $thumbnail['path'] ) ) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $file_path  = path_join( $upload_dir['basedir'], $thumbnail['path'] );
        $file_size  = file_exists( $file_path ) ? filesize( $file_path ) : 0;

        printf(
            '<enclosure url="%s" length="%s" type="%s" />',
            esc_url( $thumbnail['url'] ),
            (int) $file_size,
            esc_attr( (string) get_post_mime_type( $thumbnail_id ) )
        );
    }
}

new UAMS_Enclosure();
