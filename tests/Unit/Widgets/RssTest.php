<?php

/**
 * The RSS widget and its shortcode, with WordPress mocked. A feed is a stand-in object that returns what
 * the SimplePie feed and item methods the widget calls would.
 *
 * @see widgets/class.uams-rss.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubShortcodeApi();
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('esc_url_raw')->alias(static fn (string $url): string => 'R{'.$url.'}');
    when('esc_textarea')->alias(static fn (string $text): string => 'X{'.$text.'}');
    when('wp_parse_args')->alias(static fn (array $args, array $defaults): array => array_merge($defaults, $args));
    when('wp_specialchars_decode')->alias(static fn (string $text): string => 'D'.$text);
    when('is_feed')->justReturn(false);
    when('current_user_can')->justReturn(false);
    when('human_time_diff')->alias(static fn (int $from): string => 'diff'.$from);
    when('checked')->alias(static function (mixed $checked, mixed $current = true): void {
        echo $checked === $current ? ' CHK' : '';
    });
    when('selected')->alias(static fn (mixed $selected, mixed $current = true, bool $echo = true): string => $selected === $current ? 'SEL' : '');
});

/**
 * A feed item stand-in.
 *
 * @param  array<string, mixed>  $fields
 */
function rssItem(array $fields = []): object
{
    return new class($fields + ['title' => 'Item <b>one</b>', 'link' => '/one', 'enclosure' => null, 'date' => '2026-01-02', 'description' => 'About one'])
    {
        /** @param array<string, mixed> $fields */
        public function __construct(private array $fields) {}

        public function get_title(): string
        {
            return $this->fields['title'];
        }

        public function get_link(): string
        {
            return $this->fields['link'];
        }

        public function get_enclosure(): mixed
        {
            return $this->fields['enclosure'];
        }

        public function get_date(): mixed
        {
            return $this->fields['date'];
        }

        public function get_description(): string
        {
            return $this->fields['description'];
        }
    };
}

/**
 * Make fetch_feed() return a feed of these items, and record the url it was given and the item range asked.
 *
 * @param  list<object>  $items
 * @return ArrayObject<string, mixed>
 */
function stubFeed(array $items, string $permalink = '/feed-home'): ArrayObject
{
    $seen = new ArrayObject;
    $feed = new class($items, $permalink, $seen)
    {
        /**
         * @param  list<object>  $items
         * @param  ArrayObject<string, mixed>  $seen
         */
        public function __construct(private array $items, private string $permalink, private ArrayObject $seen) {}

        public function get_permalink(): string
        {
            return $this->permalink;
        }

        /** @return list<object> */
        public function get_items(int $start, int $max): array
        {
            $this->seen['range'] = [$start, $max];

            return $this->items;
        }
    };

    when('fetch_feed')->alias(static function (string $url) use ($feed, $seen): object {
        $seen['url'] = $url;

        return $feed;
    });

    return $seen;
}

it('registers the widget when the file loads', function (): void {
    expect(loadWidgetFile('class.uams-rss.php'))->toBe(['UAMS_RSS']);
});

it('describes the widget and registers the shortcode', function (): void {
    $shortcodes = recordShortcodes();

    $widget = new UAMS_RSS;

    expect($widget->id_base)->toBe('uams-widget-rss')
        ->and($widget->name)->toBe('UAMS RSS')
        ->and($widget->widget_options)->toBe([
            'description' => 'Similar to the Wordpress RSS widget but allows a blurb before the RSS feed is listed.',
            'classname' => 'uams-widget-rss',
        ])
        ->and($shortcodes->getArrayCopy())->toBe([['rss', [$widget, 'uams_rss_shortcode']]]);
});

describe('update', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
        when('wp_kses_post')->alias(static fn (string $text): string => 'K{'.$text.'}');
    });

    it('sanitizes every setting', function (): void {
        $new = ['url' => '<b>/feed</b>', 'title' => 'Hi', 'items' => '7', 'show_image' => '1', 'show_summary' => '1', 'show_author' => '1', 'show_date' => '1', 'text' => 'Blurb'];

        expect((new UAMS_RSS)->update($new, []))->toBe([
            'url' => 'R{/feed}', 'title' => 'T{Hi}', 'items' => 7, 'show_image' => 1, 'show_summary' => 1,
            'show_author' => 1, 'show_date' => true, 'text' => 'K{Blurb}',
        ]);
    });

    it('falls back to the defaults for missing settings', function (): void {
        expect((new UAMS_RSS)->update([], []))->toBe([
            'url' => 'R{}', 'title' => 'T{}', 'items' => 10, 'show_image' => 0, 'show_summary' => 0,
            'show_author' => 0, 'show_date' => false, 'text' => 'K{}',
        ]);
    });

    it('keeps the blurb unfiltered for a user who may post unfiltered html', function (): void {
        when('current_user_can')->alias(static fn (string $capability): bool => $capability === 'unfiltered_html');

        expect((new UAMS_RSS)->update(['text' => '<script>x</script>'], [])['text'])->toBe('<script>x</script>')
            ->and((new UAMS_RSS)->update([], [])['text'])->toBe('');
    });
});

