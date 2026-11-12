<?php

/**
 * UAMS_Iframes, the [iframe] shortcode that embeds pages from approved domains, with WordPress
 * mocked.
 *
 * @see setup/class.uams-iframes.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-iframes.php');
    when('esc_attr')->alias(static fn (string $value): string => 'A{'.$value.'}');
    when('get_option')->alias(static fn (string $name): string => match ($name) {
        'embed_size_h' => '400',
        'embed_size_w' => '600',
        default => 'wrong',
    });
    when('shortcode_atts')->alias(static function (array $pairs, mixed $atts, string $shortcode = ''): array {
        $GLOBALS['uams_test_iframe_shortcode'] = $shortcode;

        return array_merge($pairs, (array) $atts);
    });
    // esc_url() returns an empty string for a URL whose protocol is not allowed.
    when('esc_url')->alias(static function (string $url, ?array $protocols = null): string {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $GLOBALS['uams_test_iframe_protocols'][] = $protocols;

        return preg_match('#^https?:#', $url) === 1 ? $url : '';
    });
    $GLOBALS['uams_test_iframe_protocols'] = [];
    $_GET = [];
});

afterEach(function (): void {
    $_GET = [];
});

it('registers the iframe shortcode and the allowed domains', function (): void {
    $calls = recordShortcodes();
    $iframes = new UAMS_Iframes;

    expect($calls->getArrayCopy())->toBe([['iframe', [$iframes, 'add_iframe']]])
        ->and($iframes->ALLOWED_IFRAMES)->toBe($iframes->get_iframe_domains());
});

it('allows these domains', function (): void {
    expect((new UAMS_Iframes)->get_iframe_domains())->toBe([
        'uams.edu',
        'www.uams.edu',
        'uamshealth.com',
        'uamsonlinedev.com',
        'google.com',
        'docs.google.com',
        'youtube.com',
        'www.googletagmanager.com',
        'www.google.com',
        'www.youtube.com',
        'pgcalc.com',
        'www.pgcalc.com',
        'storify.com',
        'api.soundcloud.com',
        'flickr.com',
        'vimeo.com',
        'player.vimeo.com',
        'www.facebook.com',
        'facebook.com',
        'monday.com',
        'forms.monday.com',
    ]);
});

describe('add_iframe', function (): void {
    it('renders nothing without a usable source', function (mixed $src): void {
        expect((new UAMS_Iframes)->add_iframe(['src' => $src]))->toBe('')
            ->and((new UAMS_Iframes)->add_iframe([]))->toBe('');
    })->with(['empty' => [''], 'a protocol that is not allowed' => ['javascript:alert(1)']]);

    it('renders nothing for a host that is not allowed', function (string $src): void {
        expect((new UAMS_Iframes)->add_iframe(['src' => $src]))->toBe('');
    })->with([
        'another domain' => ['https://evil.test/embed'],
        'a lookalike' => ['https://uams.edu.evil.test/embed'],
    ]);

    it('renders the iframe with the size from the options by default', function (): void {
        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://www.uams.edu/form']);

        expect($html)->toBe('<iframe src="https://www.uams.edu/form" width="A{600}" height="A{400}" style="border:0"></iframe>')
            ->and($GLOBALS['uams_test_iframe_shortcode'])->toBe('iframe')
            ->and($GLOBALS['uams_test_iframe_protocols'])->toBe([['http', 'https'], ['http', 'https']]);
    });

    it('renders the size the shortcode gives', function (): void {
        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://vimeo.com/1', 'width' => '300', 'height' => '200']);

        expect($html)->toBe('<iframe src="https://vimeo.com/1" width="A{300}" height="A{200}" style="border:0"></iframe>');
    });

    it('renders a source without a host', function (): void {
        $html = (new UAMS_Iframes)->add_iframe(['src' => 'http:page']);

        expect($html)->toBe('<iframe src="http:page" width="A{600}" height="A{400}" style="border:0"></iframe>');
    });

    it('decodes the entities in the source', function (): void {
        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://www.uams.edu/p?a=1&amp;b=2']);

        expect($html)->toContain('src="https://www.uams.edu/p?a=1&b=2"');
    });

    it('appends the query of the page when the source has none', function (): void {
        $_GET = ['x' => '1', 'y' => 'two words'];

        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://www.uams.edu/p']);

        expect($html)->toContain('src="https://www.uams.edu/p?x=1&y=two+words"');
    });

    it('merges the query of the page over the query of the source', function (): void {
        $_GET = ['b' => '3', 'c' => '4'];

        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://www.uams.edu/p?a=1&b=2']);

        expect($html)->toContain('src="https://www.uams.edu/p?a=1&b=3&c=4"');
    });

    it('leaves the query of the source when the page has none', function (): void {
        $html = (new UAMS_Iframes)->add_iframe(['src' => 'https://www.uams.edu/p?a=1']);

        expect($html)->toContain('src="https://www.uams.edu/p?a=1"');
    });
});
