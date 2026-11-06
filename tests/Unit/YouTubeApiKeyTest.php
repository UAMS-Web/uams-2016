<?php

/**
 * The YouTube Data API key: read from the UAMS_YOUTUBE_API_KEY constant and passed to the
 * `site` script, with WordPress mocked.
 *
 * A constant cannot be undefined once it is defined, so the tests that need it undefined come
 * first and the one that defines it comes last. Pest runs a file's tests in the order written.
 *
 * @see setup/class.youtube-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Actions\expectAdded;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    // class.youtube-shortcode.php registers its shortcode when it is loaded.
    when('add_shortcode')->justReturn(true);

    require_once __DIR__.'/../../setup/class.youtube-shortcode.php';
});

it('has no key while the constant is undefined', function (): void {
    expect(defined('UAMS_YOUTUBE_API_KEY'))->toBeFalse()
        ->and(UAMS_YouTube::api_key())->toBe('');
});

it('passes nothing to the script while the constant is undefined', function (): void {
    Functions\expect('wp_localize_script')->never();

    (new UAMS_YouTube)->localize_api_key();
});

it('localizes after the site script is registered', function (): void {
    expectAdded('wp_enqueue_scripts')
        ->once()
        ->with(Mockery::type('array'), 20);

    new UAMS_YouTube;
});

it('normalizes the constant to a trimmed string', function (mixed $value, string $expected): void {
    expect(UAMS_YouTube::normalize_key($value))->toBe($expected);
})->with([
    'empty string' => ['', ''],
    'only whitespace' => ["  \t\n", ''],
    'null' => [null, ''],
    'false' => [false, ''],
    'an integer' => [12345, ''],
    'an array' => [['key'], ''],
    'a key' => ['test-key-123', 'test-key-123'],
    'a key with whitespace around it' => ["  test-key-123\n", 'test-key-123'],
]);

it('passes the key to the site script as uams_youtube.apiKey once the constant is defined', function (): void {
    define('UAMS_YOUTUBE_API_KEY', 'test-key-123');

    Functions\expect('wp_localize_script')
        ->once()
        ->with('site', 'uams_youtube', ['apiKey' => 'test-key-123']);

    expect(UAMS_YouTube::api_key())->toBe('test-key-123');

    (new UAMS_YouTube)->localize_api_key();
});
