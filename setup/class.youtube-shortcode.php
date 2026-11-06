<?php

/*
 *  YouTube shortcode allows for YouTube video and/or playlist to be added to content
 *  [youtube type='single' id='xxxx' max-results='5']
 */

class UAMS_YouTube
{
    public static $types = ['playlist', 'single'];

    /**
     * The constant in wp-config.php that holds the YouTube Data API key.
     */
    public const KEY_CONSTANT = 'UAMS_YOUTUBE_API_KEY';

    /**
     * The script handle the key is passed to (see class.uams-scripts.php).
     */
    public const SCRIPT_HANDLE = 'site';

    /**
     * The JavaScript object the key is passed in: `uams_youtube.apiKey`.
     */
    public const SCRIPT_OBJECT = 'uams_youtube';

    public function __construct()
    {
        add_shortcode('youtube', [$this, 'youtube_handler']);

        // After the `site` script is registered, at the default priority, in class.uams-scripts.php.
        add_action('wp_enqueue_scripts', [$this, 'localize_api_key'], 20);
    }

    /**
     * The API key from the constant, or an empty string when the constant is undefined, empty or not a string.
     */
    public static function api_key(): string
    {
        if (! defined(self::KEY_CONSTANT)) {
            return '';
        }

        return self::normalize_key(constant(self::KEY_CONSTANT));
    }

    /**
     * A usable key from whatever the constant holds: a trimmed string, or an empty string for anything else.
     */
    public static function normalize_key(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * Pass the key to the `site` script as `uams_youtube.apiKey`, when there is one.
     *
     * With no key nothing is passed, and the script renders nothing for a placeholder.
     * wp_localize_script() encodes the value as JSON, which escapes it for JavaScript.
     */
    public function localize_api_key(): void
    {
        $key = self::api_key();

        if ($key === '') {
            return;
        }

        wp_localize_script(self::SCRIPT_HANDLE, self::SCRIPT_OBJECT, ['apiKey' => $key]);
    }

    public function youtube_handler($atts): string
    {
        $params = shortcode_atts([
            'type' => '',
            'id' => '',
            'max-results' => 0,
            'max_results' => 0,
        ], $atts, 'youtube');

        $type = strtolower(trim($params['type']));

        if (empty($type)) {
            return 'required attribute "type" missing';
        }

        if (! in_array($type, self::$types, true)) {
            return sprintf('youtube type "%s" not supported', esc_html($type));
        }

        $id = sanitize_text_field($params['id']);
        if (empty($id)) {
            return 'required attribute "id" missing';
        }

        $max_results = (int) ($params['max-results'] ?: $params['max_results']);
        $el_id = 'uams-youtube-'.wp_rand(0, 1000);

        if ($max_results > 0) {
            return sprintf(
                '<div id="%s" class="uams-youtube" data-uams-youtube-type="%s" data-uams-youtube="%s" data-max-results="%d"></div>',
                esc_attr($el_id),
                esc_attr($type),
                esc_attr($id),
                $max_results
            );
        }

        return sprintf(
            '<div id="%s" class="uams-youtube" data-uams-youtube-type="%s" data-uams-youtube="%s"></div>',
            esc_attr($el_id),
            esc_attr($type),
            esc_attr($id)
        );
    }
}

new UAMS_YouTube;
