<?php

/**
 * Unit bootstrap: Composer's autoloader, Patchwork, and the theme's template
 * functions. WordPress is not loaded; a unit test that reaches a WordPress
 * function it did not mock fails with an undefined-function error.
 *
 * inc/template-functions.php holds only function definitions, so it loads
 * without WordPress. functions.php registers hooks and calls WordPress at file
 * scope, so it is not loaded here.
 */

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/../vendor/antecedent/patchwork/Patchwork.php';

if (! defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir().'/uams-2016-tests-no-wordpress/');
}

require_once __DIR__.'/../inc/template-functions.php';
