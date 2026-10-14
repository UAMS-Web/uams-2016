<?php

/**
 * UAMS Replace Media adds a 'Replace media' button on PDF files.
 */

class UAMS_Replace_Media
{
    public function __construct()
    {
        add_filter( 'attachment_fields_to_edit', array( $this, 'uams_edit_attachment_slug' ), 10, 2 );
        add_filter( 'attachment_fields_to_save', array( $this, 'uams_save_attachment_slug' ), 10, 2 );
    }

    public function uams_edit_attachment_slug( $fields, $post )
    {
        wp_enqueue_media();

        $post_id   = ( $post instanceof WP_Post ) ? $post->ID : (int) $post;
        $mime_type = (string) get_post_mime_type( $post_id );

        if ( false !== strpos( $mime_type, 'application/' ) ) {
            $nonce_field = wp_nonce_field( 'replace-media-' . $post_id, 'replace-media-nonce', true, false );

            $fields['replace_media'] = array(
                'label' => __( 'Replace Media' ),
                'input' => 'html',
                'html'  => '
                <style type="text/css">
                div.media-sidebar tr.compat-field-replace_media {
                  display:none;
                }
                </style>
                <div class="wp-media-buttons uams-replace-media">
                  <a href="#" class="button replace_media add_media" title="Replace Media">
                    <span class="wp-media-buttons-icon"></span>
                    Replace
                  </a>
                  <em class="help"></em>
                  ' . $nonce_field . '
                  <input id="replace-media-input-' . $post_id . '" type="hidden" name="replace_media_for_' . $post_id . '" value=""/>
                </div>

                <script type="text/javascript">
                var file_frame;
                jQuery(document).on("click", ".replace_media", function( event ){
                  event.preventDefault();

                  if ( file_frame ) {
                    file_frame.open();
                    return;
                  }

                  file_frame = wp.media.frames.file_frame = wp.media({
                    title: "Replace Media",
                    button: { text: "Select" },
                    props: { order: "ASC" },
                    library: { type: "application" },
                    multiple: false
                  });

                  file_frame.on( "select", function() {
                    jQuery("#replace-media-alert").remove();
                    var attachment = file_frame.state().get("selection").first().toJSON();

                    if ( attachment.id == ' . $post_id . ' ) {
                        jQuery("form#post").before("<div id=\"replace-media-alert\" class=\"updated below-h2\"><p>The media you selected is the same file as this media.</p></div>");
                        return;
                    }

                    jQuery("#replace-media-input-' . $post_id . '").val(attachment.id);
                    jQuery("form#post").before("<div id=\"replace-media-alert\" class=\"error below-h2\"><p>This media will be <b>permanently replaced</b> by <b>\"" + attachment.title + "\"</b> on " + (jQuery("#publish").val() || "Save" ) + "</p></div>");
                  });

                  file_frame.open();
                });
                </script>
                ',
            );
        }

        return $fields;
    }

    public function uams_save_attachment_slug( $attachment, $post_data )
    {
        $currentMediaID = isset( $attachment['post_ID'] ) ? (int) $attachment['post_ID'] : ( isset( $attachment['ID'] ) ? (int) $attachment['ID'] : 0 );

        if ( $currentMediaID > 0 ) {
            $mime_type = (string) get_post_mime_type( $currentMediaID );

            if ( false !== strpos( $mime_type, 'application/' ) && ! empty( $attachment[ 'replace_media_for_' . $currentMediaID ] ) ) {
                if ( empty( $attachment['replace-media-nonce'] ) || ! wp_verify_nonce( $attachment['replace-media-nonce'], 'replace-media-' . $currentMediaID ) ) {
                    wp_die( 'You do not have access to replace the media file' );
                }

                $newMediaID = (int) $attachment[ 'replace_media_for_' . $currentMediaID ];

                if ( $currentMediaID !== $newMediaID ) {
                    $currentMedia = get_attached_file( $currentMediaID );
                    $newMedia     = get_attached_file( $newMediaID );

                    if ( $newMedia && file_exists( $newMedia ) && $currentMedia ) {
                        copy( $newMedia, $currentMedia );
                        wp_delete_attachment( $newMediaID );
                    }
                }
            }
        }

        if ( ! empty( $post_data['post_name'] ) ) {
            $attachment['post_name'] = $post_data['post_name'];
        }

        return $attachment;
    }
}

new UAMS_Replace_Media();
