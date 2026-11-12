<?php

/**
 * UAMS_Settings, the theme's options on the General and Reading screens, and UAMS_QuickLinks, the quick
 * links menu and its JSON feed, with WordPress mocked.
 *
 * @see setup/class.uams-settings.php
 * @see setup/class.uams-quicklinks.php
 */

declare(strict_types=1);
use Mockery\MockInterface;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    when('is_multisite')->justReturn(false);
    loadShortcodeFile('class.uams-settings.php');
    loadShortcodeFile('class.uams-quicklinks.php');
    stubEscaping();
});

describe('UAMS_Settings', function (): void {
    it('hooks the sections and the options', function (): void {
        $settings = new UAMS_Settings;

        expect(has_action('admin_menu', [$settings, 'setup_sections']))->toBe(10)
            ->and(has_action('admin_init', [$settings, 'setup_options']))->toBe(10);
    });

    it('sets up the pages and sections, then the options', function (): void {
        /** @var UAMS_Settings&MockInterface $settings */
        $settings = Mockery::mock(UAMS_Settings::class)->makePartial();
        $settings->shouldReceive('make_setting_pages')->once()->ordered();
        $settings->shouldReceive('add_setting_sections')->once()->ordered();
        $settings->shouldReceive('register_settings')->once()->ordered();
        $settings->shouldReceive('add_settings_fields')->once()->ordered();

        $settings->setup_sections();
        $settings->setup_options();
    });

    it('has no pages or sections yet', function (): void {
        $settings = new UAMS_Settings;

        $settings->make_setting_pages();
        $settings->add_setting_sections();

        expect(has_action('admin_menu', [$settings, 'setup_sections']))->toBe(10);
    });

    it('registers each option with a sanitize callback', function (): void {
        $registered = [];
        when('register_setting')->alias(static function (string $group, string $name, array $args) use (&$registered): void {
            $registered[] = [$group, $name, $args];
        });

        (new UAMS_Settings)->register_settings();

        $sanitize = ['sanitize_callback' => 'sanitize_text_field'];
        expect($registered)->toBe([
            ['general', 'primary_uams_site', $sanitize],
            ['general', 'overly_long_title', $sanitize],
            ['reading', 'show_byline_on_posts', $sanitize],
            ['reading', 'show_category_on_posts', $sanitize],
            ['general', 'use_main_menu_on_mobile', $sanitize],
            ['general', 'google_tag_manager_id', $sanitize],
        ]);
    });

    it('adds a field for each option', function (): void {
        $added = [];
        when('add_settings_field')->alias(static function (string $id, string $title, array $callback, string $page) use (&$added): void {
            $added[] = [$id, $title, $callback[1], $page];
        });

        (new UAMS_Settings)->add_settings_fields();

        expect($added)->toBe([
            ['primary_uams_site', 'Is this a primary UAMS site (logo on the left)?', 'primary_uams_site_callback', 'general'],
            ['overly_long_title', 'Does your site title take two lines on desktop?', 'overly_long_title_callback', 'general'],
            ['show_byline_on_posts', 'Show bylines on single posts and archives?', 'show_byline_on_posts_callback', 'reading'],
            ['show_category_on_posts', 'Show categories on single posts?', 'show_category_on_posts_callback', 'reading'],
            ['use_main_menu_on_mobile', 'Use the main menu on mobile as default?', 'use_main_menu_on_mobile_callback', 'general'],
            ['google_tag_manager_id', 'Google Tag Manager ID:', 'google_tag_manager_id_callback', 'general'],
        ]);
    });

    it('prints a checkbox that is checked when the option is set', function (string $callback, string $option, string $stored, string $checked): void {
        $asked = [];
        when('get_option')->alias(static function (string $name) use (&$asked, $stored): string {
            $asked[] = $name;

            return $stored;
        });
        when('checked')->alias(static fn (mixed $checked, mixed $current, bool $echo): string => $checked === $current ? " checked='checked'" : '');

        $html = render(fn () => (new UAMS_Settings)->{$callback}());

        expect($html)->toBe("<input name='{$option}' type='checkbox' value='1'{$checked} /> (yes if checked)")
            ->and($asked)->toBe([$option]);
    })->with(function () {
        foreach ([
            'primary_uams_site_callback' => 'primary_uams_site',
            'overly_long_title_callback' => 'overly_long_title',
            'show_byline_on_posts_callback' => 'show_byline_on_posts',
            'show_category_on_posts_callback' => 'show_category_on_posts',
            'use_main_menu_on_mobile_callback' => 'use_main_menu_on_mobile',
        ] as $callback => $option) {
            yield $option.' on' => [$callback, $option, '1', " checked='checked'"];
            yield $option.' off' => [$callback, $option, '', ''];
            yield $option.' other' => [$callback, $option, '2', ''];
        }
    });

    it('prints the tag manager id field', function (): void {
        when('get_option')->alias(static fn (string $name): string => $name === 'google_tag_manager_id' ? 'GTM-1' : 'wrong');

        $html = render(fn () => (new UAMS_Settings)->google_tag_manager_id_callback());

        expect($html)->toBe("<input name='google_tag_manager_id' type='text' size='20' value='A{GTM-1}' /> (Leave Blank for Default)");
    });
});

