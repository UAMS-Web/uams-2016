<?php

/**
 * Child-process harness for HeaderRedirectTest: include header.php with the redirect field set (or
 * not, by the first argument) and print what runs after it. `exit;` in header.php ends this
 * process, so only a separate process can see that nothing after the redirect runs.
 */

declare(strict_types=1);

$redirect = $argv[1] ?? '';

function get_field(string $name): mixed
{
    global $redirect;

    return $name === 'seo_custom_redirect_url' && $redirect !== '' ? $redirect : false;
}

function esc_url_raw(string $url): string
{
    return 'RAW{'.$url.'}';
}

function wp_redirect(string $location, int $status): void
{
    echo "[redirect:{$location}|{$status}]";
}

include dirname(__DIR__, 2).'/header.php';

echo '[AFTER]';
