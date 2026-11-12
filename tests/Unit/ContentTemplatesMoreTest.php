<?php

/**
 * The remaining post content templates, run with WordPress mocked: content-archive.php,
 * content-image.php, content-link.php, content-pdf.php and content-page.php.
 *
 * Mutants not killed here, and why. The defaults of `text_cut($text, $length = 27, $dots = true)`
 * in content-page.php carry `@pest-mutate-ignore`: coverage attributes no line to a function
 * signature, so the mutation run reports their mutants uncovered and never runs a test against
 * them. Neither is an equivalent mutant: "cuts at 27 characters, with dots, by default" calls
 * `text_cut()` with both defaults and asserts the result.
 *
 * @see content-archive.php
 * @see content-image.php
 * @see content-link.php
 * @see content-pdf.php
 * @see content-page.php
 */

declare(strict_types=1);

beforeEach(function (): void {
    stubEscaping();
});

describe('content-archive.php', function (): void {
    /**
     * @param  array<string, bool>  $conditions
     * @param  array<string, mixed>  $values
     */
    function archiveItemEnv(array $conditions = [], bool $byline = false, bool $thumbnail = false, array $values = []): void
    {
        contentTags();
        wpConditionals($conditions);
        stubValues(array_merge([
            'get_option' => static fn (string $name): mixed => $name === 'show_byline_on_posts' && $byline,
            'has_post_thumbnail' => $thumbnail,
            'uams_get_permalink' => '/post-7/',
        ], $values));
    }

    it('prints the date, the linked title, the excerpt and a rule', function (): void {
        archiveItemEnv(['is_archive' => true]);

        expect(renderTemplate('content-archive.php'))->toBe(
            '[date:F j, Y|<p class="date">|</p>]<h2 style="font-size: 27px;"> <a href="U{/post-7/}" title="[title-attr]">[title]</a> </h2> [excerpt]<hr>'
        );
    });

    it('prints the author and the author description when the byline is on', function (): void {
        archiveItemEnv(['is_archive' => true], byline: true);

        expect(renderTemplate('content-archive.php'))->toContain(
            '</h2> <div class="author-info"> [author] <p class="author-desc"> <small>[author-meta:description]</small></p> </div>'
        );
    });

    it('prints no byline unless the site option is on', function (): void {
        archiveItemEnv(['is_archive' => true]);

        expect(renderTemplate('content-archive.php'))->not->toContain('author-info');
    });

    it('prints the mobile menu only outside the posts page, a search and an archive', function (array $conditions, bool $menu): void {
        archiveItemEnv($conditions);

        expect(str_contains(renderTemplate('content-archive.php'), '[mobile-menu]'))->toBe($menu);
    })->with([
        'a page' => [[], true],
        'the posts page' => [['is_home' => true], false],
        'a search' => [['is_search' => true], false],
        'an archive' => [['is_archive' => true], false],
    ]);

    it('prints the thumbnail with its bottom margin when the post has one', function (): void {
        archiveItemEnv(['is_archive' => true], thumbnail: true);

        expect(renderTemplate('content-archive.php'))->toContain(
            '[thumb:["thumbnail",{"style":"margin-bottom:5px;"}]][excerpt]'
        );
    });

    it('prints no thumbnail when the post has none', function (): void {
        archiveItemEnv(['is_archive' => true]);

        expect(renderTemplate('content-archive.php'))->not->toContain('[thumb:');
    });
});

describe('content-image.php', function (): void {
    /**
     * @param  array<string, mixed>  $values
     */
    function imageEnv(string $credit = '', array $values = []): void
    {
        contentTags();
        stubValues(array_merge([
            'get_the_ID' => 7,
            'wp_get_attachment_image' => static fn (int $id, string $size, bool $icon, array $attributes): string => '[image:'.$id.'|'.$size.'|'.var_export($icon, true).'|'.$attributes['class'].']',
            'get_the_excerpt' => 'An <i>excerpt</i>',
            'get_post_meta' => static fn (int $id, string $key, bool $single): string => $credit,
            'wp_get_attachment_url' => static fn (int $id): string => "/files/{$id}.png",
        ], $values));
    }

    it('prints the image, title, excerpt and a download link', function (): void {
        imageEnv();

        expect(renderTemplate('content-image.php'))->toBe(
            '<div class="uams-image-content"> [image:7|full|false|attachment-full center-block]'
            .' <h1 class="entry-title">[title]</h1> An <i>excerpt</i>'
            .' <div> <a href="U{/files/7.png}" title="[title-attr]" target="_blank" download="[title-attr]">Download</a> </div> </div>'
        );
    });

    it('credits the photographer when the image has a credit', function (): void {
        imageEnv('Ada <b>');

        expect(renderTemplate('content-image.php'))->toContain('An <i>excerpt</i> <br> Photo Credit: H{Ada <b>} <div>');
    });

    it('asks for the credit of the current attachment', function (): void {
        $calls = [];
        imageEnv(values: ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
            $calls[] = [$id, $key, $single];

            return '';
        }]);

        renderTemplate('content-image.php');

        expect($calls)->toBe([[7, '_media_credit', true]]);
    });
});

