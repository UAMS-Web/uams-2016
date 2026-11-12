<?php

/*
 *  Button shortcode allows for styled buttons to be added to content
 *  [button color='gray' type='type' url='link url' size='small']Button Text[/button]
 *  optional small attribute makes the button small.  Assume large if not present
 */

class UAMS_Button
{
    /**
     * @var array<int, string>
     */
    private static array $types = ['plus', 'go', 'external', 'play']; // @pest-mutate-ignore: RemoveArrayItem

    public function __construct()
    {
        add_shortcode('button', [$this, 'button_handler']);
    }

    /**
     * Render the button shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The button text.
     * @return string The button markup.
     */
    public function button_handler($atts, $content = null): string
    {
        $atts = shortcode_atts([
            'color' => 'none',
            'type' => '', // @pest-mutate-ignore: EmptyStringToNotEmpty
            'url' => '#',
            'size' => '', // @pest-mutate-ignore: EmptyStringToNotEmpty
            'small' => null,
            'text' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'button');

        $classes = ['uams-btn'];
        $color = 'btn-'.sanitize_html_class($atts['color']);

        if (empty($content) && empty($atts['text'])) {
            return 'No text in this button';
        }

        if (! empty($atts['type'])) {
            $type = strtolower(trim($atts['type']));
            if (in_array($type, self::$types, true)) {
                $classes[] = 'btn-'.$type;
            }
        }

        $url = ! empty($atts['url']) ? esc_url($atts['url']) : '#';

        if ($atts['small'] !== null) {
            $classes[] = 'btn-sm';
        }

        if (! empty($atts['size'])) {
            if (in_array($atts['size'], ['small', 'sm'], true)) {
                $classes[] = 'btn-sm';
            } elseif (in_array($atts['size'], ['large', 'lg'], true)) {
                $classes[] = 'btn-lg';
            }
        }

        if (! empty($atts['text'])) {
            $content = $atts['text'];
        }

        $class_string = implode(' ', array_unique($classes));

        return sprintf(
            '<a class="%s %s" href="%s">%s</a>',
            esc_attr($class_string),
            esc_attr($color),
            $url,
            esc_html($content)
        );
    }
}

new UAMS_Button;
