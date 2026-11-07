<?php

/**
 * Stand-ins and helpers for the Unit suite, which runs without WordPress.
 *
 * WP_Post and WP_Error are the two core classes the theme tests with `instanceof`; the stand-ins
 * carry only the properties the theme reads, untyped as in WordPress. The helpers stub the escaping functions with
 * visible markers, so a test can tell which escaper a value went through.
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;
use function Patchwork\relay;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID = 0;

        public $post_parent = 0;

        public $post_title = '';
    }
}

if (! class_exists('WP_Error')) {
    class WP_Error {}
}

/**
 * Build a post stand-in with the given properties.
 *
 * @param  array<string, mixed>  $properties
 */
function wpPost(array $properties = []): WP_Post
{
    $post = new WP_Post;

    foreach ($properties as $name => $value) {
        $post->{$name} = $value;
    }

    return $post;
}

/**
 * Stub the escaping and sanitizing functions so each wraps its input in a marker:
 * esc_attr as `A{...}`, esc_html as `H{...}`, esc_url as `U{...}`, sanitize_html_class as `S{...}`.
 */
function stubEscaping(): void
{
    when('esc_attr')->alias(static fn (string $value): string => 'A{'.$value.'}');
    when('esc_html')->alias(static fn (string $value): string => 'H{'.$value.'}');
    when('esc_url')->alias(static fn (string $value): string => 'U{'.$value.'}');
    when('sanitize_html_class')->alias(static fn (string $value): string => 'S{'.$value.'}');
    when('home_url')->alias(static fn (string $path = ''): string => 'https://site.test'.$path);
    when('site_url')->alias(static fn (string $path = ''): string => 'https://site.test'.$path);
    when('is_wp_error')->alias(static fn (mixed $value): bool => $value instanceof WP_Error);
}

/**
 * Define every conditional tag the theme's template functions ask, all false unless overridden,
 * so one test states only the conditions it depends on.
 *
 * @param  array<string, bool>  $overrides
 */
function wpConditionals(array $overrides = []): void
{
    $conditionals = array_merge([
        'is_404' => false,
        'is_archive' => false,
        'is_search' => false,
        'is_front_page' => false,
        'is_home' => false,
        'is_author' => false,
        'is_category' => false,
        'is_tax' => false,
        'is_single' => false,
        'is_page' => false,
        'is_post_type_archive' => false,
        'has_category' => false,
    ], $overrides);

    foreach ($conditionals as $name => $value) {
        when($name)->justReturn($value);
    }
}

/**
 * Report which of the named classes exist, whatever the autoloader knows.
 *
 * class_exists() is redefinable through patchwork.json. Names not listed fall through to the real
 * function, so the autoloader and Mockery are unaffected.
 *
 * @param  array<string, bool>  $classes  Class name => whether class_exists() reports it.
 */
function stubClassExists(array $classes): void
{
    when('class_exists')->alias(
        static fn (string $class, bool $autoload = true): bool => $classes[$class] ?? relay(func_get_args())
    );
}

/**
 * Run a template function that echoes, and return what it printed.
 */
function render(callable $template): string
{
    ob_start();

    try {
        $template();

        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

/**
 * Define template tags that echo a marker, as `the_title()` and its kind do: name => the text to
 * echo, or a closure that receives the call's arguments and returns it.
 *
 * @param  array<string, string|Closure>  $tags
 */
function stubTags(array $tags): void
{
    foreach ($tags as $name => $text) {
        when($name)->alias(static function (mixed ...$arguments) use ($text): void {
            echo $text instanceof Closure ? $text(...$arguments) : $text;
        });
    }
}

/**
 * Define WordPress functions that return a value: name => the value, or a closure to alias.
 *
 * @param  array<string, mixed>  $values
 */
function stubValues(array $values): void
{
    foreach ($values as $name => $value) {
        $value instanceof Closure ? when($name)->alias($value) : when($name)->justReturn($value);
    }
}

/**
 * Include a template from the theme root and return what it printed, runs of whitespace collapsed
 * to one space and the ends trimmed, so a test compares markup and not indentation.
 *
 * @param  array<string, mixed>  $variables  Variables in scope for the template, as WordPress's
 *                                           load_template() puts `$post` and its kind in scope.
 */
function renderTemplate(string $file, array $variables = []): string
{
    $output = render(static function () use ($file, $variables): void {
        extract($variables);

        include dirname(__DIR__, 2).'/'.$file;
    });

    return trim((string) preg_replace('/\s+/', ' ', $output));
}

/**
 * Report which of the named functions exist, whatever has been defined.
 *
 * function_exists() is redefinable through patchwork.json. Names not listed fall through to the
 * real function.
 *
 * @param  array<string, bool>  $functions  Function name => whether function_exists() reports it.
 */
function stubFunctionExists(array $functions): void
{
    when('function_exists')->alias(
        static fn (string $function): bool => $functions[$function] ?? relay(func_get_args())
    );
}
