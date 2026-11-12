<?php

/*
 * Shortcode for embedding tab style module
 * link is optional (#linkname)
 * [tabs name='web name']
 * [tab title='tab title' link='linkname1'] content [/tab]
 * [tab title='tab title' link='linkname2'] content [/tab]
 * [tab title='tab title' link='linkname3'] content [/tab]
 * [/tabs]
 */

class UAMS_TabsShortcode
{
    /**
     * @var list<string>
     */
    public $tab_titles = [];

    /**
     * @var list<string>
     */
    public $tab_links = [];

    public function __construct()
    {
        add_filter('the_content', [$this, 'wpex_fix_shortcodes']);
        add_shortcode('tabs', [$this, 'tabs_handler']);
        add_shortcode('tab', [$this, 'tab_handler']);
        add_action('init', [$this, 'register_uams_tabs']);
    }

    public function register_uams_tabs(): void
    {
        wp_register_script('uams-tabs', get_template_directory_uri().'/js/uams.tabs.min.js', [], '1.0', true);
    }

    /**
     * Remove the stray paragraph and line break markup that wraps shortcodes in content.
     *
     * @param  mixed  $content  The post content, or any value a filter passes.
     */
    public function wpex_fix_shortcodes($content): string
    {
        if (empty($content) || ! is_string($content)) {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore cast.string
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
     * Render the [tabs] shortcode, which wraps a set of [tab] shortcodes.
     *
     * @param  array<string, mixed>|string  $atts
     * @param  string|null  $content
     */
    public function tabs_handler($atts, $content = null): string
    {
        $this->tab_titles = [];
        $this->tab_links = [];

        $tabs_atts = shortcode_atts([
            'name' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'tabs');

        if (empty($content)) {
            return 'No content inside the tabs element. Make sure you close your tabs element. Required structure: [tabs][tab title=""]content[/tab][/tabs]';
        }

        wp_enqueue_script('uams-tabs');

        $tab_content = do_shortcode($content);
        $tab_name = sanitize_html_class(str_replace(' ', '', $tabs_atts['name']));

        $output = sprintf('<div id="uams-tabs" class="uams-tabs-shortcode %s">', esc_attr($tab_name));
        $output .= '<div class="js-tabs tabs__uams"><ul class="js-tablist tabs__uams_ul" data-hx="h2">';

        // The tab titles and links are filled by the [tab] shortcode callbacks while do_shortcode() expands the content, so
        // PHPStan sees only the empty default.
        // @phpstan-ignore foreach.emptyArray
        foreach ($this->tab_titles as $key => $title) {
            $id = $key + 1;
            // The tab titles and links are filled by the [tab] shortcode callbacks while do_shortcode() expands the content, so
            // PHPStan sees only the empty default.
            // @phpstan-ignore empty.offset, argument.type
            $link_val = ! empty($this->tab_links[$key]) ? sanitize_html_class(str_replace(' ', '', $this->tab_links[$key])) : 'tab-'.$id;

            $output .= sprintf(
                '<li class="js-tablist__item tabs__uams__li"><a href="#%s" id="label_%s" class="js-tablist__link tabs__uams__a">%s</a></li>',
                esc_attr($link_val),
                esc_attr($link_val),
                esc_html($title)
            );
        }

        $output .= '</ul><div class="uams-tab-content">';
        $output .= $tab_content;
        $output .= '</div></div></div>';

        return $output;
    }

    /**
     * Render one [tab] shortcode and record its title and link for the enclosing [tabs].
     *
     * @param  array<string, mixed>|string  $atts
     * @param  string|null  $content
     */
    public function tab_handler($atts, $content = null): string
    {
        $tab_atts = shortcode_atts([
            'title' => '',
            'link' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'tab');

        if (empty($content)) {
            $content = 'No content for this tab. Make sure you wrap your content like this: [tab]Content here[/tab]';
        }

        $title = sanitize_text_field($tab_atts['title']);
        $link = sanitize_html_class($tab_atts['link']);

        $this->tab_titles[] = $title;
        $this->tab_links[] = $link;

        $id = count($this->tab_titles);

        return sprintf(
            '<div id="%s" class="js-tabcontent tabs__uams__tabcontent">%s</div>',
            esc_attr(! empty($link) ? $link : 'tab-'.$id),
            do_shortcode($content)
        );
    }
}

new UAMS_TabsShortcode;
