<?php

class UAMS_GoogleApps
{
    public function __construct()
    {
        // Turns an iframe into a shortcode for parsing
        add_filter('pre_kses', [$this, 'uams_google_calendar_embed_to_shortcode']);
        // GoogleApps shortcode
        add_shortcode('googleapps', [$this, 'uams_google_calendar_shortcode']);
    }

    /**
     * Render the Google Apps embed shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @return string The embed markup.
     */
    public function uams_google_calendar_shortcode($atts): string
    {
        $params = shortcode_atts([
            'query' => '',
            'dir' => '',
            'domain' => 'www',
            'width' => 620,
            'height' => 500,
            'app' => 'calendar',
        ], $atts, 'googleapps');

        if ($params['dir'] === 'calendar/embed') {
            return sprintf(
                '<div class="googleapps-%s"><iframe width="%s" height="%s" style="border:0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.google.com/calendar/embed?%s"></iframe></div>',
                esc_attr($params['app']),
                esc_attr($params['width']),
                esc_attr($params['height']),
                esc_attr($params['query'])
            );
        }

        return '';
    }

    /**
     * Convert Google calendar iframes in the content to shortcodes.
     *
     * @param  mixed  $content  The content to filter.
     * @return mixed The filtered content, or the input when it is empty or not a string.
     */
    public function uams_google_calendar_embed_to_shortcode($content)
    {
        if (empty($content) || ! is_string($content)) {
            return $content;
        }

        if (! str_contains($content, '<iframe ') && ! str_contains($content, 'google.com/calendar')) {
            return $content;
        }

        $content = preg_replace_callback('#&lt;iframe\s[^&]*?(?:&(?!gt;)[^&]*?)*?src="https?://.*?\.google\.(.*?)/(.*?)\?(.+?)"[^&]*?(?:&(?!gt;)[^&]*?)*?&gt;\s*&lt;/iframe&gt;\s*(?:&lt;br\s*/?&gt;)?\s*#i', [$this, 'uams_google_calendar_embed_to_shortcode_callback'], $content);

        return preg_replace_callback('!\<iframe\s[^>]*?src="https?://.*?\.google\.(.*?)/(.*?)\?(.+?)"[^>]*?\>\s*\</iframe\>\s*!i', [$this, 'uams_google_calendar_embed_to_shortcode_callback'], $content);
    }

    /**
     * Build the shortcode for one matched iframe.
     *
     * @param  array<int, string>  $match  The regular expression matches.
     * @return string The shortcode.
     */
    public function uams_google_calendar_embed_to_shortcode_callback($match): string
    {
        if (preg_match('/\bwidth=[\'"](\d+)/', $match[0], $width)) {
            $width = min([(int) $width[1], 630]);
        } else {
            $width = 630;
        }

        if (preg_match('/\bheight=[\'"](\d+)/', $match[0], $height)) {
            $height = (int) $height[1];
        } else {
            $height = 500;
        }

        $url = $match[3] ?? '';

        return sprintf(
            '[googleapps domain="www" dir="calendar/embed" query="%s" width="%d" height="%d"]',
            esc_attr($url),
            (int) $width,
            $height
        );
    }
}

new UAMS_GoogleApps;
