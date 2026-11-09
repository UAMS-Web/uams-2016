<?php

/**
 * The [cards] and [card] shortcodes, with WordPress mocked.
 *
 * @see setup/class.cards-shortcode.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;

beforeEach(function (): void {
    loadShortcodeFile('class.cards-shortcode.php');
    stubShortcodeApi();
    expectApplied('the_content')->zeroOrMoreTimes()->andReturnUsing(static fn (string $content): string => 'F{'.$content.'}');
});

it('registers its content filter and two shortcodes', function (): void {
    $calls = recordShortcodes();
    $cards = new UAMS_CardsShortcode;

    expect(has_filter('the_content', [$cards, 'wpex_fix_shortcodes']))->toBe(10)
        ->and($calls->getArrayCopy())->toBe([
            ['cards', [$cards, 'cards_handler']],
            ['card', [$cards, 'card_handler']],
        ]);
});

describe('the content filter', function (): void {
    it('unwraps shortcodes from the paragraphs and line breaks wpautop adds', function (): void {
        expect((new UAMS_CardsShortcode)->wpex_fix_shortcodes('<p>[card]</p>x[card]<br />'))
            ->toBe('[card]x[card]');
    });

    it('returns empty or non-string content unchanged, keeping its type', function (mixed $content): void {
        expect((new UAMS_CardsShortcode)->wpex_fix_shortcodes($content))->toBe($content);
    })->with([
        'empty' => [''],
        'null' => [null],
        'an integer' => [42],
    ]);
});

describe('[cards]', function (): void {
    it('asks for content when it has none', function (mixed $content): void {
        expect((new UAMS_CardsShortcode)->cards_handler(['name' => 'N'], $content))
            ->toBe('No content inside the cards element. Make sure you close your cards element.')
            ->and(shortcodeAttsNames())->toBe(['cards']);
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('wraps the cards in a section, under the name when there is one', function (): void {
        expect((new UAMS_CardsShortcode)->cards_handler(['name' => 'News'], 'body'))
            ->toBe('<h2>H{News}</h2><section class="cards">D{body}</section>');
    });

    it('has no heading without a name', function (): void {
        expect((new UAMS_CardsShortcode)->cards_handler('', 'body'))
            ->toBe('<section class="cards">D{body}</section>');
    });
});

describe('[card]', function (): void {
    it('renders a bare card', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler('', 'body'))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-stack"><div class="card-content"><h3>H{}</h3>F{D{body}}</div></div></article>')
            ->and(shortcodeAttsNames())->toBe(['card']);
    });

    it('asks for content when it has none', function (mixed $content): void {
        expect((new UAMS_CardsShortcode)->card_handler(['title' => 'T'], $content))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-stack"><div class="card-content"><h3>H{T}</h3>F{D{No content for this section. Make sure you wrap your content like this: [card]Content here[/card]}}</div></div></article>');
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('adds the type, size and color classes', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler(['type' => 'top', 'size' => 'med', 'color' => 'gray10'], 'body'))
            ->toStartWith('<article class="cardA{ S{top}}A{ S{med}}A{ S{gray10}}">');
    });

    it('renders the image with its alt text, linked when there is a link', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler(['title' => 'T', 'image' => 'i.jpg', 'alt' => 'Alt', 'link' => 'l.test'], 'body'))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-image"><a href="U{l.test}"><img src="U{i.jpg}" alt="A{Alt}" /></a></div><div class="card-stack"><div class="card-content"><a href="U{l.test}"><h3>H{T}</h3></a>F{D{body}}</div></div></article>');
    });

    it('gives the image an empty alt when none is set, and no link without one', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler(['image' => 'i.jpg'], 'body'))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-image"><img src="U{i.jpg}" alt="" /></div><div class="card-stack"><div class="card-content"><h3>H{}</h3>F{D{body}}</div></div></article>');
    });

    it('links the title without an image', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler(['link' => 'l.test'], 'body'))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-stack"><div class="card-content"><a href="U{l.test}"><h3>H{}</h3></a>F{D{body}}</div></div></article>');
    });

    it('adds the action and footer, filtered as post content', function (): void {
        expect((new UAMS_CardsShortcode)->card_handler(['action' => '<a>Go</a>', 'footer' => 'May 4'], 'body'))
            ->toBe('<article class="cardA{}A{}A{}"><div class="card-stack"><div class="card-content"><h3>H{}</h3>F{D{body}}</div><div class="card-action">K{<a>Go</a>}</div><div class="card-footer">K{May 4}</div></div></article>');
    });
});
