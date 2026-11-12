<?php

/**
 * UAMS_Dropdowns_Walker_Menu, the walker that prints the white bar dropdowns with their accessibility
 * attributes, with WordPress mocked.
 *
 * @see setup/class.dropdowns_walker.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubEscaping();
    UamsTestWalkerNavMenu::$displayed = [];
    when('sanitize_title')->alias(static fn (string $title): string => 'san-'.strtolower($title));
});

/**
 * A menu item.
 *
 * @param  array<string, mixed>  $properties
 */
function menuItem(array $properties = []): object
{
    return (object) ($properties + ['ID' => 12, 'title' => 'About', 'post_name' => 'about', 'url' => 'https://site.test/about']);
}

/**
 * Run start_el() and return what it printed.
 *
 * @param  array<string, mixed>  $properties
 */
function walkItem(array $properties = [], int $depth = 0, mixed $args = null): string
{
    $output = '';
    (new UAMS_Dropdowns_Walker_Menu)->start_el($output, menuItem($properties), $depth, $args);

    return $output;
}

describe('start_lvl', function (): void {
    it('opens the top level list as an accessible group of the current item', function (): void {
        $walker = new UAMS_Dropdowns_Walker_Menu;
        $output = '';
        $walker->start_el($output, menuItem(['post_name' => 'about']), 0);
        $output = '';

        $walker->start_lvl($output, 0);

        expect($output)->toBe("\n<ul role=\"group\" id=\"menu-A{about}\" aria-labelledby=\"A{about}\" aria-expanded=\"false\" class=\"reddiedrops-menu\">\n");
    });

    it('opens a deeper list as a plain sub-menu, indented by depth', function (): void {
        $output = '';

        (new UAMS_Dropdowns_Walker_Menu)->start_lvl($output, 2);

        expect($output)->toBe("\n\t\t<ul class=\"sub-menu\">\n");
    });

    it('appends to what is already printed', function (): void {
        $output = 'x';

        (new UAMS_Dropdowns_Walker_Menu)->start_lvl($output, 1);

        expect($output)->toStartWith("x\n\t<ul");
    });

    it('opens an empty current id before any item', function (): void {
        $output = '';

        (new UAMS_Dropdowns_Walker_Menu)->start_lvl($output);

        expect($output)->toContain('id="menu-A{}" aria-labelledby="A{}"');
    });
});

describe('end_lvl', function (): void {
    it('closes the list at its indent', function (int $depth, string $expected): void {
        $output = 'x';

        (new UAMS_Dropdowns_Walker_Menu)->end_lvl($output, $depth);

        expect($output)->toBe($expected);
    })->with([
        'the top' => [0, "x</ul>\n"],
        'a sub-menu' => [2, "x\t\t</ul>\n"],
    ]);

    it('closes at the top level by default', function (): void {
        $output = '';

        (new UAMS_Dropdowns_Walker_Menu)->end_lvl($output);

        expect($output)->toBe("</ul>\n");
    });
});

describe('display_element', function (): void {
    it('flags an element that has children and hands over to the parent', function (array $children, bool $has): void {
        $element = menuItem();
        $output = '';
        $depth = 1;
        $args = ['k' => 'v'];

        // The real parent method returns nothing; the test stand-in returns a marker so the hand-over is visible.
        // @phpstan-ignore method.void
        $result = (new UAMS_Dropdowns_Walker_Menu)->display_element($element, $children, 3, $depth, $args, $output);

        expect($element->has_children)->toBe($has)
            ->and($result)->toBe('parent-result')
            ->and(UamsTestWalkerNavMenu::$displayed)->toBe([[$element, $children, 3, 1, ['k' => 'v']]]);
    })->with([
        'its own children' => [[12 => [menuItem(['ID' => 13])]], true],
        'no children' => [[], false],
        'an empty list of children' => [[12 => []], false],
        'children of another element' => [[99 => [menuItem(['ID' => 13])]], false],
    ]);

    it('hands over an element that is not an object untouched', function (): void {
        $children = [];
        $output = '';

        // The test hands the walker a non-object element and no arguments on purpose, to pin the untouched hand-over.
        // @phpstan-ignore argument.type, argument.type
        (new UAMS_Dropdowns_Walker_Menu)->display_element('x', $children, 0, 0, null, $output);

        expect(UamsTestWalkerNavMenu::$displayed)->toBe([['x', [], 0, 0, null]]);
    });
});

