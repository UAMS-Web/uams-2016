<?php

/*
 * Shortcode for making Bootstrap grids
 */

class UAMS_TinyShortcode
{
    /**
     * @var list<string>
     */
    public $shortcodes = [
        'grid', // @pest-mutate-ignore: RemoveArrayItem
        'button', // @pest-mutate-ignore: RemoveArrayItem
        'tilebox', // @pest-mutate-ignore: RemoveArrayItem
    ];

    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        if (! is_admin()) {
            return;
        }

        wp_enqueue_style('bs_admin_style', get_template_directory_uri().'/assets/admin/css/bootstrap-shortcode-admin.css', [], null);

        if (! current_user_can('edit_posts') && ! current_user_can('edit_pages')) {
            return;
        }

        if (get_user_option('rich_editing') === 'true') {
            add_filter('mce_external_plugins', [$this, 'regplugins']);
            add_filter('mce_buttons_3', [$this, 'regbtns']);
        }
    }

    /**
     * Add the shortcode buttons to the third TinyMCE toolbar row.
     *
     * @param  mixed  $buttons  The toolbar buttons; a non-array value is replaced with an empty list.
     * @return mixed[]
     */
    public function regbtns($buttons): array
    {
        $buttons = is_array($buttons) ? $buttons : [];
        foreach ($this->shortcodes as $shortcode) {
            $buttons[] = 'bs_'.$shortcode;
        }

        return $buttons;
    }

    /**
     * Register the shortcode button plugins with TinyMCE.
     *
     * @param  mixed  $plgs  The external plugins; a non-array value is replaced with an empty array.
     * @return mixed[]
     */
    public function regplugins($plgs): array
    {
        $plgs = is_array($plgs) ? $plgs : [];
        foreach ($this->shortcodes as $shortcode) {
            $plgs['bs_'.$shortcode] = get_template_directory_uri().'/js/plugins/'.$shortcode.'.js';
        }

        return $plgs;
    }
}

new UAMS_TinyShortcode;
