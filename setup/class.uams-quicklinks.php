<?php

/**
 * UAMS Quicklinks
 * This will register the UAMS Quicklinks navigation and provide a JSON feed for the current quicklinks menu
 */
class UAMS_QuickLinks
{
    const NAME = 'Quick Links';

    const LOCATION = 'quick-links';

    // Coverage attributes no line to a property or constant default, so its mutants are reported
    // uncovered, not surviving. 'the allowed blog' asserts the value.
    // @pest-mutate-ignore: IncrementInteger,DecrementInteger
    const ALLOWED_BLOG = 1;

    public $MULTISITE;

    public $items = [];

    public function __construct()
    {
        $this->MULTISITE = is_multisite();

        // Redundant by construction (the left operand already decided it); kept as written.
        // @phpstan-ignore booleanAnd.leftAlwaysTrue
        if (! $this->MULTISITE || ($this->MULTISITE && get_current_blog_id() === self::ALLOWED_BLOG)) {
            add_action('after_setup_theme', [$this, 'register_quick_links_menu']);
        }

        add_action('wp_ajax_quicklinks', [$this, 'uams_quicklinks_feed']);
        add_action('wp_ajax_nopriv_quicklinks', [$this, 'uams_quicklinks_feed']);
    }

    public function register_quick_links_menu(): void
    {
        register_nav_menu(self::LOCATION, __(self::NAME));
    }

    public function uams_quicklinks_feed(): void
    {
        if ($this->MULTISITE) {
            switch_to_blog(self::ALLOWED_BLOG);
        }

        $locations = get_nav_menu_locations();
        if (isset($locations[self::LOCATION])) {
            $this->items = wp_get_nav_menu_items($locations[self::LOCATION]);
        } elseif ($location = wp_get_nav_menu_object(self::LOCATION)) {
            $this->items = wp_get_nav_menu_items($location->term_id);
        }

        if ($this->MULTISITE) {
            restore_current_blog();
        }

        wp_send_json($this->parse_menu());
    }

    /**
     * @return non-empty-array[]
     */
    public function parse_menu(): array
    {
        $menu = [];

        if (! empty($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                $item_array = array_intersect_key(
                    (array) $item,
                    array_fill_keys(['ID', 'title', 'url', 'classes', 'menu_item_parent'], null)
                );

                if (empty($item_array['classes']) || empty($item_array['classes'][0])) {
                    $item_array['classes'] = false;
                }

                $menu[] = $item_array;
            }
        }

        return $menu;
    }
}

new UAMS_QuickLinks;
