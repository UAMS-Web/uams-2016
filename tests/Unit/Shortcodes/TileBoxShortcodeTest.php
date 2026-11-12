<?php

/**
 * The [box] and [tile] shortcodes, with WordPress mocked.
 *
 * [box] runs do_shortcode() over its content, which is where WordPress calls [tile] for each tile
 * and the tile counts itself. The tests stand do_shortcode() in for that by calling tile_handler()
 * on the same instance.
 *
 * setup/class.tile-box-shortcode.php carries three `@pest-mutate-ignore` markers, on `MAXTILES`,
 * the `$count` default and the list of number words. Coverage attributes no line to a constant or a
 * property's default, so the mutation run reports their mutants uncovered and runs no test against
 * them. None is equivalent: 'replaces the content of every tile past the twelfth' fails when
 * `MAXTILES` or the starting count moves by one, and 'names the box after the number of tiles in
 * it' fails when a word is removed.
 *
 * @see setup/class.tile-box-shortcode.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.tile-box-shortcode.php');
    stubShortcodeApi();
    expectApplied('the_content')->zeroOrMoreTimes()->andReturnUsing(static fn (string $content): string => 'F{'.$content.'}');
});

/**
 * Run [box] with do_shortcode() calling [tile] the given number of times.
 */
function renderBox(TileBox $box, mixed $atts, int $tiles): string
{
    when('do_shortcode')->alias(static function () use ($box, $tiles): string {
        $output = '';

        for ($i = 0; $i < $tiles; $i++) {
            $output .= $box->tile_handler([], 't');
        }

        return $output;
    });

    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore argument.type
    return $box->box_handler($atts, 'TILES');
}

it('registers its content filter and two shortcodes', function (): void {
    $calls = recordShortcodes();
    $box = new TileBox;

    expect(has_filter('the_content', [$box, 'wpex_fix_shortcodes']))->toBe(10)
        ->and($calls->getArrayCopy())->toBe([
            ['box', [$box, 'box_handler']],
            ['tile', [$box, 'tile_handler']],
        ]);
});

describe('the content filter', function (): void {
    it('unwraps shortcodes from the paragraphs and line breaks wpautop adds', function (): void {
        expect((new TileBox)->wpex_fix_shortcodes('<p>[tile]</p>x[tile]<br />'))->toBe('[tile]x[tile]');
    });

    it('returns empty or non-string content as a string, unchanged', function (mixed $content, string $expected): void {
        expect((new TileBox)->wpex_fix_shortcodes($content))->toBe($expected);
    })->with([
        'empty' => ['', ''],
        'null' => [null, ''],
        'an integer' => [42, '42'],
    ]);
    it('casts content that is not a string, rather than failing on it', function (): void {
        [$result, $warnings] = collectWarnings(static fn (): string => (new TileBox)->wpex_fix_shortcodes(['[x]']));

        expect($result)->toBe('Array')
            ->and($warnings)->toBe(['Array to string conversion']);
    });
});

describe('[box]', function (): void {
    it('asks for content when it has none', function (mixed $content): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect((new TileBox)->box_handler(['color' => 'red'], $content))
            ->toBe('No content inside the box element. Make sure you close your box element. Required structure: [box][tile]content[/tile][/box]')
            ->and(shortcodeAttsNames())->toBe(['box']);
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('names the box after the number of tiles in it', function (int $tiles, string $number): void {
        expect(renderBox(new TileBox, '', $tiles))
            ->toStartWith('<div class="box-outer"><div class="box A{'.$number.'} A{box-S{none}}A{}A{}A{}A{}">');
    })->with([
        [0, 'zero'],
        [1, 'one'],
        [2, 'two'],
        [3, 'three'],
        [4, 'four'],
        [5, 'five'],
        [6, 'six'],
        [7, 'seven'],
        [8, 'eight'],
        [9, 'nine'],
        [10, 'ten'],
        [11, 'eleven'],
        [12, 'twelve'],
        'more than twelve' => [13, 'twelve'],
    ]);

    it('renders its tiles inside it', function (): void {
        expect(renderBox(new TileBox, [], 2))
            ->toBe('<div class="box-outer"><div class="box A{two} A{box-S{none}}A{}A{}A{}A{}"><div class="A{tile}">F{t}</div><div class="A{tile}">F{t}</div></div></div>');
    });

    it('adds the alignment, color, padding, shadow and custom classes', function (): void {
        expect(renderBox(new TileBox, ['alignment' => 'centered', 'color' => 'red', 'padding' => 'none', 'shadow' => 'none', 'custom' => 'mine'], 1))
            ->toStartWith('<div class="box-outer"><div class="box A{one} A{box-S{centered}}A{ box-S{red}}A{ nopad}A{ noshadow}A{ S{mine}}">');
    });

    it("counts each box's tiles from zero", function (): void {
        $box = new TileBox;
        renderBox($box, [], 3);

        expect(renderBox($box, [], 1))->toStartWith('<div class="box-outer"><div class="box A{one} ');
    });
});

describe('[tile]', function (): void {
    it('renders its content as post content', function (): void {
        expect((new TileBox)->tile_handler('', 'hello'))->toBe('<div class="A{tile}">F{hello}</div>')
            ->and(shortcodeAttsNames())->toBe(['tile']);
    });

    it('asks for content when it has none', function (mixed $content): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect((new TileBox)->tile_handler([], $content))
            ->toBe('<div class="A{tile}">F{No content for this tile. Make sure you wrap your content like this: [tile]Content here[/tile]}</div>');
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('renders an empty tile, keeping any content it has', function (string $empty, mixed $content, string $expected): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect((new TileBox)->tile_handler(['empty' => $empty], $content))->toBe('<div class="A{tile empty}">F{'.$expected.'}</div>');
    })->with([
        'true, no content' => ['true', null, ''],
        'yes, with content' => ['yes', 'x', 'x'],
    ]);

    it('replaces the content of every tile past the twelfth', function (): void {
        $box = new TileBox;

        for ($i = 0; $i < 12; $i++) {
            expect($box->tile_handler([], 'ok'))->toBe('<div class="A{tile}">F{ok}</div>');
        }

        expect($box->tile_handler([], 'ok'))->toBe('<div class="A{tile}">F{Too many [tile]s. Only up to 12 are supported.}</div>')
            ->and($box->tile_handler(['empty' => '1'], 'ok'))->toBe('<div class="A{tile empty}">F{Too many [tile]s. Only up to 12 are supported.}</div>');
    });

    it('allows twelve tiles', function (): void {
        expect(TileBox::MAXTILES)->toBe(12);
    });
});
