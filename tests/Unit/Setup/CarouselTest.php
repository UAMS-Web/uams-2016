<?php

/**
 * UAMS_Carousel, the gallery carousel assets and the data attributes it puts on galleries and images,
 * with WordPress mocked.
 *
 * @see setup/class.uams-carousel.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectAdded;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-carousel.php');
    stubEscaping();
    when('apply_filters')->alias(static fn (string $hook, mixed $value = null): mixed => $value);
});

it('initializes on init', function (): void {
    $carousel = new UAMS_Carousel;

    expect(has_action('init', [$carousel, 'init']))->toBe(10)
        ->and($carousel->first_run)->toBeTrue()
        ->and($carousel->prebuilt_widths)->toBe([370, 700, 1000, 1200, 1400, 2000])
        ->and(UAMS_Carousel::COLUMNS)->toBe(5)
        ->and(UAMS_Carousel::LINK)->toBe('none');
});

describe('init', function (): void {
    it('does nothing when the carousel is disabled', function (): void {
        when('apply_filters')->alias(static fn (string $hook, mixed $value = null): mixed => $hook === 'uams_carousel_disable' ? true : throw new RuntimeException($hook));
        $carousel = new UAMS_Carousel;

        $carousel->init();

        expect(has_action('wp_enqueue_scripts', [$carousel, 'enqueue_assets']))->toBeFalse()
            ->and(has_filter('gallery_style', [$carousel, 'add_data_to_container']))->toBeFalse();
    });

    it('filters the widths and hooks the assets, the container and the images', function (): void {
        $seen = [];
        when('apply_filters')->alias(static function (string $hook, mixed $value = null) use (&$seen): mixed {
            $seen[] = $hook;

            return $hook === 'jp_carousel_widths' ? [100, 200] : $value;
        });
        $carousel = new UAMS_Carousel;

        $carousel->init();

        expect($seen)->toBe(['uams_carousel_disable', 'jp_carousel_widths'])
            ->and($carousel->prebuilt_widths)->toBe([100, 200])
            ->and(has_action('wp_enqueue_scripts', [$carousel, 'enqueue_assets']))->toBe(10)
            ->and(has_filter('gallery_style', [$carousel, 'add_data_to_container']))->toBe(10)
            ->and(has_filter('wp_get_attachment_image_attributes', [$carousel, 'add_data_to_images']))->toBe(10);
    });

    it('passes both attributes to the image filter', function (): void {
        $carousel = new UAMS_Carousel;
        expectAdded('wp_get_attachment_image_attributes')->once()->with([$carousel, 'add_data_to_images'], 10, 2);

        $carousel->init();
    });
});

it('reports whether it is disabled through the filter', function (): void {
    $asked = [];
    when('apply_filters')->alias(static function (string $hook, mixed $value = null) use (&$asked): mixed {
        $asked[] = [$hook, $value];

        return $value;
    });

    expect((new UAMS_Carousel)->disable())->toBeFalse()
        ->and($asked)->toBe([['uams_carousel_disable', false]]);
});

it('passes the asset version through the filter', function (): void {
    when('apply_filters')->alias(static fn (string $hook, mixed $value = null): mixed => $hook === 'jp_carousel_asset_version' ? $value.'-x' : $value);

    expect((new UAMS_Carousel)->asset_version('1.2'))->toBe('1.2-x');
});

/**
 * Stand in for what enqueue_assets() calls. Every do_action() call is recorded as [hook, ...args],
 * every apply_filters() hook name in order, and the wp_localize_script() arguments, in
 * `$GLOBALS['uams_test_carousel']`.
 *
 * @param  array<string, mixed>  $filtered  Hook name => value the filter returns instead of its input.
 */
