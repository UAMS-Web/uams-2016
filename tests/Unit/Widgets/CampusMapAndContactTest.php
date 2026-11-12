<?php

/**
 * The campus map widget and the contact list widget, with WordPress mocked.
 *
 * @see widgets/class.uams-campus-map.php
 * @see widgets/class.uams-contact.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubEscaping();
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
    when('sanitize_email')->alias(static fn (string $text): string => 'E{'.$text.'}');
    when('antispambot')->alias(static fn (string $text): string => 'AS{'.$text.'}');
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore cast.int
    when('absint')->alias(static fn (mixed $value): int => abs((int) $value));
    when('wp_parse_args')->alias(static fn (array $args, array $defaults): array => array_merge($defaults, $args));
    when('selected')->alias(static function (mixed $selected, mixed $current): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore cast.string, cast.string
        echo (string) preg_replace('/^A\{(.*)\}$/', '$1', (string) $selected) === (string) $current ? ' SEL' : '';
    });
});

describe('UAMS_Campus_Map', function (): void {
    it('registers the widget when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-campus-map.php'))->toBe(['UAMS_Campus_Map']);
    });

    it('describes the widget', function (): void {
        $widget = new UAMS_Campus_Map;

        expect($widget->id_base)->toBe('uams-campus-map')
            ->and($widget->name)->toBe('UAMS Campus Map')
            ->and($widget->widget_options)->toBe([
                'description' => 'Show your building on the UAMS campus map.',
                'classname' => 'uams-widget-campus-map',
            ]);
    });

    it('prints the map for the building between the widget wrappers', function (): void {
        $html = render(fn () => (new UAMS_Campus_Map)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], ['buildingCode' => '135']));

        expect($html)->toStartWith('<aside><div class="uams-campus-map-widget">')
            ->and($html)->toContain('src="U{//maps.uams.edu/full-screen/?marker=135}"')
            ->and($html)->toContain('<a href="U{https://maps.uams.edu/maps/fullscreen/4/?marker=135}" target="_blank" rel="noopener noreferrer">View larger</a>')
            ->and($html)->toContain('jQuery(document).ready')
            ->and($html)->toEndWith('</script></aside>')
            ->and($html)->not->toContain('<h3');
    });

    it('prints the title before the map, with the wrappers it was given or the default ones', function (array $args, string $before, string $after): void {
        $html = render(fn () => (new UAMS_Campus_Map)->widget($args + ['before_widget' => '', 'after_widget' => ''], ['title' => 'Find us']));

        expect($html)->toStartWith($before.'H{Find us}'.$after.'<div class="uams-campus-map-widget">');
    })->with([
        'given' => [['before_title' => '<h2>', 'after_title' => '</h2>'], '<h2>', '</h2>'],
        'default' => [[], '<h3 class="widget-title">', '</h3>'],
    ]);

    it('applies the title and building code filters', function (): void {
        expectApplied('widget_title')->once()->with('Find us');
        expectApplied('uams_campus_map_buildingcode')->once()->with('135');

        render(fn () => (new UAMS_Campus_Map)->widget(['before_widget' => '', 'after_widget' => ''], ['title' => 'Find us', 'buildingCode' => '135']));
    });

    it('prints a map without a building when none is set', function (): void {
        $html = render(fn () => (new UAMS_Campus_Map)->widget(['before_widget' => '', 'after_widget' => ''], []));

        expect($html)->toContain('src="U{//maps.uams.edu/full-screen/?marker=}"')
            ->and($html)->not->toContain('widget-title');
    });

    it('sanitizes the title and the building on update', function (array $new, array $expected): void {
        expect((new UAMS_Campus_Map)->update($new, []))->toBe($expected);
    })->with([
        'both' => [['title' => 'A', 'buildingCode' => '5'], ['title' => 'T{A}', 'buildingCode' => 'T{5}']],
        'neither' => [[], ['title' => 'T{}', 'buildingCode' => 'T{}']],
    ]);

    it('prints the form with the stored values and the building selected', function (): void {
        $html = squishWidgetHtml(render(fn () => (new UAMS_Campus_Map)->form(['title' => 'Find us', 'buildingCode' => '135'])));

        expect($html)->toContain('name="A{name-title}" type="text" value="A{Find us}"')
            ->and($html)->toContain('<option value="135" SEL>Hospital (HOSP)</option>')
            ->and(substr_count($html, ' SEL'))->toBe(1)
            ->and(substr_count($html, '<option '))->toBe(45);
    });

    it('prints the form without a selection when nothing is stored', function (): void {
        $html = squishWidgetHtml(render(fn () => (new UAMS_Campus_Map)->form([])));

        expect($html)->toContain('name="A{name-title}" type="text" value=""')
            ->and($html)->not->toContain(' SEL');
    });
});

describe('UAMS_Widget_Contact', function (): void {
    it('registers the widget and the scripts when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-contact.php'))->toBe(['UAMS_Widget_Contact'])
            ->and(has_action('admin_enqueue_scripts', ['UAMS_Widget_Contact', 'scripts']))->toBe(10);
    });

    it('describes the widget', function (): void {
        $widget = new UAMS_Widget_Contact;

        expect($widget->id_base)->toBe('uams-contact-list')
            ->and($widget->name)->toBe('Contact list')
            ->and($widget->widget_options)->toBe([
                'description' => 'Display important contact information',
                'classname' => 'contact-widget',
            ]);
    });

    it('enqueues the contact script and the media library', function (): void {
        $calls = [];
        when('get_template_directory_uri')->justReturn('https://site.test/theme');
        when('wp_enqueue_script')->alias(static function (mixed ...$args) use (&$calls): void {
            $calls[] = $args;
        });
        when('wp_enqueue_media')->alias(static function () use (&$calls): void {
            $calls[] = ['media'];
        });

        UAMS_Widget_Contact::scripts();

        expect($calls)->toBe([
            ['contact-card', 'https://site.test/theme/assets/admin/js/widgets/uams.contact-widget.js', ['jquery'], null, true],
            ['jquery-ui-autocomplete'],
            ['media'],
        ]);
    });

    describe('widget', function (): void {
        it('lists each person that has a name', function (): void {
            $instance = [
                'title' => 'Team',
                'amount' => 3,
                'person_name1' => 'Ann', 'person_title1' => 'Chair', 'person_phone1' => '(501) 555-0100', 'person_email1' => 'ann@uams.edu',
                'person_name2' => '', 'person_title2' => 'Ghost', 'person_phone2' => '1', 'person_email2' => 'g@uams.edu',
                'person_name3' => 'Bo',
            ];

            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Contact)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], $instance)));

            expect($html)->toBe(
                '<aside> <div class="contact-widget-inner"> <span> <h2 class="widgettitle">H{Team}</h2> </span> '
                .'<h3 class="person-name">H{Ann}</h3><p class="person-title">H{Chair}</p>'
                .'<p><a href="A{tel:5015550100}" class="person-phone">H{(501) 555-0100}</a></p>'
                .'<a href="A{mailto:AS{ann@uams.edu}}" class="person-email">H{AS{ann@uams.edu}}</a>'
                .'<h3 class="person-name">H{Bo}</h3> </div><!-- /.contact-widget-inner --> </aside>'
            );
        });

        it('keeps a plus sign in a phone number link', function (): void {
            $html = render(fn () => (new UAMS_Widget_Contact)->widget(['before_widget' => '', 'after_widget' => ''], ['person_name1' => 'Ann', 'person_phone1' => '+1 (501) x 5']));

            expect($html)->toContain('href="A{tel:+15015}"');
        });

        it('uses the default title and one person without an amount', function (): void {
            expectApplied('widget_title')->once()->with('List');

            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Contact)->widget(['before_widget' => '', 'after_widget' => ''], ['person_name1' => 'Ann', 'person_name2' => 'Bo'])));

            expect($html)->toContain('H{List}')
                ->and($html)->toContain('H{Ann}')
                ->and($html)->not->toContain('H{Bo}');
        });

        it('leaves out the title when it is empty', function (): void {
            when('apply_filters')->justReturn('');

            $html = render(fn () => (new UAMS_Widget_Contact)->widget(['before_widget' => '', 'after_widget' => ''], ['title' => 'Team']));

            expect($html)->not->toContain('widgettitle');
        });

        it('leaves out the empty details of a person', function (): void {
            $html = render(fn () => (new UAMS_Widget_Contact)->widget(['before_widget' => '', 'after_widget' => ''], ['person_name1' => 'Ann']));

            expect($html)->toContain('H{Ann}')
                ->and($html)->not->toContain('person-title')
                ->and($html)->not->toContain('person-phone')
                ->and($html)->not->toContain('person-email');
        });
    });

    describe('update', function (): void {
        it('sanitizes the title and keeps one person by default', function (): void {
            $result = (new UAMS_Widget_Contact)->update(['title' => 'Team'], []);

            expect($result)->toBe(['title' => 'T{Team}', 'amount' => 1]);
        });

        it('reorders by the order field and sanitizes each field', function (): void {
            $new = [
                'title' => 'T', 'amount' => 2, 'order' => 'widget-2,widget-1',
                'person_name1' => 'A', 'person_title1' => 'At', 'person_phone1' => 'Ap', 'person_email1' => 'Ae',
                'person_name2' => 'B', 'person_title2' => 'Bt', 'person_phone2' => 'Bp', 'person_email2' => 'Be',
            ];

            expect((new UAMS_Widget_Contact)->update($new, []))->toBe([
                'title' => 'T{T}',
                'person_name1' => 'T{B}', 'person_title1' => 'T{Bt}', 'person_phone1' => 'T{Bp}', 'person_email1' => 'E{Be}',
                'person_name2' => 'T{A}', 'person_title2' => 'T{At}', 'person_phone2' => 'T{Ap}', 'person_email2' => 'E{Ae}',
                'amount' => 2,
            ]);
        });

        it('takes an order entry without a dash as the number', function (): void {
            $new = ['order' => '2,1', 'person_name1' => 'A', 'person_name2' => 'B'];

            $result = (new UAMS_Widget_Contact)->update($new, []);

            expect($result['person_name1'])->toBe('T{B}')
                ->and($result['person_name2'])->toBe('T{A}');
        });

        it('uses the dash that is last in an order entry', function (): void {
            $result = (new UAMS_Widget_Contact)->update(['order' => 'a-b-1', 'person_name1' => 'A'], []);

            expect($result['person_name1'])->toBe('T{A}');
        });

        it('sanitizes a missing field to nothing', function (): void {
            $result = (new UAMS_Widget_Contact)->update(['order' => '1'], []);

            expect($result)->toBe([
                'title' => 'T{}',
                'person_name1' => 'T{}', 'person_title1' => 'T{}', 'person_phone1' => 'T{}', 'person_email1' => 'E{}',
                'amount' => 1,
            ]);
        });

        it('orders by position, dropping people marked for removal', function (): void {
            $new = [
                'amount' => 3, 'position1' => '2', 'position2' => '1', 'position3' => '-1',
                'person_name1' => 'A', 'person_name2' => 'B', 'person_name3' => 'C',
            ];

            $result = (new UAMS_Widget_Contact)->update($new, []);

            expect($result['person_name1'])->toBe('T{B}')
                ->and($result['person_name2'])->toBe('T{A}')
                ->and($result)->not->toHaveKey('person_name3')
                ->and($result['amount'])->toBe(2);
        });

        it('adds a new person last when asked', function (): void {
            $new = [
                'amount' => 2, 'new_person' => '1', 'position1' => '2', 'position2' => '1',
                'person_name1' => 'A', 'person_name2' => 'B', 'person_name3' => 'N',
            ];

            $result = (new UAMS_Widget_Contact)->update($new, []);

            expect($result['person_name1'])->toBe('T{B}')
                ->and($result['person_name2'])->toBe('T{A}')
                ->and($result['person_name3'])->toBe('T{N}')
                ->and($result['amount'])->toBe(3);
        });

        it('does not add a new person without positions to order', function (): void {
            $new = ['amount' => 1, 'new_person' => '1', 'position1' => '-1', 'person_name1' => 'A'];

            expect((new UAMS_Widget_Contact)->update($new, []))->toBe(['title' => 'T{}', 'amount' => 0]);
        });

        it('keeps nobody when there is neither a position nor an order', function (): void {
            expect((new UAMS_Widget_Contact)->update(['amount' => 2, 'person_name1' => 'A'], []))->toBe(['title' => 'T{}', 'amount' => 2]);
        });
    });

    describe('form', function (): void {
        it('prints a block for each person and the controls', function (): void {
            $instance = [
                'title' => 'Team', 'amount' => 2,
                'person_name1' => 'Ann', 'person_title1' => 'Chair', 'person_phone1' => '555', 'person_email1' => 'a@uams.edu',
            ];

            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Contact)->form($instance)));

            expect($html)->toContain('name="A{name-title}" type="text" value="A{Team}"')
                ->and($html)->toContain('<div id="A{id-1}" class="list-item"> <h5 class="moving-handle"><span class="number">1</span>. <span class="person-title">A{Ann}</span>')
                ->and($html)->toContain('<div id="A{id-2}" class="list-item"> <h5 class="moving-handle"><span class="number">2</span>. <span class="person-title">A{}</span>')
                ->and($html)->toContain('id="A{id-person_name1}" name="A{name-person_name1}" type="text" value="A{Ann}"')
                ->and($html)->toContain('id="A{id-person_title1}" name="A{name-person_title1}" type="text" value="A{Chair}"')
                ->and($html)->toContain('id="A{id-person_phone1}" name="A{name-person_phone1}" type="text" value="A{555}"')
                ->and($html)->toContain('id="A{id-person_email1}" name="A{name-person_email1}" type="text" value="A{a@uams.edu}"')
                ->and($html)->toContain('id="A{id-person_name2}" name="A{name-person_name2}" type="text" value="A{}"')
                ->and($html)->toContain('id="A{id-amount}" class="amount" name="A{name-amount}" value="2"')
                ->and($html)->toContain('id="A{id-order}" class="order" name="A{name-order}" value="A{1,2}"');
        });

        it('prints one empty person by default', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Contact)->form([])));

            expect(substr_count($html, 'class="list-item"'))->toBe(1)
                ->and($html)->toContain('value="1" />')
                ->and($html)->toContain('value="A{1}"')
                ->and($html)->toContain('name="A{name-title}" type="text" value="A{}"');
        });

        it('treats an amount of zero as one person', function (): void {
            $html = render(fn () => (new UAMS_Widget_Contact)->form(['amount' => 0]));

            expect(substr_count($html, 'class="list-item"'))->toBe(1);
        });
    });
});
