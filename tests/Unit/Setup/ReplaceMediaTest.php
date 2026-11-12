<?php

/**
 * UAMS_Replace_Media, the Replace button on non-image attachments and the handler that swaps the file, with
 * WordPress mocked.
 *
 * @see setup/class.uams-replace-media.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectAdded;
use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-replace-media.php');
    $GLOBALS['uams_test_replace'] = [];
    when('wp_enqueue_media')->alias(static function (): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $GLOBALS['uams_test_replace'][] = ['enqueue_media'];
    });
    when('__')->alias(static fn (string $text): string => '__('.$text.')');
    when('wp_nonce_field')->alias(static function (mixed ...$args): string {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $GLOBALS['uams_test_replace'][] = ['nonce_field', ...$args];

        return '[nonce]';
    });
    when('wp_die')->alias(static function (string $message): never {
        throw new RuntimeException($message);
    });
});

afterEach(function (): void {
    unset($GLOBALS['uams_test_replace']);
});

/**
 * Stub the mime type lookup, and record the post it was asked about.
 */
function stubMimeType(mixed $mime): void
{
    when('get_post_mime_type')->alias(static function (int $id) use ($mime): mixed {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $GLOBALS['uams_test_replace'][] = ['mime', $id];

        return $mime;
    });
}

/**
 * A scratch directory holding a "current" and a "new" media file.
 *
 * @return array{string, string, string}
 */
function mediaFiles(): array
{
    $dir = sys_get_temp_dir().'/uams-replace-media-'.getmypid().'-'.bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir.'/current.pdf', 'old');
    file_put_contents($dir.'/new.pdf', 'new');

    return [$dir, $dir.'/current.pdf', $dir.'/new.pdf'];
}

/**
 * Remove the scratch directory.
 */
