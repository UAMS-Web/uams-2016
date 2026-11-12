<?php

/**
 * The menu helpers in inc/template-functions.php, with WordPress mocked: the dropdown navigation,
 * the sidebar and mobile menus, and the page lists behind them.
 *
 * The dropdown classes live in setup/class.uams-dropdowns.php and are loaded for real. class_exists() is
 * redefinable (patchwork.json), so a test states whether each class "exists" for the code under test.
 *
 * Mutants not killed here, and why. The defaults of `uams_mobile_front_page_menu($class = '')` and
 * `uams_list_pages($mobile = false)` carry `@pest-mutate-ignore`: coverage attributes no line to a
 * function signature, so the mutation run reports their mutants as uncovered and never runs a test
 * against them. Neither is an equivalent mutant: "prints the front-page mobile navigation without
 * a class" and "wraps the pages in the sidebar list" call each function with its default and
 * assert the output the default produces.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../Support/theme-classes.php';

const HOME_LINK = '<a href="U{https://site.test/}" title="Home" class="homelink">Home</a>';
const MENU_TOGGLE = '<button class="uams-mobile-menu-toggle">Menu</button>';
const PAGES_MARKER = 'child-page-existance-tester';

/**
 * Pretend the dropdown classes are absent, or present, for the rest of the test.
 */
function dropdowns(bool $menu, bool $walker): void
{
    stubClassExists([
        'UAMS_Dropdowns' => $menu,
        'UAMS_Dropdowns_Walker_Menu' => $walker,
    ]);
}

/**
 * Arrange the globals and WordPress calls uams_list_pages() needs to reach wp_list_pages().
 *
 * @param  array<string, mixed>  $options
 */
function sidebarPage(array $options = []): void
{
    $GLOBALS['post'] = $options['post'] ?? wpPost(['ID' => 5]);
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore argument.type
    wpConditionals($options['conditionals'] ?? []);
    if ($options['stubLists'] ?? true) {
        when('get_children')->justReturn($options['children'] ?? [1]);
        when('get_pages')->justReturn($options['siblings'] ?? []);
    }
}

beforeEach(function (): void {
    stubEscaping();
    unset($GLOBALS['post'], $GLOBALS['UAMS']);
    dropdowns(false, false);
});

afterEach(function (): void {
    unset($GLOBALS['post'], $GLOBALS['UAMS']);
});

describe('uams_dropdowns', function (): void {
    it('prints the navigation wrapper around the menu', function (): void {
        when('wp_nav_menu')->alias(static function (): void {
            echo '[MENU]';
        });

        uams_dropdowns();
    })->expectOutputString('<nav id="reddiedrops" aria-label="Main menu"><div class="reddiedrops-inner container" role="application">[MENU]</div></nav>');

    it('falls back to the white-bar location and no walker without the dropdown classes', function (): void {
        $received = null;
        Functions\expect('wp_nav_menu')->once()->andReturnUsing(function (array $args) use (&$received): void {
            $received = $args;
        });

        $output = render(uams_dropdowns(...));

        expect($received)->toBe([
            'theme_location' => 'white-bar',
            'container' => false,
            'menu_class' => 'reddiedrops-nav',
            'fallback_cb' => '',
            'walker' => '',
        ])->and($output)->toContain('role="application"');
    });

    it('takes the location from the dropdown class when it exists', function (): void {
        dropdowns(true, false);
        $received = null;
        Functions\expect('wp_nav_menu')->once()->andReturnUsing(function (array $args) use (&$received): void {
            $received = $args;
        });

        render(uams_dropdowns(...));

        /** @var array<string, mixed> $received */
        expect($received['theme_location'])->toBe('white-bar')
            ->and($received['walker'])->toBe('');
    });

    it('uses the dropdown walker when its class exists', function (): void {
        dropdowns(false, true);
        $received = null;
        Functions\expect('wp_nav_menu')->once()->andReturnUsing(function (array $args) use (&$received): void {
            $received = $args;
        });

        render(uams_dropdowns(...));

        /** @var array<string, mixed> $received */
        expect($received['walker'])->toBeInstanceOf(UAMS_Dropdowns_Walker_Menu::class)
            ->and($received['theme_location'])->toBe('white-bar');
    });
});

