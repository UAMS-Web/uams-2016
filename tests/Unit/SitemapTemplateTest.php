<?php

/**
 * The sitemap page template, run with WordPress mocked.
 *
 * @see templates/template-sitemap.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
    WP_Query::$queue = [];
    WP_Query::$made = [];
    WP_Query::$current = [];
});

/**
 * @param  array<string, mixed>  $meta  Post meta by key.
 * @param  list<object>  $categories  What get_categories() returns.
 * @param  list<list<array<string, mixed>>>  $batches  The posts each category's query serves, in order. A post is
 *                                                     ['title' => ..., 'link' => ..., 'cats' => [term ids, ...]].
 * @param  array<string, mixed>  $values
 */
function sitemapEnv(array $meta = [], array $categories = [], array $batches = [], array $values = []): void
{
    chromeMarkers();
    contentTags();
    WP_Query::$queue = $batches;
    stubValues(array_merge([
        'get_the_ID' => 7,
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore return.type
        'get_post_meta' => static fn (int $id, string $key, bool $single): string => $meta[$key] ?? '',
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore return.type
        'get_the_title' => static fn (): string => WP_Query::$current['title'] ?? 'About <us>',
        'get_categories' => $categories,
        'get_the_category' => static fn (): array => array_map(
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            static fn (int $id): object => (object) ['term_id' => $id],
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            WP_Query::$current['cats'] ?? []
        ),
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore return.type
        'get_permalink' => static fn (): string => WP_Query::$current['link'] ?? '',
    ], $values));
    stubTags([
        'uams_sidebar_menu' => '[sidebar-menu]',
        'wp_list_pages' => static fn (array $args): string => '[pages:'.json_encode($args).']',
        'wp_reset_postdata' => '[reset]',
    ]);
}

function category(int $id, string $name): object
{
    return (object) ['term_id' => $id, 'name' => $name];
}

describe('template-sitemap.php', function (): void {
    it('prints the chrome, the mobile sidebar, the page list and the post list', function (): void {
        sitemapEnv();

        expect(renderTemplate('templates/template-sitemap.php'))->toBe(
            '[header][part:header,image] <div class="container uams-body"> <div class="row"> <div class="col-md-8 uams-content" role="main">'
            .' [part:breadcrumbs] <div id="main_content" class="uams-body-copy" tabindex="-1">'
            .' <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" class="visible-xs" aria-hidden="true" tabindex="1">'
            .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
            .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
            .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div>'
            .' <h2 id="pages">Pages</h2> <ul> [pages:{"exclude":"","title_li":""}] </ul>'
            .' <h2 id="posts">Posts</h2> <ul> </ul> </div> </div>'
            .' <div id="sidebar"> [sidebar] </div> </div> </div> [footer]'
        );
    });

    it('lists each category with its posts, linked', function (): void {
        sitemapEnv(categories: [category(3, 'News <1>'), category(4, 'Events')], batches: [
            [['title' => 'First <p>', 'link' => '/first/', 'cats' => [3]], ['title' => 'Second', 'link' => '/second/', 'cats' => [3, 4]]],
            [['title' => 'Party', 'link' => '/party/', 'cats' => [4]]],
        ]);

        expect(renderTemplate('templates/template-sitemap.php'))->toContain(
            '<h2 id="posts">Posts</h2> <ul>'
            .' <li><h3>H{News <1>}</h3><ul>'
            .'<li><a href="U{/first/}">H{First <p>}</a></li><li><a href="U{/second/}">H{Second}</a></li>'
            .'[reset]</ul></li>'
            .'<li><h3>H{Events}</h3><ul><li><a href="U{/party/}">H{Party}</a></li>[reset]</ul></li> </ul>'
        );
    });

    it('queries every post of the category, without sticky posts or a total count', function (): void {
        sitemapEnv(categories: [category(3, 'News'), category(4, 'Events')]);

        renderTemplate('templates/template-sitemap.php');

        expect(WP_Query::$made)->toBe([
            ['posts_per_page' => -1, 'cat' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true],
            ['posts_per_page' => -1, 'cat' => 4, 'ignore_sticky_posts' => true, 'no_found_rows' => true],
        ]);
    });

    it('lists a post only under its first category', function (): void {
        sitemapEnv(categories: [category(4, 'Events')], batches: [
            [['title' => 'Elsewhere', 'link' => '/e/', 'cats' => [3, 4]], ['title' => 'Uncategorized', 'link' => '/u/', 'cats' => []]],
        ]);

        expect(renderTemplate('templates/template-sitemap.php'))->toContain('<h3>H{Events}</h3><ul>[reset]</ul>');
    });

    it('prints an empty list for a category with no posts, and does not reset the query', function (): void {
        sitemapEnv(categories: [category(3, 'News')]);

        expect(renderTemplate('templates/template-sitemap.php'))->toContain('<li><h3>H{News}</h3><ul></ul></li>');
    });

    it('asks only for categories that have posts', function (): void {
        $received = null;
        sitemapEnv(values: ['get_categories' => static function (array $args) use (&$received): array {
            $received = $args;

            return [];
        }]);

        renderTemplate('templates/template-sitemap.php');

        expect($received)->toBe(['hide_empty' => true]);
    });

    it('drops the sidebar and widens the column when the page turns the sidebar off', function (): void {
        sitemapEnv(['sidebar' => 'on']);
        $html = renderTemplate('templates/template-sitemap.php');

        expect($html)->toContain('<div class="col-md-12 uams-content" role="main">')->and($html)->not->toContain('[sidebar]');
    });

    it('hides the breadcrumbs only when the page turns them on', function (string $value, bool $shown): void {
        sitemapEnv(['breadcrumb' => $value]);

        expect(str_contains(renderTemplate('templates/template-sitemap.php'), '[part:breadcrumbs]'))->toBe($shown);
    })->with([
        'unset' => ['', true],
        'on' => ['on', false],
        'any other value' => ['off', true],
    ]);

    it('asks for the sidebar and breadcrumb settings of the current page', function (): void {
        $calls = [];
        sitemapEnv(values: ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
            $calls[] = [$id, $key, $single];

            return '';
        }]);

        renderTemplate('templates/template-sitemap.php');

        expect($calls)->toBe([[7, 'sidebar', true], [7, 'breadcrumb', true]]);
    });
});
