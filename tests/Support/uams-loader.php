<?php

/**
 * What the UAMS loader (setup/class.uams.php) requires and creates, shared by the loader's own test and
 * the functions.php test, which runs the real loader against a fixture theme root.
 */

declare(strict_types=1);

/**
 * The classes `UAMS::initialize()` creates, as property => [class, file that defines it].
 *
 * @var array<string, array{string, string}>
 */
const UAMS_LOADER_CLASSES = [
    'Install' => ['UAMS_Install_Theme', 'class.install.php'],
    'Scripts' => ['UAMS_Scripts', 'class.uams-scripts.php'],
    'Styles' => ['UAMS_Styles', 'class.uams-styles.php'],
    'Images' => ['UAMS_Images', 'class.images.php'],
    'SquishBugs' => ['UAMS_SquishBugs', 'class.squish_bugs.php'],
    'Filters' => ['UAMS_Filters', 'class.filters.php'],
    'OEmbeds' => ['UAMS_OEmbeds', 'class.uams-oembeds.php'],
    'Mimes' => ['UAMS_Mimes', 'class.mimes.php'],
    'Users' => ['UAMS_Users', 'class.users.php'],
    'SidebarMenuWalker' => ['UAMS_Sidebar_Menu_Walker', 'class.uams-sidebar-menu-walker.php'],
    'Dropdowns' => ['UAMS_Dropdowns', 'class.uams-dropdowns.php'],
    'Quicklinks' => ['UAMS_QuickLinks', 'class.uams-quicklinks.php'],
    'Shortcodes' => ['UAMS_Shortcodes', 'class.uams-shortcodes.php'],
    'MediaCredit' => ['UAMS_Media_Credit', 'class.uams-media-credit.php'],
    'MediaCaption' => ['UAMS_Media_Caption', 'class.uams-media-caption.php'],
    'ReplaceMedia' => ['UAMS_Replace_Media', 'class.uams-replace-media.php'],
    'TinyMCE' => ['UAMS_TinyMCE', 'class.uams-tinymce.php'],
    'IFrames' => ['UAMS_Iframes', 'class.uams-iframes.php'],
    'GoogleApps' => ['UAMS_GoogleApps', 'class.googleapps.php'],
    'Enclosure' => ['UAMS_Enclosure', 'class.uams-enclosure.php'],
    'Carousel' => ['UAMS_Carousel', 'class.uams-carousel.php'],
    'Settings' => ['UAMS_Settings', 'class.uams-settings.php'],
];

/**
 * The files `UAMS::includes()` requires from setup/, in order.
 */
const UAMS_LOADER_FILES = [
    'class.install.php', 'class.uams-scripts.php', 'class.uams-styles.php', 'class.uams-dropdowns.php',
    'class.images.php', 'class.squish_bugs.php', 'class.filters.php', 'class.uams-oembeds.php',
    'class.googleapps.php', 'class.mimes.php', 'class.users.php', 'class.dropdowns_walker.php',
    'class.uams-basic-custom-post.php', 'class.uams-sidebar-menu-walker.php', 'class.uams-quicklinks.php',
    'class.uams-iframes.php', 'class.uams-shortcodes.php', 'class.uams-media-credit.php',
    'class.uams-media-caption.php', 'class.uams-replace-media.php', 'class.uams-tinymce.php',
    'class.uams-enclosure.php', 'class.uams-carousel.php', 'class.uams-settings.php',
    'class.uams-page-attributes-meta-box.php',
];

/**
 * Every class the loader or the shortcode loader asks about, as a class_exists() answer of false.
 *
 * @return array<string, false>
 */
function loaderClassesAbsent(): array
{
    return array_fill_keys([
        ...array_column(UAMS_LOADER_CLASSES, 0),
        'TileBox', 'UAMS_Button', 'UAMS_YouTube', 'UAMS_SubpageList', 'UAMS_AccordionShortcode', 'UAMS_Map',
        'UAMS_TabsShortcode', 'UAMS_GridShortcode', 'UAMS_MenuShortcode', 'UAMS_CardsShortcode',
    ], false);
}