function stubCarouselAssets(array $filtered = []): void
{
    $GLOBALS['uams_test_carousel'] = ['localized' => [], 'actions' => [], 'hooks' => []];

    when('do_action')->alias(static function (string $hook, mixed ...$args): void {
        $GLOBALS['uams_test_carousel']['actions'][] = [$hook, ...$args];
    });
    when('apply_filters')->alias(static function (string $hook, mixed $value = null) use ($filtered): mixed {
        $GLOBALS['uams_test_carousel']['hooks'][] = $hook;

        return array_key_exists($hook, $filtered) ? $filtered[$hook] : $value;
    });
    when('is_user_logged_in')->justReturn(true);
    when('get_option')->alias(static fn (string $name): string => $name === 'require_name_email' ? '1' : 'wrong');
    when('get_locale')->justReturn('FR_ca');
    when('admin_url')->alias(static fn (string $path): string => 'https://site.test/wp-admin/'.$path);
    when('set_url_scheme')->alias(static fn (string $url): string => 'scheme:'.$url);
    when('wp_create_nonce')->alias(static fn (string $action): string => 'nonce-'.$action);
    when('__')->alias(static fn (string $text, string $domain = ''): string => '__('.$text.'|'.$domain.')');
    when('get_permalink')->justReturn('https://site.test/post');
    when('wp_login_url')->alias(static fn (string $redirect): string => 'login?r='.$redirect);
    when('wp_localize_script')->alias(static function (string $handle, string $name, array $strings): void {
        $GLOBALS['uams_test_carousel']['localized'][] = [$handle, $name, $strings];
    });
}

describe('enqueue_assets', function (): void {
    it('localizes the carousel strings and runs the enqueue actions once', function (): void {
        stubCarouselAssets();
        $carousel = new UAMS_Carousel;

        $result = $carousel->enqueue_assets();
        $record = $GLOBALS['uams_test_carousel'];

        $strings = [
            'widths' => [370, 700, 1000, 1200, 1400, 2000],
            'is_logged_in' => true,
            'lang' => 'fr',
            'ajaxurl' => 'scheme:https://site.test/wp-admin/admin-ajax.php',
            'nonce' => 'nonce-carousel_nonce',
            'display_exif' => true,
            'display_geo' => false,
            'background_color' => 'white',
            'download_original' => '__(View full size <span class="photo-size">{0}<span class="photo-size-times">&times;</span>{1}</span>|jetpack)',
            'camera' => '__(Camera|jetpack)',
            'aperture' => '__(Aperture|jetpack)',
            'shutter_speed' => '__(Shutter Speed|jetpack)',
            'focal_length' => '__(Focal Length|jetpack)',
            'require_name_email' => 1,
            'login_url' => 'login?r=https://site.test/post',
        ];

        expect($result)->toBe('')
            ->and($record['localized'])->toBe([['site', 'jetpackCarouselStrings', $strings]])
            ->and($record['actions'])->toBe([['jp_carousel_thumbnails_shown'], ['jp_carousel_enqueue_assets', true, $strings]])
            ->and($record['hooks'])->toBe(['the_permalink', 'jp_carousel_localize_strings'])
            ->and($carousel->first_run)->toBeFalse();
    });

    it('does not force the output check when no output was given', function (): void {
        stubCarouselAssets();

        (new UAMS_Carousel)->enqueue_assets();

        expect($GLOBALS['uams_test_carousel']['hooks'])->not->toContain('jp_carousel_force_enable');
    });

    it('uses the filtered strings', function (): void {
        stubCarouselAssets(['jp_carousel_localize_strings' => ['only' => 'this']]);

        (new UAMS_Carousel)->enqueue_assets('');

        expect($GLOBALS['uams_test_carousel']['localized'][0][2])->toBe(['only' => 'this'])
            ->and($GLOBALS['uams_test_carousel']['actions'][1])->toBe(['jp_carousel_enqueue_assets', true, ['only' => 'this']]);
    });

    it('returns given output and unhooks the data filters when the carousel is not forced', function (): void {
        stubCarouselAssets();
        $carousel = new UAMS_Carousel;
        add_filter('gallery_style', [$carousel, 'add_data_to_container']);
        add_filter('wp_get_attachment_image_attributes', [$carousel, 'add_data_to_images']);

        $result = $carousel->enqueue_assets('<gallery>');

        expect($result)->toBe('<gallery>')
            ->and(has_filter('gallery_style', [$carousel, 'add_data_to_container']))->toBeFalse()
            ->and(has_filter('wp_get_attachment_image_attributes', [$carousel, 'add_data_to_images']))->toBeFalse()
            ->and($GLOBALS['uams_test_carousel']['actions'])->toBe([])
            ->and($GLOBALS['uams_test_carousel']['localized'])->toBe([])
            ->and($carousel->first_run)->toBeTrue();
    });

    it('carries on with output when the carousel is forced', function (): void {
        stubCarouselAssets(['jp_carousel_force_enable' => true]);
        $carousel = new UAMS_Carousel;

        $result = $carousel->enqueue_assets('<gallery>');

        expect($result)->toBe('<gallery>')
            ->and($GLOBALS['uams_test_carousel']['localized'])->toHaveCount(1)
            ->and($carousel->first_run)->toBeFalse();
    });

    it('leaves the language empty when there is no locale', function (): void {
        stubCarouselAssets();
        when('get_locale')->justReturn(false);

        (new UAMS_Carousel)->enqueue_assets();

        expect($GLOBALS['uams_test_carousel']['localized'][0][2]['lang'])->toBe('');
    });

    it('tells the actions it was the first run only the first time', function (): void {
        stubCarouselAssets();
        $carousel = new UAMS_Carousel;

        $carousel->enqueue_assets();
        $carousel->enqueue_assets();

        expect($GLOBALS['uams_test_carousel']['actions'][1][1])->toBeTrue()
            ->and($GLOBALS['uams_test_carousel']['actions'][3][1])->toBeFalse();
    });
});

