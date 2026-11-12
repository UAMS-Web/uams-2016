<?php

/**
 * Pest configuration, for the Unit suite.
 *
 * The Integration suite is class-based PHPUnit 9.6 under tests/Integration, run
 * from its own Composer setup; Pest never loads it.
 */

declare(strict_types=1);

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

/*
 * Brain Monkey around every test.
 *
 * setUp() defines WordPress' hook functions and arms Patchwork; tearDown()
 * verifies every expectation the test set (a `->once()` that never happened
 * fails here) and removes every mock, so nothing leaks into the next test.
 *
 * The assertion count: Brain Monkey's expectations are Mockery expectations,
 * which PHPUnit does not count. Without adding them, a test whose only check is
 * `Functions\expect(...)->once()` reports as risky, and failOnRisky fails it.
 */
uses()
    ->beforeEach(function (): void {
        setUp();

        // Include, not require_once: every function in the file is declared behind a
        // function_exists() guard, so each test runs the guards again. A mutant that breaks a
        // guard then fails the tests that call the function it no longer defines, which loading
        // the file once in the bootstrap (before coverage starts) could not show.
        include __DIR__.'/../inc/template-functions.php';
    })
    ->afterEach(function (): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore method.nonObject
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        tearDown();
    })
    ->in('Unit');
