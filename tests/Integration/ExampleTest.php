<?php

/**
 * The theme inside a real WordPress.
 *
 * A class-based PHPUnit 9.6 test, run from tests/Integration's own Composer
 * setup, because WordPress' test suite supports PHPUnit 9 only.
 *
 * @see functions.php
 */

declare(strict_types=1);

namespace UAMS\Tests\Integration;

use UAMS\Tests\Support\IntegrationTestCase;

final class ExampleTest extends IntegrationTestCase
{
    public function test_is_the_active_theme(): void
    {
        $this->assertSame(basename(dirname(__DIR__, 2)), get_template());
    }

    public function test_declares_woocommerce_support(): void
    {
        $this->assertTrue(current_theme_supports('woocommerce'));
    }
}
