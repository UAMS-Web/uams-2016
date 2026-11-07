<?php

/**
 * The redirect at the top of header.php, run in a child process because it ends in `exit;`.
 *
 * @see header.php
 * @see HeaderTest, which covers the rest of the header in-process.
 */

declare(strict_types=1);

/**
 * Run the harness and return its exit status and what it printed to stdout and stderr together.
 *
 * @return array{int, string}
 */
function runHeaderRedirect(string $redirect): array
{
    $process = proc_open(
        [PHP_BINARY, '-d', 'display_errors=1', '-d', 'error_reporting=-1', __DIR__.'/../Support/header-redirect.php', $redirect],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    assert(is_resource($process));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
}

describe('the header redirect', function (): void {
    it('redirects permanently to the sanitized URL, then stops the request', function (): void {
        [$status, $output] = runHeaderRedirect('https://x.test/?a=1');

        expect($output)->toBe('[redirect:RAW{https://x.test/?a=1}|301]')->and($status)->toBe(0);
    });

    it('prints no page after the redirect', function (): void {
        [, $output] = runHeaderRedirect('https://x.test/');

        expect($output)->not->toContain('<!DOCTYPE')->and($output)->not->toContain('[AFTER]');
    });

    it('goes on to the page when the redirect field is empty', function (): void {
        // The control: the same process, with the field unset, gets past the redirect check and
        // reaches the first WordPress call after it, which the harness does not define.
        [$status, $output] = runHeaderRedirect('');

        expect($output)->toContain('Call to undefined function get_bloginfo()')->and($output)->not->toContain('[redirect:')
            ->and($status)->not->toBe(0);
    });
});