describe('add_data_to_images', function (): void {
    beforeEach(function (): void {
        when('wp_get_attachment_image_src')->alias(static fn (int $id, string $size): array|false => match ($size) {
            'original' => ['https://site.test/orig.jpg', 10, 10],
            'medium' => ['https://site.test/medium.jpg'],
            'large' => ['https://site.test/large.jpg'],
            default => false,
        });
        when('wp_get_attachment_url')->justReturn('https://site.test/fallback.jpg');
        when('wp_get_attachment_metadata')->justReturn([
            'width' => '800',
            'height' => '600',
            'image_meta' => ['aperture' => '2.8', 'latitude' => '1', 'longitude' => '2', 'iso' => 100],
        ]);
        when('get_post')->justReturn((object) ['post_title' => 'Title', 'post_content' => 'Body']);
        when('wptexturize')->alias(static fn (string $text): string => 'W{'.$text.'}');
        when('wpautop')->alias(static fn (string $text): string => 'P{'.$text.'}');
    });

    /**
     * An image attachment as the filter receives it.
     */
    function carouselAttachment(int $id = 42): object
    {
        return (object) ['ID' => (string) $id];
    }

    it('returns the attributes untouched on the first run or without an attachment', function (bool $first, ?object $attachment): void {
        $carousel = new UAMS_Carousel;
        $carousel->first_run = $first;

        // A plain object stands in for the attachment post.
        // @phpstan-ignore argument.type
        expect($carousel->add_data_to_images(['class' => 'x'], $attachment))->toBe(['class' => 'x']);
    })->with([
        'first run' => [true, new stdClass],
        'no attachment' => [false, null],
    ]);

    it('adds the data the carousel reads', function (): void {
        $carousel = new UAMS_Carousel;
        $carousel->first_run = false;

        // A plain object stands in for the attachment post.
        // @phpstan-ignore argument.type
        expect($carousel->add_data_to_images(['class' => 'x'], carouselAttachment()))->toBe([
            'class' => 'x',
            'data-attachment-id' => 42,
            'data-orig-file' => 'A{https://site.test/orig.jpg}',
            'data-orig-size' => '800,600',
            'data-comments-opened' => '0',
            'data-image-meta' => 'A{{"aperture":"2.8","iso":"100"}}',
            'data-image-title' => 'A{W{Title}}',
            'data-image-description' => 'A{P{W{Body}}}',
            'data-medium-file' => 'A{https://site.test/medium.jpg}',
            'data-large-file' => 'A{https://site.test/large.jpg}',
        ]);
    });

    it('falls back when the sizes, metadata and post are missing', function (): void {
        when('wp_get_attachment_image_src')->justReturn(false);
        when('wp_get_attachment_metadata')->justReturn(['width' => '800']);
        when('get_post')->justReturn();

        $carousel = new UAMS_Carousel;
        $carousel->first_run = false;

        // A plain object stands in for the attachment post.
        // @phpstan-ignore argument.type
        $attr = $carousel->add_data_to_images([], carouselAttachment());

        expect($attr)->toBe([
            'data-attachment-id' => 42,
            'data-orig-file' => 'A{https://site.test/fallback.jpg}',
            'data-orig-size' => '',
            'data-comments-opened' => '0',
            'data-image-meta' => 'A{[]}',
            'data-image-title' => 'A{}',
            'data-image-description' => 'A{}',
            'data-medium-file' => 'A{}',
            'data-large-file' => 'A{}',
        ]);
    });

    it('leaves the image meta empty when only the location is present', function (): void {
        when('wp_get_attachment_metadata')->justReturn(['height' => '600', 'image_meta' => ['latitude' => '1', 'longitude' => '2']]);
        $carousel = new UAMS_Carousel;
        $carousel->first_run = false;

        // A plain object stands in for the attachment post.
        // @phpstan-ignore argument.type
        $attr = $carousel->add_data_to_images([], carouselAttachment());

        expect($attr['data-image-meta'])->toBe('A{[]}')
            ->and($attr['data-orig-size'])->toBe('');
    });
});

