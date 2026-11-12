<?php

/**
 * The post content templates, run with WordPress mocked: content.php, content-archive.php,
 * content-image.php, content-link.php, content-page.php and content-pdf.php.
 *
 * Template tags print a bracketed marker naming the call and its arguments, so each test reads
 * what was called, in what order, and with what.
 *
 * Mutants not killed here, and why. The defaults of `text_cut($text, $length = 27, $dots = true)`
 * in content-page.php carry `@pest-mutate-ignore`: coverage attributes no line to a function
 * signature, so the mutation run reports their mutants uncovered and never runs a test against
 * them. Neither is an equivalent mutant: "cuts at the default length" and "adds dots by default"
 * call `text_cut()` with each default and assert the result.
 *
 * @see content.php
 * @see content-archive.php
 * @see content-image.php
 * @see content-link.php
 * @see content-page.php
 * @see content-pdf.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

describe('content.php', function (): void {
    /**
     * @param  array<string, mixed>  $conditions  is_single, is_home, is_search, is_archive.
     * @param  array<string, mixed>  $options  Site options by name.
     * @param  array<string, mixed>  $meta  Post meta by key.
     * @param  array<string, mixed>  $fields  Post fields by name.
     * @param  array<string, mixed>  $values  Overrides for any stubbed function.
     */
    function contentEnv(array $conditions = [], array $options = [], array $meta = [], array $fields = [], int $thumbnail = 0, array $values = []): void
    {
        contentTags();
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        wpConditionals($conditions);
        stubValues(array_merge([
            'get_the_ID' => 7,
            'get_option' => static fn (string $name): mixed => $options[$name] ?? false,
            'get_post_meta' => static fn (int $id, string $key, bool $single): mixed => $meta[$key] ?? '',
            'get_post_thumbnail_id' => $thumbnail,
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore return.type
            'get_post_field' => static fn (string $field, int $id): string => $fields[$field] ?? '',
            'get_the_post_thumbnail_url' => 'https://x.test/full.jpg',
        ], $values));
        stubFunctionExists(['coauthors' => false]);
    }

    it('prints the title, then the whole content of a page', function (): void {
        contentEnv();

        expect(renderTemplate('content.php'))->toBe('<h1>[title]</h1> [mobile-menu][content]');
    });

    it('prints the date, as an announcement of the post, on a single post and on the posts page', function (string $condition): void {
        contentEnv([$condition => true]);

        expect(renderTemplate('content.php'))->toStartWith('[date:F j, Y|<p class="date">|</p>]<h1>[title]</h1>');
    })->with(['single' => ['is_single'], 'home' => ['is_home']]);

    it('prints no date on other pages', function (): void {
        contentEnv(['is_archive' => true]);

        expect(renderTemplate('content.php'))->not->toContain('[date:');
    });

    it('prints the excerpt and a rule instead of the content on an archive or the posts page', function (string $condition): void {
        contentEnv([$condition => true]);

        expect(renderTemplate('content.php'))->toEndWith('[thumb:[[130,130],{"class":"attachment-post-thumbnail blogroll-img"}]][excerpt]<hr>')
            ->and(renderTemplate('content.php'))->not->toContain('[content]');
    })->with(['archive' => ['is_archive'], 'home' => ['is_home']]);

    it('prints the mobile menu only on a page that is not the posts page, a search or an archive', function (array $conditions, bool $menu): void {
        contentEnv($conditions);

        expect(str_contains(renderTemplate('content.php'), '[mobile-menu]'))->toBe($menu);
    })->with([
        'a page' => [[], true],
        'a single post' => [['is_single' => true], true],
        'the posts page' => [['is_home' => true], false],
        'a search' => [['is_search' => true], false],
        'an archive' => [['is_archive' => true], false],
    ]);

    describe('the byline', function (): void {
        it('prints the author and the author description', function (string $condition): void {
            contentEnv([$condition => true], ['show_byline_on_posts' => '1']);

            expect(renderTemplate('content.php'))->toContain(
                '<div class="author-info"> By [author] <p class="author-desc"> <small>[author-meta:description]</small></p> </div>'
            );
        })->with(['single' => ['is_single'], 'home' => ['is_home']]);

        it('is off unless the site option is on', function (): void {
            contentEnv(['is_single' => true]);

            expect(renderTemplate('content.php'))->not->toContain('author-info');
        });

        it('is off outside a single post and the posts page', function (): void {
            contentEnv(['is_page' => true], ['show_byline_on_posts' => '1']);

            expect(renderTemplate('content.php'))->not->toContain('author-info');
        });

        it('names the co-authors when that plugin is active', function (): void {
            contentEnv(['is_single' => true], ['show_byline_on_posts' => '1']);
            stubTags(['coauthors' => '[coauthors]']);
            stubFunctionExists(['coauthors' => true]);

            expect(renderTemplate('content.php'))->toContain('By [coauthors] <p class="author-desc">');
        });
    });

    describe('the category line', function (): void {
        it('prints the categories of a single post', function (): void {
            contentEnv(['is_single' => true], ['show_category_on_posts' => '1']);

            expect(renderTemplate('content.php'))->toContain(
                '<div class="category-info"> <p class=""> [category:, ]</p> </div>'
            );
        });

        it('shares the class with the byline when both are on', function (): void {
            contentEnv(['is_single' => true], ['show_category_on_posts' => '1', 'show_byline_on_posts' => '1']);

            expect(renderTemplate('content.php'))->toContain('<p class="category-list"> [category:, ]</p>');
        });

        it('is off unless the site option is on', function (): void {
            contentEnv(['is_single' => true]);

            expect(renderTemplate('content.php'))->not->toContain('category-info');
        });

        it('is off outside a single post', function (): void {
            contentEnv(['is_home' => true], ['show_category_on_posts' => '1']);

            expect(renderTemplate('content.php'))->not->toContain('category-info');
        });
    });

    describe('the featured image', function (): void {
        $featured = static fn (array $overrides = []): array => array_merge([
            'conditions' => ['is_single' => true],
            'meta' => ['show_image_single' => '1', '_wp_attachment_image_alt' => 'Alt <1>', '_media_credit' => ''],
            'fields' => ['post_title' => 'Image title', 'post_excerpt' => ''],
            'thumbnail' => 99,
        ], $overrides);

        it('prints the linked image with its alt text and title', function () use ($featured): void {
            $setup = $featured();
            contentEnv($setup['conditions'], [], $setup['meta'], $setup['fields'], $setup['thumbnail']);

            expect(renderTemplate('content.php'))->toStartWith(
                '<p class="featured-image"> <a href="U{https://x.test/full.jpg}" title="A{Alt <1>}" data-title="A{Image title}" data-caption="A{}">'
                .' <span class="screen-reader-text">A{View Larger Image|UAMS}</span> [thumb:["post-image"]] </a> </p>'
            );
        });

        it('prints the caption and the credit when the image has them', function () use ($featured): void {
            $setup = $featured([
                'meta' => ['show_image_single' => '1', '_wp_attachment_image_alt' => 'Alt', '_media_credit' => 'Ada <b>'],
                'fields' => ['post_title' => 'T', 'post_excerpt' => 'Cap'],
            ]);
            contentEnv($setup['conditions'], [], $setup['meta'], $setup['fields'], $setup['thumbnail']);

            expect(renderTemplate('content.php'))->toStartWith(
                '<p class="featured-image"> <a href="U{https://x.test/full.jpg}" title="A{Alt}" data-title="A{T}" data-caption="A{Cap}">'
                .' <span class="screen-reader-text">A{View Larger Image|UAMS}</span> [thumb:["post-image"]]'
                .' <br/><span class="wp-caption-text">H{Cap}</span> </a>'
                .' <span class="wp-media-credit">Image by H{Ada <b>}</span> </p>'
            );
        });

        it('is off unless the post is set to show it', function () use ($featured): void {
            $setup = $featured(['meta' => ['show_image_single' => '']]);
            contentEnv($setup['conditions'], [], $setup['meta'], $setup['fields'], $setup['thumbnail']);

            expect(renderTemplate('content.php'))->not->toContain('featured-image');
        });

        it('is off outside a single post', function () use ($featured): void {
            $setup = $featured(['conditions' => ['is_page' => true]]);
            contentEnv($setup['conditions'], [], $setup['meta'], $setup['fields'], $setup['thumbnail']);

            expect(renderTemplate('content.php'))->not->toContain('featured-image');
        });

        it('is off when the post has no thumbnail', function () use ($featured): void {
            $setup = $featured(['thumbnail' => 0]);
            contentEnv($setup['conditions'], [], $setup['meta'], $setup['fields'], $setup['thumbnail']);

            expect(renderTemplate('content.php'))->not->toContain('featured-image');
        });
    });
});
