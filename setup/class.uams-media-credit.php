<?php

/**
 * Adds a media credit to images in the media library
 */
class UAMS_Media_Credit
{
    public function __construct()
    {
        add_filter('mce_external_plugins', [$this, 'add_media_credit_shortcode_to_tinymce']);
        add_filter('image_send_to_editor', [$this, 'mediacredit_tinymce_html'], 10, 7);
        add_shortcode('mediacredit', [$this, 'mediacredit_html']);
        add_filter('attachment_fields_to_edit', [$this, 'image_attachment_fields_to_edit'], 100, 2);
        add_filter('attachment_fields_to_save', [$this, 'custom_image_attachment_fields_to_save'], 10, 2);
    }

    /**
     * Register the media credit plugin with TinyMCE.
     *
     * @param  mixed  $plugins  The external plugins; a non-array value is replaced with an empty array.
     * @return mixed[]
     */
    public function add_media_credit_shortcode_to_tinymce($plugins): array
    {
        $plugins = is_array($plugins) ? $plugins : [];
        $plugins['mediacredit'] = get_template_directory_uri().'/assets/admin/js/media-credit.js';

        return $plugins;
    }

    /**
     * Wrap an image inserted into the editor in a media credit block when it has a credit and no caption.
     *
     * @param  string  $html  The image markup.
     * @param  int  $id  The attachment ID.
     * @param  string  $caption  The image caption.
     * @param  string  $title  The image title.
     * @param  string  $align  The image alignment.
     * @param  string  $url  The image link URL.
     * @param  list<int>|string  $size  The image size.
     * @return string
     */
    public function mediacredit_tinymce_html($html, $id, $caption, $title, $align, $url, $size)
    {
        if (! empty($caption)) {
            return $html;
        }

        $credit = get_post_meta((int) $id, '_media_credit', true);
        $img = wp_get_attachment_image_src((int) $id, $size);
        $width = isset($img[1]) ? (int) $img[1] : 0;

        return ! empty($credit) ?
            "<dl class='mediacredit align".esc_attr($align)."' data-credit='".esc_attr($credit)."' data-align='align".esc_attr($align)."' data-size='".esc_attr($size)."' style='width:{$width}px'>
                <dt class='mediacredit-dt'>{$html}</dt>
                <dd class='wp-caption-dd'>".esc_html($credit).'</dd>
            </dl>' : $html;
    }

    /**
     * Render the [mediacredit] shortcode.
     *
     * @param  array<string, mixed>|string  $attrs  The shortcode attributes.
     * @param  string|null  $content  The shortcode content.
     */
    public function mediacredit_html($attrs, $content = null): string
    {
        $atts = shortcode_atts([
            'id' => '',
            'align' => '',
            'size' => 'full',
            'credit' => '',
        ], $attrs, 'mediacredit');

        $post_id = (int) $atts['id'];
        if ($post_id < 1) {
            return '';
        }

        $img = wp_get_attachment_image_src($post_id, $atts['size']);
        $width = isset($img[1]) ? (int) $img[1] : 0;

        return '<div class="wp-caption '.esc_attr($atts['align']).'" style="width:'.(10 + $width).'px">'.
            do_shortcode((string) $content).'<p class="wp-media-credit">Image by '.esc_html($atts['credit']).'</p></div>';
    }

    /**
     * Add the image credit field to the attachment edit form.
     *
     * @param  array<string, mixed>  $form_fields  The attachment form fields.
     * @param  WP_Post|int  $post  The attachment post.
     * @return array<string, mixed>
     */
    public function image_attachment_fields_to_edit($form_fields, $post)
    {
        $post_id = ($post instanceof WP_Post) ? $post->ID : (int) $post;
        $context = (array) get_post_meta($post_id, '_wp_attachment_context');

        if (! in_array('custom-header', $context, true) && wp_attachment_is_image($post_id)) {
            $form_fields['media_credit'] = [
                'label' => __('Image Credit'),
                'input' => 'text',
                'value' => get_post_meta($post_id, '_media_credit', true),
            ];
        }

        return $form_fields;
    }

    /**
     * Save the image credit field from the attachment edit form.
     *
     * @param  array<string, mixed>  $post  The attachment post data.
     * @param  array<string, mixed>  $attachment  The submitted attachment fields.
     * @return array<string, mixed>
     */
    public function custom_image_attachment_fields_to_save($post, $attachment)
    {
        if (isset($attachment['media_credit'], $post['ID'])) {
            update_post_meta((int) $post['ID'], '_media_credit', sanitize_text_field($attachment['media_credit']));
        }

        return $post;
    }
}

new UAMS_Media_Credit;
