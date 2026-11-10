<?php

/**
 * The theme classes under setup/ that the templates and template functions name: the dropdown menu
 * classes (looked up with class_exists()) are the real ones, so the tests of the classes themselves can
 * run in the same process, and the sidebar's id constant is a stand-in.
 */

declare(strict_types=1);

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

if (! class_exists('UAMS_Sidebar', false)) {
    class UAMS_Sidebar
    {
        public const ID = 'stub-sidebar';
    }
}
