<?php

/**
 * Stand-ins for the theme classes under setup/ that the templates and template functions name:
 * the dropdown menu classes (looked up with class_exists()) and the sidebar's id constant.
 */

declare(strict_types=1);

if (! class_exists('UAMS_Dropdowns', false)) {
    class UAMS_Dropdowns
    {
        public const LOCATION = 'stub-location';
    }
}

if (! class_exists('UAMS_Dropdowns_Walker_Menu', false)) {
    class UAMS_Dropdowns_Walker_Menu {}
}

if (! class_exists('UAMS_Sidebar', false)) {
    class UAMS_Sidebar
    {
        public const ID = 'stub-sidebar';
    }
}
