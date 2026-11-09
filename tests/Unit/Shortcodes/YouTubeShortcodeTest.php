<?php

/**
 * The [youtube] shortcode's markup, with WordPress mocked. The API key half of the class is
 * tested in tests/Unit/YouTubeApiKeyTest.php, and the file is mutated against both test files.
 *
 * setup/class.youtube-shortcode.php carries two `@pest-mutate-ignore` markers:
 *
 * - `RemoveArrayItem` on the static list of types. Coverage attributes no line to a property's
 *   default, so the mutation run reports the mutants uncovered. Not equivalent: 'renders a
 *   placeholder for a single video or a playlist' renders both types, so removing one fails it.
 * - `DecrementInteger` on the `max_results` default. Equivalent: it is read only when `max-results`
 *   is unset or zero, and -1, like 0, is not positive, so neither sets a limit.
 *
 * @see setup/class.youtube-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.youtube-shortcode.php');
    stubShortcodeApi();
    when('sanitize_text_field')->returnArg();
});

it('registers the youtube shortcode', function (): void {
    $calls = recordShortcodes();
    $youtube = new UAMS_YouTube;

    expect($calls->getArrayCopy())->toBe([['youtube', [$youtube, 'youtube_handler']]])
        ->and(UAMS_YouTube::$types)->toBe(['playlist', 'single']);
});

it('asks for the type when it is missing', function (mixed $atts): void {
    expect((new UAMS_YouTube)->youtube_handler($atts))->toBe('required attribute "type" missing')
        ->and(shortcodeAttsNames())->toBe(['youtube']);
})->with([
    'no attributes' => [''],
    'only spaces' => [['type' => '  ', 'id' => 'x']],
]);

it('refuses a type it does not support', function (): void {
    expect((new UAMS_YouTube)->youtube_handler(['type' => 'Channel', 'id' => 'x']))->toBe('youtube type "H{channel}" not supported');
});

it('asks for the id when it is missing', function (): void {
    expect((new UAMS_YouTube)->youtube_handler(['type' => 'single']))->toBe('required attribute "id" missing');
});

it('sanitizes the id', function (): void {
    when('wp_rand')->justReturn(42);
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');

    expect((new UAMS_YouTube)->youtube_handler(['type' => 'single', 'id' => 'abc']))->toContain('data-uams-youtube="A{T{abc}}"');
});

it('renders a placeholder for a single video or a playlist, with a random element id', function (string $type, string $expected): void {
    Functions\expect('wp_rand')->once()->with(0, 1000)->andReturn(7);

    expect((new UAMS_YouTube)->youtube_handler(['type' => $type, 'id' => 'abc']))
        ->toBe('<div id="A{uams-youtube-7}" class="uams-youtube" data-uams-youtube-type="A{'.$expected.'}" data-uams-youtube="A{abc}"></div>');
})->with([
    'single' => ['single', 'single'],
    'a playlist, padded and capitalized' => [' PlayList ', 'playlist'],
]);

it('limits the results when max-results or max_results is set', function (array $atts, int $max): void {
    when('wp_rand')->justReturn(42);

    expect((new UAMS_YouTube)->youtube_handler(array_merge(['type' => 'playlist', 'id' => 'abc'], $atts)))
        ->toBe('<div id="A{uams-youtube-42}" class="uams-youtube" data-uams-youtube-type="A{playlist}" data-uams-youtube="A{abc}" data-max-results="'.$max.'"></div>');
})->with([
    'max-results' => [['max-results' => '5'], 5],
    'max_results' => [['max_results' => '3'], 3],
    'max-results wins over max_results' => [['max-results' => '5', 'max_results' => '3'], 5],
    'one' => [['max-results' => '1'], 1],
]);

it('has no limit when the maximum is not positive', function (array $atts): void {
    when('wp_rand')->justReturn(42);

    expect((new UAMS_YouTube)->youtube_handler(array_merge(['type' => 'playlist', 'id' => 'abc'], $atts)))
        ->not->toContain('data-max-results');
})->with([
    'zero' => [['max-results' => '0']],
    'not a number' => [['max-results' => 'abc']],
    'negative' => [['max-results' => '-2']],
    'unset' => [[]],
]);
