<?php

/**
 * This shortcode allows iFrames for editors.
 */
class UAMS_Iframes
{
    /**
     * @var mixed[]
     */
    public $ALLOWED_IFRAMES = [];

    public function __construct()
    {
        $this->ALLOWED_IFRAMES = $this->get_iframe_domains();
        add_shortcode('iframe', [$this, 'add_iframe']);
    }

    public function add_iframe($atts): string
    {
        $params = shortcode_atts([
            'src' => '',
            'height' => get_option('embed_size_h'),
            'width' => get_option('embed_size_w'),
        ], $atts, 'iframe');

        $params['src'] = esc_url($params['src'], ['http', 'https']);
        if (empty($params['src'])) {
            return '';
        }

        $parsed = parse_url($params['src']);
        if (isset($parsed['host']) && ! in_array($parsed['host'], $this->ALLOWED_IFRAMES, true)) {
            return '';
        }

        $iframeSrc = html_entity_decode($params['src']);
        $iframeQueryString = parse_url($iframeSrc, PHP_URL_QUERY);
        $parentQueryString = ! empty($_GET) ? http_build_query($_GET) : '';

        if (! empty($iframeQueryString) && ! empty($parentQueryString)) {
            parse_str((string) $iframeQueryString, $iframeQueryParams);
            parse_str($parentQueryString, $parentQueryParams);
            $query_merged = array_merge($iframeQueryParams, $parentQueryParams);
            $iframeSrc = str_replace($iframeQueryString, http_build_query($query_merged), $iframeSrc);
        } elseif (! empty($parentQueryString)) {
            $iframeSrc .= '?'.$parentQueryString;
        }

        $iframeSrc = esc_url($iframeSrc, ['http', 'https']);

        return sprintf(
            '<iframe src="%s" width="%s" height="%s" style="border:0"></iframe>',
            $iframeSrc,
            esc_attr($params['width']),
            esc_attr($params['height'])
        );
    }

    public function get_iframe_domains(): array
    {
        return [
            'uams.edu',
            'www.uams.edu',
            'uamshealth.com',
            'uamsonlinedev.com',
            'google.com',
            'docs.google.com',
            'youtube.com',
            'www.googletagmanager.com',
            'www.google.com',
            'www.youtube.com',
            'pgcalc.com',
            'www.pgcalc.com',
            'storify.com',
            'api.soundcloud.com',
            'flickr.com',
            'vimeo.com',
            'player.vimeo.com',
            'www.facebook.com',
            'facebook.com',
            'monday.com',
            'forms.monday.com',
        ];
    }
}

new UAMS_Iframes;
