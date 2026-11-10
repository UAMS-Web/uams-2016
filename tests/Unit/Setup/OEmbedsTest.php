<?php

/**
 * UAMS_OEmbeds, the campus map oEmbed providers, and the content width the file sets, with WordPress
 * mocked.
 *
 * @see setup/class.uams-oembeds.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

it('sets the content width and registers the providers on init when the file loads', function (): void {
    unset($GLOBALS['content_width']);

    (static function (): void {
        global $content_width;

        require_once dirname(__DIR__, 3).'/setup/class.uams-oembeds.php';
    })();

    expect($GLOBALS['content_width'])->toBe(750)
        ->and(has_action('init'))->toBeTrue();
});

it('hooks the campus map providers on init', function (): void {
    $oembeds = new UAMS_OEmbeds;

    expect(has_action('init', [$oembeds, 'campus_map']))->toBe(10);
});

it('adds the four campus map providers', function (): void {
    $added = [];
    when('wp_oembed_add_provider')->alias(static function (string $format, string $provider) use (&$added): void {
        $added[] = [$format, $provider];
    });

    (new UAMS_OEmbeds)->campus_map();

    $endpoint = '//www.washington.edu/maps/api/oembed/place/';
    expect($added)->toBe([
        ['http://uw.edu/maps/*', $endpoint],
        ['http://www.washington.edu/maps/*', $endpoint],
        ['https://uw.edu/maps/*', $endpoint],
        ['https://www.washington.edu/maps/*', $endpoint],
    ]);
});
