<?php

/*
Plugin Name: UAMS Carousel
Plugin URL: http://uw.edu
Description: Transform your standard image galleries into an immersive full-screen experience.
Version: 0.1
Author: Automattic
*/

class UAMS_Carousel
{
    // Coverage attributes no line to a property or constant default, so its mutants are reported
    // uncovered, not surviving. 'initializes on init' asserts the value.
    // @pest-mutate-ignore: IncrementInteger,DecrementInteger
    const COLUMNS = 5;

    const LINK = 'none';

    /**
     * @var list<int>
     */
    // Coverage attributes no line to a property or constant default, so its mutants are reported
    // uncovered, not surviving. 'initializes on init' asserts the value.
    // @pest-mutate-ignore: IncrementInteger,DecrementInteger,RemoveArrayItem
    public $prebuilt_widths = [370, 700, 1000, 1200, 1400, 2000];

    /**
     * @var bool
     */
    // Coverage attributes no line to a property or constant default, so its mutants are reported
    // uncovered, not surviving. 'initializes on init' asserts the value.
    // @pest-mutate-ignore: TrueToFalse
    public $first_run = true;

    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        if ($this->disable()) {
            return;
        }

        $this->prebuilt_widths = apply_filters('jp_carousel_widths', $this->prebuilt_widths);
        // The callback is also run as a filter over the enqueue output, so it returns a string.
        // @phpstan-ignore return.void
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('gallery_style', [$this, 'add_data_to_container']);
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_data_to_images'], 10, 2);
    }

    /**
     * Whether the carousel is disabled, as filtered by `uams_carousel_disable`.
     *
     * @return bool
     */
    public function disable()
    {
        return apply_filters('uams_carousel_disable', false);
    }

    /**
     * Filter the carousel asset version.
     *
     * @param  string  $version  The asset version.
     * @return string
     */
    public function asset_version($version)
    {
        return apply_filters('jp_carousel_asset_version', $version);
    }

    /**
     * Enqueue the carousel assets, or pass the output through when the carousel is not forced on.
     *
     * @param  string  $output  The gallery output.
     * @return string
     */
    public function enqueue_assets($output = '')
    {
        if (! empty($output) && ! apply_filters('jp_carousel_force_enable', false)) {
            remove_filter('gallery_style', [$this, 'add_data_to_container']);
            remove_filter('wp_get_attachment_image_attributes', [$this, 'add_data_to_images']);

            return $output;
        }

        do_action('jp_carousel_thumbnails_shown');

        $is_logged_in = is_user_logged_in();
        $require_name_email = (int) get_option('require_name_email');

        $localize_strings = [
            'widths' => $this->prebuilt_widths,
            'is_logged_in' => $is_logged_in,
            'lang' => strtolower(substr((string) get_locale(), 0, 2)),
            'ajaxurl' => set_url_scheme(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('carousel_nonce'),
            'display_exif' => true,
            'display_geo' => false,
            'background_color' => 'white',
            'download_original' => sprintf(__('View full size <span class="photo-size">%1$s<span class="photo-size-times">&times;</span>%2$s</span>', 'jetpack'), '{0}', '{1}'),
            'camera' => __('Camera', 'jetpack'),
            'aperture' => __('Aperture', 'jetpack'),
            'shutter_speed' => __('Shutter Speed', 'jetpack'),
            'focal_length' => __('Focal Length', 'jetpack'),
            'require_name_email' => $require_name_email,
            'login_url' => wp_login_url(apply_filters('the_permalink', get_permalink())),
        ];

        $localize_strings = apply_filters('jp_carousel_localize_strings', $localize_strings);
        wp_localize_script('site', 'jetpackCarouselStrings', $localize_strings);

        do_action('jp_carousel_enqueue_assets', $this->first_run, $localize_strings);

        $this->first_run = false;

        return $output;
    }

    /**
     * Add the carousel data attributes to a gallery image.
     *
     * @param  array<string, int|string>  $attr  The image attributes.
     * @param  WP_Post|null  $attachment  The attachment post.
     * @return array<string, int|string>
     */
    public function add_data_to_images($attr, $attachment = null)
    {
        if ($this->first_run || empty($attachment)) {
            return $attr;
        }

        $attachment_id = (int) $attachment->ID;
        $orig_file = wp_get_attachment_image_src($attachment_id, 'original');
        $orig_file = $orig_file[0] ?? wp_get_attachment_url($attachment_id);

        $meta = wp_get_attachment_metadata($attachment_id);
        $size = isset($meta['width'], $meta['height']) ? (int) $meta['width'].','.(int) $meta['height'] : '';
        $img_meta = ! empty($meta['image_meta']) ? (array) $meta['image_meta'] : [];

        $medium_file_info = wp_get_attachment_image_src($attachment_id, 'medium');
        $medium_file = $medium_file_info[0] ?? '';

        $large_file_info = wp_get_attachment_image_src($attachment_id, 'large');
        $large_file = $large_file_info[0] ?? '';

        $post_obj = get_post($attachment_id);
        $attachment_title = $post_obj ? wptexturize($post_obj->post_title) : '';
        $attachment_desc = $post_obj ? wpautop(wptexturize($post_obj->post_content)) : '';

        if ($img_meta !== []) {
            unset($img_meta['latitude'], $img_meta['longitude']);
        }

        $json_img_meta = json_encode(array_map(strval(...), $img_meta));

        $attr['data-attachment-id'] = $attachment_id;
        // The stubs type this value as possibly false; the call is unchanged.
        // @phpstan-ignore argument.type
        $attr['data-orig-file'] = esc_attr($orig_file);
        $attr['data-orig-size'] = $size;
        $attr['data-comments-opened'] = '0';
        // The stubs type this value as possibly false; the call is unchanged.
        // @phpstan-ignore argument.type
        $attr['data-image-meta'] = esc_attr($json_img_meta);
        $attr['data-image-title'] = esc_attr($attachment_title);
        $attr['data-image-description'] = esc_attr($attachment_desc);
        $attr['data-medium-file'] = esc_attr($medium_file);
        $attr['data-large-file'] = esc_attr($large_file);

        return $attr;
    }

    /**
     * Add the carousel data attribute to the gallery container markup.
     *
     * @param  string  $html  The gallery container markup.
     * @return string
     */
    public function add_data_to_container($html)
    {
        global $post;

        if (isset($post) && $post instanceof WP_Post) {
            $blog_id = (int) get_current_blog_id();

            $extra_data = [
                'data-carousel-extra' => [
                    'blog_id' => $blog_id,
                    'permalink' => get_permalink($post->ID),
                ],
            ];

            $extra_data = apply_filters('jp_carousel_add_data_to_container', $extra_data);

            foreach ((array) $extra_data as $data_key => $data_values) {
                // The stubs type this value as possibly false; the call is unchanged.
                // @phpstan-ignore argument.type
                $html = str_replace('<div ', '<div '.esc_attr($data_key)."='".esc_attr(json_encode($data_values))."' ", $html);
            }
        }

        return $html;
    }
}
