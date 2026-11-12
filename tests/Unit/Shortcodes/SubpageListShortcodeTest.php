<?php

/**
 * The [subpage-list] shortcode, with WordPress mocked.
 *
 * @see setup/class.subpage-list-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.subpage-list-shortcode.php');
    stubShortcodeApi();

    when('get_the_ID')->justReturn(7);
    when('get_permalink')->alias(static fn (int $id): string => 'p'.$id);
    when('get_the_author_meta')->alias(static fn (string $field, int $author): string => $field.':'.$author);
    when('get_the_post_thumbnail')->alias(static fn (int $id, string $size): string => 'thumb'.$id.'-'.$size);
    when('get_option')->justReturn(false);
});

/**
 * Pages with the given ids, each titled, excerpted and authored after its id.
 *
 * @return list<WP_Post>
 */
function subpages(int ...$ids): array
{
    return array_values(array_map(static fn (int $id): WP_Post => wpPost([
        'ID' => $id,
        'post_title' => 'Page '.$id,
        'post_excerpt' => 'Excerpt '.$id,
        'post_author' => 100 + $id,
    ]), $ids));
}

it('registers the subpage-list shortcode', function (): void {
    $calls = recordShortcodes();
    $list = new UAMS_SubpageList;

    expect($calls->getArrayCopy())->toBe([['subpage-list', [$list, 'list_subpages']]]);
});

it("asks for the current page's children, sorted by title", function (): void {
    Functions\expect('get_pages')->once()->with(['parent' => 7, 'sort_column' => 'post_title'])->andReturn([]);

    expect((new UAMS_SubpageList)->list_subpages(''))->toBe('')
        ->and(shortcodeAttsNames())->toBe(['subpage-list']);
});

it('renders nothing when the result is not a list of pages', function (mixed $pages): void {
    when('get_pages')->justReturn($pages);

    expect((new UAMS_SubpageList)->list_subpages([]))->toBe('');
})->with([
    'false' => [false],
    'a string' => ['pages'],
]);

describe('as a list', function (): void {
    it('lists each page with its excerpt and a read-more link', function (): void {
        when('get_pages')->justReturn(subpages(1, 2));

        expect((new UAMS_SubpageList)->list_subpages([]))->toBe(
            "<h2><a href='U{p1}'>H{Page 1}</a></h2><p>K{Excerpt 1}</p><a class='uams-btn btn-sm' href='U{p1}'>H{Read more}</a>"
            ."<h2><a href='U{p2}'>H{Page 2}</a></h2><p>K{Excerpt 2}</p><a class='uams-btn btn-sm' href='U{p2}'>H{Read more}</a>"
        );
    });

    it('adds the author when bylines are on', function (): void {
        when('get_pages')->justReturn(subpages(1));
        when('get_option')->alias(static fn (string $name): mixed => $name === 'show_byline_on_posts' ? '1' : false);

        expect((new UAMS_SubpageList)->list_subpages([]))->toBe(
            "<h2><a href='U{p1}'>H{Page 1}</a></h2><div class='author-info'><p class='author-desc'><small>H{display_name:101}</small></p></div><p>K{Excerpt 1}</p><a class='uams-btn btn-sm' href='U{p1}'>H{Read more}</a>"
        );
    });

    it('takes the link text from the attribute, and leaves the link out when it is empty', function (string $link, string $tail): void {
        when('get_pages')->justReturn(subpages(1));

        expect((new UAMS_SubpageList)->list_subpages(['link' => $link]))
            ->toBe("<h2><a href='U{p1}'>H{Page 1}</a></h2><p>K{Excerpt 1}</p>".$tail);
    })->with([
        'custom text' => ['More', "<a class='uams-btn btn-sm' href='U{p1}'>H{More}</a>"],
        'empty' => ['', ''],
    ]);

    it('treats a tilebox value that is not true as a list', function (string $tilebox): void {
        when('get_pages')->justReturn(subpages(1));

        expect((new UAMS_SubpageList)->list_subpages(['tilebox' => $tilebox]))->toStartWith('<h2>');
    })->with(['false', 'no', '0', 'maybe']);
});

describe('as tiles', function (): void {
    it('pairs the tiles in boxes', function (): void {
        when('get_pages')->justReturn(subpages(1, 2));

        expect((new UAMS_SubpageList)->list_subpages(['tilebox' => 'true']))->toBe(
            "<div class='box-outer'><div class='box two'>"
            ."<div class='tile'><div>thumb1-half</div><h3><a href='U{p1}'>H{Page 1}</a></h3><p>K{Excerpt 1}</p></div>"
            ."<div class='tile'><div>thumb2-half</div><h3><a href='U{p2}'>H{Page 2}</a></h3><p>K{Excerpt 2}</p></div>"
            .'</div></div>'
        );
    });

    it('starts a new box after every second tile, and fills an odd one out with an empty tile', function (): void {
        when('get_pages')->justReturn(subpages(1, 2, 3));

        expect((new UAMS_SubpageList)->list_subpages(['tilebox' => 'yes']))->toBe(
            "<div class='box-outer'><div class='box two'>"
            ."<div class='tile'><div>thumb1-half</div><h3><a href='U{p1}'>H{Page 1}</a></h3><p>K{Excerpt 1}</p></div>"
            ."<div class='tile'><div>thumb2-half</div><h3><a href='U{p2}'>H{Page 2}</a></h3><p>K{Excerpt 2}</p></div>"
            .'</div></div>'
            ."<div class='box-outer'><div class='box two'>"
            ."<div class='tile'><div>thumb3-half</div><h3><a href='U{p3}'>H{Page 3}</a></h3><p>K{Excerpt 3}</p></div>"
            ."<div class='tile empty'></div></div></div>"
        );
    });

    it('adds the author when bylines are on', function (): void {
        when('get_pages')->justReturn(subpages(1, 2));
        when('get_option')->alias(static fn (string $name): mixed => $name === 'show_byline_on_posts' ? '1' : false);

        expect((new UAMS_SubpageList)->list_subpages(['tilebox' => '1']))->toBe(
            "<div class='box-outer'><div class='box two'>"
            ."<div class='tile'><div>thumb1-half</div><h3><a href='U{p1}'>H{Page 1}</a></h3><div class='author-info'><p class='author-desc'><small>H{display_name:101}</small></p></div><p>K{Excerpt 1}</p></div>"
            ."<div class='tile'><div>thumb2-half</div><h3><a href='U{p2}'>H{Page 2}</a></h3><div class='author-info'><p class='author-desc'><small>H{display_name:102}</small></p></div><p>K{Excerpt 2}</p></div>"
            .'</div></div>'
        );
    });
});
