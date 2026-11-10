<?php

/**
 * UAMS_Documentation_Dashboard_Widget, the dashboard widget that links to the documentation, with
 * WordPress mocked.
 *
 * @see setup/class.uams-documentation-dashboard-widget.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-documentation-dashboard-widget.php');
    $GLOBALS['wp_meta_boxes'] = [];
});

afterEach(function (): void {
    unset($GLOBALS['wp_meta_boxes']);
});

it('adds the widget on the dashboard setup', function (): void {
    $widget = new UAMS_Documentation_Dashboard_Widget;

    expect(has_action('wp_dashboard_setup', [$widget, 'uams_add_documentation_dashboard_widget']))->toBe(10);
});

describe('uams_add_documentation_dashboard_widget', function (): void {
    beforeEach(function (): void {
        $this->calls = [];
        $calls = &$this->calls;
        when('wp_add_dashboard_widget')->alias(static function (string $id, string $title, callable $callback) use (&$calls): void {
            $calls[] = ['add', $id, $title, $callback];
        });
        when('remove_meta_box')->alias(static function (string $id, string $screen, string $context) use (&$calls): void {
            $calls[] = ['remove', $id, $screen, $context];
        });
    });

    it('registers the widget and removes the default boxes', function (): void {
        $widget = new UAMS_Documentation_Dashboard_Widget;

        $widget->uams_add_documentation_dashboard_widget();

        expect($this->calls)->toBe([
            ['add', 'uams-documentation-dashboard-widget', 'Documentation and FAQs', [$widget, 'uams_documentation_html']],
            ['remove', 'dashboard_quick_press', 'dashboard', 'side'],
            ['remove', 'dashboard_primary', 'dashboard', 'side'],
            ['remove', 'dashboard_secondary', 'dashboard', 'side'],
            ['remove', 'dashboard_recent_drafts', 'dashboard', 'side'],
            ['remove', 'dashboard_incoming_links', 'dashboard', 'normal'],
        ])->and($GLOBALS['wp_meta_boxes'])->toBe([]);
    });

    it('moves the widget from the main column to the side column', function (): void {
        $box = ['id' => 'uams-documentation-dashboard-widget', 'title' => 'Documentation and FAQs'];
        $GLOBALS['wp_meta_boxes'] = ['dashboard' => ['normal' => ['core' => ['uams-documentation-dashboard-widget' => $box, 'other' => ['id' => 'other']]]]];

        (new UAMS_Documentation_Dashboard_Widget)->uams_add_documentation_dashboard_widget();

        expect($GLOBALS['wp_meta_boxes'])->toBe(['dashboard' => [
            'normal' => ['core' => ['other' => ['id' => 'other']]],
            'side' => ['core' => ['uams-documentation-dashboard-widget' => $box]],
        ]]);
    });
});

it('prints the documentation links and the two feeds', function (): void {
    $shortcodes = [];
    when('do_shortcode')->alias(static function (string $content) use (&$shortcodes): string {
        $shortcodes[] = $content;

        return 'D'.count($shortcodes);
    });

    $html = render(fn () => (new UAMS_Documentation_Dashboard_Widget)->uams_documentation_html());

    expect($shortcodes)->toBe([
        '[rss url="http://www.washington.edu/marketing/topic/wp-documentation/feed/" number=3 title="Latest Tutorials" heading="h2" show_image="false" show_date="false" show_more="false"]',
        '[rss url="http://www.washington.edu/marketing/topic/wordpress/feed" number=3 title="Web Team Updates" heading="h2" show_image="false" show_date="false"]',
    ])->and($html)->toContain('<h2>Top Tutorials</h2>')
        ->and($html)->toContain('<p>D1</p>')
        ->and($html)->toContain('<p>D2</p>')
        ->and($html)->toContain('<h2>Stuck?</h2>')
        ->and(substr_count($html, '<li>'))->toBe(5);
});