describe('uams_list_pages', function (): void {
    it('lists nothing outside a post', function (): void {
        $GLOBALS['post'] = null;
        Functions\expect('wp_list_pages')->never();

        expect(uams_list_pages())->toBe('');
    });

    it('lists nothing when the page is its own parent and has no published children', function (): void {
        sidebarPage([
            'post' => wpPost(['ID' => 5, 'post_parent' => 5]),
            'children' => [],
        ]);
        when('get_post')->justReturn(wpPost(['ID' => 5]));
        Functions\expect('wp_list_pages')->never();

        expect(uams_list_pages())->toBe('');
    });

    it('asks for published children of the current page', function (): void {
        sidebarPage(['post' => wpPost(['ID' => 5, 'post_parent' => 0]), 'stubLists' => false]);
        when('get_pages')->justReturn([]);
        Functions\expect('get_children')
            ->once()
            ->with(['post_parent' => 5, 'post_status' => 'publish'])
            ->andReturn([]);
        when('wp_list_pages')->justReturn('');

        uams_list_pages();
    });

    it('lists the pages when the page is its own parent but has children', function (): void {
        sidebarPage([
            'post' => wpPost(['ID' => 5, 'post_parent' => 5]),
            'children' => [9],
        ]);
        when('get_post')->justReturn(wpPost(['ID' => 5]));
        Functions\expect('wp_list_pages')->once()->andReturn('');

        uams_list_pages();
    });

    it('lists the pages when the parent is another page', function (): void {
        sidebarPage([
            'post' => wpPost(['ID' => 5, 'post_parent' => 3]),
            'children' => [],
        ]);
        when('get_post')->justReturn(wpPost(['ID' => 3, 'post_parent' => 0]));
        Functions\expect('wp_list_pages')->once()->andReturn('');

        uams_list_pages();
    });

    it('lists the pages for a mobile menu without asking for children', function (): void {
        sidebarPage(['post' => wpPost(['ID' => 5, 'post_parent' => 5])]);
        when('get_post')->justReturn(wpPost(['ID' => 5]));
        Functions\expect('get_children')->never();
        Functions\expect('wp_list_pages')->once()->andReturn('');

        uams_list_pages(true);
    });

    it('does not look up a parent for a top-level page', function (): void {
        sidebarPage(['post' => wpPost(['ID' => 5, 'post_parent' => 0])]);
        Functions\expect('get_post')->never();
        when('wp_list_pages')->justReturn('');

        uams_list_pages();
    });

    it('reads siblings and the exclusion list from the parent page', function (): void {
        sidebarPage(['post' => wpPost(['ID' => 5, 'post_parent' => 3]), 'stubLists' => false]);
        when('get_children')->justReturn([]);
        when('get_post')->justReturn(wpPost(['ID' => 3, 'post_parent' => 1]));
        Functions\expect('get_pages')
            ->once()
            ->with(['parent' => 1, 'post_type' => 'page', 'exclude' => [3]])
            ->andReturn([]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['child_of'] === 1))
            ->andReturn('');

        uams_list_pages();
    });

    it('treats a parent that is not a post as no parent', function (): void {
        sidebarPage(['post' => wpPost(['ID' => 5, 'post_parent' => 3]), 'stubLists' => false]);
        when('get_children')->justReturn([]);
        when('get_post')->justReturn();
        Functions\expect('get_pages')
            ->once()
            ->with(['parent' => 0, 'post_type' => 'page', 'exclude' => []])
            ->andReturn([]);
        when('wp_list_pages')->justReturn('');

        uams_list_pages();
    });

    it('passes the whole argument list to wp_list_pages', function (): void {
        sidebarPage(['siblings' => [wpPost(['ID' => 7]), wpPost(['ID' => 8])]]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with([
                'title_li' => HOME_LINK,
                'child_of' => 0,
                'exclude_tree' => [7, 8],
                'depth' => 3,
                'echo' => false,
                'walker' => '',
            ])
            ->andReturn('');

        uams_list_pages();
    });

    it('excludes nothing on the front page', function (): void {
        sidebarPage([
            'conditionals' => ['is_front_page' => true],
            'siblings' => [wpPost(['ID' => 7])],
        ]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['exclude_tree'] === []))
            ->andReturn('');

        uams_list_pages();
    });

    it('excludes nothing when the siblings are not a list', function (): void {
        sidebarPage(['siblings' => false]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['exclude_tree'] === []))
            ->andReturn('');

        uams_list_pages();
    });

    it('hands the sidebar menu walker to wp_list_pages when the theme has one', function (): void {
        $walker = new stdClass;
        $GLOBALS['UAMS'] = (object) ['SidebarMenuWalker' => $walker];
        sidebarPage();
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['walker'] === $walker))
            ->andReturn('');

        uams_list_pages();
    });

    it('uses no walker when the theme object has none', function (mixed $theme): void {
        $GLOBALS['UAMS'] = $theme;
        sidebarPage();
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['walker'] === ''))
            ->andReturn('');

        uams_list_pages();
    })->with([
        'an object without the walker' => [(object) ['other' => 1]],
        'a value that is not an object' => ['SidebarMenuWalker'],
        'nothing' => [null],
    ]);

    it('returns nothing when there are no pages', function (mixed $pages): void {
        sidebarPage();
        when('wp_list_pages')->justReturn($pages);

        expect(uams_list_pages())->toBe('');
    })->with([
        'an empty string' => [''],
        'an empty array' => [[]],
        'a list that is not a string' => [[PAGES_MARKER]],
    ]);

    it('returns nothing when the pages carry no child marker', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li>Plain</li>');

        expect(uams_list_pages())->toBe('');
    });

    it('returns nothing on a search results page', function (): void {
        sidebarPage(['conditionals' => ['is_search' => true]]);
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        expect(uams_list_pages())->toBe('');
    });

    it('wraps the pages in the sidebar list', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        expect(uams_list_pages())->toBe('<ul class="A{uams-sidebar-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul>');
    });

    it('wraps the pages in the mobile list with its toggle', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        expect(uams_list_pages(true))->toBe(MENU_TOGGLE.'<ul class="A{uams-mobile-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul>');
    });
});

