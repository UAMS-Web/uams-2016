<?php

/**
 * uams_has_sidebar(), get_uams_content_class() and uams_content_class(), with WordPress mocked.
 *
 * The column class follows the sidebar: col-md-8 beside one, col-md-12 without. A gallery post
 * has no sidebar except on an archive or a search results page, and the 404 page never has one.
 *
 * Mutants not killed here, and why. `uams_content_class()` and `get_uams_content_class()` default
 * `$class` to an empty string, and the `EmptyStringToNotEmpty` mutant of that default carries
 * `@pest-mutate-ignore`: coverage attributes no line to a function signature, so the mutation run
 * reports the mutant as uncovered and never runs a test against it. It is not an equivalent
 * mutant: "passes an empty class to the filter by default" asserts the default and would fail on it.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    stubEscaping();
    unset($GLOBALS['post']);
});

afterEach(function (): void {
    unset($GLOBALS['post']);
});

it('has no sidebar on the 404 page, before it reads the post', function (): void {
    wpConditionals(['is_404' => true]);
    Functions\expect('get_the_ID')->never();
    Functions\expect('get_post_format')->never();

    expect(uams_has_sidebar())->toBeFalse();
});

it('has a sidebar for a standard post', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals();
    Functions\expect('get_post_format')->once()->with(12)->andReturn(false);

    expect(uams_has_sidebar())->toBeTrue();
});

it('has a sidebar for any format other than gallery', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals();
    when('get_post_format')->justReturn('link');

    expect(uams_has_sidebar())->toBeTrue();
});

it('has no sidebar for a gallery post', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals();
    when('get_post_format')->justReturn('gallery');

    expect(uams_has_sidebar())->toBeFalse();
});

it('keeps the sidebar for a gallery format on an archive', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals(['is_archive' => true]);
    when('get_post_format')->justReturn('gallery');

    expect(uams_has_sidebar())->toBeTrue();
});

it('keeps the sidebar for a gallery format on a search results page', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals(['is_search' => true]);
    when('get_post_format')->justReturn('gallery');

    expect(uams_has_sidebar())->toBeTrue();
});

it('falls back to the current post id when the global is not a post', function (): void {
    $GLOBALS['post'] = null;
    wpConditionals();
    Functions\expect('get_the_ID')->once()->andReturn(31);
    Functions\expect('get_post_format')->once()->with(31)->andReturn('gallery');

    expect(uams_has_sidebar())->toBeFalse();
});

it('does not ask for a format when there is no post id', function (): void {
    wpConditionals();
    when('get_the_ID')->justReturn(false);
    Functions\expect('get_post_format')->never();

    expect(uams_has_sidebar())->toBeTrue();
});

it('uses the narrow column beside a sidebar', function (): void {
    $GLOBALS['post'] = wpPost(['ID' => 12]);
    wpConditionals();
    when('get_post_format')->justReturn(false);

    expect(get_uams_content_class())->toBe(['A{uams-content}', 'A{col-md-8}']);
});

it('uses the full-width column without a sidebar', function (): void {
    wpConditionals(['is_404' => true]);

    expect(get_uams_content_class())->toBe(['A{uams-content}', 'A{col-md-12}']);
});

it('applies the content class filter with the classes and the requested class', function (): void {
    wpConditionals(['is_404' => true]);
    expectApplied('uams_content_class')
        ->once()
        ->with(['A{uams-content}', 'A{col-md-12}'], 'extra')
        ->andReturn(['filtered']);

    expect(get_uams_content_class('extra'))->toBe(['filtered']);
});

it('passes an empty class to the filter by default', function (): void {
    wpConditionals(['is_404' => true]);
    expectApplied('uams_content_class')
        ->once()
        ->with(Mockery::type('array'), '');

    get_uams_content_class();
});

it('echoes the class attribute with the escaped, space-separated classes', function (): void {
    wpConditionals(['is_404' => true]);

    uams_content_class();
})->expectOutputString('class="A{A{uams-content} A{col-md-12}}"');

it('passes the requested class through to the filter when echoing', function (): void {
    wpConditionals(['is_404' => true]);
    expectApplied('uams_content_class')
        ->once()
        ->with(Mockery::type('array'), 'wide')
        ->andReturn(['one', 'two']);

    uams_content_class('wide');
})->expectOutputString('class="A{one two}"');