describe('content-link.php', function (): void {
    /**
     * @param  array<string, mixed>  $values
     */
    function linkEnv(string $link, bool $single, array $values = []): void
    {
        contentTags();
        wpConditionals(['is_single' => $single]);
        stubValues(array_merge([
            'get_the_ID' => 7,
            'get_post_meta' => static fn (int $id, string $key, bool $single): string => $link,
        ], $values));
    }

    it('prints a single post with an external link as a linked heading', function (): void {
        linkEnv('https://example.org/x?a=1&b=2', true);

        expect(renderTemplate('content-link.php'))->toBe(
            '<h1 class="post-link-title"><i class="far fa-link fa-sm post-link-icon"></i> <a href="U{https://example.org/x?a=1&b=2}">[title]</a></h1>'
            .' <p><em>This is an external link and will take you to a new page.</em></p> [content]'
        );
    });

    it('asks for the external link of the current post', function (): void {
        $calls = [];
        linkEnv('', true, ['get_post_meta' => static function (int $id, string $key, bool $single) use (&$calls): string {
            $calls[] = [$id, $key, $single];

            return 'https://example.org/x';
        }]);

        renderTemplate('content-link.php');

        expect($calls)->toBe([[7, 'post_custom_link', true], [7, 'post_custom_link', true]]);
    });

    it('prints an article when the post has no external link or is not single', function (string $link, bool $single): void {
        linkEnv($link, $single);

        expect(renderTemplate('content-link.php'))->toBe(
            '<article id="post-[id]" class="entry"> <h1 class="screen-reader-text">[title]</h1> [content] </article> <!-- .entry-->'
        );
    })->with([
        'no link on a single post' => ['', true],
        'a link on a post that is not single' => ['https://example.org/x', false],
        'neither' => ['', false],
    ]);
});

describe('content-pdf.php', function (): void {
    it('prints the title, a download link, the excerpt and a full-size viewer for a PDF', function (): void {
        contentTags();
        stubValues(['get_the_ID' => 7, 'is_pdf' => true, 'wp_get_attachment_url' => static fn (int $id): string => "/files/{$id}.pdf"]);

        $before = time();
        $html = renderTemplate('content-pdf.php');
        $after = time();

        expect($html)->toStartWith(
            '<h1 class="entry-title">[title]</h1> <div style="margin-top: 30px">'
            .' <a class="uams-btn btn-sm btn-plus" href="/files/7.pdf" title="[title]" target="_blank">Download</a> </div> [excerpt]'
        )->and(preg_match('#<iframe class="uams-pdf-view" style="width:100%;height:900px;" src="/files/7\.pdf\?(\d+)"></iframe>$#', $html, $match))->toBe(1)
            ->and((int) $match[1])->toBeGreaterThanOrEqual($before)->toBeLessThanOrEqual($after);
    });

    it('collapses the viewer when the attachment is not a PDF', function (): void {
        contentTags();
        stubValues(['get_the_ID' => 7, 'is_pdf' => false, 'wp_get_attachment_url' => '/files/7.png']);

        expect(renderTemplate('content-pdf.php'))->toContain('style="width:0px;height:0px;"');
    });
});

describe('content-page.php', function (): void {
    function pageContentEnv(string $title): void
    {
        contentTags();
        stubValues(['get_the_title' => $title]);
        stubTags(['uams_sidebar_menu_mobile' => '[sidebar-menu]']);
    }

    it('prints the title, the mobile sidebar toggle with the cut title, the menu and the content', function (): void {
        pageContentEnv('About <us>');

        expect(renderTemplate('content-page.php'))->toBe(
            '<h1>[title]</h1> <div id="mobile-sidebar"> <button id="mobile-sidebar-menu" aria-hidden="true" tabindex="1">'
            .' <div aria-hidden="true" id="ham"> <span></span> <span></span> <span></span> <span></span> </div>'
            .' <div id="mobile-sidebar-title" class="page_item"> H{About <us>} </div> </button>'
            .' <div id="mobile-sidebar-links" aria-hidden="true">[sidebar-menu]</div> </div> [content]'
        );
    });

    it('cuts a long title at the last word that fits in 27 characters and adds dots', function (): void {
        pageContentEnv('Department of Orthopaedic Surgery and Rehabilitation');

        expect(renderTemplate('content-page.php'))->toContain('page_item"> H{Department of Orthopaedic...} </div>');
    });

    describe('text_cut', function (): void {
        it('collapses runs of whitespace to one space and trims', function (): void {
            expect(text_cut("  A \t\n  B  ", 27, true))->toBe('A B');
        });

        it('keeps a text that fits whole, with no dots', function (): void {
            expect(text_cut('Exactly twenty-seven chars.', 27, true))->toBe('Exactly twenty-seven chars.');
        });

        it('cuts at the space nearest before the limit', function (): void {
            expect(text_cut('one two three four', 8, true))->toBe('one two...');
        });

        it('cuts at the limit when a space falls exactly there', function (): void {
            expect(text_cut('abcdefgh ijkl', 8, true))->toBe('abcdefgh...');
        });

        it('leaves nothing, and no dots, when the first word is longer than the limit', function (): void {
            expect(text_cut('Supercalifragilistic', 5, true))->toBe('');
        });

        it('adds no dots when asked not to', function (): void {
            expect(text_cut('one two three four', 8, false))->toBe('one two');
        });

        it('cuts at 27 characters, with dots, by default', function (): void {
            expect(text_cut('Department of Orthopaedic Surgery'))->toBe('Department of Orthopaedic...');
        });

        it('casts a non-string to a string', function (): void {
            // A non-string is the case under test.
            // @phpstan-ignore argument.type
            expect(text_cut(12345))->toBe('12345');
        });
    });
});
