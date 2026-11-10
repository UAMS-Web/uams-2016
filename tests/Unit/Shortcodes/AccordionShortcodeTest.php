<?php

/**
 * The [accordion], [section] and [subsection] shortcodes, with WordPress mocked.
 *
 * Unlike the other shortcode files, class.accordion-shortcode.php does not construct its class at
 * file scope: UAMS_Shortcodes does.
 *
 * @see setup/class.accordion-shortcode.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.accordion-shortcode.php');
    stubShortcodeApi();
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
    expectApplied('the_content')->zeroOrMoreTimes()->andReturnUsing(static fn (string $content): string => 'F{'.$content.'}');
});

it('registers its content filter and three shortcodes', function (): void {
    $calls = recordShortcodes();
    $accordion = new UAMS_AccordionShortcode;

    expect(has_filter('the_content', [$accordion, 'wpex_fix_shortcodes']))->toBe(10)
        ->and($calls->getArrayCopy())->toBe([
            ['accordion', [$accordion, 'accordion_handler']],
            ['section', [$accordion, 'section_handler']],
            ['subsection', [$accordion, 'subsection_handler']],
        ]);
});

describe('the content filter', function (): void {
    it('unwraps shortcodes from the paragraphs and line breaks wpautop adds', function (): void {
        expect((new UAMS_AccordionShortcode)->wpex_fix_shortcodes('<p>[section]</p>x[tile]<br />'))
            ->toBe('[section]x[tile]');
    });

    it('returns empty or non-string content as a string, unchanged', function (mixed $content, string $expected): void {
        expect((new UAMS_AccordionShortcode)->wpex_fix_shortcodes($content))->toBe($expected);
    })->with([
        'empty' => ['', ''],
        'null' => [null, ''],
        'zero' => ['0', '0'],
        'an integer' => [42, '42'],
    ]);
    it('casts content that is not a string, rather than failing on it', function (): void {
        [$result, $warnings] = collectWarnings(static fn (): string => (new UAMS_AccordionShortcode)->wpex_fix_shortcodes(['[x]']));

        expect($result)->toBe('Array')
            ->and($warnings)->toBe(['Array to string conversion']);
    });
});

describe('[accordion]', function (): void {
    it('asks for content when it has none', function (mixed $content): void {
        expect((new UAMS_AccordionShortcode)->accordion_handler(['name' => 'N'], $content))
            ->toBe('No content inside the accordion element. Make sure your close your accordion element. Required stucture: [accordion][section]content[/section][/accordion]');
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('loads the accordion script and wraps the sections under the name', function (): void {
        expect((new UAMS_AccordionShortcode)->accordion_handler(['name' => 'FAQ'], 'body'))
            ->toBe('<script src="https://t.test/theme/js/uams.accordionmodule.js" type="text/javascript"></script><div id="accordion uams-accordion-shortcode"><h3>FAQ</h3><div class="js-accordion" data-accordion-prefix-classes="uams-accordion-shortcode">D{body}</div></div>');
    });

    it('has an empty heading by default', function (): void {
        expect((new UAMS_AccordionShortcode)->accordion_handler('', 'body'))
            ->toContain('<h3></h3>');
    });
});

describe('[section] and [subsection]', function (): void {
    it('renders a closed section', function (): void {
        expect((new UAMS_AccordionShortcode)->section_handler(['title' => 'T'], 'body'))
            ->toBe('<h2 class="js-accordion__header">T</h2><div class="js-accordion__panel" >F{D{body}}</div>');
    });

    it('renders an open section when active is set', function (): void {
        expect((new UAMS_AccordionShortcode)->section_handler(['title' => 'T', 'active' => 'true'], 'body'))
            ->toBe('<h2 class="js-accordion__header">T</h2><div class="js-accordion__panel"  data-accordion-opened="true">F{D{body}}</div>');
    });

    it('asks for section content when it has none, with an empty title by default', function (): void {
        expect((new UAMS_AccordionShortcode)->section_handler('', ''))
            ->toBe('<h2 class="js-accordion__header"></h2><div class="js-accordion__panel" >F{D{No content for this section.  Make sure you wrap your content like this: [section]Content here[/section]}}</div>');
    });

    it('renders a closed subsection', function (): void {
        expect((new UAMS_AccordionShortcode)->subsection_handler(['title' => 'T'], 'body'))
            ->toBe('<h3 class="js-accordion__header uams-accordion-subheader">T</h3><div class="js-accordion__panel" >F{D{body}}</div>');
    });

    it('renders an open subsection when active is set', function (): void {
        expect((new UAMS_AccordionShortcode)->subsection_handler(['title' => 'T', 'active' => '1'], 'body'))
            ->toBe('<h3 class="js-accordion__header uams-accordion-subheader">T</h3><div class="js-accordion__panel"  data-accordion-opened="true">F{D{body}}</div>');
    });

    it('asks for subsection content when it has none, with an empty title by default', function (): void {
        expect((new UAMS_AccordionShortcode)->subsection_handler('', null))
            ->toBe('<h3 class="js-accordion__header uams-accordion-subheader"></h3><div class="js-accordion__panel" >F{D{No content for this tile.  Make sure you wrap your content like this: [section]Content here[/section]}}</div>');
    });
});
