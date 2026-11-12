<?php

/**
 * The [row] and [col] shortcodes, with WordPress mocked.
 *
 * @see setup/class.grid-shortcode.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    loadShortcodeFile('class.grid-shortcode.php');
    stubShortcodeApi();
});

it('registers the row and col shortcodes', function (): void {
    $calls = recordShortcodes();
    $grid = new UAMS_GridShortcode;

    expect($calls->getArrayCopy())->toBe([
        ['row', [$grid, 'bs_row']],
        ['col', [$grid, 'bs_span']],
    ]);
});

describe('[row]', function (): void {
    it('wraps the content in a row, with the content line breaks WordPress adds removed', function (): void {
        expect((new UAMS_GridShortcode)->bs_row('', 'a<br class="nc" />b<br class="nc"/>c'))
            ->toBe('B{<div class="A{row}">D{ab<br class="nc"/>c}</div>}')
            ->and(shortcodeAttsNames())->toBe(['row']);
    });

    it('takes its class from the attribute', function (): void {
        expect((new UAMS_GridShortcode)->bs_row(['class' => 'row gutters'], 'x'))
            ->toBe('B{<div class="A{row gutters}">D{x}</div>}');
    });

    it('renders an empty row when there is no content', function (mixed $content): void {
        expect((new UAMS_GridShortcode)->bs_row([], $content))
            ->toBe('B{<div class="A{row}">D{}</div>}');
    })->with([
        'the default' => [''],
        'null' => [null],
    ]);

    it('casts content that is not a string before it removes the line breaks', function (): void {
        // Non-string content is the case under test.
        // @phpstan-ignore argument.type
        [$result, $warnings] = collectWarnings(static fn (): string => (new UAMS_GridShortcode)->bs_row([], ['x']));

        expect($result)->toBe('B{<div class="A{row}">D{Array}</div>}')
            ->and($warnings)->toBe(['Array to string conversion']);
    });

    it('renders an empty row when it is called with no content argument', function (): void {
        expect((new UAMS_GridShortcode)->bs_row([]))
            ->toBe('B{<div class="A{row}">D{}</div>}');
    });
});

describe('[col]', function (): void {
    it('wraps the content in a one-column cell by default', function (): void {
        expect((new UAMS_GridShortcode)->bs_span('', 'x'))
            ->toBe('B{<div class="A{col-sm-1}">D{x}</div>}')
            ->and(shortcodeAttsNames())->toBe(['col']);
    });

    it('takes its class from the attribute', function (): void {
        expect((new UAMS_GridShortcode)->bs_span(['class' => 'col-md-12'], 'x'))
            ->toBe('B{<div class="A{col-md-12}">D{x}</div>}');
    });

    it('renders an empty cell when it has no content', function (): void {
        expect((new UAMS_GridShortcode)->bs_span([], null))
            ->toBe('B{<div class="A{col-sm-1}">D{}</div>}')
            ->and((new UAMS_GridShortcode)->bs_span([]))
            ->toBe('B{<div class="A{col-sm-1}">D{}</div>}');
    });
});
