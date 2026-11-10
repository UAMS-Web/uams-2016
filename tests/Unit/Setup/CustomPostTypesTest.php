<?php

/**
 * setup/custom-post-types.php, which loads the vendored Cuztom library when it is present, with WordPress
 * mocked.
 *
 * @see setup/custom-post-types.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

it('loads the Cuztom library when it is present', function (): void {
    $theme = dirname(__DIR__, 3);
    when('plugin_dir_path')->alias(static fn (string $file): string => dirname($file).'/');
    when('wp_normalize_path')->alias(static fn (string $path): string => str_replace(DIRECTORY_SEPARATOR, '/', $path));
    when('get_template_directory')->justReturn(str_replace(DIRECTORY_SEPARATOR, '/', $theme));
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('esc_url_raw')->returnArg();
    when('trailingslashit')->alias(static fn (string $value): string => rtrim($value, '/'.DIRECTORY_SEPARATOR).'/');

    require_once $theme.'/setup/custom-post-types.php';

    expect(class_exists('Cuztom_Initializer', false))->toBeTrue()
        ->and(CUZTOM_URL)->toBe('https://site.test/theme/setup/cuztom/')
        ->and(has_action('admin_init'))->toBeTrue();
});
