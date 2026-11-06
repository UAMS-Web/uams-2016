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
    public $tab_titles = [];

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

    public function tabs_handler($atts, $content = null): string
    {
        $this->tab_titles = [];
        $this->tab_links = [];

        $tabs_atts = shortcode_atts([
            'name' => '',
        ], $atts, 'tabs');

        if (empty($content)) {
            return 'No content inside the tabs element. Make sure you close your tabs element. Required structure: [tabs][tab title=""]content[/tab][/tabs]';
        }

        wp_enqueue_script('uams-tabs');

        $tab_content = do_shortcode($content);
        $tab_name = sanitize_html_class(str_replace(' ', '', $tabs_atts['name']));

        $output = sprintf('<div id="uams-tabs" class="uams-tabs-shortcode %s">', esc_attr($tab_name));
        $output .= '<div class="js-tabs tabs__uams"><ul class="js-tablist tabs__uams_ul" data-hx="h2">';

        foreach ($this->tab_titles as $key => $title) {
            $id = $key + 1;
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

    public function tab_handler($atts, $content = null): string
    {
        $tab_atts = shortcode_atts([
            'title' => '',
            'link' => '',
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