describe('add_data_to_container', function (): void {
    afterEach(function (): void {
        unset($GLOBALS['post']);
    });

    beforeEach(function (): void {
        when('get_current_blog_id')->justReturn('3');
        when('get_permalink')->alias(static fn (int $id): string => 'https://site.test/p/'.$id);
    });

    it('returns the gallery markup untouched without a post', function (): void {
        unset($GLOBALS['post']);

        expect((new UAMS_Carousel)->add_data_to_container("<div class='gallery'>"))->toBe("<div class='gallery'>");
    });

    it('returns the gallery markup untouched when the post is not a post object', function (): void {
        $GLOBALS['post'] = (object) ['ID' => 7];

        expect((new UAMS_Carousel)->add_data_to_container('<div id="g">'))->toBe('<div id="g">');
    });

    it('adds the carousel data to every div', function (): void {
        $GLOBALS['post'] = wpPost(['ID' => 7]);

        $html = (new UAMS_Carousel)->add_data_to_container('<div id="a"><div id="b">');

        expect($html)->toBe('<div A{data-carousel-extra}=\'A{{"blog_id":3,"permalink":"https:\/\/site.test\/p\/7"}}\' id="a"><div A{data-carousel-extra}=\'A{{"blog_id":3,"permalink":"https:\/\/site.test\/p\/7"}}\' id="b">');
    });

    it('adds the data a filter supplies, as an array when it is not one', function (): void {
        $GLOBALS['post'] = wpPost(['ID' => 7]);
        when('apply_filters')->alias(static fn (string $hook, mixed $value): mixed => $hook === 'jp_carousel_add_data_to_container' ? 'value' : $value);

        $html = (new UAMS_Carousel)->add_data_to_container('<div id="a">');

        expect($html)->toBe('<div A{0}=\'A{"value"}\' id="a">');
    });
});
