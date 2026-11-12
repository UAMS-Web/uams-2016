<?php

/**
 * UAMS_Styles, the theme stylesheets and where they are registered and enqueued, with WordPress mocked.
 *
 * @see setup/class.uams-styles.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-styles.php');
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('get_stylesheet_uri')->justReturn('https://site.test/child/style.css');
    when('is_user_logged_in')->justReturn(false);
    when('get_template_directory')->justReturn('/themes/parent');
    when('get_stylesheet_directory')->justReturn('/themes/parent');
});

/**
 * Record the style registrations and enqueues the class makes.
 *
 * @return ArrayObject<int, array<int|string, mixed>>
 */
function recordStyles(): ArrayObject
{
    /** @var ArrayObject<int, array<int|string, mixed>> $calls */
    $calls = new ArrayObject;
    when('wp_register_style')->alias(static function (mixed ...$args) use ($calls): void {
        $calls[] = ['register', ...$args];
    });
    when('wp_enqueue_style')->alias(static function (string $handle) use ($calls): void {
        $calls[] = ['enqueue', $handle];
    });

    return $calls;
}

it('describes the master and the child stylesheet', function (): void {
    expect((new UAMS_Styles)->STYLES)->toBe([
        'uams-master' => [
            'id' => 'uams-master',
            'url' => 'https://site.test/theme/style.css',
            'deps' => [],
            'version' => '3.6',
        ],
        'uams-style' => [
            'id' => 'uams-style',
            'url' => 'https://site.test/child/style.css',
            'deps' => ['uams-master'],
            'version' => '3.6',
            'child' => true,
        ],
    ]);
});

it('uses the development stylesheet for a signed-in user', function (): void {
    when('is_user_logged_in')->justReturn(true);

    $styles = new UAMS_Styles;

    expect($styles->STYLES['uams-master']['url'])->toBe('https://site.test/theme/style.dev.css')
        ->and($styles->dev_stylesheet())->toBe('.dev');
});

it('uses the minified stylesheet for a visitor', function (): void {
    expect((new UAMS_Styles)->dev_stylesheet())->toBe('');
});

it('hooks the front end and the admin', function (): void {
    $styles = new UAMS_Styles;

    expect(has_action('wp_enqueue_scripts', [$styles, 'uams_register_default_styles']))->toBe(10)
        ->and(has_action('wp_enqueue_scripts', [$styles, 'uams_enqueue_default_styles']))->toBe(10)
        ->and(has_action('admin_enqueue_scripts', [$styles, 'uams_enqueue_admin_styles']))->toBe(10);
});

it('registers each style with its dependencies and version', function (): void {
    $calls = recordStyles();
    $styles = new UAMS_Styles;
    $styles->STYLES = [
        'a' => ['id' => 'a', 'url' => 'u-a', 'deps' => ['x'], 'version' => '1'],
        'b' => ['id' => 'b', 'url' => 'u-b'],
    ];

    $styles->uams_register_default_styles();

    expect($calls->getArrayCopy())->toBe([
        ['register', 'a', 'u-a', ['x'], '1'],
        ['register', 'b', 'u-b', [], false],
    ]);
});

describe('enqueuing the default styles', function (): void {
    it('enqueues the master and then every style, skipping the child style of a parent theme', function (): void {
        $calls = recordStyles();

        (new UAMS_Styles)->uams_enqueue_default_styles();

        expect($calls->getArrayCopy())->toBe([
            ['enqueue', 'uams-master'],
            ['enqueue', 'uams-master'],
        ]);
    });

    it('enqueues the child style of a child theme', function (): void {
        when('get_stylesheet_directory')->justReturn('/themes/child');
        $calls = recordStyles();

        (new UAMS_Styles)->uams_enqueue_default_styles();

        expect($calls->getArrayCopy())->toBe([
            ['enqueue', 'uams-master'],
            ['enqueue', 'uams-master'],
            ['enqueue', 'uams-style'],
        ]);
    });

    it('tells a child theme from its parent', function (string $stylesheet, bool $child): void {
        when('get_stylesheet_directory')->justReturn($stylesheet);

        expect((new UAMS_Styles)->is_child_theme())->toBe($child);
    })->with([
        'a child' => ['/themes/child', true],
        'the parent' => ['/themes/parent', false],
    ]);
});

describe('enqueuing the admin styles', function (): void {
    it('registers and enqueues the styles marked for the admin', function (): void {
        when('is_admin')->justReturn(true);
        $calls = recordStyles();
        $styles = new UAMS_Styles;
        $styles->STYLES = [
            'front' => ['id' => 'front', 'url' => 'u-f'],
            'back' => ['id' => 'back', 'url' => 'u-b', 'deps' => ['x'], 'version' => '2', 'admin' => true],
            'bare' => ['id' => 'bare', 'url' => 'u-c', 'admin' => true],
        ];

        $styles->uams_enqueue_admin_styles();

        expect($calls->getArrayCopy())->toBe([
            ['register', 'back', 'u-b', ['x'], '2'],
            ['enqueue', 'back'],
            ['register', 'bare', 'u-c', [], false],
            ['enqueue', 'bare'],
        ]);
    });

    it('does nothing outside the admin', function (): void {
        when('is_admin')->justReturn(false);
        $calls = recordStyles();
        $styles = new UAMS_Styles;
        $styles->STYLES = ['back' => ['id' => 'back', 'url' => 'u-b', 'admin' => true]];

        $styles->uams_enqueue_admin_styles();

        expect($calls->getArrayCopy())->toBe([]);
    });
});
