<?php

/**
 * The theme classes under setup/ that the templates and template functions name: the dropdown menu
 * classes (looked up with class_exists()) are the real ones, so the tests of the classes themselves can
 * run in the same process, and the sidebar's id constant is a stand-in.
 */

declare(strict_types=1);

use function Brain\Monkey\Actions\expectAdded;

if (! class_exists('UamsTestWalkerNavMenu', false)) {
    /**
     * Stands in for the WordPress walker the dropdown walker extends: display_element() records what the
     * parent receives. It is aliased to the WordPress name below, so static analysis keeps reading the
     * WordPress stubs for `Walker_Nav_Menu`.
     */
    class UamsTestWalkerNavMenu
    {
        /** @var list<array{mixed, array<int, mixed>, mixed, mixed, mixed}> */
        public static array $displayed = [];

        /** What display_element() returns. */
        public static mixed $result = 'parent-result';

        /**
         * @param  array<int, mixed>  $children_elements
         */
        public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output)
        {
            self::$displayed[] = [$element, $children_elements, $max_depth, $depth, $args];

            return self::$result;
        }
    }
}

if (! class_exists('Walker_Nav_Menu', false)) {
    class_alias(UamsTestWalkerNavMenu::class, 'Walker_Nav_Menu');
}

if (! class_exists('Walker_Page', false)) {
    /**
     * The WordPress page walker the sidebar menu walker extends.
     */
    class Walker_Page {}
}

require_once dirname(__DIR__, 2).'/setup/class.uams-dropdowns.php';
require_once dirname(__DIR__, 2).'/setup/class.dropdowns_walker.php';

if (! class_exists('UamsTestWidget', false)) {
    /**
     * Stands in for WP_Widget: it keeps what the widget classes pass to the parent constructor and builds
     * field ids and names the way a test can read. Aliased to the WordPress name below, so static analysis
     * keeps reading the WordPress stubs for `WP_Widget`.
     */
    class UamsTestWidget
    {
        /**
         * @param  array<string, mixed>  $widget_options
         */
        public function __construct(
            public mixed $id_base = '',
            public mixed $name = '',
            /** @var array<string, mixed> */
            public array $widget_options = []
        ) {}

        public function get_field_id($field_name): string
        {
            return 'id-'.$field_name;
        }

        public function get_field_name($field_name): string
        {
            return 'name-'.$field_name;
        }
    }
}

if (! class_exists('WP_Widget', false)) {
    class_alias(UamsTestWidget::class, 'WP_Widget');
}

/**
 * Load the real sidebar widget file once per process, with the hook registration it makes at file scope
 * recorded. Every test that needs UAMS_Sidebar goes through here, so the answer does not depend on which
 * test ran first. Call it while Brain Monkey is active.
 *
 * @return bool Whether loading the file hooked the sidebar registration to `widgets_init`.
 */
function loadSidebarWidgetFile(): bool
{
    static $hooked = null;

    if ($hooked === null) {
        $hooked = false;
        expectAdded('widgets_init')->once()->whenHappen(static function () use (&$hooked): void {
            $hooked = true;
        });

        require_once dirname(__DIR__, 2).'/widgets/class.uams-sidebar.php';
    }

    return $hooked;
}
