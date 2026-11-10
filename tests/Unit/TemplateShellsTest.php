<?php

/**
 * The small root templates, run with WordPress mocked: breadcrumbs.php, comments.php,
 * menu-mobile.php, sidebar.php, searchform.php and header-image.php.
 *
 * Each template is included and its output compared with whitespace collapsed (see
 * renderTemplate()). comments.php holds no executable statement beyond `declare(strict_types=1)`,
 * so it has no mutants; its test pins that it prints nothing.
 *
 * @see breadcrumbs.php
 * @see comments.php
 * @see menu-mobile.php
 * @see sidebar.php
 * @see searchform.php
 * @see header-image.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

require_once __DIR__.'/../Support/theme-classes.php';

beforeEach(function (): void {
    stubEscaping();
});

it('prints the breadcrumb trail', function (): void {
    Functions\expect('uams_breadcrumbs')->once();

    expect(renderTemplate('breadcrumbs.php'))->toBe('');
});

it('prints nothing for comments', function (): void {
    expect(renderTemplate('comments.php'))->toBe('');
});

it('prints the front-page mobile menu', function (): void {
    Functions\expect('uams_mobile_front_page_menu')->once();

    expect(renderTemplate('menu-mobile.php'))->toBe('');
});

describe('sidebar.php', function (): void {
    it('prints nothing without a sidebar', function (): void {
        Functions\expect('uams_has_sidebar')->once()->andReturn(false);
        Functions\expect('uams_sidebar_menu')->never();
        Functions\expect('dynamic_sidebar')->never();

        expect(renderTemplate('sidebar.php'))->toBe('');
    });

    it('prints the sidebar menu and then the widget area', function (): void {
        loadSidebarWidgetFile();
        Functions\expect('uams_has_sidebar')->once()->andReturn(true);
        Functions\expect('uams_sidebar_menu')->once()->andReturnUsing(function (): void {
            echo '[menu]';
        });
        Functions\expect('dynamic_sidebar')->once()->with('sidebar')->andReturnUsing(function (): void {
            echo '[widgets]';
        });

        expect(renderTemplate('sidebar.php'))->toBe('<div class="col-md-4 uams-sidebar"> [menu] [widgets] </div>');
    });
});

it('prints the search form that posts to the site home', function (): void {
    stubValues(['set_url_scheme' => static fn (string $url): string => 'scheme('.$url.')']);

    expect(renderTemplate('searchform.php'))->toBe(
        '<form role="search" method="get" id="searchform" class="searchform" action="U{scheme(https://site.test/)}"> <fieldset> <div>'
        .' <label class="screen-reader-text" for="s">Search for:</label>'
        .' <input type="text" value="" name="s" id="s" placeholder="Search for:" autocomplete="off">'
        .' <input type="submit" id="searchsubmit" value="Search"> </div> </fieldset> </form>'
    );
});

describe('header-image.php', function (): void {
    it('prints the hero without a background when there is no header image', function (): void {
        stubValues(['get_header_image' => '']);
        Functions\expect('set_url_scheme')->never();

        expect(renderTemplate('header-image.php'))->toBe('<div class="uams-hero-image" ></div>');
    });

    it('prints the header image as the hero background, with the site scheme', function (): void {
        stubValues([
            'get_header_image' => 'http://site.test/hero.jpg',
            'set_url_scheme' => static fn (string $url): string => 'scheme('.$url.')',
        ]);

        expect(renderTemplate('header-image.php'))->toBe(
            '<div class="uams-hero-image" style="background-image:url(\'scheme(http://site.test/hero.jpg)\');" ></div>'
        );
    });
});
