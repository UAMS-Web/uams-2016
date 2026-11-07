<?php

/**
 * Stand-in for the bundled ACF Pro: records that it was included.
 */

declare(strict_types=1);

$GLOBALS['uams_fixture_acf_included'] = ($GLOBALS['uams_fixture_acf_included'] ?? 0) + 1;
