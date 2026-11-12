<?php

/*
 * Shortcode for embedding cards style module
 * [cards name='web name']
 * [card title='section title' size='med' type='top' footer='Update May 4, 2018' image='image-url' color='gray10'] content [/card]
 * [card title='section title' size='xs' type='bottom' action='<a href="some-url">Text</a>' image='image-url' color='gray10'] content [/card]
 * [card title='section title' size='xs' image='image-url' color='gray10'] content [/card]
 * [/cards]
 * [cards name='Horizontal']
 * [card title='section title' size='med' type='left' footer='Update May 4, 2018' image='image-url' color='gray10'] content [/card]
 * [card title='section title' size='med' type='right boxed' image='image-url' color='gray10'] content [/card]
 * [card title='section title' size='xl' image='image-url' color='gray10'] content [/card]
 * [/cards]
 *
 * Types:
 *  Top: Image appears above all text
 *  Bottom: Image appears below all text
 *  Left: Image floats to the left of the text
 *  Right: Image floats to the right of the text
 *  Boxed: Adds padding around image for left and right types
 *
 * Sizes: (will wrap and expand to fill area)
 *  blank / none = full width of the container (preferred if adding into columns)
 *  xl = 44em ~ 3/4 width on desktop
 *  lrg = 39em ~ 2/3 width on desktop
 *  med = 29em ~ 1/2 width on desktop
 *  sml = 19em ~ 1/3 width on desktop
 *  xs = 14em ~ 1/4 width on desktop
 *
 * Options:
 *  footer: Add footer information below all text (gray background)
 *  action: Add action element below the main content (transparent)
 *  image: URL of image
 *  alt: Alt tag for image (Required for accessibility)
 *  color: color class for card background
 *
 */

class UAMS_CardsShortcode
{
    public function __construct()
    {
        add_filter('the_content', [$this, 'wpex_fix_shortcodes']);
        add_shortcode('cards', [$this, 'cards_handler']);
        add_shortcode('card', [$this, 'card_handler']);
    }

    /**
     * Remove the paragraph and line break markup WordPress wraps around shortcodes.
     *
     * @param  mixed  $content  The post content.
     * @return mixed The content with the markup removed, or the input when it is empty or not a string.
     */
    public function wpex_fix_shortcodes($content)
    {
        if (empty($content) || ! is_string($content)) {
            return $content;
        }

        $array = [
            '<p>[' => '[',
            ']</p>' => ']',
            ']<br />' => ']',
        ];

        return strtr($content, $array);
    }

    /**
     * Render the cards shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed cards.
     * @return string The cards markup.
     */
    public function cards_handler($atts, $content = null): string
    {
        $cards_atts = shortcode_atts([
            'name' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'cards');

        if (empty($content)) {
            return 'No content inside the cards element. Make sure you close your cards element.';
        }

        $output = do_shortcode($content);
        $title = ! empty($cards_atts['name']) ? sprintf('<h2>%s</h2>', esc_html($cards_atts['name'])) : '';

        return sprintf('%s<section class="cards">%s</section>', $title, $output);
    }

    /**
     * Render a single card.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The card body.
     * @return string The card markup.
     */
    public function card_handler($atts, $content = null): string
    {
        $card_atts = shortcode_atts([
            'title' => '',
            'size' => '',
            'image' => '',
            'color' => '',
            'type' => '',
            'link' => '',
            'alt' => '',
            'action' => '',
            'footer' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'card');

        $size = ! empty($card_atts['size']) ? ' '.sanitize_html_class($card_atts['size']) : '';
        $color = ! empty($card_atts['color']) ? ' '.sanitize_html_class($card_atts['color']) : '';
        $type = ! empty($card_atts['type']) ? ' '.sanitize_html_class($card_atts['type']) : '';
        $link = '';
        $endlink = '';
        $image = '';
        $action = '';
        $footer = '';

        if (empty($content)) {
            $content = 'No content for this section. Make sure you wrap your content like this: [card]Content here[/card]';
        }

        if (! empty($card_atts['link'])) {
            $link = '<a href="'.esc_url($card_atts['link']).'">';
            $endlink = '</a>';
        }

        if (! empty($card_atts['image'])) {
            $alt = ! empty($card_atts['alt']) ? ' alt="'.esc_attr($card_atts['alt']).'"' : ' alt=""';
            $image = '<div class="card-image">'.$link.'<img src="'.esc_url($card_atts['image']).'"'.$alt.' />'.$endlink.'</div>';
        }

        if (! empty($card_atts['action'])) {
            $action = '<div class="card-action">'.wp_kses_post($card_atts['action']).'</div>';
        }

        if (! empty($card_atts['footer'])) {
            $footer = '<div class="card-footer">'.wp_kses_post($card_atts['footer']).'</div>';
        }

        $output = do_shortcode($content);

        return sprintf(
            '<article class="card%s%s%s">%s<div class="card-stack"><div class="card-content">%s<h3>%s</h3>%s%s</div>%s%s</div></article>',
            esc_attr($type),
            esc_attr($size),
            esc_attr($color),
            $image,
            $link,
            esc_html($card_atts['title']),
            $endlink,
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            apply_filters('the_content', $output),
            $action,
            $footer
        );
    }
}

new UAMS_CardsShortcode;
