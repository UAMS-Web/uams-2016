<?php

/**
 * UAMS_Media_Caption and UAMS_Media_Credit, the caption markup and the image credit, with
 * WordPress mocked.
 *
 * @see setup/class.uams-media-caption.php
 * @see setup/class.uams-media-credit.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectAdded;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-media-caption.php');
    loadShortcodeFile('class.uams-media-credit.php');
    stubEscaping();
    when('shortcode_atts')->alias(static function (array $pairs, mixed $atts, string $shortcode = ''): array {
        $GLOBALS['uams_test_media_shortcode'][] = $shortcode;

        return array_merge($pairs, (array) $atts);
    });
    when('do_shortcode')->alias(static fn (string $content): string => 'D{'.$content.'}');
    $GLOBALS['uams_test_media_shortcode'] = [];
});

describe('UAMS_Media_Caption', function (): void {
    it('filters the caption shortcode with all three arguments', function (): void {
        $caption = new UAMS_Media_Caption;
        expectAdded('img_caption_shortcode')->once()->with([$caption, 'add_media_credit_to_caption_shortcode_filter'], 10, 3);

        new UAMS_Media_Caption;

        expect(has_filter('img_caption_shortcode', [$caption, 'add_media_credit_to_caption_shortcode_filter']))->toBe(10);
    });

    it('returns the content when there is no usable width or caption', function (array $attr): void {
        expect((new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', $attr, 'inner'))->toBe('inner')
            ->and((new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', $attr))->toBeNull()
            ->and($GLOBALS['uams_test_media_shortcode'])->toBe(['caption', 'caption']);
    })->with([
        'no width' => [['caption' => 'Text']],
        'a zero width' => [['width' => '0', 'caption' => 'Text']],
        'no caption' => [['width' => '100']],
        'an empty caption' => [['width' => '100', 'caption' => '']],
    ]);

    it('wraps the content and caption', function (): void {
        $html = (new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', ['width' => '100', 'caption' => 'Text', 'align' => 'alignleft'], '<img>');

        expect($html)->toBe('<div class="wp-caption A{alignleft}" style="width: 110px">D{<img>}<p class="wp-caption-text">Text</p></div>');
    });

    it('wraps a missing content as empty', function (): void {
        $html = (new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', ['width' => '1', 'caption' => 'T']);

        expect($html)->toBe('<div class="wp-caption A{}" style="width: 11px">D{}<p class="wp-caption-text">T</p></div>');
    });

    it('adds the id and the credit of the attachment', function (): void {
        $asked = [];
        when('get_post_meta')->alias(static function (int $id, string $key, bool $single) use (&$asked): string {
            $asked[] = [$id, $key, $single];

            return 'Jane <Doe>';
        });
        when('esc_html')->alias(static fn (string $text): string => 'H{'.$text.'}');

        $html = (new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', ['id' => 'attachment_42', 'width' => '100', 'caption' => 'Text', 'align' => 'aligncenter'], '<img>');

        expect($html)->toBe('<div id="A{attachment_42}" class="wp-caption A{aligncenter}" style="width: 110px">D{<img>}<p class="wp-caption-text">Text<span class="wp-media-credit">H{Jane <Doe>}</span></p></div>')
            ->and($asked)->toBe([[42, '_media_credit', true]]);
    });

    it('adds no credit when the attachment has none or the id has no number', function (string $id, mixed $meta, int $lookups): void {
        $asked = 0;
        when('get_post_meta')->alias(static function () use (&$asked, $meta): mixed {
            $asked++;

            return $meta;
        });

        $html = (new UAMS_Media_Caption)->add_media_credit_to_caption_shortcode_filter('', ['id' => $id, 'width' => '100', 'caption' => 'Text'], 'x');

        expect($html)->toBe('<div id="A{'.$id.'}" class="wp-caption A{}" style="width: 110px">D{x}<p class="wp-caption-text">Text</p></div>')
            ->and($asked)->toBe($lookups);
    })->with([
        'an empty credit' => ['attachment_7', '', 1],
        'an id without a number' => ['photo', 'Jane', 0],
    ]);
});

describe('UAMS_Media_Credit', function (): void {
    it('registers its filters and shortcode', function (): void {
        $credit = new UAMS_Media_Credit;

        expect(has_filter('mce_external_plugins', [$credit, 'add_media_credit_shortcode_to_tinymce']))->toBe(10)
            ->and(has_filter('image_send_to_editor', [$credit, 'mediacredit_tinymce_html']))->toBe(10)
            ->and(has_filter('attachment_fields_to_edit', [$credit, 'image_attachment_fields_to_edit']))->toBe(100)
            ->and(has_filter('attachment_fields_to_save', [$credit, 'custom_image_attachment_fields_to_save']))->toBe(10);
    });

    it('passes the right number of arguments to each filter', function (): void {
        $credit = new UAMS_Media_Credit;
        expectAdded('image_send_to_editor')->once()->with([$credit, 'mediacredit_tinymce_html'], 10, 7);
        expectAdded('attachment_fields_to_edit')->once()->with([$credit, 'image_attachment_fields_to_edit'], 100, 2);
        expectAdded('attachment_fields_to_save')->once()->with([$credit, 'custom_image_attachment_fields_to_save'], 10, 2);

        new UAMS_Media_Credit;
    });

    it('registers the mediacredit shortcode', function (): void {
        $calls = recordShortcodes();
        $credit = new UAMS_Media_Credit;

        expect($calls->getArrayCopy())->toBe([['mediacredit', [$credit, 'mediacredit_html']]]);
    });

    it('adds the media credit script to the editor plugins', function (mixed $plugins, array $expected): void {
        when('get_template_directory_uri')->justReturn('https://site.test/theme');

        expect((new UAMS_Media_Credit)->add_media_credit_shortcode_to_tinymce($plugins))->toBe($expected);
    })->with([
        'plugins' => [['other' => 'o.js'], ['other' => 'o.js', 'mediacredit' => 'https://site.test/theme/assets/admin/js/media-credit.js']],
        'not an array' => ['x', ['mediacredit' => 'https://site.test/theme/assets/admin/js/media-credit.js']],
    ]);

    describe('mediacredit_tinymce_html', function (): void {
        it('returns the image html when there is a caption', function (): void {
            when('get_post_meta')->alias(static function (): never {
                throw new RuntimeException('looked up');
            });

            expect((new UAMS_Media_Credit)->mediacredit_tinymce_html('<img>', 5, 'A caption', 'T', 'left', 'u', 'full'))->toBe('<img>');
        });

        it('returns the image html when there is no credit', function (): void {
            when('get_post_meta')->justReturn('');
            when('wp_get_attachment_image_src')->justReturn(['u', 300, 200]);

            expect((new UAMS_Media_Credit)->mediacredit_tinymce_html('<img>', 5, '', 'T', 'left', 'u', 'full'))->toBe('<img>');
        });

        it('wraps the image with its credit and width', function (): void {
            $asked = [];
            when('get_post_meta')->alias(static function (int $id, string $key, bool $single) use (&$asked): string {
                $asked[] = [$id, $key, $single];

                return 'Jane';
            });
            when('wp_get_attachment_image_src')->alias(static function (int $id, string $size) use (&$asked): array {
                $asked[] = [$id, $size];

                return ['u', 300, 200];
            });
            when('esc_html')->alias(static fn (string $text): string => 'H{'.$text.'}');

            $html = (new UAMS_Media_Credit)->mediacredit_tinymce_html('<img>', '5', '', 'T', 'left', 'u', 'medium');

            expect($html)->toBe("<dl class='mediacredit alignA{left}' data-credit='A{Jane}' data-align='alignA{left}' data-size='A{medium}' style='width:300px'>\n                <dt class='mediacredit-dt'><img></dt>\n                <dd class='wp-caption-dd'>H{Jane}</dd>\n            </dl>")
                ->and($asked)->toBe([[5, '_media_credit', true], [5, 'medium']]);
        });

        it('uses a width of zero when the size is unknown', function (mixed $src): void {
            when('get_post_meta')->justReturn('Jane');
            when('wp_get_attachment_image_src')->justReturn($src);
            when('esc_html')->returnArg();

            expect((new UAMS_Media_Credit)->mediacredit_tinymce_html('<img>', 5, '', 'T', 'left', 'u', 'full'))->toContain("style='width:0px'");
        })->with(['false' => [false], 'no width' => [['u']]]);
    });

    describe('mediacredit_html', function (): void {
        it('renders nothing without an image id', function (mixed $id): void {
            expect((new UAMS_Media_Credit)->mediacredit_html(['id' => $id]))->toBe('')
                ->and((new UAMS_Media_Credit)->mediacredit_html([]))->toBe('');
        })->with(['empty' => [''], 'zero' => ['0'], 'negative' => ['-3'], 'not a number' => ['abc']]);

        it('wraps the content with the credit', function (): void {
            $asked = [];
            when('wp_get_attachment_image_src')->alias(static function (int $id, string $size) use (&$asked): array {
                $asked[] = [$id, $size];

                return ['u', 300, 200];
            });
            when('esc_html')->alias(static fn (string $text): string => 'H{'.$text.'}');

            $html = (new UAMS_Media_Credit)->mediacredit_html(['id' => '8', 'align' => 'alignright', 'size' => 'large', 'credit' => 'Jane'], '<img>');

            expect($html)->toBe('<div class="wp-caption A{alignright}" style="width:310px">D{<img>}<p class="wp-media-credit">Image by H{Jane}</p></div>')
                ->and($asked)->toBe([[8, 'large']])
                ->and($GLOBALS['uams_test_media_shortcode'])->toBe(['mediacredit']);
        });

        it('uses the full size, no content and a narrow wrapper by default', function (): void {
            $asked = [];
            when('wp_get_attachment_image_src')->alias(static function (int $id, string $size) use (&$asked): bool {
                $asked[] = $size;

                return false;
            });
            when('esc_html')->returnArg();

            $html = (new UAMS_Media_Credit)->mediacredit_html(['id' => '8']);

            expect($html)->toBe('<div class="wp-caption A{}" style="width:10px">D{}<p class="wp-media-credit">Image by </p></div>')
                ->and($asked)->toBe(['full']);
        });
    });

    describe('image_attachment_fields_to_edit', function (): void {
        it('adds the credit field for an image', function (mixed $post): void {
            $asked = [];
            when('get_post_meta')->alias(static function (int $id, string $key, bool $single = false) use (&$asked): mixed {
                $asked[] = [$id, $key, $single];

                return $key === '_wp_attachment_context' ? ['other'] : 'Jane';
            });
            when('wp_attachment_is_image')->justReturn(true);
            when('__')->alias(static fn (string $text): string => '__('.$text.')');

            $fields = (new UAMS_Media_Credit)->image_attachment_fields_to_edit(['existing' => 1], $post);

            expect($fields)->toBe(['existing' => 1, 'media_credit' => ['label' => '__(Image Credit)', 'input' => 'text', 'value' => 'Jane']])
                ->and($asked)->toBe([[9, '_wp_attachment_context', false], [9, '_media_credit', true]]);
        })->with(['a post object' => [fn (): WP_Post => wpPost(['ID' => 9])], 'an id' => ['9']]);

        it('leaves the fields for a header image or a file that is not an image', function (mixed $context, bool $isImage): void {
            when('get_post_meta')->justReturn($context);
            when('wp_attachment_is_image')->justReturn($isImage);

            expect((new UAMS_Media_Credit)->image_attachment_fields_to_edit(['existing' => 1], 9))->toBe(['existing' => 1]);
        })->with([
            'a header image' => [['custom-header'], true],
            'a header image given as a string' => ['custom-header', true],
            'not an image' => [[], false],
        ]);
    });

    describe('custom_image_attachment_fields_to_save', function (): void {
        beforeEach(function (): void {
            $this->updated = [];
            $updated = &$this->updated;
            when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
            when('update_post_meta')->alias(static function (int $id, string $key, mixed $value) use (&$updated): void {
                $updated[] = [$id, $key, $value];
            });
        });

        it('saves the credit and returns the post', function (): void {
            $post = ['ID' => '15', 'post_title' => 'x'];

            $result = (new UAMS_Media_Credit)->custom_image_attachment_fields_to_save($post, ['media_credit' => 'Jane']);

            expect($result)->toBe($post)
                ->and($this->updated)->toBe([[15, '_media_credit', 'T{Jane}']]);
        });

        it('saves nothing without the credit or the id', function (array $post, array $attachment): void {
            $result = (new UAMS_Media_Credit)->custom_image_attachment_fields_to_save($post, $attachment);

            expect($result)->toBe($post)
                ->and($this->updated)->toBe([]);
        })->with([
            'no credit' => [['ID' => 15], []],
            'no id' => [['post_title' => 'x'], ['media_credit' => 'Jane']],
        ]);
    });
});
