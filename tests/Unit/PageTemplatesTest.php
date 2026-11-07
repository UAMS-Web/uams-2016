<?php

/**
 * The 404 and category archive templates, run with WordPress mocked.
 *
 * @see 404.php
 * @see archive.php
 */

declare(strict_types=1);

require_once __DIR__.'/../Support/theme-classes.php';

describe('404.php', function (): void {
    it('prints the page chrome around Reddie and the search form', function (): void {
        chromeMarkers();
        stubValues(['get_template_directory_uri' => 'https://t.test/theme']);

        expect(renderTemplate('404.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row"> <div class="content" role="main">'
            .' [part:breadcrumbs] <div class="row show-grid"> <div class="col-md-12">'
            .' <div class="reddie"><img src="https://t.test/theme/assets/images/reddie-404.png" alt="Never fear. Reddie is here!" /></div>'
            .' <div class="row show-grid"> <div class="col-md-6"> [search] <h3>Can\'t find what you\'re looking for?</h3>'
            ." <p> Reddie, the red blood cell \u{2014} and official UAMS mascot, is here to help you find what you're looking for.</p> </div>"
            .' <div class="col-md-5 col-md-offset-1"> <ul>'
            .' <li><a href="//www.uams.edu">UAMS home page</a></li>'
            .' <li><a href="//www.uamshealth.com/">UAMSHealth home page</a></li>'
            .' <li><a href="//www.uamshealth.com/maps/">Maps</a></li>'
            .' <li><a href="//www.uamshealth.com/news/">UAMS Today</a></li>'
            .' </ul> </div> </div> </div> </div> </div> </div> </div> [footer]'
        );
    });
});

describe('archive.php', function (): void {
    /**
     * @param  int  $posts  How many passes the loop makes.
     */
    function archiveEnv(string $description, int $posts, ?Closure $categoryTitle = null): void
    {
        chromeMarkers();
        $remaining = $posts;
        stubValues([
            'single_cat_title' => $categoryTitle ?? static fn (string $prefix = '', bool $display = true): string => 'Cat <b>',
            'category_description' => $description,
            'have_posts' => static function () use (&$remaining): bool {
                return $remaining-- > 0;
            },
        ]);
        stubTags([
            'the_post' => '[post]',
            'posts_nav_link' => static fn (string ...$args): string => '[nav:'.implode('|', $args).']',
        ]);
    }

    it('prints the category title, each post, and the paging links', function (): void {
        archiveEnv('', 2);

        expect(renderTemplate('archive.php'))->toBe(
            '[header] [part:header,image] <div class="container uams-body"> <div class="row"> <div class="content" role=\'main\'>'
            .' <div id=\'main_content\' class="uams-body-copy" tabindex="-1"> <h1>Cat <b></h1> <hr>'
            .' [post][part:content,archive][post][part:content,archive]'
            .' <br /> [nav: |Previous page|Next page] </div> </div> [sidebar] </div> </div> [footer]'
        );
    });

    it('prints the category description when there is one', function (): void {
        archiveEnv('<p>About</p>', 0);

        expect(renderTemplate('archive.php'))->toContain(
            '<h1>Cat <b></h1> <div class="archive-meta"><p>About</p></div> <hr> <br />'
        );
    });

    it('asks for the category title without a prefix and without printing it', function (): void {
        $received = null;
        archiveEnv('', 0, static function (string $prefix = 'unset', bool $display = true) use (&$received): string {
            $received = [$prefix, $display];

            return '';
        });

        renderTemplate('archive.php');

        expect($received)->toBe(['', false]);
    });
});
