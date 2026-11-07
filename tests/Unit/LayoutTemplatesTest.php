<?php

/**
 * The page layout templates, run with WordPress mocked: index.php, page.php, search.php,
 * image.php, application.php, woocommerce.php, thinstrip.php and footer.php.
 *
 * Template tags print a bracketed marker naming the call and its arguments, so each test reads
 * what was called, in what order, and with what.
 *
 * @see index.php
 * @see page.php
 * @see search.php
 * @see image.php
 * @see application.php
 * @see woocommerce.php
 * @see thinstrip.php
 * @see footer.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

beforeEach(function (): void {
    stubEscaping();
});

/**
 * Stub the loop and comment tags: $posts passes of the loop, then the comments state.
 *
 * @param  array<string, mixed>  $values
 */
function loopEnv(int $posts, bool $commentsOpen = false, int $commentCount = 0, array $values = []): void
{
    chromeMarkers();
    stubValues(array_merge([
        'have_posts' => loopOf($posts),
        'comments_open' => $commentsOpen,
        'get_comments_number' => $commentCount,
        'get_post_format' => 'video',
    ], $values));
    stubTags([
        'the_post' => '[post]',
        'comments_template' => '[comments]',
        'next_posts_link' => static fn (string ...$args): string => '[next:'.implode('|', $args).']',
    ]);
}

