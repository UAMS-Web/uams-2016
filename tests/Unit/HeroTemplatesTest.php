<?php

/**
 * The hero page templates, run with WordPress mocked: template-big-hero.php and
 * template-small-hero.php.
 *
 * @see templates/template-big-hero.php
 * @see templates/template-small-hero.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

/**
 * @param  array<string, mixed>  $meta  Post meta by key.
 * @param  array<string, mixed>  $fields  ACF fields by name.
 * @param  array<string, mixed>  $values  Overrides for any stubbed function.
 */
function heroEnv(array $meta = [], array $fields = [], int $thumbnail = 0, int $posts = 1, array $values = []): void
{
    chromeMarkers();
    contentTags();
    stubValues(array_merge([
        'get_the_ID' => 7,
        'get_post_meta' => static fn (int $id, string $key, bool $single): mixed => $meta[$key] ?? '',
        'get_field' => static fn (string $name): mixed => $fields[$name] ?? false,
        'get_post_thumbnail_id' => $thumbnail,
        'wp_get_attachment_url' => static fn (int $id): string => "/files/{$id}.jpg",
        'get_template_directory_uri' => 'https://t.test/theme',
        'have_posts' => loopOf($posts),
        'comments_open' => false,
        'get_comments_number' => 0,
        'get_the_title' => 'About <us>',
    ], $values));
    stubTags(['the_post' => '[post]', 'comments_template' => '[comments]', 'uams_sidebar_menu' => '[sidebar-menu]']);
}

dataset('hero templates', [
    'big hero' => ['templates/template-big-hero.php', 'hero-height '],
    'small hero' => ['templates/template-small-hero.php', 'hero-height2 '],
]);

