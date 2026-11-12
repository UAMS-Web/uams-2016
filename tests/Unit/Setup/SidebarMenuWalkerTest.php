<?php

/**
 * UAMS_Sidebar_Menu_Walker, the page walker behind the sidebar menu, with WordPress mocked.
 *
 * @see setup/class.uams-sidebar-menu-walker.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    require_once dirname(__DIR__, 3).'/setup/class.uams-sidebar-menu-walker.php';
    stubEscaping();
    when('__')->returnArg();
    when('get_permalink')->alias(static fn (int $id): string => 'https://site.test/?p='.$id);
    when('get_post_meta')->justReturn('');
    when('get_option')->justReturn(0);
    when('get_post')->justReturn();
});

/**
 * Run start_el() for a page and return what it printed.
 *
 * @param  array<string, mixed>  $page
 * @param  array<string, mixed>|object  $args
 */
function walkPage(array $page = [], array|object $args = [], int|string $current = 0, int $depth = 0): string
{
    $output = '';
    // A bare object stands in for the page, and the helper forwards its loosely typed arguments to the walker.
    // @phpstan-ignore argument.type, argument.type, argument.type
    (new UAMS_Sidebar_Menu_Walker)->start_el($output, (object) ($page + ['ID' => 4, 'post_title' => 'Title', 'post_parent' => 0]), $depth, $args, $current);

    return $output;
}

it('prints a link for a page', function (): void {
    expect(walkPage())->toBe('<li class="A{page_item page-item-4 child-page-existance-tester}"><a href="U{https://site.test/?p=4}">Title</a>');
});

it('passes the classes and the page through the css class filter', function (): void {
    expectApplied('page_css_class')->once()->with(['page_item', 'page-item-4'], Mockery::type('object'), 2, ['k' => 'v'], 0);

    walkPage(args: ['k' => 'v'], depth: 2);
});

it('passes the title through the title filter', function (): void {
    expectApplied('the_title')->once()->with('Title', 4);

    walkPage();
});

it('takes the arguments as an object', function (): void {
    expect(walkPage(args: (object) ['pages_with_children' => [4 => true], 'link_before' => '<b>']))
        ->toContain('page_item_has_children')
        ->toContain('><b>Title</a>');
});

it('indents by depth', function (): void {
    expect(walkPage(depth: 2))->toStartWith("\t\t<li")
        ->and(walkPage(depth: 0))->toStartWith('<li');
});

it('marks a page that has children', function (array $withChildren, bool $marked): void {
    $html = walkPage(args: ['pages_with_children' => $withChildren]);

    expect(str_contains($html, 'page_item_has_children'))->toBe($marked);
})->with([
    'its own entry' => [[4 => true], true],
    'another entry' => [[5 => true], false],
    'none' => [[], false],
]);

it('wraps the title in the link markers', function (): void {
    expect(walkPage(args: ['link_before' => '[', 'link_after' => ']']))->toContain('>[Title]</a>');
});

it('titles a page that has none', function (): void {
    expect(walkPage(['post_title' => '']))->toContain('>#4 (no title)</a>');
});

describe('the current page', function (): void {
    it('prints the current page as a span with its classes', function (): void {
        when('get_post')->justReturn(wpPost(['ID' => 4, 'post_parent' => 2]));

        expect(walkPage(args: ['link_before' => '[', 'link_after' => ']'], current: 4))
            ->toBe('<li class="A{page_item page-item-4 current_page_item}"><span>[Title]</span>');
    });

    it('asks for the current page by its id', function (): void {
        $asked = [];
        when('get_post')->alias(static function (mixed $id) use (&$asked): null {
            $asked[] = $id;

            return null;
        });

        walkPage(current: '9');

        expect($asked)->toBe(['9']);
    });

    it('marks the parent of the current page', function (): void {
        when('get_post')->justReturn(wpPost(['ID' => 9, 'post_parent' => '4']));

        expect(walkPage(current: 9))->toContain('page-item-4 current_page_parent');
    });

    it('marks the ancestors of the current page', function (mixed $ancestors, bool $marked): void {
        when('get_post')->justReturn(wpPost(['ID' => 9, 'post_parent' => 0, 'ancestors' => $ancestors]));

        expect(str_contains(walkPage(current: 9), 'current_page_ancestor'))->toBe($marked);
    })->with([
        'a listed ancestor' => [[4, 1], true],
        'not listed' => [[3, 1], false],
        'a string id is not strictly equal' => [['4'], false],
        'none' => [[], false],
        'not an array' => ['4', false],
    ]);

    it('marks an ancestor that is also the parent with both classes', function (): void {
        when('get_post')->justReturn(wpPost(['ID' => 9, 'post_parent' => 4, 'ancestors' => [4]]));

        expect(walkPage(current: 9))->toContain('page-item-4 current_page_ancestor current_page_parent');
    });

    it('marks nothing when the current page is not a post', function (): void {
        when('get_post')->justReturn();

        expect(walkPage(current: 9))->toContain('"A{page_item page-item-4 child-page-existance-tester}"');
    });
});

describe('the posts page', function (): void {
    it('is marked as the parent when there is no current page', function (): void {
        $asked = [];
        when('get_option')->alias(static function (string $name) use (&$asked): string {
            $asked[] = $name;

            return '4';
        });

        expect(walkPage())->toContain('page-item-4 current_page_parent')
            ->and($asked)->toBe(['page_for_posts']);
    });

    it('is not marked when it is another page or a current page is set', function (int|string $current, string $posts): void {
        when('get_option')->justReturn($posts);

        expect(walkPage(current: $current))->not->toContain('current_page_parent');
    })->with([
        'another page' => [0, '5'],
        'a current page that is not a post' => [9, '4'],
    ]);
});

describe('hidden pages', function (): void {
    it('hides a page marked off the child navigation below the top level', function (): void {
        when('get_post_meta')->justReturn('on');

        expect(walkPage(depth: 1))->toBe('');
    });

    it('asks for the parent flag of the page', function (): void {
        $asked = [];
        when('get_post_meta')->alias(static function (int $id, string $key, bool $single) use (&$asked): string {
            $asked[] = [$id, $key, $single];

            return '';
        });

        walkPage();

        expect($asked)->toBe([[4, 'parent', true]]);
    });

    it('still shows the flagged page at the top level', function (): void {
        when('get_post_meta')->justReturn('on');

        expect(walkPage(depth: 0))->toContain('<a href=');
    });

    it('still shows the current page when it is flagged', function (): void {
        when('get_post_meta')->justReturn('on');
        when('get_post')->justReturn(wpPost(['ID' => 4]));

        expect(walkPage(current: 4, depth: 1))->toContain('<span>');
    });

    it('shows a page whose flag is anything else', function (): void {
        when('get_post_meta')->justReturn('yes');

        expect(walkPage(depth: 1))->toContain('<a href=');
    });
});

describe('dates', function (): void {
    beforeEach(function (): void {
        when('mysql2date')->alias(static fn (string $format, string $time): string => 'D('.$format.','.$time.')');
    });

    it('appends the creation date', function (): void {
        expect(walkPage(['post_date' => 'created', 'post_modified' => 'changed'], ['show_date' => true, 'date_format' => 'Y']))
            ->toEndWith('</a> D(Y,created)');
    });

    it('appends the modified date when asked', function (): void {
        expect(walkPage(['post_date' => 'created', 'post_modified' => 'changed'], ['show_date' => 'modified']))
            ->toEndWith('</a> D(,changed)');
    });

    it('appends no date by default', function (): void {
        expect(walkPage(['post_date' => 'created'], ['show_date' => '']))->toEndWith('</a>');
    });
});
