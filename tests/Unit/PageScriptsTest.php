<?php

/**
 * The per-page header and footer script meta, tied to `unfiltered_html`, with WordPress mocked.
 *
 * @see setup/class.uams-page-scripts.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Actions\expectAdded as expectActionAdded;
use function Brain\Monkey\Filters\expectAdded as expectFilterAdded;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    require_once __DIR__.'/../../setup/class.uams-page-scripts.php';
});

it('guards the two Custom CSS/JS keys with unfiltered_html', function (): void {
    expect(UAMS_Page_Scripts::KEYS)->toBe(['custom_header_script', 'custom_footer_script'])
        ->and(UAMS_Page_Scripts::CAPABILITY)->toBe('unfiltered_html');
});

it('registers the keys at init and filters protection and every write', function (): void {
    expectActionAdded('init')->once()->with([UAMS_Page_Scripts::class, 'register_meta']);
    expectFilterAdded('is_protected_meta')->once()->with([UAMS_Page_Scripts::class, 'protect_meta'], 10, 3);
    expectFilterAdded('add_post_metadata')->once()->with([UAMS_Page_Scripts::class, 'refuse_unprivileged_write'], 10, 3);
    expectFilterAdded('update_post_metadata')->once()->with([UAMS_Page_Scripts::class, 'refuse_unprivileged_write'], 10, 3);

    new UAMS_Page_Scripts;
});

it('registers each key as a single string on any post type, off REST, with the capability as its auth callback', function (): void {
    $registered = [];
    when('register_meta')->alias(static function (string $type, string $key, array $args) use (&$registered): bool {
        $registered[] = [$type, $key, $args];

        return true;
    });

    UAMS_Page_Scripts::register_meta();

    $args = [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
        'auth_callback' => [UAMS_Page_Scripts::class, 'current_user_may_write'],
    ];
    expect($registered)->toBe([
        ['post', 'custom_header_script', $args],
        ['post', 'custom_footer_script', $args],
    ]);
});

it('lets a user write only with unfiltered_html', function (bool $can): void {
    Functions\expect('current_user_can')->once()->with('unfiltered_html')->andReturn($can);

    expect(UAMS_Page_Scripts::current_user_may_write())->toBe($can);
})->with(['with the capability' => [true], 'without it' => [false]]);

describe('protect_meta()', function (): void {
    it('protects both keys on posts', function (string $key): void {
        expect(UAMS_Page_Scripts::protect_meta(false, $key, 'post'))->toBeTrue();
    })->with(['custom_header_script', 'custom_footer_script']);

    it('leaves any other key, and the keys on other object types, as WordPress decided', function (bool $protected, string $key, string $type): void {
        expect(UAMS_Page_Scripts::protect_meta($protected, $key, $type))->toBe($protected);
    })->with([
        'a plain key' => [false, 'subtitle', 'post'],
        'an underscore key' => [true, '_thumbnail_id', 'post'],
        'the header key on a user' => [false, 'custom_header_script', 'user'],
        'the footer key on a term' => [false, 'custom_footer_script', 'term'],
    ]);
});

describe('refuse_unprivileged_write()', function (): void {
    it('refuses a write of either key by a user without the capability', function (string $key): void {
        when('current_user_can')->justReturn(false);

        expect(UAMS_Page_Scripts::refuse_unprivileged_write(null, 7, $key))->toBeFalse();
    })->with(['custom_header_script', 'custom_footer_script']);

    it('lets a user with the capability write either key', function (string $key): void {
        when('current_user_can')->justReturn(true);

        expect(UAMS_Page_Scripts::refuse_unprivileged_write(null, 7, $key))->toBeNull();
    })->with(['custom_header_script', 'custom_footer_script']);

    it('passes through what an earlier filter decided for the keys when the user may write', function (): void {
        when('current_user_can')->justReturn(true);

        expect(UAMS_Page_Scripts::refuse_unprivileged_write(true, 7, 'custom_header_script'))->toBeTrue();
    });

    it('never consults the capability for any other key', function (): void {
        Functions\expect('current_user_can')->never();

        expect(UAMS_Page_Scripts::refuse_unprivileged_write(null, 7, 'subtitle'))->toBeNull()
            ->and(UAMS_Page_Scripts::refuse_unprivileged_write(true, 7, '_thumbnail_id'))->toBeTrue();
    });
});