describe('the sidebar and mobile menu wrappers', function (): void {
    it('wraps the sidebar menu for desktop', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        uams_sidebar_menu();
    })->expectOutputString('<nav id="desktop-relative" aria-label="mobile menu that is not visible in the desktop version"><ul class="A{uams-sidebar-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul></nav>');

    it('wraps the sidebar menu for mobile', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        uams_sidebar_menu_mobile();
    })->expectOutputString('<nav id="mobile-relative" aria-label="mobile menu"><ul class="A{uams-sidebar-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul></nav>');

    it('wraps the mobile menu', function (): void {
        sidebarPage();
        when('get_option')->justReturn(false);
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');

        uams_mobile_menu();
    })->expectOutputString('<nav id="mobile-reddiedrops" aria-label="mobile menu">'.MENU_TOGGLE.'<ul class="A{uams-mobile-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul></nav>');
});

describe('uams_list_front_page_menu_items', function (): void {
    it('passes the whole argument list to wp_nav_menu', function (): void {
        Functions\expect('wp_nav_menu')
            ->once()
            ->with([
                'title_li' => HOME_LINK,
                'theme_location' => 'white-bar',
                'depth' => 3,
                'container_class' => '',
                'menu_class' => '',
                'fallback_cb' => '',
                'echo' => false,
            ])
            ->andReturn('');

        uams_list_front_page_menu_items();
    });

    it('takes the location from the dropdown class when it exists', function (): void {
        dropdowns(true, false);
        Functions\expect('wp_nav_menu')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['theme_location'] === 'white-bar'))
            ->andReturn('');

        uams_list_front_page_menu_items();
    });

    it('returns nothing when the menu is empty', function (mixed $items): void {
        when('wp_nav_menu')->justReturn($items);

        expect(uams_list_front_page_menu_items())->toBe('');
    })->with(['an empty string' => [''], 'false' => [false]]);

    it('wraps the menu items in the mobile list', function (): void {
        when('wp_nav_menu')->justReturn('<li>Item</li>');

        expect(uams_list_front_page_menu_items())->toBe(MENU_TOGGLE.'<ul class="uams-mobile-menu first-level"><li>Item</li></ul>');
    });
});

describe('uams_mobile_front_page_menu', function (): void {
    it('prints the front-page mobile navigation without a class', function (): void {
        when('wp_nav_menu')->justReturn('<li>Item</li>');

        uams_mobile_front_page_menu();
    })->expectOutputString('<nav id="mobile-reddiedrops" class="frontpage" aria-label="relative">'.MENU_TOGGLE.'<ul class="uams-mobile-menu first-level"><li>Item</li></ul></nav>');

    it('adds the sanitized class and a spacer when given a class', function (): void {
        when('wp_nav_menu')->justReturn('<li>Item</li>');

        uams_mobile_front_page_menu('dark');
    })->expectOutputString('<nav id="mobile-reddiedrops" class="frontpage S{dark}" aria-label="relative"><div id="spacer"></div>'.MENU_TOGGLE.'<ul class="uams-mobile-menu first-level"><li>Item</li></ul></nav>');

    it('prints a class that PHP calls empty as it was given, without a spacer', function (): void {
        when('wp_nav_menu')->justReturn('');

        uams_mobile_front_page_menu('0');
    })->expectOutputString('<nav id="mobile-reddiedrops" class="frontpage0" aria-label="relative"></nav>');
});

