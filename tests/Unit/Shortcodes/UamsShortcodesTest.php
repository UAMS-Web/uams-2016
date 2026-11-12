<?php

/**
 * UAMS_Shortcodes, the object that loads and holds every shortcode, with WordPress mocked.
 *
 * Its constructor requires each shortcode file from the template directory with require_once, so
 * a path is included at most once per process. The tests point the template directory at two
 * fixture roots of stand-in files that record their inclusion (tests/Support/shortcodes-root, and
 * shortcodes-root-no-youtube without the optional YouTube file), and `loadedShortcodeFiles()`
 * keeps what each root's first construction included. The real classes are loaded beforehand, so
 * the objects it builds are the real ones.
 *
 * @see setup/class.uams-shortcodes.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

const SHORTCODE_CLASSES = [
    'tile_box' => 'TileBox',
    'button' => 'UAMS_Button',
    'youtube' => 'UAMS_YouTube',
    'subpage_list' => 'UAMS_SubpageList',
    'accordion' => 'UAMS_AccordionShortcode',
    'maps' => 'UAMS_Map',
    'tabs' => 'UAMS_TabsShortcode',
    'bootstrap' => 'UAMS_GridShortcode',
    'custommenu' => 'UAMS_MenuShortcode',
    'cards' => 'UAMS_CardsShortcode',
];

beforeEach(function (): void {
    foreach (['tile-box', 'button', 'youtube', 'subpage-list', 'accordion', 'maps', 'tabs', 'grid', 'menu', 'cards'] as $name) {
        loadShortcodeFile('class.'.$name.'-shortcode.php');
    }

    loadShortcodeFile('class.uams-shortcodes.php');
});

/**
 * Construct UAMS_Shortcodes with the template directory at a fixture root.
 */
function shortcodesFrom(string $root): UAMS_Shortcodes
{
    when('get_template_directory')->justReturn(dirname(__DIR__, 2).'/Support/'.$root);

    return new UAMS_Shortcodes;
}

/**
 * The fixture files the first construction from a root included, in order.
 *
 * @return list<string>
 */
function loadedShortcodeFiles(string $root): array
{
    static $records = [];

    if (! isset($records[$root])) {
        $GLOBALS['uams_fixture_shortcode_includes'] = [];
        shortcodesFrom($root);
        $records[$root] = $GLOBALS['uams_fixture_shortcode_includes'];
    }

    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore return.type
    return $records[$root];
}

it('includes every shortcode file, in order', function (): void {
    expect(loadedShortcodeFiles('shortcodes-root'))->toBe(array_map(static fn (string $name): string => 'shortcodes-root/class.'.$name.'-shortcode.php', [
        'tile-box', 'button', 'youtube', 'subpage-list', 'accordion', 'maps', 'tabs', 'grid', 'menu', 'cards',
    ]));
});

it('skips the YouTube file when it is not there', function (): void {
    expect(loadedShortcodeFiles('shortcodes-root-no-youtube'))->toBe(array_map(static fn (string $name): string => 'shortcodes-root-no-youtube/class.'.$name.'-shortcode.php', [
        'tile-box', 'button', 'subpage-list', 'accordion', 'maps', 'tabs', 'grid', 'menu', 'cards',
    ]));
});

it('builds each shortcode whose class exists', function (): void {
    $shortcodes = shortcodesFrom('shortcodes-root');

    foreach (SHORTCODE_CLASSES as $property => $class) {
        expect($shortcodes->{$property})->toBeInstanceOf($class);
    }
});

it('leaves a shortcode empty when its class does not exist', function (): void {
    stubClassExists(array_fill_keys(SHORTCODE_CLASSES, false));
    $shortcodes = shortcodesFrom('shortcodes-root');

    foreach (array_keys(SHORTCODE_CLASSES) as $property) {
        expect($shortcodes->{$property})->toBeNull();
    }
});