describe('form', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('prints the stored settings', function (): void {
        $instance = ['title' => 'Hi', 'text' => 'Blurb', 'url' => '/feed', 'items' => '7', 'show_date' => true];

        $html = squishWidgetHtml(render(fn () => (new UAMS_RSS)->form($instance)));

        expect($html)->toContain('name="A{name-title}" type="text" value="A{Hi}"')
            ->and($html)->toContain('name="A{name-text}">X{Blurb}</textarea>')
            ->and($html)->toContain('name="A{name-url}" type="text" value="U{/feed}"')
            ->and($html)->toContain("<option value='7' SEL>7</option>")
            ->and(substr_count($html, 'SEL'))->toBe(1)
            ->and(substr_count($html, '<option '))->toBe(20)
            ->and($html)->toContain('type="checkbox" id="A{id-show_date}" name="A{name-show_date}" CHK />')
            ->and($html)->toContain('Give the feed a title (optional):')
            ->and($html)->toContain('Featured blurb:')
            ->and($html)->toContain('Enter the RSS feed URL here:')
            ->and($html)->toContain('Number of items to display:')
            ->and($html)->toContain('Show the date?');
    });

    it('prints the defaults without settings', function (): void {
        $html = squishWidgetHtml(render(fn () => (new UAMS_RSS)->form([])));

        expect($html)->toContain("<option value='10' SEL>10</option>")
            ->and($html)->toContain('type="checkbox" id="A{id-show_date}" name="A{name-show_date}" CHK />');
    });

    it('selects ten items for a count outside one to twenty', function (mixed $items): void {
        $html = render(fn () => (new UAMS_RSS)->form(['items' => $items]));

        expect($html)->toContain("<option value='10' SEL>10</option>")
            ->and(substr_count($html, 'SEL'))->toBe(1);
    })->with(['zero' => [0], 'negative' => [-3], 'twenty-one' => [21]]);

    it('selects the edges of the range', function (int $items): void {
        $html = render(fn () => (new UAMS_RSS)->form(['items' => $items]));

        expect($html)->toContain("<option value='{$items}' SEL>");
    })->with(['one' => [1], 'twenty' => [20]]);

    it('leaves the date box unchecked when the date is off', function (): void {
        $html = render(fn () => (new UAMS_RSS)->form(['show_date' => false]));

        expect($html)->not->toContain('CHK');
    });
});

describe('widget', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('prints the title, the blurb and the shortcode for the feed', function (): void {
        expectApplied('widget_title')->once()->with('Hi');

        $instance = ['title' => 'Hi', 'text' => 'Blurb', 'url' => '/feed', 'items' => '3', 'show_date' => true];

        $html = render(fn () => (new UAMS_RSS)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>', 'before_title' => '<h2>', 'after_title' => '</h2>'], $instance));

        expect($html)->toBe('<aside><span></span><h2>H{Hi}</h2><div class="featured">Blurb</div>D{[rss url="/feed" number="3" show_date="true" has_blurb="true"]}</aside>');
    });

    it('uses the default title wrappers and no wrappers around the widget', function (): void {
        $html = render(fn () => (new UAMS_RSS)->widget([], ['title' => 'Hi']));

        expect($html)->toBe('<span></span><h3>H{Hi}</h3>D{[rss url="" number="10" show_date="false" has_blurb="false"]}');
    });

    it('prints no title and no blurb when they are empty', function (): void {
        $html = render(fn () => (new UAMS_RSS)->widget(['before_widget' => '', 'after_widget' => ''], ['url' => '/feed', 'items' => 0]));

        expect($html)->toBe('<span></span>D{[rss url="/feed" number="10" show_date="false" has_blurb="false"]}');
    });
});

