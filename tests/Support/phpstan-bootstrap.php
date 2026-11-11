<?php

/**
 * Stand-ins for constants the site and a vendored library define, so PHPStan treats them as defined.
 *
 * Listed under `bootstrapFiles` in phpstan.neon.dist. PHPStan runs this file before the analysis;
 * Pest and WordPress never load it.
 */

declare(strict_types=1);

// The Twitter widget reads its API credentials from wp-config.php.
define('TWITTER_CONSUMER_KEY', '');
define('TWITTER_CONSUMER_SECRET', '');
define('TWITTER_OAUTH_TOKEN', '');
define('TWITTER_OAUTH_TOKEN_SECRET', '');

// The vendored Cuztom library defines this when it loads; setup/cuztom is excluded from analysis.
define('CUZTOM_URL', '');
