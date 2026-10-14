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

    function __construct()
    {

      add_filter('the_content', array( $this, 'wpex_fix_shortcodes' ) );

        add_shortcode('accordion', array($this, 'accordion_handler'));
        add_shortcode('section', array($this, 'section_handler'));
        add_shortcode('subsection', array($this, 'subsection_handler'));
    }

    function wpex_fix_shortcodes( $content ) {
    if ( empty( $content ) || ! is_string( $content ) ) {
        return (string) $content;
    }
    $array = array(
        '<p>['    => '[',
        ']</p>'   => ']',
        ']<br />' => ']',
    );

    return strtr( $content, $array );
}

    function accordion_handler( $atts, $content )
    {
        $accordion_atts = shortcode_atts( array(
          'name' => '',
        ), $atts);

        if ( empty( $content ) )
            return 'No content inside the accordion element. Make sure your close your accordion element. Required stucture: [accordion][section]content[/section][/accordion]';

        $output = do_shortcode( $content );
        return sprintf( '<script src="' . get_template_directory_uri() . '/js/uams.accordionmodule.js" type="text/javascript"></script><div id="accordion uams-accordion-shortcode"><h3>%s</h3><div class="js-accordion" data-accordion-prefix-classes="uams-accordion-shortcode">%s</div></div>', $accordion_atts['name'], $output );
    }

    function section_handler( $atts, $content )
    {
        $section_atts = shortcode_atts( array(
          'title' => '',
          'active' => false,
        ), $atts);

        $active = '';
        if ( empty( $content ) ){
          $content = 'No content for this section.  Make sure you wrap your content like this: [section]Content here[/section]';
        }

        if ($section_atts['active']) {
            $active = ' data-accordion-opened="true"';
        }

        $output = do_shortcode( $content );
        return sprintf( '<h2 class="js-accordion__header">%s</h2><div class="js-accordion__panel" %s>%s</div>', $section_atts['title'], $active, apply_filters( 'the_content', $output ) );
    }

    function subsection_handler( $atts, $content )
    {
        $section_atts = shortcode_atts( array(
          'title' => '',
          'active' => false,
        ), $atts);

        $active = '';
        if ( empty( $content ) ){
          $content = 'No content for this tile.  Make sure you wrap your content like this: [section]Content here[/section]';
        }

        if ($section_atts['active']) {
            $active = ' data-accordion-opened="true"';
        }
        $output = do_shortcode( $content );
        return sprintf( '<h3 class="js-accordion__header uams-accordion-subheader">%s</h3><div class="js-accordion__panel" %s>%s</div>', $section_atts['title'], $active, apply_filters( 'the_content', $output ) );
    }
}