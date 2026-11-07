<?php

/**
 * The home page template, run with WordPress mocked and ACF's repeater functions stood in for.
 *
 * @see templates/template-home.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

/**
 * @param  array<string, mixed>  $meta  Post meta by key.
 * @param  array<string, mixed>  $fields  ACF fields by name.
 * @param  array<string, list<array<string, mixed>>>  $rows  Repeater rows by repeater name.
 * @param  array<string, mixed>  $values  Overrides for any stubbed function.
 */
function homeEnv(array $meta = [], array $fields = [], array $rows = [], int $thumbnail = 0, array $values = []): void
{
    chromeMarkers();
    contentTags();
    $position = [];
    $current = [];
    stubValues(array_merge([
        'get_the_ID' => 7,
        'get_post_meta' => static fn (int $id, string $key, bool $single): mixed => $meta[$key] ?? '',
        'get_field' => static fn (string $name): mixed => $fields[$name] ?? ($rows[$name] ?? false),
        'have_rows' => static function (string $name) use (&$position, $rows): bool {
            if (($position[$name] ?? 0) < count($rows[$name] ?? [])) {
                return true;
            }

            // As in ACF, reaching the end of a repeater resets it, so the next call starts again.
            $position[$name] = 0;

            return false;
        },
        'get_sub_field' => static function (string $name) use (&$current): mixed {
            return $current[$name] ?? false;
        },
        'get_post_thumbnail_id' => $thumbnail,
        'wp_get_attachment_url' => static fn (int $id): string => "/files/{$id}.jpg",
        'get_template_directory_uri' => 'https://t.test/theme',
        'have_posts' => loopOf(1),
        'comments_open' => false,
        'get_comments_number' => 0,
        'get_the_title' => 'About <us>',
    ], $values));
    stubTags([
        'the_post' => '[post]',
        'comments_template' => '[comments]',
        'uams_sidebar_menu' => '[sidebar-menu]',
        'the_row' => static function () use (&$position, &$current, $rows): void {
            // The repeater being walked is the last one asked about with have_rows().
            foreach ($rows as $name => $list) {
                if (($position[$name] ?? 0) < count($list)) {
                    $current = $list[$position[$name] ?? 0];
                    $position[$name] = ($position[$name] ?? 0) + 1;

                    return;
                }
            }
        },
        'wp_enqueue_script' => static fn (mixed ...$args): string => '[enqueue:'.implode('|', array_map(static fn (mixed $arg): string => is_array($arg) ? '['.implode(',', $arg).']' : (string) $arg, $args)).']',
    ]);
}

/**
 * @return array<string, mixed>
 */
function slides(): array
{
    return [
        'home_page_slider' => 'slide',
        'home_slides' => [
            [
                'home_slide_desktop' => ['url' => '/d1.jpg'],
                'home_slide_mobile' => ['url' => '/m1.jpg'],
                'home_slide_internal_link' => '/one/',
                'home_slide_text_color' => 'darktext',
                'home_slide_title' => 'One <b>',
                'home_slide_text' => 'First',
                'home_slide_button_text' => 'Go one',
            ],
            [
                'home_slide_desktop' => '/d2.jpg',
                'home_slide_mobile' => '',
                'home_slide_internal_link' => '/two/',
                'home_slide_external' => true,
                'home_slide_external_link' => 'https://x.test/two',
                'home_slide_title' => 'Two',
                'home_slide_text' => 'Second',
                'home_slide_button_text' => 'Go two',
            ],
        ],
    ];
}

