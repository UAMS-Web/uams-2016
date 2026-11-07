<?php

/**
 * The plain page templates, run with WordPress mocked: template-no-sidebar.php, template-blank.php
 * and template-no-title.php.
 *
 * @see templates/template-no-sidebar.php
 * @see templates/template-blank.php
 * @see templates/template-no-title.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

/**
 * @param  array<string, mixed>  $meta  Post meta by key.
 */
function plainEnv(array $meta = [], int $posts = 1, bool $commentsOpen = false, int $commentCount = 0, string $title = 'About <us>'): void
{
    chromeMarkers();
    contentTags();
    stubValues([
        'get_the_ID' => 7,
        'get_post_meta' => static fn (int $id, string $key, bool $single): string => $meta[$key] ?? '',
        'have_posts' => loopOf($posts),
        'comments_open' => $commentsOpen,
        'get_comments_number' => $commentCount,
        'get_the_title' => $title,
    ]);
    stubTags(['the_post' => '[post]', 'comments_template' => '[comments]', 'uams_sidebar_menu' => '[sidebar-menu]']);
}

/**
 * Render a template and return the post meta lookups it made, as [id, key, single] triples.
 *
 * @return list<array{int, string, bool}>
 */
function metaLookups(string $template): array
{
    $calls = [];
    plainEnv();
    stubValues(['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
        $calls[] = [$id, $key, $single];

        return '';
    }]);

    renderTemplate($template);

    return $calls;
}

describe('template-no-sidebar.php', function (): void {
    it('prints the header image, mobile menu, breadcrumbs and each post as a page', function (): void {
        plainEnv(posts: 2);

        expect(renderTemplate('templates/template-no-sidebar.php'))->toBe(
            '[header][part:header,image] <div class="container uams-body"> <div class="row"> <div class="col-md-12 uams-content" role="main">'
            .' [part:menu,mobile] [part:breadcrumbs] <div id="main_content" class="uams-body-copy" tabindex="-1">'
            .' [post][part:content,page][post][part:content,page] </div> </div> </div> </div> [footer]'
        );
    });

    it('hides the breadcrumbs only when the post turns them on', function (string $value, bool $shown): void {
        plainEnv(['breadcrumb' => $value]);

        expect(str_contains(renderTemplate('templates/template-no-sidebar.php'), '[part:breadcrumbs]'))->toBe($shown);
    })->with([
        'unset' => ['', true],
        'on' => ['on', false],
        'any other value' => ['off', true],
    ]);

    it('asks for the breadcrumb setting of the current post', function (): void {
        expect(metaLookups('templates/template-no-sidebar.php'))->toBe([[7, 'breadcrumb', true]]);
    });

    it('loads the comments template when comments are open or the post has comments', function (bool $open, int $count, bool $loaded): void {
        plainEnv(commentsOpen: $open, commentCount: $count);

        expect(str_contains(renderTemplate('templates/template-no-sidebar.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);
});

describe('template-blank.php', function (): void {
    it('prints the title banner, the mobile sidebar, each post and the sidebar', function (): void {
        plainEnv(posts: 2);

        expect(renderTemplate('templates/template-blank.php'))->toBe(
            '[header] <div class="uams-hero-image hero-blank no-title"> <h1 class="container uams-site-title-blank">[title]</h1> </div>'
            .' <div class="container uams-body blank"> <div class="row"> <div class="col-md-8 uams-content" role="main">'
            .' <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">'
            .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
            .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
            .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div>'
            .' <div id="main_content" class="uams-body-copy" tabindex="-1"> [post][content][post][content] </div> </div>'
            .' <div id="sidebar"> [sidebar] </div> </div> </div> [footer]'
        );
    });

    it('drops the sidebar and widens the column when the post turns the sidebar off', function (): void {
        plainEnv(['sidebar' => 'on']);

        expect(renderTemplate('templates/template-blank.php'))->toContain('<div class="col-md-12 uams-content" role="main">')
            ->and(renderTemplate('templates/template-blank.php'))->not->toContain('[sidebar]');
    });

    it('asks for the sidebar and breadcrumb settings of the current post', function (): void {
        expect(metaLookups('templates/template-blank.php'))->toBe([[7, 'sidebar', true], [7, 'breadcrumb', true]]);
    });

    it('loads the comments template when comments are open or the post has comments', function (bool $open, int $count, bool $loaded): void {
        plainEnv(commentsOpen: $open, commentCount: $count);

        expect(str_contains(renderTemplate('templates/template-blank.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);

    it('cuts a long title in the mobile sidebar toggle', function (): void {
        plainEnv(title: 'Department of Orthopaedic Surgery and Rehabilitation');

        expect(renderTemplate('templates/template-blank.php'))->toContain('page_item"> H{Department of Orthopaedic...} </div>');
    });
});

describe('template-no-title.php', function (): void {
    it('prints the title banner, breadcrumbs, mobile sidebar, each post and the sidebar', function (): void {
        plainEnv(posts: 2);

        expect(renderTemplate('templates/template-no-title.php'))->toBe(
            '[header] <div class="uams-hero-image hero-blank no-title"> <h1 class="container uams-site-title-blank">[title]</h1> </div>'
            .' <div class="container uams-body"> <div class="row"> <div class="col-md-8 uams-content" role="main"> [part:breadcrumbs]'
            .' <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">'
            .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
            .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
            .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div>'
            .' <div id="main_content" class="uams-body-copy" tabindex="-1"> [post][content][post][content] </div> </div>'
            .' <div id="sidebar"> [sidebar] </div> </div> </div> [footer]'
        );
    });

    it('drops the sidebar and widens the column when the post turns the sidebar off', function (): void {
        plainEnv(['sidebar' => 'on']);

        expect(renderTemplate('templates/template-no-title.php'))->toContain('<div class="col-md-12 uams-content" role="main">')
            ->and(renderTemplate('templates/template-no-title.php'))->not->toContain('[sidebar]');
    });

    it('hides the breadcrumbs only when the post turns them on', function (string $value, bool $shown): void {
        plainEnv(['breadcrumb' => $value]);

        expect(str_contains(renderTemplate('templates/template-no-title.php'), '[part:breadcrumbs]'))->toBe($shown);
    })->with([
        'unset' => ['', true],
        'on' => ['on', false],
        'any other value' => ['off', true],
    ]);

    it('asks for the sidebar and breadcrumb settings of the current post', function (): void {
        expect(metaLookups('templates/template-no-title.php'))->toBe([[7, 'sidebar', true], [7, 'breadcrumb', true]]);
    });

    it('loads the comments template when comments are open or the post has comments', function (bool $open, int $count, bool $loaded): void {
        plainEnv(commentsOpen: $open, commentCount: $count);

        expect(str_contains(renderTemplate('templates/template-no-title.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);
});