describe('uams_list_mobile_pages', function (): void {
    it('returns the page menu away from the front page', function (): void {
        sidebarPage();
        when('wp_list_pages')->justReturn('<li class="'.PAGES_MARKER.'"></li>');
        when('get_option')->justReturn(true);

        expect(uams_list_mobile_pages())->toBe(MENU_TOGGLE.'<ul class="A{uams-mobile-menu} first-level"><li class="'.PAGES_MARKER.'"></li></ul>');
    });

    it('falls back to the main menu when the page menu is empty and the option is on', function (): void {
        $GLOBALS['post'] = null;
        wpConditionals();
        Functions\expect('get_option')->once()->with('use_main_menu_on_mobile')->andReturn('1');
        when('wp_nav_menu')->justReturn('<li>Main</li>');

        expect(uams_list_mobile_pages())->toBe(MENU_TOGGLE.'<ul class="uams-mobile-menu first-level"><li>Main</li></ul>');
    });

    it('returns the empty page menu when the option is off', function (): void {
        $GLOBALS['post'] = null;
        wpConditionals();
        when('get_option')->justReturn(false);
        Functions\expect('wp_nav_menu')->never();

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('has no menu on the front page without a menu for the location', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['other' => 4]);

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('looks the menu up by the dropdown location', function (): void {
        dropdowns(true, false);
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        Functions\expect('wp_get_nav_menu_object')->once()->with(9)->andReturn(false);

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('looks the menu up by the white-bar location without the dropdown class', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        Functions\expect('wp_get_nav_menu_object')->once()->with(9)->andReturn(false);

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('has no menu when the location points at a menu that is gone', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn(false);

        Functions\expect('wp_get_nav_menu_items')->never();

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('has no menu when the menu has no items', function (mixed $items): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        Functions\expect('wp_get_nav_menu_items')->once()->with(21)->andReturn($items);
        Functions\expect('wp_list_pages')->never();

        expect(uams_list_mobile_pages())->toBe('');
    })->with([
        'an empty list' => [[]],
        'false' => [false],
        'something that is not a list' => ['items'],
    ]);

    it('has no menu when every item is a child item', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        when('wp_get_nav_menu_items')->justReturn([
            (object) ['menu_item_parent' => '12', 'object_id' => 31],
        ]);
        Functions\expect('wp_list_pages')->never();

        expect(uams_list_mobile_pages())->toBe('');
    });

    it('lists the top-level menu items as pages', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        when('wp_get_nav_menu_items')->justReturn([
            (object) ['menu_item_parent' => '0', 'object_id' => 31],
            (object) ['menu_item_parent' => '12', 'object_id' => 32],
            (object) ['menu_item_parent' => 0, 'object_id' => 33],
        ]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with([
                'title_li' => HOME_LINK,
                'include' => '31,33',
                'sort_order' => 'menu_order',
                'depth' => 1,
                'echo' => false,
                'walker' => '',
            ])
            ->andReturn('<li>Pages</li>');

        expect(uams_list_mobile_pages())->toBe(MENU_TOGGLE.'<ul class="uams-mobile-menu first-level"><li>Pages</li></ul>');
    });

    it('hands the sidebar menu walker to the front-page page list', function (): void {
        $walker = new stdClass;
        $GLOBALS['UAMS'] = (object) ['SidebarMenuWalker' => $walker];
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        when('wp_get_nav_menu_items')->justReturn([(object) ['menu_item_parent' => 0, 'object_id' => 31]]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['walker'] === $walker))
            ->andReturn('');

        uams_list_mobile_pages();
    });

    it('uses no walker on the front page when the theme object has none', function (mixed $theme): void {
        $GLOBALS['UAMS'] = $theme;
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        when('wp_get_nav_menu_items')->justReturn([(object) ['menu_item_parent' => 0, 'object_id' => 31]]);
        Functions\expect('wp_list_pages')
            ->once()
            ->with(Mockery::on(fn (array $args): bool => $args['walker'] === ''))
            ->andReturn('');

        uams_list_mobile_pages();
    })->with([
        'an object without the walker' => [(object) ['other' => 1]],
        'a value that is not an object' => ['SidebarMenuWalker'],
        'nothing' => [null],
    ]);

    it('returns nothing when the front-page page list is empty', function (): void {
        wpConditionals(['is_front_page' => true]);
        when('get_nav_menu_locations')->justReturn(['white-bar' => 9]);
        when('wp_get_nav_menu_object')->justReturn((object) ['term_id' => 21]);
        when('wp_get_nav_menu_items')->justReturn([(object) ['menu_item_parent' => 0, 'object_id' => 31]]);
        when('wp_list_pages')->justReturn('');

        expect(uams_list_mobile_pages())->toBe('');
    });
});
