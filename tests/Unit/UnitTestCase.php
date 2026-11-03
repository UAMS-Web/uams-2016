<?php

/**
 * The base case for unit tests: Brain Monkey around every test.
 *
 * setUp() defines WordPress' hook functions and arms Patchwork; tearDown()
 * verifies every expectation the test set and removes every mock, so nothing
 * leaks into the next test. Brain Monkey's expectations are Mockery
 * expectations, which PHPUnit does not count, so they are added to the
 * assertion count here: a test whose only check is
 * `Functions\expect(...)->once()` is not reported as risky.
 */

declare(strict_types=1);

namespace UAMS\Tests\Unit;

use Brain\Monkey;
use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        Monkey\tearDown();
        parent::tearDown();
    }
}
