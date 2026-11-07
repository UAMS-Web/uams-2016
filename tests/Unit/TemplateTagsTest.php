<?php

/**
 * The thumbnail, title and permalink helpers in inc/template-functions.php, with WordPress mocked.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    stubEscaping();
});

describe('uams_get_thumbnail_url and uams_thumbnail_url', function (): void {
    it('returns the first element of the attachment image source', function (): void {
        Functions\expect('get_post_thumbnail_id')->once()->andReturn(42);
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(42, 'medium', true)
            ->andReturn(['/img.png', 300, 200]);

        expect(uams_get_thumbnail_url('medium'))->toBe('/img.png');
    });

    it('asks for the original size by default', function (): void {
        when('get_post_thumbnail_id')->justReturn(42);
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(42, 'original', true)
            ->andReturn(false);

        expect(uams_get_thumbnail_url())->toBe('');
    });

    it('returns an empty string when there is no image', function (): void {
        when('get_post_thumbnail_id')->justReturn(0);
        when('wp_get_attachment_image_src')->justReturn(false);

        expect(uams_get_thumbnail_url('full'))->toBe('');
    });

    it('echoes the escaped url for the requested size', function (): void {
        when('get_post_thumbnail_id')->justReturn(42);
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(42, 'large', true)
            ->andReturn(['/a b.png']);

        uams_thumbnail_url('large');
    })->expectOutputString('U{/a b.png}');

    it('echoes the original size by default', function (): void {
        when('get_post_thumbnail_id')->justReturn(42);
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(42, 'original', true)
            ->andReturn(['/o.png']);

        uams_thumbnail_url();
    })->expectOutputString('U{/o.png}');
});

describe('uams_site_title', function (): void {
    it('links the site name to the home page', function (): void {
        when('get_option')->justReturn(false);
        Functions\expect('get_bloginfo')->once()->with('name')->andReturn('UAMS Health');

        uams_site_title();
    })->expectOutputString('<a href="U{https://site.test/}" title="A{UAMS Health}" tabindex="-1" aria-hidden="true" class="uams-site-title'.'A{}'.'">H{UAMS Health}</a>');

    it('adds the long-title class when the option is on', function (): void {
        Functions\expect('get_option')->once()->with('overly_long_title')->andReturn('1');
        when('get_bloginfo')->justReturn('UAMS');

        uams_site_title();
    })->expectOutputString('<a href="U{https://site.test/}" title="A{UAMS}" tabindex="-1" aria-hidden="true" class="uams-site-titleA{ long-title}">H{UAMS}</a>');

    it('collapses the triple-space break to one space in the title, and a line break in the text', function (): void {
        when('get_option')->justReturn(false);
        when('get_bloginfo')->justReturn('Winthrop   P.   Rockefeller');

        uams_site_title();
    })->expectOutputString('<a href="U{https://site.test/}" title="A{Winthrop P. Rockefeller}" tabindex="-1" aria-hidden="true" class="uams-site-title'.'A{}'.'">H{Winthrop <br/>P. <br/>Rockefeller}</a>');
});

describe('uams_page_title', function (): void {
    it('links the escaped page title', function (): void {
        when('get_option')->justReturn(false);
        when('get_the_title')->justReturn('About <us>');

        uams_page_title();
    })->expectOutputString('<a href="" title="A{About <us>}"><div class="A{uams-page-title}">H{About <us>}</div></a>');

    it('adds the long-title class when the option is on', function (): void {
        Functions\expect('get_option')->once()->with('overly_long_title')->andReturn('1');
        when('get_the_title')->justReturn('About');

        uams_page_title();
    })->expectOutputString('<a href="" title="A{About}"><div class="A{uams-page-title long-title}">H{About}</div></a>');
});

describe('uams_get_permalink', function (): void {
    it('uses the current post when none is given', function (mixed $post): void {
        Functions\expect('get_the_ID')->once()->andReturn(8);
        Functions\expect('get_post')->never();
        when('get_post_meta')->justReturn('');
        when('get_post_format')->justReturn(false);
        Functions\expect('get_permalink')->once()->with(8)->andReturn('/post-8/');

        expect(uams_get_permalink($post))->toBe('/post-8/');
    })->with([
        'no argument' => [null],
        'zero' => [0],
        'an empty string' => [''],
    ]);

    it('resolves a given post to its id', function (): void {
        Functions\expect('get_the_ID')->never();
        Functions\expect('get_post')->once()->with(55)->andReturn(wpPost(['ID' => 9]));
        when('get_post_meta')->justReturn('');
        when('get_post_format')->justReturn(false);
        Functions\expect('get_permalink')->once()->with(9)->andReturn('/post-9/');

        expect(uams_get_permalink(55))->toBe('/post-9/');
    });

    it('returns an empty string when the post cannot be resolved', function (): void {
        when('get_post')->justReturn();
        Functions\expect('get_post_meta')->never();
        Functions\expect('get_permalink')->never();

        expect(uams_get_permalink(55))->toBe('');
    });

    it('returns an empty string when the current post has no id', function (): void {
        when('get_the_ID')->justReturn(false);
        Functions\expect('get_permalink')->never();

        expect(uams_get_permalink())->toBe('');
    });

    it('returns the external link of a link-format post', function (): void {
        when('get_the_ID')->justReturn(8);
        Functions\expect('get_post_meta')->once()->with(8, 'post_custom_link', true)->andReturn('https://example.org/x');
        Functions\expect('get_post_format')->once()->with(8)->andReturn('link');
        Functions\expect('get_permalink')->never();

        expect(uams_get_permalink())->toBe('https://example.org/x');
    });

    it('keeps the permalink for a link without an external address', function (): void {
        when('get_the_ID')->justReturn(8);
        when('get_post_meta')->justReturn('');
        when('get_post_format')->justReturn('link');
        when('get_permalink')->justReturn('/post-8/');

        expect(uams_get_permalink())->toBe('/post-8/');
    });

    it('keeps the permalink for an external address on a post that is not a link format', function (): void {
        when('get_the_ID')->justReturn(8);
        when('get_post_meta')->justReturn('https://example.org/x');
        when('get_post_format')->justReturn('gallery');
        when('get_permalink')->justReturn('/post-8/');

        expect(uams_get_permalink())->toBe('/post-8/');
    });
});
