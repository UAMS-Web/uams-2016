<?php

/**
 * The widget visibility admin: the `widget_conditions_options` AJAX request, with WordPress mocked.
 *
 * @see widgets/class.uams-widget-visibility.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    // class.uams-widget-visibility.php constructs the class when it is loaded, in the admin.
    when('is_admin')->justReturn(true);

    require_once __DIR__.'/../../widgets/class.uams-widget-visibility.php';
});

afterEach(function (): void {
    unset($_REQUEST['major'], $_REQUEST['minor']);
});

it('passes the nonce of the options request to the admin script', function (): void {
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
    when('wp_enqueue_style')->justReturn();
    when('wp_enqueue_script')->justReturn();
    Functions\expect('wp_create_nonce')->once()->with('widget-conditions-options')->andReturn('n0nce');
    Functions\expect('wp_localize_script')->once()->with('widget-conditions', 'uamsWidgetConditions', ['nonce' => 'n0nce']);

    UAMS_Widget_Conditions::widget_admin_setup();
});

describe('widget_conditions_options()', function (): void {
    it('checks the nonce before anything else', function (): void {
        Functions\expect('check_ajax_referer')->once()->with('widget-conditions-options')->andThrow(new RuntimeException('died'));
        Functions\expect('current_user_can')->never();

        expect(static fn () => UAMS_Widget_Conditions::widget_conditions_options())->toThrow(RuntimeException::class);
    });

    it('refuses a user who cannot manage widgets, before any option is read', function (): void {
        $_REQUEST['major'] = 'author';
        when('check_ajax_referer')->justReturn(1);
        Functions\expect('current_user_can')->once()->with('edit_theme_options')->andReturn(false);
        Functions\expect('wp_die')->once()->with(-1, 403)->andThrow(new RuntimeException('died'));
        Functions\expect('get_users')->never();

        expect(static fn () => UAMS_Widget_Conditions::widget_conditions_options())->toThrow(RuntimeException::class);
    });

    it('serves the options to a user who can manage widgets', function (): void {
        $_REQUEST['major'] = 'author';
        when('check_ajax_referer')->justReturn(1);
        when('current_user_can')->justReturn(true);
        when('sanitize_text_field')->alias(static fn (string $value): string => $value);
        when('get_users')->justReturn([(object) ['ID' => 3, 'display_name' => 'Pat <Author>']]);
        stubEscaping();
        stubTags([
            '_e' => static fn (string $text, string $domain): string => "[e:{$text}]",
            'selected' => '',
        ]);
        Functions\expect('wp_die')->once()->withNoArgs();

        $html = render(static fn () => UAMS_Widget_Conditions::widget_conditions_options());

        expect(trim((string) preg_replace('/\s+/', ' ', $html)))->toBe('<option value="">[e:All author pages]</option> <option value="A{3}" >H{Pat <Author>}</option>');
    });
});
