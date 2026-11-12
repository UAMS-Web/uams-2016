<?php

/**
 * UAMS_Dropdowns, the default White Bar menu the theme installs, with WordPress mocked.
 *
 * @see setup/class.uams-dropdowns.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    require_once __DIR__.'/../../Support/theme-classes.php';
    $this->calls = [];
});

/**
 * The default menu: top-level title, URL, and its children as [title, URL] pairs.
 *
 * @return list<array{string, string, list<array{string, string}>}>
 */
function defaultDropdowns(): array
{
    return [
        ['About', 'http://web.uams.edu/about/', [
            ['About UAMS', 'http://web.uams.edu/about/'],
            ['Vision, Mission & Core Values', 'http://web.uams.edu/about/vision-mission-core-values/'],
            ['Leadership', 'http://web.uams.edu/about/leadership/'],
            ['Fast Facts', 'http://web.uams.edu/about/fast-facts/'],
            ['History', 'http://web.uams.edu/about/uams-history/'],
            ['Contact Us', 'http://web.uams.edu/about/contact-information/'],
        ]],
        ['Academics', 'http://web.uams.edu/for-faculty-staff-and-students/', [
            ['Departments & Divisions', 'http://web.uams.edu/departments-and-divisions/'],
            ['Colleges', 'http://web.uams.edu/educational-programs-at-uams/'],
            ['Students', 'http://students.uams.edu'],
            ['Faculty & Administration', 'http://web.uams.edu/for-faculty-staff-and-students/'],
        ]],
        ['Apply', 'http://students.uams.edu/apply/', []],
        ['News & Events', 'http://uamshealth.com/news/', []],
        ['Research', 'http://research.uams.edu', []],
    ];
}

/**
 * A menu item as the class builds it.
 *
 * @return array<string, string>
 */
function dropdownItem(string $title, string $url): array
{
    return ['menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-status' => 'publish'];
}

it('hooks the menu registration, installation and save check', function (): void {
    $dropdowns = new UAMS_Dropdowns;

    expect(has_action('after_setup_theme', [$dropdowns, 'register_white_bar_menu']))->toBe(10)
        ->and(has_action('after_setup_theme', [$dropdowns, 'install_default_white_bar_menu']))->toBe(10)
        ->and(has_action('wp_update_nav_menu', [$dropdowns, 'save_white_bar']))->toBe(10)
        ->and($dropdowns->menu_items)->toBe([])
        ->and(UAMS_Dropdowns::NAME)->toBe('White Bar')
        ->and(UAMS_Dropdowns::LOCATION)->toBe('white-bar')
        ->and(UAMS_Dropdowns::DISPLAY_NAME)->toBe('Dropdowns')
        ->and(UAMS_Dropdowns::DEFAULT_STATUS)->toBe('publish');
});

it('registers the white bar location', function (): void {
    $registered = [];
    when('__')->alias(static fn (string $text): string => '__('.$text.')');
    when('register_nav_menu')->alias(static function (string $location, string $description) use (&$registered): void {
        $registered[] = [$location, $description];
    });

    (new UAMS_Dropdowns)->register_white_bar_menu();

    expect($registered)->toBe([['white-bar', '__(White Bar)']]);
});

describe('add_menu_item', function (): void {
    it('adds a top-level item under its name', function (): void {
        $dropdowns = new UAMS_Dropdowns;

        $dropdowns->add_menu_item('Apply', 'http://x.test/apply');

        expect($dropdowns->menu_items)->toBe(['Apply' => dropdownItem('Apply', 'http://x.test/apply')]);
    });

    it('adds a child under its parent', function (): void {
        $dropdowns = new UAMS_Dropdowns;
        $dropdowns->add_menu_item('About', 'http://x.test/about');

        $dropdowns->add_menu_item('Team', 'http://x.test/team', 'About');

        expect($dropdowns->menu_items)->toBe(['About' => dropdownItem('About', 'http://x.test/about') + [
            'children' => ['Team' => dropdownItem('Team', 'http://x.test/team')],
        ]]);
    });

    it('treats an empty parent as none', function (): void {
        $dropdowns = new UAMS_Dropdowns;

        $dropdowns->add_menu_item('Apply', 'http://x.test/apply', '');

        expect(array_keys($dropdowns->menu_items))->toBe(['Apply']);
    });
});

it('lists the default dropdowns', function (): void {
    $dropdowns = new UAMS_Dropdowns;

    $dropdowns->generate_menu_list();

    $expected = [];
    foreach (defaultDropdowns() as [$title, $url, $children]) {
        $expected[$title] = dropdownItem($title, $url);
        foreach ($children as [$childTitle, $childUrl]) {
            // The item array is extended with a children map after dropdownItem() builds it.
            // @phpstan-ignore offsetAssign.dimType
            $expected[$title]['children'][$childTitle] = dropdownItem($childTitle, $childUrl);
        }
    }

    expect($dropdowns->menu_items)->toBe($expected);
});

