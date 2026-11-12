<?php

/**
 * UAMS_Scripts, the theme scripts and where they are registered and enqueued, with WordPress mocked. The
 * hand-off of `style_dir` is covered by ScriptsLocalizeTest.
 *
 * @see setup/class.uams-scripts.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-scripts.php');
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('is_user_logged_in')->justReturn(false);
    when('site_url')->justReturn('https://site.test');
});

/**
 * Record the script registrations, deregistrations and enqueues the class makes.
 *
 * @return ArrayObject<int, array<int|string, mixed>>
 */
function recordScripts(): ArrayObject
{
    /** @var ArrayObject<int, array<int|string, mixed>> $calls */
    $calls = new ArrayObject;
    when('wp_deregister_script')->alias(static function (string $handle) use ($calls): void {
        $calls[] = ['deregister', $handle];
    });
    when('wp_register_script')->alias(static function (mixed ...$args) use ($calls): void {
        $calls[] = ['register', ...$args];
    });
    when('wp_enqueue_script')->alias(static function (mixed ...$args) use ($calls): void {
        $calls[] = ['enqueue', ...$args];
    });

    return $calls;
}

it('describes the scripts the theme registers', function (): void {
    expect((new UAMS_Scripts)->SCRIPTS)->toBe([
        'jquery' => [
            'id' => 'jquery',
            'url' => 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js',
            'deps' => [],
            'version' => '3.5.1',
            'admin' => false,
            'footer' => false,
        ],
        'migrate' => [
            'id' => 'migrate',
            'url' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.2/jquery-migrate.min.js',
            'deps' => ['jquery'],
            'version' => '3.3.2',
            'admin' => false,
            'footer' => false,
        ],
        'site' => [
            'id' => 'site',
            'url' => 'https://site.test/theme/js/site.js',
            'deps' => ['backbone'],
            'version' => '1.0.3',
            'admin' => false,
            'style_dir' => 'https://site.test',
            'footer' => false,
        ],
        'alert' => [
            'id' => 'alert',
            'url' => '//www.uams.edu/web/alert/uamsalert.js',
            'deps' => ['jquery'],
            'version' => '1.0.1',
            'admin' => false,
            'footer' => true,
        ],
        'admin' => [
            'id' => 'wp.admin',
            'url' => 'https://site.test/theme/assets/admin/js/admin.js',
            'deps' => ['jquery'],
            'version' => '1.0',
            'admin' => true,
            'footer' => false,
        ],
    ]);
});

it('uses the development script for a signed-in user', function (): void {
    when('is_user_logged_in')->justReturn(true);

    $scripts = new UAMS_Scripts;

    expect($scripts->SCRIPTS['site']['url'])->toBe('https://site.test/theme/js/site.dev.js')
        ->and($scripts->dev_script())->toBe('.dev');
});

it('uses the minified script for a visitor', function (): void {
    expect((new UAMS_Scripts)->dev_script())->toBe('');
});

it('hooks the front end, the admin and the customizer', function (): void {
    $scripts = new UAMS_Scripts;

    expect(has_action('wp_enqueue_scripts', [$scripts, 'uams_register_default_scripts']))->toBe(10)
        ->and(has_action('wp_enqueue_scripts', [$scripts, 'uams_localize_default_scripts']))->toBe(10)
        ->and(has_action('wp_enqueue_scripts', [$scripts, 'uams_enqueue_default_scripts']))->toBe(10)
        ->and(has_action('admin_enqueue_scripts', [$scripts, 'uams_enqueue_admin_scripts']))->toBe(10)
        ->and(has_action('customize_controls_init', [$scripts, 'uams_customizer_preview']))->toBe(10);
});

it('enqueues the customizer script in the footer', function (): void {
    $calls = recordScripts();

    (new UAMS_Scripts)->uams_customizer_preview();

    expect($calls->getArrayCopy())->toBe([
        ['enqueue', 'uams-themecustomize', 'https://site.test/theme/js/uams.themecustomizer.js', ['jquery', 'customize-controls'], false, true],
    ]);
});

it('keeps only the scripts already set when collecting the child theme scripts', function (mixed $scripts, array $expected): void {
    $instance = new UAMS_Scripts;
    $instance->SCRIPTS = $scripts;

    expect((new ReflectionMethod($instance, 'get_child_theme_scripts'))->invoke($instance))->toBe($expected);
})->with([
    'a list' => [['a' => ['id' => 'a']], ['a' => ['id' => 'a']]],
    'not a list' => ['x', []],
    'null' => [null, []],
]);

describe('uams_register_default_scripts', function (): void {
    it('replaces core jquery and registers every script with its defaults', function (): void {
        $calls = recordScripts();
        $scripts = new UAMS_Scripts;
        $scripts->SCRIPTS = [
            'a' => ['id' => 'a', 'url' => 'u-a', 'deps' => ['x'], 'version' => '1', 'footer' => true],
            'b' => ['id' => 'b', 'url' => 'u-b'],
        ];

        $scripts->uams_register_default_scripts();

        expect($calls->getArrayCopy())->toBe([
            ['deregister', 'jquery'],
            ['register', 'a', 'u-a', ['x'], '1', true],
            ['register', 'b', 'u-b', [], false, false],
        ]);
    });
});

describe('uams_enqueue_default_scripts', function (): void {
    it('enqueues every script that is not for the admin', function (): void {
        $calls = recordScripts();
        $scripts = new UAMS_Scripts;
        $scripts->SCRIPTS = [
            'front' => ['id' => 'front', 'admin' => false],
            'back' => ['id' => 'back', 'admin' => true],
            'unmarked' => ['id' => 'unmarked'],
        ];

        $scripts->uams_enqueue_default_scripts();

        expect($calls->getArrayCopy())->toBe([['enqueue', 'front'], ['enqueue', 'unmarked']]);
    });
});

describe('uams_enqueue_admin_scripts', function (): void {
    it('registers and enqueues the scripts marked for the admin', function (): void {
        when('is_admin')->justReturn(true);
        $calls = recordScripts();
        $scripts = new UAMS_Scripts;
        $scripts->SCRIPTS = [
            'front' => ['id' => 'front', 'url' => 'u-f', 'admin' => false],
            'back' => ['id' => 'back', 'url' => 'u-b', 'deps' => ['x'], 'version' => '2', 'footer' => true, 'admin' => true],
            'bare' => ['id' => 'bare', 'url' => 'u-c', 'admin' => true],
        ];

        $scripts->uams_enqueue_admin_scripts();

        expect($calls->getArrayCopy())->toBe([
            ['register', 'back', 'u-b', ['x'], '2', true],
            ['enqueue', 'back'],
            ['register', 'bare', 'u-c', [], false, false],
            ['enqueue', 'bare'],
        ]);
    });

    it('does nothing outside the admin', function (): void {
        when('is_admin')->justReturn(false);
        $calls = recordScripts();
        $scripts = new UAMS_Scripts;
        $scripts->SCRIPTS = ['back' => ['id' => 'back', 'url' => 'u-b', 'admin' => true]];

        $scripts->uams_enqueue_admin_scripts();

        expect($calls->getArrayCopy())->toBe([]);
    });
});
