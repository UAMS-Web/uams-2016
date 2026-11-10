<?php

/**
 * How `style_dir` reaches js/site.js in a real WordPress: as inline script before the script that
 * reads it, with no incorrect-usage notice from WP_Scripts::localize().
 *
 * WP_UnitTestCase fails a test on any _doing_it_wrong() call it was not told to expect, so
 * enqueueing the theme's scripts without the notice is itself an assertion.
 *
 * @see setup/class.uams-scripts.php
 */

declare(strict_types=1);

namespace UAMS\Tests\Integration;

use UAMS\Tests\Support\IntegrationTestCase;

final class StyleDirScriptTest extends IntegrationTestCase
{
    public function test_defines_style_dir_before_site_script_without_a_notice(): void
    {
        do_action('wp_enqueue_scripts');

        $printed = get_echo('wp_print_scripts');

        $definition = 'var style_dir = '.wp_json_encode(site_url()).';';
        $this->assertStringContainsString($definition, $printed);
        $this->assertLessThan(
            strpos($printed, 'js/site'),
            strpos($printed, $definition),
            'style_dir is defined before the script that reads it'
        );
    }
}
