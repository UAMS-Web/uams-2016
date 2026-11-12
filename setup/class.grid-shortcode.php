<?php

/*
Shortcode for using the Links list found in the dashboard

Example:
[bookmark category_name=pfw categorize="0" title_li=""]
[row]
    [col class='col-md-12']Text[/col]
[/row]
*/

class UAMS_GridShortcode
{
    public function __construct()
    {
        add_shortcode('row', [$this, 'bs_row']);
        add_shortcode('col', [$this, 'bs_span']);
    }

    /**
     * Render a grid row.
     *
     * @param  array<string, mixed>|string  $params  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The row markup.
     */
    public function bs_row($params, $content = '')
    {
        $atts = shortcode_atts([
            'class' => 'row',
        ], $params, 'row');

        $content = ! empty($content) ? preg_replace('/<br class="nc".\/>/', '', (string) $content) : '';
        $result = '<div class="'.esc_attr($atts['class']).'">';
        $result .= do_shortcode($content);
        $result .= '</div>';

        return force_balance_tags($result);
    }

    /**
     * Render a grid column.
     *
     * @param  array<string, mixed>|string  $params  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string The column markup.
     */
    public function bs_span($params, $content = '')
    {
        $atts = shortcode_atts([
            'class' => 'col-sm-1',
        ], $params, 'col');

        $result = '<div class="'.esc_attr($atts['class']).'">';
        $result .= do_shortcode((string) $content);
        $result .= '</div>';

        return force_balance_tags($result);
    }
}

new UAMS_GridShortcode;
