<?php

/* box shortcode:
 * meant for front page
 * boxes contain tiles. Boxes support only tiles inside and only between 1 and 12 tiles.
 *
 * structure: [box alignment="centered" shadow="none" padding="none"][tile][/tile][tile][/tile][/box]
 */

class TileBox
{
    const MAXTILES = 12; // @pest-mutate-ignore: IncrementInteger,DecrementInteger

    private int $count = 0; // @pest-mutate-ignore: IncrementInteger,DecrementInteger

    /**
     * @var list<string>
     */
    private array $NumbersArray = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve']; // @pest-mutate-ignore: RemoveArrayItem

    public function __construct()
    {
        add_filter('the_content', [$this, 'wpex_fix_shortcodes']);
        add_shortcode('box', [$this, 'box_handler']);
        add_shortcode('tile', [$this, 'tile_handler']);
    }

    /**
     * Remove the stray paragraph and line break markup that wraps shortcodes in content.
     *
     * @param  mixed  $content  The post content, or any value a filter passes.
     */
    public function wpex_fix_shortcodes($content): string
    {
        if (empty($content) || ! is_string($content)) {
            return (string) $content;
        }

        $array = [
            '<p>[' => '[',
            ']</p>' => ']',
            ']<br />' => ']',
        ];

        return strtr($content, $array);
    }

    /**
     * Render the [box] shortcode, which wraps between 1 and 12 [tile] shortcodes.
     *
     * @param  array<string, mixed>|string  $atts
     * @param  string|null  $content
     */
    public function box_handler($atts, $content = null): string
    {
        $boxCenter = shortcode_atts([
            'alignment' => 'none',
            'color' => '',
            'padding' => '',
            'shadow' => '',
            'custom' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'box');

        $color = ! empty($boxCenter['color']) ? ' box-'.sanitize_html_class($boxCenter['color']) : '';
        $padding = ! empty($boxCenter['padding']) ? ' nopad' : '';
        $shadow = ! empty($boxCenter['shadow']) ? ' noshadow' : '';
        $custom = ! empty($boxCenter['custom']) ? ' '.sanitize_html_class($boxCenter['custom']) : '';
        $center = 'box-'.sanitize_html_class($boxCenter['alignment']);

        $this->count = 0;

        if (empty($content)) {
            return 'No content inside the box element. Make sure you close your box element. Required structure: [box][tile]content[/tile][/box]';
        }

        $output = do_shortcode($content);
        $count_key = min($this->count, self::MAXTILES);
        $number = $this->NumbersArray[$count_key] ?? 'twelve';

        return sprintf(
            '<div class="box-outer"><div class="box %s %s%s%s%s%s">%s</div></div>',
            esc_attr($number),
            esc_attr($center),
            esc_attr($color),
            esc_attr($padding),
            esc_attr($shadow),
            esc_attr($custom),
            $output
        );
    }

    /**
     * Render one [tile] shortcode inside a [box].
     *
     * @param  array<string, mixed>|string  $atts
     * @param  string|null  $content
     */
    public function tile_handler($atts, $content = null): string
    {
        $this->count++;
        $tile_atts = shortcode_atts([
            'empty' => 'false',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'tile');

        $classes = 'tile';

        if ($this->count > self::MAXTILES) {
            $content = 'Too many [tile]s. Only up to 12 are supported.';
        }

        if (filter_var($tile_atts['empty'], FILTER_VALIDATE_BOOLEAN)) {
            $classes .= ' empty';
        } elseif (empty($content)) {
            $content = 'No content for this tile. Make sure you wrap your content like this: [tile]Content here[/tile]';
        }

        return sprintf('<div class="%s">%s</div>', esc_attr($classes), apply_filters('the_content', (string) $content));
    }
}

new TileBox;