describe('shortcode', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('prints nothing without a url or in a feed', function (): void {
        stubFeed([rssItem()]);

        expect((new UAMS_RSS)->uams_rss_shortcode([]))->toBe('')
            ->and((new UAMS_RSS)->uams_rss_shortcode(['url' => '']))->toBe('');

        when('is_feed')->justReturn(true);

        expect((new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed']))->toBe('');
    });

    it('asks for the feed with the decoded url and the number of items', function (): void {
        $seen = stubFeed([]);

        (new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed', 'number' => '3']);

        expect($seen['url'])->toBe('D/feed')
            ->and($seen['range'])->toBe([0, 3])
            ->and(shortcodeAttsNames())->toBe(['']);
    });

    it('asks for five items by default', function (): void {
        $seen = stubFeed([]);

        (new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed']);

        expect($seen['range'])->toBe([0, 5]);
    });

    it('prints an item with its image, title and age, and the more link', function (): void {
        stubFeed([rssItem(['enclosure' => (object) ['link' => '/pic.jpg']])]);

        expect((new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed']))->toBe(
            '<ul class="uams-widget-rss"><li>'
            ."<a class='widget-thumbnail' href='U{/one}' title='A{Item one}'><img src='U{/pic.jpg}' alt='A{Item one}' /></a>"
            ."<a class='widget-link' href='U{/one}' title='A{Item one}'>A{Item one}<span>diff".strtotime('2026-01-02').' ago</span></a>'
            .'</li></ul><a class="widget-more more" href="U{/feed-home}">More</a>'
        );
    });

    it('starts with the blurb marker when the widget has a blurb', function (mixed $value, string $expected): void {
        stubFeed([]);

        expect((new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed', 'has_blurb' => $value]))->toStartWith($expected);
    })->with([
        'true' => ['true', '<span></span><ul'],
        'boolean true' => [true, '<span></span><ul'],
        'false' => ['false', '<ul'],
        'one' => ['1', '<span></span><ul'],
    ]);

    it('uses the more url given in place of the feed home', function (): void {
        stubFeed([]);

        $html = (new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed', 'more' => '/archive']);

        expect($html)->toContain('<a class="widget-more more" href="U{/archive}">More</a>');
    });

    it('leaves out the more link when it is off or has no url', function (): void {
        stubFeed([], '');
        $rss = new UAMS_RSS;

        expect($rss->uams_rss_shortcode(['url' => '/feed']))->not->toContain('widget-more');

        stubFeed([]);

        expect($rss->uams_rss_shortcode(['url' => '/feed', 'show_more' => 'false']))->not->toContain('widget-more');
    });

    it('leaves out the image for an item without an enclosure link or when images are off', function (): void {
        $rss = new UAMS_RSS;

        stubFeed([rssItem()]);
        expect($rss->uams_rss_shortcode(['url' => '/feed']))->not->toContain('widget-thumbnail');

        stubFeed([rssItem(['enclosure' => (object) ['other' => 1]])]);
        expect($rss->uams_rss_shortcode(['url' => '/feed']))->not->toContain('widget-thumbnail');

        stubFeed([rssItem(['enclosure' => (object) ['link' => '']])]);
        expect($rss->uams_rss_shortcode(['url' => '/feed']))->not->toContain('widget-thumbnail');

        stubFeed([rssItem(['enclosure' => (object) ['link' => '/pic.jpg']])]);
        expect($rss->uams_rss_shortcode(['url' => '/feed', 'show_image' => 'false']))->not->toContain('widget-thumbnail');
    });

    it('prints no age when dates are off or the item has none', function (): void {
        $rss = new UAMS_RSS;

        stubFeed([rssItem()]);
        expect($rss->uams_rss_shortcode(['url' => '/feed', 'show_date' => 'false']))->toContain('A{Item one}<span></span></a>');

        stubFeed([rssItem(['date' => null])]);
        expect($rss->uams_rss_shortcode(['url' => '/feed']))->toContain('A{Item one}<span></span></a>');

        stubFeed([rssItem(['date' => ''])]);
        expect($rss->uams_rss_shortcode(['url' => '/feed']))->toContain('A{Item one}<span></span></a>');
    });

    it('prints the description when asked, after a line break only when dates show', function (): void {
        $rss = new UAMS_RSS;
        $age = 'diff'.strtotime('2026-01-02').' ago';

        stubFeed([rssItem()]);
        expect($rss->uams_rss_shortcode(['url' => '/feed', 'show_desc' => 'true']))
            ->toContain("<span>{$age}<br style='line-height:2;'>About one</span>")
            ->and($rss->uams_rss_shortcode(['url' => '/feed', 'show_desc' => 'true', 'show_date' => 'false']))
            ->toContain('<span>About one</span>')
            ->and($rss->uams_rss_shortcode(['url' => '/feed', 'show_desc' => 'false']))
            ->not->toContain('About one');
    });

    it('prints a list item for each item in the feed', function (): void {
        stubFeed([rssItem(), rssItem(['title' => 'Two', 'link' => '/two'])]);

        $html = (new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed']);

        expect(substr_count($html, '<li>'))->toBe(2)
            ->and($html)->toContain("href='U{/two}' title='A{Two}'>A{Two}<span>");
    });

    it('prints nothing but the marker when the feed cannot be fetched', function (): void {
        when('fetch_feed')->justReturn(new WP_Error);

        expect((new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed']))->toBe('')
            ->and((new UAMS_RSS)->uams_rss_shortcode(['url' => '/feed', 'has_blurb' => 'true']))->toBe('<span></span>');
    });
});
