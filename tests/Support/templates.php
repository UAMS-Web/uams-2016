<?php

/**
 * Helpers for the template tests: stubs for the template tags the post content templates share.
 */

declare(strict_types=1);

/**
 * Print markers for the template tags every content template uses.
 *
 * @param  array<string, string|Closure>  $extra
 */
function contentTags(array $extra = []): void
{
    $json = static fn (mixed $value): string => json_encode($value, JSON_UNESCAPED_SLASHES);

    stubTags(array_merge([
        'the_title' => '[title]',
        'the_title_attribute' => '[title-attr]',
        'the_author' => '[author]',
        'the_author_meta' => static fn (string $field): string => "[author-meta:{$field}]",
        'the_category' => static fn (string $separator): string => "[category:{$separator}]",
        'the_excerpt' => '[excerpt]',
        'the_content' => '[content]',
        'the_date' => static fn (string ...$args): string => '[date:'.implode('|', $args).']',
        'the_post_thumbnail' => static fn (mixed ...$args): string => '[thumb:'.$json($args).']',
        'the_ID' => '[id]',
        'post_class' => static fn (string $class): string => "class=\"{$class}\"",
        'uams_mobile_menu' => '[mobile-menu]',
        'esc_attr_e' => static fn (string $text, string $domain): string => "A{{$text}|{$domain}}",
    ], $extra));
}

/**
 * A have_posts() stand-in that is true for the given number of passes and then false.
 *
 * @return Closure(): bool
 */
function loopOf(int $passes): Closure
{
    return static function () use (&$passes): bool {
        return $passes-- > 0;
    };
}

/**
 * Stub the theme chrome the page templates share: each tag prints a bracketed marker, so the output
 * shows what was called, with which arguments, and in what order.
 */
function chromeMarkers(): void
{
    stubTags([
        'get_header' => '[header]',
        'get_footer' => '[footer]',
        'get_sidebar' => '[sidebar]',
        'get_search_form' => '[search]',
        'uams_content_class' => 'class="content"',
        'get_template_part' => static fn (mixed ...$parts): string => '[part:'.implode(',', array_map(strval(...), $parts)).']',
    ]);
}
