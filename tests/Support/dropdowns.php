<?php

/**
 * Stand-ins for the dropdown menu classes in setup/class.uams-dropdowns.php, which the
 * template functions look up with class_exists().
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
