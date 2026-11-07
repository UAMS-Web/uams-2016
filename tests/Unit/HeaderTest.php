<?php

/**
 * The document header, run with WordPress mocked: header.php.
 *
 * Not covered here, and why. The redirect branch ends in `exit;`, which stops the process that
 * runs it, so no in-process test can see past it. HeaderRedirectTest runs it in a child process.
 * The mutants on that branch (the `301`, the `esc_url_raw()` call and the early return of the
 * other fields) are therefore not run against a test by the mutation run, which executes tests
 * in-process; the child-process test is their only guard and is a pinning test, not a mutation
 * kill.
 *
 * @see header.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

/**
 * @param  array<string, mixed>  $fields  ACF fields by name.
 * @param  array<string, mixed>  $meta  Post meta by key.
 * @param  array<string, mixed>  $values  Overrides for any stubbed function.
 */
function headerEnv(array $fields = [], array $meta = [], array $options = [], bool $singular = true, bool $menu = false, array $values = []): void
{
    chromeMarkers();
    stubValues(array_merge([
        'get_field' => static fn (string $name): mixed => $fields[$name] ?? false,
        'get_bloginfo' => static fn (string $show = '', string $filter = 'raw'): string => match ($show) {
            'description' => "Blog tagline <{$filter}>",
            'name' => 'UAMS   Health',
            default => '',
        },
        'is_singular' => $singular,
        'wp_title' => static fn (string $sep, bool $display, string $location): string => "[wp_title:{$sep}|".var_export($display, true)."|{$location}]",
        'get_option' => static fn (string $name): mixed => $options[$name] ?? false,
        'get_template_directory_uri' => 'https://t.test/theme',
        'get_template_directory' => dirname(__DIR__, 2),
        'get_the_ID' => 7,
        'get_post_meta' => static fn (int $id, string $key, bool $single): string => $meta[$key] ?? '',
        'has_nav_menu' => static fn (string $location): bool => $location === 'white-bar' && $menu,
    ], $values));
    stubTags([
        'language_attributes' => 'lang="en-US"',
        'bloginfo' => static fn (string $show): string => "[bloginfo:{$show}]",
        'wp_head' => '[wp_head]',
        'body_class' => static fn (string ...$classes): string => 'class="[body:'.implode(',', $classes).']"',
        'uams_dropdowns' => '[dropdowns]',
    ]);
}

