<?php

/**
 * The [button] shortcode, with WordPress mocked.
 *
 * setup/class.button-shortcode.php carries three `@pest-mutate-ignore` markers:
 *
 * - `RemoveArrayItem` on the static list of types. Coverage attributes no line to a property's
 *   default, so the mutation run reports the mutants uncovered and runs no test against them. Not
 *   equivalent: 'adds a known type' renders each of the four, so removing one fails its case.
 * - `EmptyStringToNotEmpty` on the `type` and `size` defaults. Equivalent: the mutator's string is
 *   neither a known type nor a known size, so it adds no class, as the empty default adds none.
 *
 * @see setup/class.button-shortcode.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    loadShortcodeFile('class.button-shortcode.php');
    stubShortcodeApi();
});

it('registers the button shortcode', function (): void {
    $calls = recordShortcodes();
    $button = new UAMS_Button;

    expect($calls->getArrayCopy())->toBe([['button', [$button, 'button_handler']]]);
});

it('asks for a message when there is no text', function (mixed $content): void {
    expect((new UAMS_Button)->button_handler([], $content))->toBe('No text in this button')
        ->and(shortcodeAttsNames())->toBe(['button']);
})->with([
    'no content' => [null],
    'empty content' => [''],
]);

it('renders a plain button with the default color and link', function (): void {
    expect((new UAMS_Button)->button_handler('', 'Go'))
        ->toBe('<a class="A{uams-btn} A{btn-S{none}}" href="U{#}">H{Go}</a>');
});

it('adds a known type, trimmed and lowercased', function (string $type, string $class): void {
    expect((new UAMS_Button)->button_handler(['type' => $type], 'Go'))
        ->toBe('<a class="A{uams-btn '.$class.'} A{btn-S{none}}" href="U{#}">H{Go}</a>');
})->with([
    'plus' => ['plus', 'btn-plus'],
    'go' => ['go', 'btn-go'],
    'external' => ['external', 'btn-external'],
    'play, padded and capitalized' => ['  Play ', 'btn-play'],
]);

it('ignores an unknown type', function (): void {
    expect((new UAMS_Button)->button_handler(['type' => 'shiny'], 'Go'))
        ->toBe('<a class="A{uams-btn} A{btn-S{none}}" href="U{#}">H{Go}</a>');
});

it('escapes the link, and falls back to # when it is empty', function (string $url, string $href): void {
    expect((new UAMS_Button)->button_handler(['url' => $url, 'color' => 'red'], 'Go'))
        ->toBe('<a class="A{uams-btn} A{btn-S{red}}" href="'.$href.'">H{Go}</a>');
})->with([
    'a link' => ['https://x.test', 'U{https://x.test}'],
    'empty' => ['', '#'],
]);

it('makes the button small when the small attribute is present, whatever its value', function (): void {
    expect((new UAMS_Button)->button_handler(['small' => ''], 'Go'))
        ->toBe('<a class="A{uams-btn btn-sm} A{btn-S{none}}" href="U{#}">H{Go}</a>');
});

it('sizes the button from the size attribute', function (string $size, string $classes): void {
    expect((new UAMS_Button)->button_handler(['size' => $size], 'Go'))
        ->toBe('<a class="A{'.$classes.'} A{btn-S{none}}" href="U{#}">H{Go}</a>');
})->with([
    'small' => ['small', 'uams-btn btn-sm'],
    'sm' => ['sm', 'uams-btn btn-sm'],
    'large' => ['large', 'uams-btn btn-lg'],
    'lg' => ['lg', 'uams-btn btn-lg'],
    'unknown' => ['huge', 'uams-btn'],
]);

it('lists btn-sm once when both small and size ask for it', function (): void {
    expect((new UAMS_Button)->button_handler(['small' => '1', 'size' => 'sm', 'type' => 'go'], 'Go'))
        ->toBe('<a class="A{uams-btn btn-go btn-sm} A{btn-S{none}}" href="U{#}">H{Go}</a>');
});

it('prefers the text attribute to the content', function (?string $content): void {
    expect((new UAMS_Button)->button_handler(['text' => 'Label'], $content))
        ->toBe('<a class="A{uams-btn} A{btn-S{none}}" href="U{#}">H{Label}</a>');
})->with([
    'with content' => ['Go'],
    'without content' => [null],
]);
