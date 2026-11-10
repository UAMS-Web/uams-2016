<?php

/**
 * UAMS_Images, the theme's image sizes and the ones offered in the editor, with WordPress mocked.
 *
 * @see setup/class.images.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.images.php');
});

it('hooks the sizes and the editor choices', function (): void {
    $images = new UAMS_Images;

    expect(has_action('after_setup_theme', [$images, 'add_uams_image_sizes']))->toBe(10)
        ->and(has_filter('image_size_names_choose', [$images, 'show_image_sizes']))->toBe(10);
});

it('defines the sizes the theme ships', function (): void {
    expect((new UAMS_Images)->IMAGE_SIZES)->toBe([
        'mug-shot' => ['name' => 'Mug Shot', 'width' => 150, 'height' => 250, 'crop' => true, 'show' => true],
        'sidebar' => ['name' => 'Sidebar', 'width' => 375, 'height' => 9999, 'crop' => false, 'show' => true],
        'half' => ['name' => 'Half width', 'width' => 375, 'height' => 9999, 'crop' => false, 'show' => true],
        'full-content' => ['name' => 'Content area', 'width' => 750, 'height' => 9999, 'crop' => false, 'show' => true],
        'page' => ['name' => 'Full page', 'width' => 1140, 'height' => 9999, 'crop' => false, 'show' => true],
        'thimble' => ['name' => 'Thimble', 'width' => 50, 'height' => 50, 'crop' => true, 'show' => false],
        'thumbnail-large' => ['name' => 'Thumbnail large', 'width' => 300, 'height' => 300, 'crop' => true, 'show' => false],
        'rss' => ['name' => 'RSS', 'width' => 108, 'height' => 81, 'crop' => true, 'show' => false],
    ]);
});

it('registers every size with WordPress', function (): void {
    $added = [];
    when('add_image_size')->alias(static function (string $name, int $width, int $height, bool $crop) use (&$added): void {
        $added[] = [$name, $width, $height, $crop];
    });

    (new UAMS_Images)->add_uams_image_sizes();

    expect($added)->toBe([
        ['mug-shot', 150, 250, true],
        ['sidebar', 375, 9999, false],
        ['half', 375, 9999, false],
        ['full-content', 750, 9999, false],
        ['page', 1140, 9999, false],
        ['thimble', 50, 50, true],
        ['thumbnail-large', 300, 300, true],
        ['rss', 108, 81, true],
    ]);
});

it('offers the sizes marked to show, ahead of the defaults', function (mixed $defaults, array $expected): void {
    expect((new UAMS_Images)->show_image_sizes($defaults))->toBe($expected);
})->with([
    'the default sizes' => [
        ['thumbnail' => 'Thumbnail', 'full' => 'Full Size'],
        [
            'mug-shot' => 'Mug Shot',
            'sidebar' => 'Sidebar',
            'half' => 'Half width',
            'full-content' => 'Content area',
            'page' => 'Full page',
            'thumbnail' => 'Thumbnail',
            'full' => 'Full Size',
        ],
    ],
    'a default overrides a theme size of the same key' => [
        ['sidebar' => 'Narrow'],
        [
            'mug-shot' => 'Mug Shot',
            'sidebar' => 'Narrow',
            'half' => 'Half width',
            'full-content' => 'Content area',
            'page' => 'Full page',
        ],
    ],
    'defaults that are not an array' => [
        'x',
        [
            'mug-shot' => 'Mug Shot',
            'sidebar' => 'Sidebar',
            'half' => 'Half width',
            'full-content' => 'Content area',
            'page' => 'Full page',
        ],
    ],
]);
