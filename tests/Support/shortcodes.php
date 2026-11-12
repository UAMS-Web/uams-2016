<?php

/**
 * Helpers for the shortcode tests: loading a class file under setup/ and standing in for the
 * WordPress functions the shortcode handlers call.
 *
 * Each shortcode class file declares its class and, at file scope, constructs it once, which
 * registers the shortcode. A class can be declared once per process, so `loadShortcodeFile()`
 * requires the file once with `add_shortcode()` stubbed; tests then construct the class again
 * themselves to observe what the constructor registers.
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

/**
 * Require a class file from setup/ once, with the registration functions it calls at file scope
 * stubbed.
 */
function loadShortcodeFile(string $file): void
{
    when('add_shortcode')->justReturn(true);

    require_once dirname(__DIR__, 2).'/setup/'.$file;
}

/**
 * Record every add_shortcode() call, and return the list the calls are appended to.
 *
 * @return ArrayObject<int, array{string, mixed}>
 */
function recordShortcodes(): ArrayObject
{
    $calls = new ArrayObject;

    when('add_shortcode')->alias(static function (string $tag, mixed $callback) use ($calls): bool {
        $calls[] = [$tag, $callback];

        return true;
    });

    return $calls;
}

/**
 * Stand in for the WordPress functions a shortcode handler calls.
 *
 * `shortcode_atts()` behaves as WordPress's does (attributes the shortcode does not declare are
 * dropped, the rest fall back to their defaults) and records the shortcode name it was given in
 * `$GLOBALS['uams_test_shortcode_atts']`. The others wrap their input in a marker, as
 * `stubEscaping()` does: do_shortcode as `D{...}`, force_balance_tags as `B{...}`, wp_kses_post as
 * `K{...}`, sanitize_text_field as `T{...}`.
 */
function stubShortcodeApi(): void
{
    stubEscaping();

    $GLOBALS['uams_test_shortcode_atts'] = [];

    when('shortcode_atts')->alias(static function (array $pairs, mixed $atts, string $shortcode = ''): array {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $GLOBALS['uams_test_shortcode_atts'][] = $shortcode;
        $atts = (array) $atts;
        $out = [];

        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }

        return $out;
    });

    when('do_shortcode')->alias(static fn (string $content): string => 'D{'.$content.'}');
    when('force_balance_tags')->alias(static fn (string $text): string => 'B{'.$text.'}');
    when('wp_kses_post')->alias(static fn (string $text): string => 'K{'.$text.'}');
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore binaryOp.invalid
    when('sanitize_text_field')->alias(static fn (mixed $text): string => 'T{'.$text.'}');
}

/**
 * The shortcode names shortcode_atts() was given since stubShortcodeApi(), in order.
 *
 * @return list<string>
 */
function shortcodeAttsNames(): array
{
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore return.type
    return $GLOBALS['uams_test_shortcode_atts'] ?? [];
}

/**
 * Run a callback, collecting the warnings it raises instead of letting them through, and return
 * its result with the warning messages.
 *
 * @return array{mixed, list<string>}
 */
function collectWarnings(callable $callback): array
{
    $warnings = [];
    set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    }, E_WARNING);

    try {
        return [$callback(), $warnings];
    } finally {
        restore_error_handler();
    }
}
