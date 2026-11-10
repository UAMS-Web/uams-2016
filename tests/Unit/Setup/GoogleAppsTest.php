<?php

/**
 * UAMS_GoogleApps, the calendar embed shortcode and the filter that turns a pasted iframe into it.
 *
 * @see setup/class.googleapps.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.googleapps.php');
    stubEscaping();
    when('shortcode_atts')->alias(static function (array $pairs, mixed $atts, string $shortcode = ''): array {
        $GLOBALS['uams_test_googleapps_shortcode'] = $shortcode;

        return array_merge($pairs, (array) $atts);
    });
});

it('registers the googleapps shortcode and the pre_kses filter', function (): void {
    $calls = recordShortcodes();
    $apps = new UAMS_GoogleApps;

    expect($calls->getArrayCopy())->toBe([['googleapps', [$apps, 'uams_google_calendar_shortcode']]])
        ->and(has_filter('pre_kses', [$apps, 'uams_google_calendar_embed_to_shortcode']))->toBe(10);
});

describe('the shortcode', function (): void {
    it('renders the calendar iframe', function (): void {
        $html = (new UAMS_GoogleApps)->uams_google_calendar_shortcode([
            'dir' => 'calendar/embed',
            'query' => 'src=a&b=1',
            'width' => 400,
            'height' => 300,
            'app' => 'cal',
        ]);

        expect($html)->toBe('<div class="googleapps-A{cal}"><iframe width="A{400}" height="A{300}" style="border:0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.google.com/calendar/embed?A{src=a&b=1}"></iframe></div>')
            ->and($GLOBALS['uams_test_googleapps_shortcode'])->toBe('googleapps');
    });

    it('uses the default size and app', function (): void {
        $html = (new UAMS_GoogleApps)->uams_google_calendar_shortcode(['dir' => 'calendar/embed']);

        expect($html)->toBe('<div class="googleapps-A{calendar}"><iframe width="A{620}" height="A{500}" style="border:0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.google.com/calendar/embed?A{}"></iframe></div>');
    });

    it('renders nothing for another directory', function (string $dir): void {
        expect((new UAMS_GoogleApps)->uams_google_calendar_shortcode(['dir' => $dir]))->toBe('');
    })->with(['none' => [''], 'other' => ['maps/embed']]);
});

describe('the pasted iframe', function (): void {
    $src = 'https://calendar.google.com/calendar/embed?src=abc&ctz=UTC';

    it('returns content that is empty or not a string unchanged', function (mixed $content): void {
        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))->toBe($content);
    })->with(['empty' => [''], 'null' => [null], 'array' => [['x']]]);

    it('returns content without an iframe or calendar link unchanged', function (): void {
        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode('<p>Plain text</p>'))->toBe('<p>Plain text</p>');
    });

    it('turns an iframe into the shortcode, capping the width', function () use ($src): void {
        $content = 'Before <iframe src="'.$src.'" width="800" height="400"></iframe> After';

        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))
            ->toBe('Before [googleapps domain="www" dir="calendar/embed" query="A{src=abc&ctz=UTC}" width="630" height="400"]After');
    });

    it('keeps a width under the cap', function () use ($src): void {
        $content = '<iframe width="500" src="'.$src.'" height="350"></iframe>';

        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))
            ->toBe('[googleapps domain="www" dir="calendar/embed" query="A{src=abc&ctz=UTC}" width="500" height="350"]');
    });

    it('defaults the size when the iframe gives none', function () use ($src): void {
        $content = '<iframe src="'.$src.'"></iframe>';

        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))
            ->toBe('[googleapps domain="www" dir="calendar/embed" query="A{src=abc&ctz=UTC}" width="630" height="500"]');
    });

    it('turns an escaped iframe into the shortcode', function () use ($src): void {
        $content = '&lt;iframe src="'.$src.'" width="700" height="450"&gt;&lt;/iframe&gt;&lt;br /&gt; tail';

        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))
            ->toBe('[googleapps domain="www" dir="calendar/embed" query="A{src=abc&ctz=UTC}" width="630" height="450"]tail');
    });

    it('leaves an iframe that is not a Google embed', function (): void {
        $content = '<iframe src="https://example.com/x?y=1"></iframe>';

        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode($content))->toBe($content);
    });

    it('converts a match without a query to an empty one', function (): void {
        expect((new UAMS_GoogleApps)->uams_google_calendar_embed_to_shortcode_callback(['<iframe>']))
            ->toBe('[googleapps domain="www" dir="calendar/embed" query="A{}" width="630" height="500"]');
    });
});