describe('template-home.php', function (): void {
    describe('the slider', function (): void {
        /**
         * @return array<string, mixed>
         */
        $setup = static function (array $overrides = []): array {
            $all = slides();
            $fields = ['home_page_slider' => $all['home_page_slider']];

            return array_merge(['fields' => $fields, 'rows' => ['home_slides' => $all['home_slides']]], $overrides);
        };

        it('prints a slide per row, the first active, with its images, colour, text and link', function () use ($setup): void {
            $s = $setup();
            homeEnv(fields: $s['fields'], rows: $s['rows']);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain('<div class="uams-homepage-slider-container" role="region" aria-label="homepage-slides">')
                ->and($html)->toContain(
                    '<div data-mobimg="U{/m1.jpg}" data-dtimg="U{/d1.jpg}" class="uams-hero-image uams-homepage-slider A{darktext} activeslide"'
                    .' style="background-position: center center; background-image:url(\'U{/d1.jpg}\');">'
                    .' <div> <h3 class="slide-title" id="slide-title-0">H{One <b>}<span class="udub-slant"><span></span></span></h3>'
                    .' <p class="slide-content">H{First}</p>'
                    .' <p><a class="uams-btn btn-sm btn-none" href="U{/one/}" aria-describedby="slide-title-0">H{Go one}</a></p> </div> </div>'
                );
        });

        it('uses the desktop image on mobile when a slide has no mobile image, and the external link when it is set', function () use ($setup): void {
            $s = $setup();
            homeEnv(fields: $s['fields'], rows: $s['rows']);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain(
                '<div data-mobimg="U{/d2.jpg}" data-dtimg="U{/d2.jpg}" class="uams-hero-image uams-homepage-slider A{lighttext} "'
            )->and($html)->toContain('href="U{https://x.test/two}" aria-describedby="slide-title-1">H{Go two}</a>');
        });

        it('prints the slide controls in the first slide colour when there is more than one slide', function () use ($setup): void {
            $s = $setup();
            homeEnv(fields: $s['fields'], rows: $s['rows']);

            expect(renderTemplate('templates/template-home.php'))->toContain('<div class="slideshow-controls A{darktext}"> <button class="next-headline">');
        });

        it('prints no slide controls for a single slide', function () use ($setup): void {
            $s = $setup(['rows' => ['home_slides' => [slides()['home_slides'][0]]]]);
            homeEnv(fields: $s['fields'], rows: $s['rows']);

            expect(renderTemplate('templates/template-home.php'))->not->toContain('slideshow-controls');
        });

        it('enqueues the slider script with its dependencies before the footer', function () use ($setup): void {
            $s = $setup();
            homeEnv(fields: $s['fields'], rows: $s['rows']);

            expect(renderTemplate('templates/template-home.php'))->toEndWith('[enqueue:home-slider-script|https://t.test/theme/js/home-slider.js|[jquery]|1.1|1][footer]');
        });

        it('prints the hero banner instead when the slider is off, empty or the field plugin is missing', function (string $slider, array $rows, bool $acf): void {
            homeEnv(fields: ['home_page_slider' => $slider], rows: $rows);
            $acf ?: stubFunctionExists(['get_field' => false, 'have_rows' => false]);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain('<div class="uams-hero-image hero-height')->and($html)->not->toContain('uams-homepage-slider')
                ->and($html)->not->toContain('enqueue');
        })->with([
            'another type' => ['image', ['home_slides' => [['home_slide_title' => 'x']]], true],
            'slides, no rows' => ['slide', [], true],
            'no field plugin' => ['slide', ['home_slides' => [['home_slide_title' => 'x']]], false],
        ]);
    });

    describe('the hero banner', function (): void {
        it('prints the featured image as the banner background', function (): void {
            homeEnv(thumbnail: 99);

            expect(renderTemplate('templates/template-home.php'))->toContain(
                '<div class="uams-hero-image hero-heightA{}" style="background-image: url(\'U{/files/99.jpg}\');">'
            );
        });

        it('falls back to the grey pattern without a featured image, and without a URL for it', function (int $thumbnail, string $url): void {
            homeEnv(thumbnail: $thumbnail, values: ['wp_get_attachment_url' => $url]);

            expect(renderTemplate('templates/template-home.php'))->toContain("url('U{https://t.test/theme/assets/headers/uams-pattern-grey.png}')");
        })->with(['no thumbnail' => [0, '/x.jpg'], 'no URL' => [99, '']]);

        it('prints the page title unless the home image has one', function (): void {
            homeEnv();

            expect(renderTemplate('templates/template-home.php'))->toContain('<h1 class="uams-site-titleA{}">H{About <us>}</h1>');

            homeEnv(fields: ['home_image_title' => 'Welcome <home>']);

            expect(renderTemplate('templates/template-home.php'))->toContain('<h1 class="uams-site-titleA{}">H{Welcome <home>}</h1>');
        });

        it('prints the title with dark text when the post asks for it', function (): void {
            homeEnv(meta: ['home_image_dark_text' => ['1']]);

            expect(renderTemplate('templates/template-home.php'))->toContain('<h1 class="uams-site-titleA{ hero-text-dark}">');
        });

        it('prints a mobile image from a field array with a URL or from a plain string', function (mixed $field): void {
            homeEnv(fields: ['home_image_mobile' => $field]);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain('hero-heightA{ hero-mobile-image}"')
                ->and($html)->toContain('<div class="mobile-image" style="background-image: url(\'U{/m.jpg}\');"></div>');
        })->with(['array' => [['url' => '/m.jpg']], 'string' => ['/m.jpg']]);

        it('prints no mobile image for an array without a URL, an empty value or another type', function (mixed $field): void {
            homeEnv(fields: ['home_image_mobile' => $field]);

            expect(renderTemplate('templates/template-home.php'))->not->toContain('mobile-image');
        })->with(['no URL' => [['url' => '']], 'empty array' => [[]], 'false' => [false], 'integer' => [5]]);

        it('prints the button with its text and internal link', function (): void {
            homeEnv(fields: ['home_image_add_button' => true, 'home_image_button_text' => 'Apply <now>', 'home_image_internal_url' => '/apply/', 'home_image_external_url' => 'https://x.test/']);

            expect(renderTemplate('templates/template-home.php'))->toContain('<a class="uams-btn btn-sm btn-none" href="U{/apply/}">H{Apply <now>}</a>');
        });

        it('links the button to the external URL when it is external', function (): void {
            homeEnv(fields: ['home_image_add_button' => true, 'home_image_button_text' => 'Go', 'home_image_external' => true, 'home_image_internal_url' => '/apply/', 'home_image_external_url' => 'https://x.test/']);

            expect(renderTemplate('templates/template-home.php'))->toContain('href="U{https://x.test/}">H{Go}</a>');
        });

        it('prints no button unless it is turned on', function (): void {
            homeEnv(fields: ['home_image_button_text' => 'Go']);

            expect(renderTemplate('templates/template-home.php'))->not->toContain('uams-btn');
        });

        it('reads no fields when the field plugin is missing', function (): void {
            homeEnv(values: ['get_field' => static fn (): never => throw new LogicException('get_field called')]);
            stubFunctionExists(['get_field' => false]);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain('<h1 class="uams-site-titleA{}">H{About <us>}</h1>')->and($html)->not->toContain('uams-btn');
        });
    });

    describe('the action menu', function (): void {
        $menu = [
            ['action_link_title' => 'Maps <1>', 'action_link_icon' => 'icon-map', 'action_link' => false, 'action_link_page' => '/maps/', 'action_link_url' => 'https://x.test/maps'],
            ['action_link_title' => 'News', 'action_link_icon' => 'icon-news', 'action_link' => true, 'action_link_page' => '/news/', 'action_link_url' => 'https://x.test/news'],
        ];

        it('prints a link per row, each sized to the number of rows', function () use ($menu): void {
            homeEnv(fields: ['action_menu_active' => true], rows: ['action_menu' => $menu]);

            expect(renderTemplate('templates/template-home.php'))->toContain(
                '<div class="full-bar"> <nav aria-label="popular links" class="container action-bar"> <ul class="center-block">'
                .' <li class="ab-1_2"><a href="U{/maps/}" title="A{Maps <1>}"><span class="icon A{icon-map}"></span><span>H{Maps <1>}</span></a></li>'
                .' <li class="ab-1_2"><a href="U{https://x.test/news}" title="A{News}"><span class="icon A{icon-news}"></span><span>H{News}</span></a></li>'
                .' </ul> </nav> </div>'
            );
        });

        it('prints no menu unless it is active and has rows', function (bool $active, bool $rows): void {
            homeEnv(fields: ['action_menu_active' => $active], rows: $rows ? ['action_menu' => [['action_link_title' => 'x']]] : []);

            expect(renderTemplate('templates/template-home.php'))->not->toContain('full-bar');
        })->with(['inactive' => [false, true], 'no rows' => [true, false]]);

        it('prints no menu when the field plugin is missing', function (): void {
            homeEnv(fields: ['action_menu_active' => true], rows: ['action_menu' => [['action_link_title' => 'x']]]);
            stubFunctionExists(['get_field' => false, 'have_rows' => false]);

            expect(renderTemplate('templates/template-home.php'))->not->toContain('full-bar');
        });

        it('sizes the links to one when the rows are not an array', function (): void {
            homeEnv(
                fields: ['action_menu_active' => true, 'action_menu' => 'not an array'],
                rows: ['action_menu' => [['action_link_title' => 'x']]],
            );

            expect(renderTemplate('templates/template-home.php'))->toContain('<li class="ab-1_1">');
        });
    });

    describe('the page body', function (): void {
        it('prints the breadcrumbs, mobile sidebar, each post and the sidebar', function (): void {
            homeEnv();

            expect(renderTemplate('templates/template-home.php'))->toContain(
                '<div class="container uams-body"> <div class="row"> <div class="hero-content col-md-8 uams-content" role="main"> [part:breadcrumbs]'
                .' <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">'
                .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
                .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
                .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div>'
                .' <div id="main_content" class="uams-body-copy" tabindex="-1"> [post][content] </div> </div>'
                .' <div id="sidebar"> [sidebar] </div> </div> </div>'
            );
        });

        it('drops the sidebar and widens the column when the page turns the sidebar off', function (): void {
            homeEnv(['sidebar' => 'on']);
            $html = renderTemplate('templates/template-home.php');

            expect($html)->toContain('hero-content col-md-12 uams-content')->and($html)->not->toContain('[sidebar]');
        });

        it('hides the breadcrumbs only when the page turns them on', function (string $value, bool $shown): void {
            homeEnv(['breadcrumb' => $value]);

            expect(str_contains(renderTemplate('templates/template-home.php'), '[part:breadcrumbs]'))->toBe($shown);
        })->with(['unset' => ['', true], 'on' => ['on', false], 'any other value' => ['off', true]]);

        it('loads the comments template when comments are open or the post has comments', function (bool $open, int $count, bool $loaded): void {
            homeEnv(values: ['comments_open' => $open, 'get_comments_number' => $count]);

            expect(str_contains(renderTemplate('templates/template-home.php'), '[comments]'))->toBe($loaded);
        })->with(['open, none yet' => [true, 0, true], 'closed, with comments' => [false, 3, true], 'closed, none' => [false, 0, false]]);

        it('asks for the post meta of the current page', function (): void {
            $calls = [];
            homeEnv(values: ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
                $calls[] = [$id, $key, $single];

                return '';
            }]);

            renderTemplate('templates/template-home.php');

            expect($calls)->toBe([[7, 'sidebar', true], [7, 'breadcrumb', true], [7, 'home_image_dark_text', true]]);
        });
    });
});