describe('index.php', function (): void {
    it('prints the chrome, each post with its format template, and the next-page link', function (): void {
        loopEnv(2);

        expect(renderTemplate('index.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row"> <div class="content" role=\'main\'>'
            .' [part:breadcrumbs] <div id=\'main_content\' class="uams-body-copy" tabindex="-1">'
            .' [post][part:content,video][post][part:content,video]'
            .' <span class="next-page">[next:Next page|]</span> </div> </div> [sidebar] </div> </div> [footer]'
        );
    });

    it('asks for the generic content template for a post without a format', function (): void {
        loopEnv(1, values: ['get_post_format' => false]);

        expect(renderTemplate('index.php'))->toContain('[post][part:content,]');
    });

    it('loads the comments template when comments are open or the post has comments', function (bool $open, int $count, bool $loaded): void {
        loopEnv(1, $open, $count);

        expect(str_contains(renderTemplate('index.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);

    it('skips the header, header image and content class when those functions are missing', function (): void {
        loopEnv(0);
        stubFunctionExists([
            'get_header' => false,
            'get_template_part' => false,
            'uams_content_class' => false,
        ]);

        expect(renderTemplate('index.php'))->toBe(
            '<div class="container uams-body"> <div class="row"> <div role=\'main\'>'
            .' [part:breadcrumbs] <div id=\'main_content\' class="uams-body-copy" tabindex="-1">'
            .' <span class="next-page">[next:Next page|]</span> </div> </div> [sidebar] </div> </div> [footer]'
        );
    });

    it('prints each of those parts on its own when the others are missing', function (string $present, string $marker): void {
        loopEnv(0);
        stubFunctionExists(array_diff_key(
            ['get_header' => false, 'get_template_part' => false, 'uams_content_class' => false],
            [$present => false]
        ));

        expect(renderTemplate('index.php'))->toContain($marker);
    })->with([
        'get_header' => ['get_header', '[header]'],
        'get_template_part' => ['get_template_part', '[part:header,image]'],
        'uams_content_class' => ['uams_content_class', 'class="content"'],
    ]);
});

describe('page.php', function (): void {
    /**
     * @param  array<string, mixed>  $meta  Post meta by key, each a list as get_post_meta() returns.
     */
    function pageEnv2(int $posts, array $meta = [], bool $commentsOpen = false, int $commentCount = 0): void
    {
        loopEnv($posts, $commentsOpen, $commentCount, [
            'get_the_ID' => 7,
            'get_post_meta' => static fn (int $id, string $key): array => $meta[$key] ?? [],
        ]);
    }

    it('prints the page with the sidebar and breadcrumbs by default', function (): void {
        pageEnv2(1);

        expect(renderTemplate('page.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row">'
            .' <div class="col-md-8 uams-content" role=\'main\'> [part:breadcrumbs]'
            .' <div id=\'main_content\' class="uams-body-copy" tabindex="-1"> [post][part:content,page] </div> </div>'
            .' <div id="sidebar"> [sidebar] </div> </div> </div> [footer]'
        );
    });

    it('asks for the sidebar and breadcrumb meta of the current post', function (): void {
        $calls = [];
        loopEnv(0, values: [
            'get_the_ID' => 7,
            'get_post_meta' => static function (int $id, string $key) use (&$calls): array {
                $calls[] = [$id, $key];

                return [];
            },
        ]);

        renderTemplate('page.php');

        expect($calls)->toBe([[7, 'sidebar'], [7, 'breadcrumb']]);
    });

    it('goes full width and drops the sidebar when the page turns it off', function (): void {
        pageEnv2(0, ['sidebar' => ['on']]);
        $html = renderTemplate('page.php');

        expect($html)->toContain('class="col-md-12 uams-content"')
            ->and($html)->toContain('<div id="sidebar"> </div>')
            ->and($html)->toContain('[part:breadcrumbs]');
    });

    it('keeps the sidebar for any other sidebar value', function (): void {
        pageEnv2(0, ['sidebar' => ['off']]);
        $html = renderTemplate('page.php');

        expect($html)->toContain('class="col-md-8 uams-content"')
            ->and($html)->toContain('[sidebar]');
    });

    it('drops the breadcrumbs when the page turns them off', function (): void {
        pageEnv2(0, ['breadcrumb' => ['on']]);

        expect(renderTemplate('page.php'))->not->toContain('[part:breadcrumbs]');
    });

    it('keeps the breadcrumbs for any other breadcrumb value', function (): void {
        pageEnv2(0, ['breadcrumb' => ['off']]);

        expect(renderTemplate('page.php'))->toContain('[part:breadcrumbs]');
    });

    it('loads the comments template when comments are open or the page has comments', function (bool $open, int $count, bool $loaded): void {
        pageEnv2(1, [], $open, $count);

        expect(str_contains(renderTemplate('page.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);
});

describe('search.php', function (): void {
    function searchEnv(int $posts): void
    {
        chromeMarkers();
        stubValues(['have_posts' => loopOf($posts)]);
        stubTags([
            'the_post' => '[post]',
            'uams_site_title' => '[site-title]',
            'posts_nav_link' => static fn (string ...$args): string => '[nav:'.implode('|', $args).']',
        ]);
    }

    it('prints the chrome, the results and the paging links', function (): void {
        // have_posts() is asked once by the if, then once per pass: three answers, two posts.
        searchEnv(3);

        expect(renderTemplate('search.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row"> <div class="content" role=\'main\'>'
            .' [site-title] [part:menu,mobile] [part:breadcrumbs] <div id=\'main_content\' class="uams-body-copy" tabindex="-1">'
            .' [post][part:content,archive][post][part:content,archive] [nav: ] </div> </div> [sidebar] </div> </div> [footer]'
        );
    });

    it('says so when nothing matched', function (): void {
        searchEnv(0);
        $html = renderTemplate('search.php');

        expect($html)->toContain("<h3 class='no-results'>Sorry, no results matched your criteria.</h3> [nav: ]")
            ->and($html)->not->toContain('[post]');
    });
});

describe('image.php', function (): void {
    function imageLayoutEnv(int $posts, bool $commentsOpen = false, int $commentCount = 0): void
    {
        loopEnv($posts, $commentsOpen, $commentCount);
        stubValues(['get_bloginfo' => 'UAMS "Health"']);
        stubTags(['bloginfo' => '[bloginfo]']);
    }

    it('prints the site link, the breadcrumbs and each image', function (): void {
        imageLayoutEnv(2);

        expect(renderTemplate('image.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row">'
            .' <div class="col-md-12 uams-content center-block" role=\'main\'>'
            .' <a href="https://site.test/" title="A{UAMS "Health"}"><h2 class="uams-site-title">[bloginfo]</h2></a>'
            .' [part:breadcrumbs] <div class="uams-body-copy"> [post][part:content,image][post][part:content,image] </div> </div> </div> </div> [footer]'
        );
    });

    it('loads the comments template when comments are open or the image has comments', function (bool $open, int $count, bool $loaded): void {
        imageLayoutEnv(1, $open, $count);

        expect(str_contains(renderTemplate('image.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);
});

describe('application.php', function (): void {
    function applicationEnv(int $posts, bool $commentsOpen = false, int $commentCount = 0): void
    {
        loopEnv($posts, $commentsOpen, $commentCount);
        stubTags(['uams_site_title' => '[site-title]']);
    }

    it('prints the site title and each attachment with the PDF template', function (): void {
        applicationEnv(2);

        expect(renderTemplate('application.php'))->toBe(
            '[header] [part:header,image]<div class="container uams-body"> <div class="row">'
            .' <div class="col-md-8 uams-content" role=\'main\'> [site-title]'
            .' <div class="uams-body-copy"> [post][part:content,pdf][post][part:content,pdf] </div> </div> </div> </div> [footer]'
        );
    });

    it('loads the comments template when comments are open or the attachment has comments', function (bool $open, int $count, bool $loaded): void {
        applicationEnv(1, $open, $count);

        expect(str_contains(renderTemplate('application.php'), '[comments]'))->toBe($loaded);
    })->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);
});

describe('woocommerce.php', function (): void {
    /**
     * @param  array<string, array<int, string>>  $meta
     */
    function shopEnv(array $meta = []): void
    {
        chromeMarkers();
        stubValues(['get_post_meta' => static fn (int $id, string $key): array => $meta[$key] ?? []]);
        stubTags(['woocommerce_content' => '[shop]']);
    }

    it('prints the shop content with the sidebar and breadcrumbs by default', function (): void {
        shopEnv();

        expect(renderTemplate('woocommerce.php', ['post' => wpPost(['ID' => 7])]))->toBe(
            '[header] [part:header,image] <!--<div class="col-md-12 mobile-menu"> [part:menu,mobile] </div>--> <div class="container uams-body"> <div class="row">'
            .' <div class="col-md-8 uams-content" role=\'main\'> [part:breadcrumbs]'
            .' <div id=\'main_content\' class="uams-body-copy" tabindex="-1"> [shop] </div> </div>'
            .' <div id="sidebar">[sidebar]</div> </div> </div> [footer]'
        );
    });

    it('asks for the sidebar and breadcrumb meta of the current post', function (): void {
        $calls = [];
        shopEnv();
        stubValues(['get_post_meta' => static function (int $id, string $key) use (&$calls): array {
            $calls[] = [$id, $key];

            return [];
        }]);

        renderTemplate('woocommerce.php', ['post' => wpPost(['ID' => 7])]);

        expect($calls)->toBe([[7, 'sidebar'], [7, 'breadcrumb']]);
    });

    it('goes full width and drops the sidebar when the page turns it off', function (): void {
        shopEnv(['sidebar' => ['on']]);
        $html = renderTemplate('woocommerce.php', ['post' => wpPost(['ID' => 7])]);

        expect($html)->toContain('class="col-md-12 uams-content"')
            ->and($html)->toContain('<div id="sidebar"></div>');
    });

    it('keeps the sidebar for any other sidebar value', function (): void {
        shopEnv(['sidebar' => ['off']]);
        $html = renderTemplate('woocommerce.php', ['post' => wpPost(['ID' => 7])]);

        expect($html)->toContain('class="col-md-8 uams-content"')
            ->and($html)->toContain('[sidebar]');
    });

    it('drops the breadcrumbs when the page turns them off, and keeps them for any other value', function (string $value, bool $shown): void {
        shopEnv(['breadcrumb' => [$value]]);

        expect(str_contains(renderTemplate('woocommerce.php', ['post' => wpPost(['ID' => 7])]), '[part:breadcrumbs]'))->toBe($shown);
    })->with([
        'on' => ['on', false],
        'off' => ['off', true],
    ]);
});

describe('thinstrip.php', function (): void {
    it('prints the site title in the strip heading', function (): void {
        stubTags(['uams_site_title' => '[site-title]']);

        expect(renderTemplate('thinstrip.php'))->toContain('<h1 class="vertical-align">[site-title]</h1>');
    });
});

describe('footer.php', function (): void {
    function footerEnv(string $script = ''): void
    {
        stubTags(['wp_footer' => '[wp-footer]']);
        stubValues([
            'get_the_ID' => 7,
            'get_post_meta' => static fn (int $id, string $key, bool $single): string => $script,
        ]);
    }

    it('prints the footer, the current year and the footer hook', function (): void {
        footerEnv();

        $html = renderTemplate('footer.php');

        expect($html)->toContain('<p>&copy; '.date('Y').' University of Arkansas for Medical Sciences | Little Rock, AR</p>')
            ->and($html)->toContain('</div><!-- #uams-container-inner --> </div><!-- #uams-container --> [wp-footer] </body> </html>');
    });

    it('prints the footer script of the current page after the hook', function (): void {
        footerEnv('<script>track()</script>');

        expect(renderTemplate('footer.php'))->toContain('[wp-footer] <script>track()</script> </body>');
    });

    it('asks for the footer script of the current post', function (): void {
        $calls = [];
        stubTags(['wp_footer' => '']);
        stubValues([
            'get_the_ID' => 7,
            'get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
                $calls[] = [$id, $key, $single];

                return '';
            },
        ]);

        renderTemplate('footer.php');

        expect($calls)->toBe([[7, 'custom_footer_script', true]]);
    });
});