function removeMediaFiles(string $dir): void
{
    foreach (glob($dir.'/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($dir);
}

it('adds the field and save filters with two arguments', function (): void {
    $media = new UAMS_Replace_Media;
    expectAdded('attachment_fields_to_edit')->once()->with([$media, 'uams_edit_attachment_slug'], 10, 2);
    expectAdded('attachment_fields_to_save')->once()->with([$media, 'uams_save_attachment_slug'], 10, 2);

    new UAMS_Replace_Media;

    expect(has_filter('attachment_fields_to_edit', [$media, 'uams_edit_attachment_slug']))->toBe(10)
        ->and(has_filter('attachment_fields_to_save', [$media, 'uams_save_attachment_slug']))->toBe(10);
});

describe('uams_edit_attachment_slug', function (): void {
    it('adds the replace button to an application file', function (mixed $post): void {
        stubMimeType('application/pdf');

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $fields = (new UAMS_Replace_Media)->uams_edit_attachment_slug(['kept' => 1], $post);

        expect($fields['kept'])->toBe(1)
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_keys($fields['replace_media']))->toBe(['label', 'input', 'html'])
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            ->and($fields['replace_media']['label'])->toBe('__(Replace Media)')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            ->and($fields['replace_media']['input'])->toBe('html')
            ->and($GLOBALS['uams_test_replace'])->toBe([
                ['enqueue_media'],
                ['mime', 7],
                ['nonce_field', 'replace-media-7', 'replace-media-nonce', true, false],
            ]);
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $html = $fields['replace_media']['html'];
        expect($html)->toContain('div.media-sidebar tr.compat-field-replace_media')
            ->and($html)->toContain("<em class=\"help\"></em>\n                  [nonce]\n                  <input id=\"replace-media-input-7\" type=\"hidden\" name=\"replace_media_for_7\" value=\"\"/>")
            ->and($html)->toContain('if ( attachment.id == 7 ) {')
            ->and($html)->toContain('jQuery("#replace-media-input-7").val(attachment.id);')
            ->and($html)->toContain('library: { type: "application" },');
    })->with([
        'a post' => [fn (): WP_Post => wpPost(['ID' => 7])],
        'an id' => ['7'],
    ]);

    it('leaves the fields alone for any other file', function (mixed $mime): void {
        stubMimeType($mime);

        $fields = (new UAMS_Replace_Media)->uams_edit_attachment_slug(['kept' => 1], wpPost(['ID' => 7]));

        expect($fields)->toBe(['kept' => 1])
            ->and($GLOBALS['uams_test_replace'])->toBe([['enqueue_media'], ['mime', 7]]);
    })->with([
        'an image' => ['image/jpeg'],
        'no mime type' => [false],
        'application without the slash' => ['application'],
    ]);
});

describe('uams_save_attachment_slug', function (): void {
    beforeEach(function (): void {
        [$this->dir, $this->current, $this->new] = mediaFiles();
        when('get_attached_file')->alias(fn (int $id): mixed => match ($id) {
            5 => $this->current,
            9 => $this->new,
            default => false,
        });
        when('wp_verify_nonce')->alias(static function (string $nonce, string $action): bool {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_replace'][] = ['verify', $nonce, $action];

            return $nonce === 'good';
        });
        when('wp_delete_attachment')->alias(static function (int $id): void {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_replace'][] = ['delete', $id];
        });
        stubMimeType('application/pdf');
    });

    afterEach(function (): void {
        removeMediaFiles($this->dir);
    });

    it('replaces the file with the one chosen and deletes the chosen attachment', function (array $attachment): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $result = (new UAMS_Replace_Media)->uams_save_attachment_slug($attachment + ['replace_media_for_5' => '9', 'replace-media-nonce' => 'good'], []);

        expect(file_get_contents($this->current))->toBe('new')
            ->and(file_get_contents($this->new))->toBe('new')
            ->and($GLOBALS['uams_test_replace'])->toBe([
                ['mime', 5],
                ['verify', 'good', 'replace-media-5'],
                ['delete', 9],
            ])
            ->and($result['replace_media_for_5'])->toBe('9');
    })->with([
        'post_ID' => [['post_ID' => '5']],
        'ID' => [['ID' => 5]],
        'post_ID before ID' => [['post_ID' => '5', 'ID' => 9]],
    ]);

    it('does nothing without a media id', function (array $attachment): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $result = (new UAMS_Replace_Media)->uams_save_attachment_slug($attachment, []);

        expect($result)->toBe($attachment)
            ->and($GLOBALS['uams_test_replace'])->toBe([]);
    })->with([
        'none' => [[]],
        'zero' => [['post_ID' => '0']],
        'negative' => [['ID' => -3]],
    ]);

    it('does nothing for a file that is not an application or has no replacement chosen', function (mixed $mime, array $extra): void {
        stubMimeType($mime);

        (new UAMS_Replace_Media)->uams_save_attachment_slug(['post_ID' => 5, 'replace-media-nonce' => 'good'] + $extra, []);

        expect(file_get_contents($this->current))->toBe('old')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_column($GLOBALS['uams_test_replace'], 0))->not->toContain('delete')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_column($GLOBALS['uams_test_replace'], 0))->not->toContain('verify');
    })->with([
        'an image' => ['image/png', ['replace_media_for_5' => '9']],
        'no replacement' => ['application/pdf', []],
        'an empty replacement' => ['application/pdf', ['replace_media_for_5' => '']],
        'a replacement of zero' => ['application/pdf', ['replace_media_for_5' => '0']],
    ]);

    it('dies without a valid nonce', function (array $extra): void {
        $attachment = ['post_ID' => 5, 'replace_media_for_5' => '9'] + $extra;

        expect(fn () => (new UAMS_Replace_Media)->uams_save_attachment_slug($attachment, []))
            ->toThrow(RuntimeException::class, 'You do not have access to replace the media file');
        expect(file_get_contents($this->current))->toBe('old');
    })->with([
        'no nonce' => [[]],
        'an empty nonce' => [['replace-media-nonce' => '']],
        'a bad nonce' => [['replace-media-nonce' => 'bad']],
    ]);

    it('leaves the file when the replacement is the same media or cannot be found', function (string $replacement): void {
        (new UAMS_Replace_Media)->uams_save_attachment_slug(['post_ID' => 5, 'replace_media_for_5' => $replacement, 'replace-media-nonce' => 'good'], []);

        expect(file_get_contents($this->current))->toBe('old')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_column($GLOBALS['uams_test_replace'], 0))->not->toContain('delete');
    })->with([
        'the same media' => ['5'],
        'a media without a file' => ['12'],
    ]);

    it('leaves the file when the replacement file is gone from disk', function (): void {
        unlink($this->new);

        (new UAMS_Replace_Media)->uams_save_attachment_slug(['post_ID' => 5, 'replace_media_for_5' => '9', 'replace-media-nonce' => 'good'], []);

        expect(file_get_contents($this->current))->toBe('old')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_column($GLOBALS['uams_test_replace'], 0))->not->toContain('delete');
    });

    it('leaves the replacement alone when the current media has no file', function (): void {
        when('get_attached_file')->alias(fn (int $id): mixed => $id === 9 ? $this->new : false);

        (new UAMS_Replace_Media)->uams_save_attachment_slug(['post_ID' => 5, 'replace_media_for_5' => '9', 'replace-media-nonce' => 'good'], []);

        expect(file_get_contents($this->new))->toBe('new')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            ->and(array_column($GLOBALS['uams_test_replace'], 0))->not->toContain('delete');
    });

    it('carries the slug over from the posted data', function (array $postData, mixed $expected): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $result = (new UAMS_Replace_Media)->uams_save_attachment_slug(['post_ID' => 5], $postData);

        expect($result['post_name'] ?? null)->toBe($expected);
    })->with([
        'a slug' => [['post_name' => 'a-slug'], 'a-slug'],
        'an empty slug' => [['post_name' => ''], null],
        'no slug' => [[], null],
    ]);
});
