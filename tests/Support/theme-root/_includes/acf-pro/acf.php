<?php

/**
 * Stand-in for the bundled ACF Pro: records that it was included.
 */

declare(strict_types=1);

// The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
// @phpstan-ignore binaryOp.invalid
$GLOBALS['uams_fixture_acf_included'] = ($GLOBALS['uams_fixture_acf_included'] ?? 0) + 1;
