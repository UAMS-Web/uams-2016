<?php

/**
 * The small widgets: the sidebar registration, the intro text widget and the social icons widget, with
 * WordPress mocked.
 *
 * @see widgets/class.uams-sidebar.php
 * @see widgets/class.uams-intro.php
 * @see widgets/class.uams-social.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubEscaping();
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
    when('esc_url_raw')->alias(static fn (string $url): string => 'R{'.$url.'}');
});

describe('UAMS_Sidebar', function (): void {
    it('registers the sidebar on widgets_init when the file loads', function (): void {
        expect(loadSidebarWidgetFile())->toBeTrue();
    });

    it('registers one sidebar', function (): void {
        $received = [];
        when('register_sidebar')->alias(static function (array $args) use (&$received): void {
            $received[] = $args;
        });

        $sidebar = new UAMS_Sidebar;
        $sidebar->register_sidebar();

        expect(has_action('widgets_init', [$sidebar, 'register_sidebar']))->toBe(10)
            ->and($received)->toBe([[
                'name' => 'Sidebar',
                'id' => 'sidebar',
                'description' => 'Right column widgets',
                'before_widget' => '<div role="navigation" aria-label="sidebar_navigation" id="%1$s" class="widget %2$s">',
                'after_widget' => '</div>',
            ]]);
    });
});

describe('UAMS_Intro_Text', function (): void {
    it('registers the widget when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-intro.php'))->toBe(['UAMS_Intro_Text']);
    });

    it('registers the shortcode and describes the widget', function (): void {
        $shortcodes = recordShortcodes();

        $widget = new UAMS_Intro_Text;

        expect($shortcodes->getArrayCopy())->toBe([['intro', [$widget, 'intro_shortcode']]])
            ->and($widget->id_base)->toBe('uams-intro-text')
            ->and($widget->name)->toBe('UAMS Intro Text')
            ->and($widget->widget_options)->toBe(['description' => 'Italicized block of intro text.', 'classname' => 'uams-intro-text']);
    });

    it('prints the intro text between the widget wrappers', function (array $args, string $expected): void {
        when('add_shortcode')->justReturn(true);

        $html = render(fn () => (new UAMS_Intro_Text)->widget($args, ['introContent' => 'Hello <b>']));

        expect($html)->toBe($expected);
    })->with([
        'wrappers' => [['before_widget' => '<aside>', 'after_widget' => '</aside>'], '<aside><p class="intro">H{Hello <b>}</p></aside>'],
        'no wrappers' => [[], '<p class="intro">H{Hello <b>}</p>'],
    ]);

    it('prints nothing without intro text', function (array $instance): void {
        when('add_shortcode')->justReturn(true);

        $html = render(fn () => (new UAMS_Intro_Text)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], $instance));

        expect($html)->toBe('');
    })->with([
        'none' => [[]],
        'empty' => [['introContent' => '']],
        'zero' => [['introContent' => '0']],
    ]);

    it('sanitizes the intro text on update', function (array $new, string $expected): void {
        when('add_shortcode')->justReturn(true);

        expect((new UAMS_Intro_Text)->update($new, []))->toBe(['introContent' => $expected]);
    })->with([
        'text' => [['introContent' => 'Hi'], 'T{Hi}'],
        'missing' => [[], 'T{}'],
    ]);

    it('prints the form field', function (array $instance, string $value): void {
        when('add_shortcode')->justReturn(true);

        $html = render(fn () => (new UAMS_Intro_Text)->form($instance));

        expect(preg_replace('/\s+/', ' ', $html))->toBe(' <p> <label for="A{id-introContent}">Intro text:</label> <input class="widefat" id="A{id-introContent}" name="A{name-introContent}" type="text" value="'.$value.'" /> </p> ');
    })->with([
        'a value' => [['introContent' => 'Hi'], 'A{Hi}'],
        'no value' => [[], ''],
    ]);

    it('renders the shortcode', function (mixed $content, string $expected): void {
        when('add_shortcode')->justReturn(true);

        expect((new UAMS_Intro_Text)->intro_shortcode([], $content))->toBe($expected);
    })->with([
        'text' => [' Hello ', '<p class="intro">H{Hello}</p>'],
        'none' => [null, ''],
        'blank' => ['  ', ''],
        'zero' => ['0', ''],
    ]);
});

describe('UAMS_Social_Icons', function (): void {
    it('registers the widget when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-social.php'))->toBe(['UAMS_Social_Icons']);
    });

    it('describes the widget', function (): void {
        $widget = new UAMS_Social_Icons;

        expect($widget->id_base)->toBe('uams-widget-social')
            ->and($widget->name)->toBe('UAMS Social Icons')
            ->and($widget->widget_options)->toBe([
                'description' => 'Display social media icons with links to the appropriate account',
                'classname' => 'social-widget',
            ]);
    });

    it('sanitizes every field on update', function (): void {
        $new = ['title' => 'Hi', 'facebook' => 'f', 'twitter' => 't', 'instagram' => 'i', 'youtube' => 'y', 'linkedin' => 'l', 'pinterest' => 'p'];

        expect((new UAMS_Social_Icons)->update($new, []))->toBe([
            'title' => 'T{Hi}',
            'facebook' => 'R{f}',
            'twitter' => 'R{t}',
            'instagram' => 'R{i}',
            'youtube' => 'R{y}',
            'linkedin' => 'R{l}',
            'pinterest' => 'R{p}',
        ])
            ->and((new UAMS_Social_Icons)->update([], []))->toBe([
                'title' => 'T{}', 'facebook' => 'R{}', 'twitter' => 'R{}', 'instagram' => 'R{}',
                'youtube' => 'R{}', 'linkedin' => 'R{}', 'pinterest' => 'R{}',
            ]);
    });

    it('prints a field for each network, with the stored values', function (): void {
        $instance = ['title' => 'Find us', 'facebook' => 'fb', 'twitter' => 'tw', 'instagram' => 'ig', 'youtube' => 'yt', 'linkedin' => 'li', 'pinterest' => 'pi'];

        $html = preg_replace('/\s+/', ' ', render(fn () => (new UAMS_Social_Icons)->form($instance)));

        expect($html)->toContain('<label for="A{id-title}">Title: <small><b>Appears above the icons</b></small></label>')
            ->and($html)->toContain('name="A{name-title}" type="text" value="A{Find us}"')
            ->and($html)->toContain('<label for="A{id-facebook}">Facebook URI:</label>')
            ->and($html)->toContain('name="A{name-facebook}" type="text" value="U{fb}"')
            ->and($html)->toContain('name="A{name-twitter}" type="text" value="U{tw}"')
            ->and($html)->toContain('<label for="A{id-instagram}">Instagram URI:</label>')
            ->and($html)->toContain('name="A{name-instagram}" type="text" value="U{ig}"')
            ->and($html)->toContain('<label for="A{id-youtube}">YouTube URI:</label>')
            ->and($html)->toContain('name="A{name-youtube}" type="text" value="U{yt}"')
            ->and($html)->toContain('<label for="A{id-linkedin}">LinkedIn URI:</label>')
            ->and($html)->toContain('name="A{name-linkedin}" type="text" value="U{li}"')
            ->and($html)->toContain('<label for="A{id-pinterest}">Pinterest URI:</label>')
            ->and($html)->toContain('name="A{name-pinterest}" type="text" value="U{pi}"');
    });

    it('prints the default title and empty links without an instance', function (): void {
        $html = (string) preg_replace('/\s+/', ' ', render(fn () => (new UAMS_Social_Icons)->form([])));

        expect($html)->toContain('type="text" value="Connect with us:"')
            ->and(substr_count($html, 'value=""'))->toBe(6);
    });

    it('prints the title and a link for each network that is set', function (): void {
        $instance = ['title' => 'Find us', 'facebook' => 'fb', 'twitter' => 'tw', 'instagram' => 'ig', 'youtube' => 'yt', 'linkedin' => 'li', 'pinterest' => 'pi'];

        $html = preg_replace('/\s+/', ' ', render(fn () => (new UAMS_Social_Icons)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], $instance)));

        expect($html)->toBe(
            '<aside><h2 class="widgettitle">H{Find us}</h2><span class="udub-slant"><span></span></span> '
            .'<nav aria-label="social networks"> <ul class="widget-social"> '
            .'<li><a class="facebook" title="Facebook" href="U{fb}">Facebook</a></li> '
            .'<li><a class="twitter" title="Twitter" href="U{tw}">Twitter</a></li> '
            .'<li><a class="instagram" title="Instagram" href="U{ig}">Instagram</a></li> '
            .'<li><a class="youtube" title="YouTube" href="U{yt}">YouTube</a></li> '
            .'<li><a class="linkedin" title="LinkedIn" href="U{li}">LinkedIn</a></li> '
            .'<li><a class="pinterest" title="Pinterest" href="U{pi}">Pinterest</a></li> '
            .'</ul> </nav> </aside>'
        );
    });

    it('leaves out the title and the networks that are not set', function (): void {
        $html = preg_replace('/\s+/', ' ', render(fn () => (new UAMS_Social_Icons)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], ['youtube' => 'yt'])));

        expect($html)->toBe('<aside> <nav aria-label="social networks"> <ul class="widget-social"> <li><a class="youtube" title="YouTube" href="U{yt}">YouTube</a></li> </ul> </nav> </aside>');
    });

    it('prints each network alone', function (string $network, string $label): void {
        $html = render(fn () => (new UAMS_Social_Icons)->widget(['before_widget' => '', 'after_widget' => ''], [$network => 'x']));

        expect($html)->toContain('class="'.$network.'" title="'.$label.'"')
            ->and(substr_count($html, '<li>'))->toBe(1);
    })->with([
        'facebook' => ['facebook', 'Facebook'],
        'twitter' => ['twitter', 'Twitter'],
        'instagram' => ['instagram', 'Instagram'],
        'youtube' => ['youtube', 'YouTube'],
        'linkedin' => ['linkedin', 'LinkedIn'],
        'pinterest' => ['pinterest', 'Pinterest'],
    ]);
});