describe('start_el', function (): void {
    it('prints a top level item as a link', function (): void {
        expect(walkItem())->toBe('<li   class="A{reddiedrops-item}"><a href="U{https://site.test/about}" title="A{About}" id="A{about}">About</a>');
    });

    it('passes the classes and the item through the css class filter', function (): void {
        expectApplied('nav_menu_css_class')->once()->with(['reddiedrops-item', 'featured'], Mockery::type('object'));

        walkItem(['classes' => ['featured', 'x']]);
    });

    it('adds the first class of a top level item only', function (): void {
        expect(walkItem(['classes' => ['featured', 'other']]))->toContain('class="A{reddiedrops-item featured}"')
            ->and(walkItem(['classes' => ['featured']], 1))->not->toContain('class=');
    });

    it('ignores classes that are not a list or are empty', function (mixed $classes): void {
        expect(walkItem(['classes' => $classes]))->toContain('class="A{reddiedrops-item}"');
    })->with(['a string' => ['featured'], 'empty' => [[]], 'an empty first class' => [['']]]);

    it('prints a nested item without top level markup', function (): void {
        expect(walkItem(depth: 2))->toBe("\t\t<li ><a href=\"U{https://site.test/about}\" tabindex=\"-1\" title=\"A{About}\" id=\"A{about}\">About</a>");
    });

    it('marks a top level item that has children', function (): void {
        expect(walkItem(['has_children' => true]))
            ->toContain(' class="dropdown-toggle" title="A{About}" aria-controls="menu-A{about}" aria-expanded="false" aria-haspopup="true" id="A{about}"');
    });

    it('does not mark a nested item that has children', function (): void {
        expect(walkItem(['has_children' => true], 1))
            ->not->toContain('dropdown-toggle')
            ->not->toContain('aria-controls');
    });

    it('uses the title of the item before the post title', function (): void {
        expect(walkItem(['title' => '<b>Item</b>', 'post_title' => 'Post']))->toContain('title="A{Item}"')
            ->and(walkItem(['title' => '', 'post_title' => 'Post']))->toContain('title="A{Post}"');
    });

    it('names the item after its post name, or its sanitized title', function (): void {
        expect(walkItem(['post_name' => '']))->toContain('id="A{san-about}"')
            ->and(walkItem(['post_name' => 'slug']))->toContain('id="A{slug}"');
    });

    it('lists the optional attributes in order', function (): void {
        $html = walkItem([
            'attr_title' => 'Attr',
            'description' => '<i>Long</i> text',
            'target' => '_blank',
            'xfn' => 'nofollow',
        ]);

        expect($html)->toContain('<a title="A{Attr}" title="A{About - Long text}" target="A{_blank}" rel="A{nofollow}" href="U{https://site.test/about}" title="A{About}"');
    });

    it('leaves out the link when the item has no url', function (): void {
        expect(walkItem(['url' => '']))->not->toContain('href=');
    });

    it('wraps the link in the arguments', function (): void {
        $args = (object) ['before' => '<b>', 'after' => '</b>', 'link_before' => '[', 'link_after' => ']'];

        expect(walkItem(args: $args))->toContain('<b><a ')->toContain('>[About]</a></b>');
    });

    it('wraps nothing when the arguments are not an object or lack the keys', function (mixed $args): void {
        expect(walkItem(args: $args))->toContain('"><a ')->toEndWith('>About</a>');
    })->with(['null' => [null], 'an array' => [['before' => 'x']], 'an empty object' => [new stdClass]]);

    it('passes the title through the title filter and the link through the start filter', function (): void {
        expectApplied('the_title')->once()->with('About', 12);
        expectApplied('walker_nav_menu_start_el')->once()->with(Mockery::type('string'), Mockery::type('object'), 1, null);

        walkItem(depth: 1);
    });
});
