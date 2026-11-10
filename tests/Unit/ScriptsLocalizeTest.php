<?php

/**
 * How the theme hands `style_dir` to js/site.js: as inline script that defines the global, not through
 * wp_localize_script(), which requires an array and reports a string as incorrect usage.
 *
 * @see setup/class.uams-scripts.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

/**
 * Record every wp_add_inline_script() and wp_localize_script() call.
 *
 * @return array{inline: ArrayObject<int, array{string, string, string}>, localized: ArrayObject<int, string>}
 */
function recordScriptCalls(): array
{
    $inline = new ArrayObject;
    $localized = new ArrayObject;

    when('wp_add_inline_script')->alias(static function (string $handle, string $data, string $position) use ($inline): bool {
        $inline[] = [$handle, $data, $position];

        return true;
    });
    when('wp_localize_script')->alias(static function (string $handle) use ($localized): bool {
        $localized[] = $handle;

        return true;
    });

    return ['inline' => $inline, 'localized' => $localized];
}

beforeEach(function (): void {
    when('add_action')->justReturn(true);
    when('get_template_directory_uri')->justReturn('https://example.test/wp-content/themes/uams-2016');
    when('is_user_logged_in')->justReturn(false);
    when('site_url')->justReturn('https://example.test');
    when('wp_json_encode')->alias(static fn (mixed $value): string|false => json_encode($value));

    require_once dirname(__DIR__, 2).'/setup/class.uams-scripts.php';
});

it('defines the style_dir global before the script that reads it', function (): void {
    $calls = recordScriptCalls();

    (new UAMS_Scripts)->uams_localize_default_scripts();

    expect($calls['inline']->getArrayCopy())->toBe([['site', 'var style_dir = "https:\/\/example.test";', 'before']])
        ->and($calls['localized']->getArrayCopy())->toBe([]);
});

it('adds inline script only for entries that carry a style_dir', function (): void {
    $calls = recordScriptCalls();
    $scripts = new UAMS_Scripts;
    $scripts->SCRIPTS = [
        'a' => ['id' => 'a', 'style_dir' => ''],
        'b' => ['id' => 'b'],
        'c' => ['id' => 'c', 'style_dir' => 'x'],
    ];

    $scripts->uams_localize_default_scripts();

    expect($calls['inline']->getArrayCopy())->toBe([['c', 'var style_dir = "x";', 'before']]);
});
