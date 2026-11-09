<?php

/**
 * The editor buttons for the shortcodes (UAMS_TinyShortcode), with WordPress mocked.
 *
 * UAMS_Shortcodes does not load this file; it is in the coverage population all the same.
 *
 * The `RemoveArrayItem` on each of the three shortcode names carries `@pest-mutate-ignore`: coverage
 * attributes no line to a property's default, so the mutation run reports the mutants uncovered.
 * Not equivalent: the button and plugin tests list all three names, so removing one fails them.
 *
 * @see setup/class.tiny-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.tiny-shortcode.php');
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
});

it('hooks its setup to init', function (): void {
    $tiny = new UAMS_TinyShortcode;

    expect(has_action('init', [$tiny, 'init']))->toBe(10)
        ->and($tiny->shortcodes)->toBe(['grid', 'button', 'tilebox']);
});

describe('init', function (): void {
    it('does nothing outside the admin', function (): void {
        when('is_admin')->justReturn(false);
        Functions\expect('wp_enqueue_style')->never();

        (new UAMS_TinyShortcode)->init();
    });

    it('loads the admin style, and adds no buttons for a user who cannot edit', function (): void {
        when('is_admin')->justReturn(true);
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('bs_admin_style', 'https://t.test/theme/assets/admin/css/bootstrap-shortcode-admin.css', [], null);
        when('current_user_can')->justReturn(false);
        Functions\expect('get_user_option')->never();

        $tiny = new UAMS_TinyShortcode;
        $tiny->init();

        expect(has_filter('mce_external_plugins', [$tiny, 'regplugins']))->toBeFalse();
    });

    it('adds the editor buttons for a user who can edit posts or pages, with the visual editor on', function (bool $posts, bool $pages): void {
        when('is_admin')->justReturn(true);
        when('wp_enqueue_style')->justReturn();
        when('current_user_can')->alias(static fn (string $cap): bool => $cap === 'edit_posts' ? $posts : $pages);
        Functions\expect('get_user_option')->once()->with('rich_editing')->andReturn('true');

        $tiny = new UAMS_TinyShortcode;
        $tiny->init();

        expect(has_filter('mce_external_plugins', [$tiny, 'regplugins']))->toBe(10)
            ->and(has_filter('mce_buttons_3', [$tiny, 'regbtns']))->toBe(10);
    })->with([
        'posts and pages' => [true, true],
        'posts only' => [true, false],
        'pages only' => [false, true],
    ]);

    it('adds no buttons with the visual editor off', function (mixed $option): void {
        when('is_admin')->justReturn(true);
        when('wp_enqueue_style')->justReturn();
        when('current_user_can')->justReturn(true);
        when('get_user_option')->justReturn($option);

        $tiny = new UAMS_TinyShortcode;
        $tiny->init();

        expect(has_filter('mce_external_plugins', [$tiny, 'regplugins']))->toBeFalse()
            ->and(has_filter('mce_buttons_3', [$tiny, 'regbtns']))->toBeFalse();
    })->with([
        'false' => ['false'],
        'unset' => [false],
    ]);
});

it('adds a button for each shortcode to the third toolbar row', function (mixed $buttons, array $expected): void {
    expect((new UAMS_TinyShortcode)->regbtns($buttons))->toBe($expected);
})->with([
    'after the existing buttons' => [['bold'], ['bold', 'bs_grid', 'bs_button', 'bs_tilebox']],
    'when the row is not a list' => [null, ['bs_grid', 'bs_button', 'bs_tilebox']],
]);

it('registers a TinyMCE plugin script for each shortcode', function (mixed $plugins, array $expected): void {
    expect((new UAMS_TinyShortcode)->regplugins($plugins))->toBe($expected);
})->with([
    'beside the existing plugins' => [['other' => 'o.js'], [
        'other' => 'o.js',
        'bs_grid' => 'https://t.test/theme/js/plugins/grid.js',
        'bs_button' => 'https://t.test/theme/js/plugins/button.js',
        'bs_tilebox' => 'https://t.test/theme/js/plugins/tilebox.js',
    ]],
    'when the list is not an array' => ['', [
        'bs_grid' => 'https://t.test/theme/js/plugins/grid.js',
        'bs_button' => 'https://t.test/theme/js/plugins/button.js',
        'bs_tilebox' => 'https://t.test/theme/js/plugins/tilebox.js',
    ]],
]);