describe('header.php', function (): void {
    it('prints the document head and the opening of the body', function (): void {
        headerEnv();
        $html = renderTemplate('header.php');

        expect($html)->toStartWith(
            '<!DOCTYPE html> <html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" class="no-js"> <head>'
            .' <meta http-equiv="X-UA-Compatible" content="IE=edge">'
            .' <title>H{[wp_title: | |false|right]}H{UAMS Health}</title>'
            .' <meta charset="[bloginfo:charset]"> <meta name="viewport" content="width=device-width, initial-scale=1">'
            .' <meta name="description" content="A{Blog tagline <display>}">'
        )->and($html)->toContain(
            '<!--[if lt IE 9]> <body class="[body:lt-ie9]"> <![endif]--> <!--[if gt IE 8]><!--> <body class="[body:]"> <!--<![endif]-->'
        )->and($html)->toEndWith(
            '<div id="uams-container"> <div id="uams-container-inner"> [part:thinstrip]'
        );
    });

    describe('the document title and description', function (): void {
        it('uses the page SEO title, then the site name, on a singular page', function (): void {
            headerEnv(['seo_document_title' => 'Custom <title>']);

            expect(renderTemplate('header.php'))->toContain('<title>H{Custom <title> | }H{UAMS Health}</title>');
        });

        it('uses the page SEO description on a singular page', function (): void {
            headerEnv(['seo_meta_description' => 'Custom <desc>']);

            expect(renderTemplate('header.php'))->toContain('<meta name="description" content="A{Custom <desc>}">');
        });

        it('uses the site title and tagline when the page has no SEO fields', function (): void {
            headerEnv();

            expect(renderTemplate('header.php'))->toContain('<title>H{[wp_title: | |false|right]}')->toContain('A{Blog tagline <display>}');
        });

        it('reads the SEO fields on a page that is not singular only when a post id is in scope', function (bool $singular, bool $postId, bool $used): void {
            headerEnv(['seo_document_title' => 'Custom', 'seo_meta_description' => 'Desc'], singular: $singular);

            $html = renderTemplate('header.php', $postId ? ['post_id' => 7] : []);

            expect(str_contains($html, '<title>H{Custom | }'))->toBe($used)
                ->and(str_contains($html, 'content="A{Desc}"'))->toBe($used);
        })->with([
            'singular' => [true, false, true],
            'not singular, post id set' => [false, true, true],
            'neither' => [false, false, false],
        ]);

        it('ignores the SEO fields when the field plugin is missing', function (): void {
            headerEnv(['seo_document_title' => 'Custom']);
            stubFunctionExists(['get_field' => false]);

            expect(renderTemplate('header.php'))->not->toContain('Custom')->toContain('[wp_title:');
        });
    });

    describe('the keywords and canonical link', function (): void {
        it('prints the canonical link when the page sets one', function (): void {
            headerEnv(['seo_canonical_url' => 'https://x.test/canon?a=1&b=2']);

            expect(renderTemplate('header.php'))->toContain('<link rel="canonical" href="U{https://x.test/canon?a=1&b=2}" />');
        });

        it('prints no canonical link or keywords by default', function (): void {
            headerEnv();
            $html = renderTemplate('header.php');

            expect($html)->not->toContain('canonical')->and($html)->not->toContain('name="keywords"');
        });

        it('prints the keywords only when the redirect field reads as set the second time', function (): void {
            // The redirect field is read first to decide the redirect, then again to decide the keywords:
            // the keywords are reachable only if a later read of that field is truthy.
            $reads = 0;
            headerEnv(['seo_meta_keywords' => 'a, <b>'], values: [
                'get_field' => static function (string $name) use (&$reads): mixed {
                    if ($name === 'seo_custom_redirect_url') {
                        return ++$reads > 1 ? 'https://x.test/' : false;
                    }

                    return $name === 'seo_meta_keywords' ? 'a, <b>' : false;
                },
            ]);

            expect(renderTemplate('header.php'))->toContain('<meta name="keywords" content="A{a, <b>}" />');
        });
    });

    describe('the tag manager', function (): void {
        it('uses the site tag manager id when one is set, in the script and the noscript frame', function (): void {
            headerEnv(options: ['google_tag_manager_id' => 'GTM-ABC123']);
            $html = renderTemplate('header.php');

            expect($html)->toContain("'dataLayer','A{GTM-ABC123}');</script>")
                ->and($html)->toContain('ns.html?id=A{GTM-ABC123}"');
        });

        it('falls back to the default id when none is set', function (): void {
            headerEnv();
            $html = renderTemplate('header.php');

            expect($html)->toContain("'dataLayer','GTM-NGG4P7F');</script>")->and($html)->toContain('ns.html?id=GTM-NGG4P7F"');
        });
    });

    describe('the legacy browser scripts', function (): void {
        it('links the shims for old Internet Explorer from the theme', function (): void {
            headerEnv();

            expect(renderTemplate('header.php'))->toContain(
                '[wp_head] <!--[if lt IE 9]>'
                .' <script src="U{https://t.test/theme}/assets/ie/js/html5shiv.js" type="text/javascript"></script>'
                .' <script src="U{https://t.test/theme}/assets/ie/js/respond.js" type="text/javascript"></script>'
                ." <link rel='stylesheet' href='U{https://t.test/theme}/assets/ie/css/ie.css' type='text/css' media='all' /> <![endif]-->"
            );
        });
    });

    describe('the page scripts and styles', function (): void {
        it('prints the page JavaScript, CSS and custom header script, unescaped, in that order', function (): void {
            headerEnv(meta: ['javascript' => '<script>js()</script>', 'css' => '<style>c{}</style>', 'custom_header_script' => '<script>custom()</script>']);

            expect(renderTemplate('header.php'))->toContain('<script>js()</script><style>c{}</style><script>custom()</script> </head>');
        });

        it('prints nothing for the ones the page does not set', function (): void {
            headerEnv(meta: ['css' => '<style>c{}</style>']);
            $html = renderTemplate('header.php');

            expect($html)->toContain('<style>c{}</style> </head>')->and($html)->not->toContain('js()');
        });

        it('asks for the scripts of the current page', function (): void {
            $calls = [];
            headerEnv(values: ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
                $calls[] = [$id, $key, $single];

                return '';
            }]);

            renderTemplate('header.php');

            expect($calls)->toBe([[7, 'javascript', true], [7, 'css', true], [7, 'custom_header_script', true]]);
        });
    });

    describe('the navigation', function (): void {
        it('prints the dropdown menu and the mobile menu when the white bar menu exists', function (): void {
            headerEnv(menu: true);

            expect(renderTemplate('header.php'))->toEndWith('[part:thinstrip] [dropdowns] <div class="col-md-12 mobile-menu">[part:menu,mobile]</div>');
        });

        it('prints neither without the white bar menu', function (): void {
            headerEnv(menu: false);
            $html = renderTemplate('header.php');

            expect($html)->not->toContain('[dropdowns]')->and($html)->not->toContain('mobile-menu');
        });
    });
});
