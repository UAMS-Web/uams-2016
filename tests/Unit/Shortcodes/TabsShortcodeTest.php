<?php

/**
 * The [tabs] and [tab] shortcodes, with WordPress mocked.
 *
 * [tabs] runs do_shortcode() over its content, which is where WordPress calls [tab] for each tab
 * and the tab records its title and link for the tab list. The tests stand do_shortcode() in for
 * that by calling tab_handler() on the same instance.
 *
 * @see setup/class.tabs-shortcode.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.tabs-shortcode.php');
    stubShortcodeApi();
    when('sanitize_text_field')->returnArg();
    when('sanitize_html_class')->returnArg();
});

/**
 * Run [tabs] with do_shortcode() calling [tab] once for each set of attributes.
 *
 * @param  list<array<string, string>>  $tabs
 */
function renderTabs(UAMS_TabsShortcode $shortcode, mixed $atts, array $tabs): string
{
    when('do_shortcode')->alias(static function (string $content) use ($shortcode, $tabs): string {
        if ($content !== 'TABS') {
            return 'D{'.$content.'}';
        }

        return implode('', array_map(static fn (array $tab): string => $shortcode->tab_handler($tab, 'body'), $tabs));
    });

    return $shortcode->tabs_handler($atts, 'TABS');
}

it('registers its content filter, two shortcodes and its script', function (): void {
    $calls = recordShortcodes();
    $tabs = new UAMS_TabsShortcode;

    expect(has_filter('the_content', [$tabs, 'wpex_fix_shortcodes']))->toBe(10)
        ->and(has_action('init', [$tabs, 'register_uams_tabs']))->toBe(10)
        ->and($calls->getArrayCopy())->toBe([
            ['tabs', [$tabs, 'tabs_handler']],
            ['tab', [$tabs, 'tab_handler']],
        ]);
});

it('registers the tabs script in the footer', function (): void {
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
    Functions\expect('wp_register_script')
        ->once()
        ->with('uams-tabs', 'https://t.test/theme/js/uams.tabs.min.js', [], '1.0', true);

    (new UAMS_TabsShortcode)->register_uams_tabs();
});

describe('the content filter', function (): void {
    it('unwraps shortcodes from the paragraphs and line breaks wpautop adds', function (): void {
        expect((new UAMS_TabsShortcode)->wpex_fix_shortcodes('<p>[tab]</p>x[tab]<br />'))->toBe('[tab]x[tab]');
    });

    it('returns empty or non-string content as a string, unchanged', function (mixed $content, string $expected): void {
        expect((new UAMS_TabsShortcode)->wpex_fix_shortcodes($content))->toBe($expected);
    })->with([
        'empty' => ['', ''],
        'null' => [null, ''],
        'an integer' => [42, '42'],
    ]);
    it('casts content that is not a string, rather than failing on it', function (): void {
        [$result, $warnings] = collectWarnings(static fn (): string => (new UAMS_TabsShortcode)->wpex_fix_shortcodes(['[x]']));

        expect($result)->toBe('Array')
            ->and($warnings)->toBe(['Array to string conversion']);
    });
});

describe('[tabs]', function (): void {
    it('asks for content when it has none, and loads no script', function (mixed $content): void {
        Functions\expect('wp_enqueue_script')->never();

        expect((new UAMS_TabsShortcode)->tabs_handler(['name' => 'N'], $content))
            ->toBe('No content inside the tabs element. Make sure you close your tabs element. Required structure: [tabs][tab title=""]content[/tab][/tabs]')
            ->and(shortcodeAttsNames())->toBe(['tabs']);
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('loads the script and lists each tab, linked by its link or its position', function (): void {
        Functions\expect('wp_enqueue_script')->once()->with('uams-tabs');

        expect(renderTabs(new UAMS_TabsShortcode, ['name' => 'My Tabs'], [['title' => 'One', 'link' => 'first'], ['title' => 'Two']]))->toBe(
            '<div id="uams-tabs" class="uams-tabs-shortcode A{MyTabs}">'
            .'<div class="js-tabs tabs__uams"><ul class="js-tablist tabs__uams_ul" data-hx="h2">'
            .'<li class="js-tablist__item tabs__uams__li"><a href="#A{first}" id="label_A{first}" class="js-tablist__link tabs__uams__a">H{One}</a></li>'
            .'<li class="js-tablist__item tabs__uams__li"><a href="#A{tab-2}" id="label_A{tab-2}" class="js-tablist__link tabs__uams__a">H{Two}</a></li>'
            .'</ul><div class="uams-tab-content">'
            .'<div id="A{first}" class="js-tabcontent tabs__uams__tabcontent">D{body}</div>'
            .'<div id="A{tab-2}" class="js-tabcontent tabs__uams__tabcontent">D{body}</div>'
            .'</div></div></div>'
        );
    });

    it('removes spaces from a link in the tab list', function (): void {
        when('wp_enqueue_script')->justReturn();
        $tabs = new UAMS_TabsShortcode;
        when('do_shortcode')->alias(static function () use ($tabs): string {
            $tabs->tab_titles[] = 'One';
            $tabs->tab_links[] = 'a b';

            return '';
        });

        expect($tabs->tabs_handler([], 'x'))->toContain('<a href="#A{ab}" id="label_A{ab}"');
    });

    it('starts each set of tabs with an empty list', function (): void {
        when('wp_enqueue_script')->justReturn();
        $tabs = new UAMS_TabsShortcode;
        renderTabs($tabs, [], [['title' => 'One']]);

        expect(renderTabs($tabs, [], [['title' => 'Two']]))
            ->not->toContain('H{One}')
            ->toContain('<a href="#A{tab-1}" id="label_A{tab-1}" class="js-tablist__link tabs__uams__a">H{Two}</a>');
    });

    it('has an empty name class by default', function (): void {
        when('wp_enqueue_script')->justReturn();

        expect(renderTabs(new UAMS_TabsShortcode, '', []))->toStartWith('<div id="uams-tabs" class="uams-tabs-shortcode A{}">');
    });
});

describe('[tab]', function (): void {
    it('asks for content when it has none', function (mixed $content): void {
        expect((new UAMS_TabsShortcode)->tab_handler(['link' => 'x'], $content))
            ->toBe('<div id="A{x}" class="js-tabcontent tabs__uams__tabcontent">D{No content for this tab. Make sure you wrap your content like this: [tab]Content here[/tab]}</div>')
            ->and(shortcodeAttsNames())->toBe(['tab']);
    })->with([
        'empty' => [''],
        'null' => [null],
    ]);

    it('records its sanitized title and link', function (): void {
        when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
        when('sanitize_html_class')->alias(static fn (string $text): string => 'S{'.$text.'}');

        $tabs = new UAMS_TabsShortcode;
        $tabs->tab_handler(['title' => 'One', 'link' => 'l'], 'body');

        expect($tabs->tab_titles)->toBe(['T{One}'])
            ->and($tabs->tab_links)->toBe(['S{l}']);
    });

    it('is numbered by its position when it has no link', function (): void {
        $tabs = new UAMS_TabsShortcode;
        $tabs->tab_handler('', 'a');

        expect($tabs->tab_handler([], 'b'))->toBe('<div id="A{tab-2}" class="js-tabcontent tabs__uams__tabcontent">D{b}</div>')
            ->and($tabs->tab_titles)->toBe(['', ''])
            ->and($tabs->tab_links)->toBe(['', '']);
    });
});
