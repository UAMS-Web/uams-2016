<?php

/*
 * Shortcode for embedding accordion style module
 * [accordion name='web name']
 * [section title='section title' active="true"] content [/section]
 * [section title='section title'] content [/section]
 * [section title='section title'] content [/section]
 * [/accordion]
 */

class UAMS_AccordionShortcode
{
    public function __construct()
    {

        add_filter('the_content', [$this, 'wpex_fix_shortcodes']);

        add_shortcode('accordion', [$this, 'accordion_handler']);
        add_shortcode('section', [$this, 'section_handler']);
        add_shortcode('subsection', [$this, 'subsection_handler']);
    }

    /**
     * Remove the paragraph and line break markup WordPress wraps around shortcodes.
     *
     * @param  mixed  $content  The post content.
     * @return string The content with the markup removed.
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
     * Render the accordion shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The accordion markup.
     */
    public function accordion_handler($atts, $content): string
    {
        $accordion_atts = shortcode_atts([
            'name' => '',
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts);

        if (empty($content)) {
            return 'No content inside the accordion element. Make sure your close your accordion element. Required stucture: [accordion][section]content[/section][/accordion]';
        }

        $output = do_shortcode($content);

        return sprintf('<script src="'.get_template_directory_uri().'/js/uams.accordionmodule.js" type="text/javascript"></script><div id="accordion uams-accordion-shortcode"><h3>%s</h3><div class="js-accordion" data-accordion-prefix-classes="uams-accordion-shortcode">%s</div></div>', $accordion_atts['name'], $output);
    }

    /**
     * Render a section inside the accordion.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The section markup.
     */
    public function section_handler($atts, $content): string
    {
        $section_atts = shortcode_atts([
            'title' => '',
            'active' => false,
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts);

        $active = '';
        if (empty($content)) {
            $content = 'No content for this section.  Make sure you wrap your content like this: [section]Content here[/section]';
        }

        if ($section_atts['active']) {
            $active = ' data-accordion-opened="true"';
        }

        $output = do_shortcode($content);

        return sprintf('<h2 class="js-accordion__header">%s</h2><div class="js-accordion__panel" %s>%s</div>', $section_atts['title'], $active, apply_filters('the_content', $output));
    }

    /**
     * Render a subsection inside the accordion.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The subsection markup.
     */
    public function subsection_handler($atts, $content): string
    {
        $section_atts = shortcode_atts([
            'title' => '',
            'active' => false,
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts);

        $active = '';
        if (empty($content)) {
            $content = 'No content for this tile.  Make sure you wrap your content like this: [section]Content here[/section]';
        }

        if ($section_atts['active']) {
            $active = ' data-accordion-opened="true"';
        }

        $output = do_shortcode($content);

        return sprintf('<h3 class="js-accordion__header uams-accordion-subheader">%s</h3><div class="js-accordion__panel" %s>%s</div>', $section_atts['title'], $active, apply_filters('the_content', $output));
    }
}
