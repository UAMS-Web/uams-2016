<?php

/**
 * The [custommenu] shortcode, with WordPress mocked.
 *
 * setup/class.menu-shortcode.php marks the `walker` default's `EmptyStringToNotEmpty` with
 * `@pest-mutate-ignore`. Equivalent: a default that names no class passes no walker, the same
 * empty string the empty default passes ('passes no walker when its class does not exist').
 *
 * @see setup/class.menu-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.menu-shortcode.php');
    stubShortcodeApi();
});

/**
 * The arguments the shortcode passes wp_nav_menu() for no attributes.
 *
 * @return array<string, mixed>
 */
function defaultMenuArgs(): array
{
    return [
        'menu' => '',
        'container' => 'div',
        'container_class' => 'icon-menu',
        'container_id' => '',
        'menu_class' => 'menu',
        'menu_id' => '',
        'echo' => false,
        'fallback_cb' => 'wp_page_menu',
        'before' => '',
        'after' => '',
        'link_before' => '',
        'link_after' => '',
        'depth' => 0,
        'walker' => '',
        'theme_location' => '',
    ];
}

it('registers the custommenu shortcode', function (): void {
    $calls = recordShortcodes();
    $menu = new UAMS_MenuShortcode;

    expect($calls->getArrayCopy())->toBe([['custommenu', [$menu, 'vo_custom_menu_shortcode']]]);
});

it('returns the menu, built with the defaults and never echoed', function (): void {
    Functions\expect('wp_nav_menu')->once()->with(defaultMenuArgs())->andReturn('<nav/>');

    expect((new UAMS_MenuShortcode)->vo_custom_menu_shortcode(''))->toBe('<nav/>')
        ->and(shortcodeAttsNames())->toBe(['custommenu']);
});

it('passes every attribute through, with the depth as an integer', function (): void {
    $atts = [
        'menu' => 'Main',
        'container' => 'nav',
        'container_class' => 'c',
        'container_id' => 'cid',
        'menu_class' => 'm',
        'menu_id' => 'mid',
        'fallback_cb' => 'fb',
        'before' => 'b',
        'after' => 'a',
        'link_before' => 'lb',
        'link_after' => 'la',
        'depth' => '2',
        'theme_location' => 'primary',
    ];

    $passed = null;
    when('wp_nav_menu')->alias(static function (array $args) use (&$passed): string {
        $passed = $args;

        return '<nav/>';
    });

    expect((new UAMS_MenuShortcode)->vo_custom_menu_shortcode($atts))->toBe('<nav/>')
        ->and($passed)->toBe([
            'menu' => 'Main',
            'container' => 'nav',
            'container_class' => 'c',
            'container_id' => 'cid',
            'menu_class' => 'm',
            'menu_id' => 'mid',
            'echo' => false,
            'fallback_cb' => 'fb',
            'before' => 'b',
            'after' => 'a',
            'link_before' => 'lb',
            'link_after' => 'la',
            'depth' => 2,
            'walker' => '',
            'theme_location' => 'primary',
        ]);
});

it('constructs the walker when its class exists', function (): void {
    Functions\expect('wp_nav_menu')
        ->once()
        ->with(Mockery::on(static fn (array $args): bool => $args['walker'] instanceof ArrayObject))
        ->andReturn('<nav/>');

    (new UAMS_MenuShortcode)->vo_custom_menu_shortcode(['walker' => 'ArrayObject']);
});

it('passes no walker when its class does not exist', function (): void {
    Functions\expect('wp_nav_menu')
        ->once()
        ->with(array_merge(defaultMenuArgs()))
        ->andReturn('<nav/>');

    (new UAMS_MenuShortcode)->vo_custom_menu_shortcode(['walker' => 'No_Such_Walker_Class']);
});
