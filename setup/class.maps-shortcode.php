<?php

/*
 * Maps shortcode allows for map area to be added to content
 * [map building='135' width='100%' height='480'][/map]
 * Width default is 100%. Height default is 480px.
 */

class UAMS_Map
{
    /**
     * @var array<int, string>
     */
    private static array $buildingcode = [
        '127', '116', '117', '118', '119', '120', '121', '122', '123', '124', // @pest-mutate-ignore: RemoveArrayItem
        '125', '128', '129', '126', '131', '130', '132', '133', '134', '135', // @pest-mutate-ignore: RemoveArrayItem
        '136', '137', '138', '139', '141', '142', '143', '144', '145', '146', // @pest-mutate-ignore: RemoveArrayItem
        '147', '148', '149', '150', '151', '152', '153', '154', '155', '2', // @pest-mutate-ignore: RemoveArrayItem
        '3', '4', '7', '6', // @pest-mutate-ignore: RemoveArrayItem
    ];

    const URL = '//maps.uams.edu/full-screen/?markerid=';

    public function __construct()
    {
        add_shortcode('map', [$this, 'map_handler']);
    }

    /**
     * Render the map shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The map markup.
     */
    public function map_handler($atts, $content = null): string
    {
        $params = shortcode_atts(
            [
                'building' => '',
                'width' => '100%',
                'height' => '480px',
            ],
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
            $atts,
            'map'
        );

        $building = sanitize_text_field($params['building']);

        if (empty($building)) {
            return 'required attribute "building" missing';
        }

        if (! in_array($building, self::$buildingcode, true)) {
            return sprintf('Building "%s" is not supported', esc_html($building));
        }

        $width = esc_attr($params['width']);
        $height = esc_attr($params['height']);

        return sprintf(
            '<div class="uams-campus-map">
                <iframe width="%s" height="%s" src="%s%s" frameborder="0"></iframe>
                <a href="https://maps.uams.edu/map-mashup/?markerid=%s" target="_blank" rel="noopener noreferrer">View full map</a>
            </div>',
            $width,
            $height,
            esc_url(self::URL),
            rawurlencode($building),
            rawurlencode($building)
        );
    }
}

new UAMS_Map;