describe('UAMS_QuickLinks', function (): void {
    it('registers the menu on a single site and on the allowed blog of a network', function (bool $multisite, mixed $blog): void {
        when('is_multisite')->justReturn($multisite);
        when('get_current_blog_id')->justReturn($blog);

        $links = new UAMS_QuickLinks;

        expect(has_action('after_setup_theme', [$links, 'register_quick_links_menu']))->toBe(10)
            ->and($links->MULTISITE)->toBe($multisite)
            ->and(has_action('wp_ajax_quicklinks', [$links, 'uams_quicklinks_feed']))->toBe(10)
            ->and(has_action('wp_ajax_nopriv_quicklinks', [$links, 'uams_quicklinks_feed']))->toBe(10);
    })->with([
        'a single site' => [false, 9],
        'the allowed blog' => [true, 1],
    ]);

    it('leaves the menu to the allowed blog of a network', function (mixed $blog): void {
        when('is_multisite')->justReturn(true);
        when('get_current_blog_id')->justReturn($blog);

        $links = new UAMS_QuickLinks;

        expect(has_action('after_setup_theme', [$links, 'register_quick_links_menu']))->toBeFalse()
            ->and(has_action('wp_ajax_quicklinks', [$links, 'uams_quicklinks_feed']))->toBe(10);
    })->with(['another blog' => [2], 'the blog id as a string' => ['1']]);

    it('registers the quick links location', function (): void {
        $registered = [];
        when('__')->alias(static fn (string $text): string => '__('.$text.')');
        when('register_nav_menu')->alias(static function (string $location, string $description) use (&$registered): void {
            $registered[] = [$location, $description];
        });

        (new UAMS_QuickLinks)->register_quick_links_menu();

        expect($registered)->toBe([['quick-links', '__(Quick Links)']]);
    });

    describe('the feed', function (): void {
        beforeEach(function (): void {
            $GLOBALS['uams_test_links'] = [];
            when('switch_to_blog')->alias(static function (int $blog): void {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $GLOBALS['uams_test_links'][] = ['switch', $blog];
            });
            when('restore_current_blog')->alias(static function (): void {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $GLOBALS['uams_test_links'][] = ['restore'];
            });
            when('wp_get_nav_menu_items')->alias(static function (mixed $menu): array {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $GLOBALS['uams_test_links'][] = ['items', $menu];

                return [(object) ['ID' => 1, 'title' => 'One', 'url' => 'https://x.test/1', 'classes' => ['', 'a'], 'menu_item_parent' => '0', 'extra' => 'dropped']];
            });
            when('wp_send_json')->alias(static function (mixed $response): void {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $GLOBALS['uams_test_links'][] = ['json', $response];
            });
        });

        it('sends the menu assigned to the location', function (): void {
            when('get_nav_menu_locations')->justReturn(['quick-links' => 12]);
            when('wp_get_nav_menu_object')->alias(static function (): never {
                throw new RuntimeException('looked up by name');
            });

            (new UAMS_QuickLinks)->uams_quicklinks_feed();

            expect($GLOBALS['uams_test_links'])->toBe([
                ['items', 12],
                ['json', [['ID' => 1, 'title' => 'One', 'url' => 'https://x.test/1', 'classes' => false, 'menu_item_parent' => '0']]],
            ]);
        });

        it('falls back to the menu named after the location', function (): void {
            $asked = [];
            when('get_nav_menu_locations')->justReturn(['other' => 3]);
            when('wp_get_nav_menu_object')->alias(static function (string $name) use (&$asked): object {
                $asked[] = $name;

                return (object) ['term_id' => 44];
            });

            (new UAMS_QuickLinks)->uams_quicklinks_feed();

            expect($asked)->toBe(['quick-links'])
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                ->and($GLOBALS['uams_test_links'][0])->toBe(['items', 44]);
        });

        it('sends an empty menu when there is none', function (): void {
            when('get_nav_menu_locations')->justReturn([]);
            when('wp_get_nav_menu_object')->justReturn(false);

            (new UAMS_QuickLinks)->uams_quicklinks_feed();

            expect($GLOBALS['uams_test_links'])->toBe([['json', []]]);
        });

        it('reads the menu from the main blog of a network and switches back', function (): void {
            when('is_multisite')->justReturn(true);
            when('get_current_blog_id')->justReturn(1);
            when('get_nav_menu_locations')->justReturn(['quick-links' => 12]);

            (new UAMS_QuickLinks)->uams_quicklinks_feed();

            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            expect(array_column($GLOBALS['uams_test_links'], 0))->toBe(['switch', 'items', 'restore', 'json'])
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                ->and($GLOBALS['uams_test_links'][0])->toBe(['switch', 1]);
        });
    });

    describe('parse_menu', function (): void {
        it('keeps only the fields the feed needs, in the order of the item', function (): void {
            $links = new UAMS_QuickLinks;
            $links->items = [
                (object) ['url' => 'https://x.test/a', 'ID' => 2, 'title' => 'A', 'classes' => ['featured'], 'menu_item_parent' => '0', 'target' => '_blank'],
            ];

            expect($links->parse_menu())->toBe([
                ['url' => 'https://x.test/a', 'ID' => 2, 'title' => 'A', 'classes' => ['featured'], 'menu_item_parent' => '0'],
            ]);
        });

        it('replaces missing or empty classes with false', function (array $item): void {
            $links = new UAMS_QuickLinks;
            $links->items = [(object) (['ID' => 1] + $item)];

            expect($links->parse_menu())->toBe([['ID' => 1, 'classes' => false]]);
        })->with([
            'no classes' => [[]],
            'an empty list' => [['classes' => []]],
            'an empty first class' => [['classes' => ['']]],
            'a first class of zero' => [['classes' => ['0']]],
        ]);

        it('keeps classes whose first entry is set', function (): void {
            $links = new UAMS_QuickLinks;
            $links->items = [(object) ['classes' => ['a', '']]];

            expect($links->parse_menu())->toBe([['classes' => ['a', '']]]);
        });

        it('returns an empty menu without items', function (mixed $items): void {
            $links = new UAMS_QuickLinks;
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore assign.propertyType
            $links->items = $items;

            expect($links->parse_menu())->toBe([]);
        })->with(['none' => [[]], 'not an array' => ['x'], 'false' => [false]]);
    });
});