describe('the big and small hero templates', function (): void {
    it('prints the featured image as the banner background', function (string $template, string $class): void {
        heroEnv(thumbnail: 99);

        expect(renderTemplate($template))->toContain(
            '<div class="uams-hero-image '.$class.'A{}" style="background-image: url(\'U{/files/99.jpg}\');">'
        );
    })->with('hero templates');

    it('falls back to the grey pattern when the post has no featured image', function (string $template, string $class): void {
        heroEnv();

        expect(renderTemplate($template))->toContain("url('U{https://t.test/theme/assets/headers/uams-pattern-grey.png}')");
    })->with('hero templates');

    it('falls back to the grey pattern when the featured image has no URL', function (string $template, string $class): void {
        heroEnv(thumbnail: 99, values: ['wp_get_attachment_url' => '']);

        expect(renderTemplate($template))->toContain('U{https://t.test/theme/assets/headers/uams-pattern-grey.png}');
    })->with('hero templates');

    it('looks the attachment up only when there is a featured image', function (string $template): void {
        $calls = 0;
        heroEnv(values: ['wp_get_attachment_url' => static function () use (&$calls): string {
            $calls++;

            return '';
        }]);

        renderTemplate($template);

        expect($calls)->toBe(0);
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('prints a mobile image from a field array with a URL', function (string $template, string $class): void {
        heroEnv(fields: ['home_image_mobile' => ['url' => '/m.jpg']]);
        $html = renderTemplate($template);

        expect($html)->toContain('<div class="uams-hero-image '.$class.'A{hero-mobile-image}"')
            ->and($html)->toContain('<div class="mobile-image" style="background-image: url(\'U{/m.jpg}\');"></div>');
    })->with('hero templates');

    it('prints a mobile image from a field that is a plain string', function (string $template): void {
        heroEnv(fields: ['home_image_mobile' => '/m.jpg']);

        expect(renderTemplate($template))->toContain('url(\'U{/m.jpg}\');"></div>');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('prints no mobile image for an array without a URL, an empty value or another type', function (string $template, mixed $field): void {
        heroEnv(fields: ['home_image_mobile' => $field]);
        $html = renderTemplate($template);

        expect($html)->not->toContain('mobile-image')->and($html)->toContain('A{}"');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php'])->with([
        'array without a URL' => [['url' => '']],
        'empty array' => [[]],
        'false' => [false],
        'integer' => [5],
    ]);

    it('prints the title with dark text when the post asks for it', function (string $template, string $title): void {
        heroEnv(meta: ['home_image_dark_text' => ['1']]);

        expect(renderTemplate($template))->toContain('<h1 class="'.$title.'A{ hero-text-dark}">[title]</h1>');
    })->with([
        'big hero' => ['templates/template-big-hero.php', 'uams-site-title'],
        'small hero' => ['templates/template-small-hero.php', 'uams-site-title2'],
    ]);

    it('prints the title with plain text unless the post asks for dark text', function (string $template, mixed $darkText): void {
        heroEnv(meta: ['home_image_dark_text' => $darkText]);

        expect(renderTemplate($template))->toContain('A{}">[title]</h1>')->not->toContain('hero-text-dark');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php'])->with([
        'unset' => [''],
        'a different value' => [['2']],
        'an integer one' => [[1]],
    ]);

    it('prints the button with its text and internal link', function (string $template): void {
        heroEnv(fields: [
            'home_image_add_button' => true,
            'home_image_button_text' => 'Apply <now>',
            'home_image_internal_url' => '/apply/',
            'home_image_external_url' => 'https://x.test/',
        ]);

        expect(renderTemplate($template))->toContain('<a class="uams-btn btn-sm btn-none" href="U{/apply/}">H{Apply <now>}</a>');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('links the button to the external URL when it is external', function (string $template): void {
        heroEnv(fields: [
            'home_image_add_button' => true,
            'home_image_button_text' => 'Go',
            'home_image_external' => true,
            'home_image_internal_url' => '/apply/',
            'home_image_external_url' => 'https://x.test/',
        ]);

        expect(renderTemplate($template))->toContain('href="U{https://x.test/}">H{Go}</a>');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('prints no button without text or without the button turned on', function (string $template, bool $button, string $text): void {
        heroEnv(fields: ['home_image_add_button' => $button, 'home_image_button_text' => $text]);

        expect(renderTemplate($template))->not->toContain('uams-btn');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php'])->with([
        'text, button off' => [false, 'Go'],
        'button on, no text' => [true, ''],
    ]);

    it('reads no fields when the field plugin is missing', function (string $template): void {
        heroEnv();
        stubFunctionExists(['get_field' => false]);
        stubValues(['get_field' => static fn (): never => throw new LogicException('get_field called')]);

        expect(renderTemplate($template))->not->toContain('mobile-image')->not->toContain('uams-btn');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('prints the breadcrumbs, mobile sidebar, each post and the sidebar', function (string $template): void {
        heroEnv(posts: 2);

        expect(renderTemplate($template))->toEndWith(
            '<div class="container uams-body"> <div class="row"> <div class="hero-content col-md-8 uams-content" role="main"> [part:breadcrumbs]'
            .' <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">'
            .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
            .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
            .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div>'
            .' <div id="main_content" class="uams-body-copy" tabindex="-1"> [post][content][post][content] </div> </div>'
            .' <div id="sidebar"> [sidebar] </div> </div> </div> [footer]'
        );
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('drops the sidebar and widens the column when the post turns the sidebar off', function (string $template): void {
        heroEnv(['sidebar' => 'on']);
        $html = renderTemplate($template);

        expect($html)->toContain('hero-content col-md-12 uams-content')->and($html)->not->toContain('[sidebar]');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('hides the breadcrumbs only when the post turns them on', function (string $template, string $value, bool $shown): void {
        heroEnv(['breadcrumb' => $value]);

        expect(str_contains(renderTemplate($template), '[part:breadcrumbs]'))->toBe($shown);
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php'])->with([
        'unset' => ['', true],
        'on' => ['on', false],
        'any other value' => ['off', true],
    ]);

    it('loads the comments template when comments are open or the post has comments', function (string $template, bool $open, int $count, bool $loaded): void {
        heroEnv(values: ['comments_open' => $open, 'get_comments_number' => $count]);

        expect(str_contains(renderTemplate($template), '[comments]'))->toBe($loaded);
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php'])->with([
        'open, none yet' => [true, 0, true],
        'closed, with comments' => [false, 3, true],
        'closed, none' => [false, 0, false],
    ]);

    it('asks for the post meta of the current post', function (string $template): void {
        $calls = [];
        heroEnv(values: ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
            $calls[] = [$id, $key, $single];

            return '';
        }]);

        renderTemplate($template);

        expect($calls)->toBe([[7, 'sidebar', true], [7, 'breadcrumb', true], [7, 'home_image_dark_text', true]]);
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('cuts a long title in the mobile sidebar toggle', function (string $template): void {
        heroEnv(values: ['get_the_title' => 'Department of Orthopaedic Surgery and Rehabilitation']);

        expect(renderTemplate($template))->toContain('page_item"> H{Department of Orthopaedic...} </div>');
    })->with(['templates/template-big-hero.php', 'templates/template-small-hero.php']);

    it('prints the big hero container and slant, and the small hero its own', function (): void {
        heroEnv();

        expect(renderTemplate('templates/template-big-hero.php'))->toContain('<div id="hero-bg"> <div id="hero-container" class="container">')
            ->and(renderTemplate('templates/template-big-hero.php'))->toContain('<span class="udub-slant"><span></span></span>');

        heroEnv(meta: ['home_image_dark_text' => ['1']]);

        expect(renderTemplate('templates/template-small-hero.php'))->toContain('<span class="udub-slant'.'A{ hero-text-dark}"><span></span></span>')
            ->and(renderTemplate('templates/template-small-hero.php'))->not->toContain('hero-bg');
    });
});
