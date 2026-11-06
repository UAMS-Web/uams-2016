<?php

/**
 * is_pdf(), uams_is_custom_post_type() and uams_get_sticky_posts(), with
 * WordPress mocked.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

it('treats a PDF attachment as a PDF', function (): void {
    Functions\when('get_post_mime_type')->justReturn('application/pdf');

    expect(is_pdf())->toBeTrue();
});

it('treats any other mime type as not a PDF', function (): void {
    Functions\when('get_post_mime_type')->justReturn('image/png');

    expect(is_pdf())->toBeFalse();
});

it('recognises a custom post type by its registration', function (): void {
    Functions\when('get_post_type')->justReturn('provider');
    Functions\expect('get_post_types')
        ->once()
        ->with(['_builtin' => false])
        ->andReturn(['provider' => 'provider']);

    expect(uams_is_custom_post_type())->toBeTrue();
});

it('does not treat a built-in post type as custom', function (): void {
    Functions\when('get_post_type')->justReturn('page');
    Functions\when('get_post_types')->justReturn(['provider' => 'provider']);

    expect(uams_is_custom_post_type())->toBeFalse();
});

it('has no custom post type outside a post', function (): void {
    Functions\when('get_post_type')->justReturn(false);

    expect(uams_is_custom_post_type())->toBeFalse();
});

it('fetches sticky posts by their ids', function (): void {
    Functions\when('get_option')->justReturn([7, 9]);
    Functions\when('wp_parse_args')->alias(
        static fn (array $args, array $defaults): array => array_merge($defaults, $args)
    );
    Functions\expect('get_posts')
        ->once()
        ->with(['post__in' => [7, 9]])
        ->andReturn(['first', 'second']);

    expect(uams_get_sticky_posts())->toBe(['first', 'second']);
});

it('matches nothing when there are no sticky posts', function (): void {
    Functions\when('get_option')->justReturn([]);
    Functions\when('wp_parse_args')->alias(
        static fn (array $args, array $defaults): array => array_merge($defaults, $args)
    );
    Functions\expect('get_posts')
        ->once()
        ->with(['post__in' => [0]])
        ->andReturn([]);

    expect(uams_get_sticky_posts())->toBe([]);
});
