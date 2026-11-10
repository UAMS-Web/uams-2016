<?php

/**
 * The required plugins registered with TGM Plugin Activation, with WordPress mocked.
 *
 * @see setup/class.uams-plugins.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

it('loads the activation library and registers on tgmpa_register when the file loads', function (): void {
    $theme = sys_get_temp_dir().'/uams-plugins-test-'.getmypid();
    mkdir($theme.'/setup', 0777, true);
    file_put_contents($theme.'/setup/class-tgm-plugin-activation.php', "<?php\n");
    when('get_template_directory')->justReturn($theme);

    require_once dirname(__DIR__, 3).'/setup/class.uams-plugins.php';

    unlink($theme.'/setup/class-tgm-plugin-activation.php');
    rmdir($theme.'/setup');
    rmdir($theme);

    expect(has_action('tgmpa_register', 'uams_2016_register_required_plugins'))->toBe(10);
});

it('requires Advanced Custom Fields PRO from the theme', function (): void {
    $received = [];
    when('get_template_directory')->justReturn('/themes/uams');
    when('tgmpa')->alias(static function (array $plugins, array $config) use (&$received): void {
        $received = [$plugins, $config];
    });

    uams_2016_register_required_plugins();

    expect($received)->toBe([
        [[
            'name' => 'Advanced Custom Fields PRO',
            'slug' => 'advanced-custom-fields-pro',
            'source' => '/themes/uams/plugins/advanced-custom-fields-pro.zip',
            'required' => true,
        ]],
        [
            'id' => 'uams-2016',
            'default_path' => '',
            'menu' => 'tgmpa-install-plugins',
            'parent_slug' => 'themes.php',
            'capability' => 'edit_theme_options',
            'has_notices' => true,
            'dismissable' => true,
            'dismiss_msg' => '',
            'is_automatic' => true,
            'message' => '',
        ],
    ]);
});