describe('install_default_white_bar_menu', function (): void {
    beforeEach(function (): void {
        when('is_wp_error')->alias(static fn (mixed $value): bool => $value instanceof WP_Error);
        $calls = &$this->calls;
        when('get_theme_mod')->justReturn(['primary' => 3]);
        when('set_theme_mod')->alias(static function (string $name, mixed $value) use (&$calls): void {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $calls[] = ['theme_mod', $name, $value];
        });
    });

    it('does nothing when the menu already exists', function (): void {
        when('wp_create_nav_menu')->justReturn(new WP_Error);
        when('wp_update_nav_menu_item')->alias(static function (): never {
            throw new RuntimeException('item added');
        });
        $dropdowns = new UAMS_Dropdowns;

        $dropdowns->install_default_white_bar_menu();

        expect($this->calls)->toBe([])
            ->and($dropdowns->MENU_ID)->toBeInstanceOf(WP_Error::class);
    });

    it('creates the menu with its items and children, then sets its location', function (): void {
        $created = [];
        $next = 100;
        $calls = &$this->calls;
        when('wp_create_nav_menu')->alias(static function (string $name) use (&$created): int {
            $created[] = $name;

            return 7;
        });
        when('wp_update_nav_menu_item')->alias(static function (int $menu, int $item, array $attributes) use (&$calls, &$next): int {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $calls[] = ['item', $menu, $item, $attributes];

            return $next++;
        });

        $dropdowns = new UAMS_Dropdowns;
        $dropdowns->install_default_white_bar_menu();

        $expected = [];
        $id = 100;
        foreach (defaultDropdowns() as [$title, $url, $children]) {
            $parent = $id++;
            $expected[] = ['item', 7, 0, dropdownItem($title, $url)];
            foreach ($children as [$childTitle, $childUrl]) {
                $expected[] = ['item', 7, 0, dropdownItem($childTitle, $childUrl) + ['menu-item-parent-id' => $parent]];
                $id++;
            }
        }

        $expected[] = ['theme_mod', 'nav_menu_locations', ['primary' => 3, 'white-bar' => 7]];

        expect($created)->toBe(['Dropdowns'])
            ->and($this->calls)->toBe($expected)
            ->and($dropdowns->MENU_ID)->toBe(7);
    });

    it('skips the children of an item that could not be added', function (): void {
        $calls = &$this->calls;
        when('wp_create_nav_menu')->justReturn(7);
        when('wp_update_nav_menu_item')->alias(static function (int $menu, int $item, array $attributes) use (&$calls): WP_Error|int {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $calls[] = $attributes['menu-item-title'];

            return $attributes['menu-item-title'] === 'About' ? new WP_Error : 1;
        });

        (new UAMS_Dropdowns)->install_default_white_bar_menu();

        expect($calls)->not->toContain('About UAMS')
            ->and($calls)->toContain('About', 'Academics', 'Colleges');
    });
});

describe('set_uams_menu_location', function (): void {
    it('adds the white bar to the existing locations', function (mixed $existing, array $expected): void {
        $set = [];
        when('get_theme_mod')->alias(static fn (string $name): mixed => $name === 'nav_menu_locations' ? $existing : 'wrong');
        when('set_theme_mod')->alias(static function (string $name, mixed $value) use (&$set): void {
            $set[] = [$name, $value];
        });
        $dropdowns = new UAMS_Dropdowns;
        $dropdowns->MENU_ID = 9;

        $dropdowns->set_uams_menu_location();

        expect($set)->toBe([['nav_menu_locations', $expected]]);
    })->with([
        'existing locations' => [['primary' => 3], ['primary' => 3, 'white-bar' => 9]],
        'none set' => [false, [0 => false, 'white-bar' => 9]],
        'null' => [null, ['white-bar' => 9]],
    ]);
});

describe('save_white_bar', function (): void {
    beforeEach(function (): void {
        when('wp_die')->alias(static function (string $message): never {
            throw new RuntimeException($message);
        });
    });

    it('lets any edit through for a menu that is not the dropdowns menu', function (mixed $menu): void {
        when('wp_get_nav_menu_object')->justReturn($menu);
        when('current_user_can')->justReturn(false);

        (new UAMS_Dropdowns)->save_white_bar(5);

        expect($menu)->not->toBeNull();
    })->with([
        'none' => [false],
        'another menu' => [(object) ['slug' => 'main']],
        'a menu without a slug' => [(object) []],
    ]);

    it('lets a network admin or administrator edit the dropdowns menu', function (string $capability): void {
        when('wp_get_nav_menu_object')->justReturn((object) ['slug' => 'dropdowns']);
        when('current_user_can')->alias(static fn (string $asked): bool => $asked === $capability);

        (new UAMS_Dropdowns)->save_white_bar(5);

        expect($capability)->not->toBe('');
    })->with(['manage_network', 'administrator']);

    it('stops anyone else editing the dropdowns menu', function (): void {
        $asked = [];
        when('wp_get_nav_menu_object')->alias(static function (int $id) use (&$asked): object {
            $asked[] = $id;

            return (object) ['slug' => 'dropdowns'];
        });
        when('current_user_can')->justReturn(false);

        expect(fn () => (new UAMS_Dropdowns)->save_white_bar(5))
            ->toThrow(RuntimeException::class, 'Insufficient permission: cannot edit the default dropdowns menu.')
            ->and($asked)->toBe([5]);
    });
});
