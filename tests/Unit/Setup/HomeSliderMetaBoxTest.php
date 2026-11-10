<?php

/**
 * UAMS_Home_Slider_Meta_Box, the options box on the home slider post type and its save handler, with
 * WordPress mocked.
 *
 * The autosave test defines DOING_AUTOSAVE for the rest of the process, so it stays the last test.
 *
 * @see setup/class.uams-homeslider-meta-box.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-homeslider-meta-box.php');
    stubEscaping();
    $_POST = [];
});

afterEach(function (): void {
    $_POST = [];
});

/**
 * Stand in for the capability, nonce and meta functions save() calls, recording each call as
 * [function, ...arguments] in `$GLOBALS['uams_test_slider']`.
 *
 * @param  array<string, bool>  $caps  Capability => whether the user has it.
 */
function stubSliderSave(bool $nonceValid = true, array $caps = []): void
{
    $GLOBALS['uams_test_slider'] = [];

    when('wp_verify_nonce')->alias(static function (mixed $nonce, string $action) use ($nonceValid): bool {
        $GLOBALS['uams_test_slider'][] = ['nonce', $nonce, $action];

        return $nonceValid;
    });
    when('current_user_can')->alias(static function (string $cap, int $post) use ($caps): bool {
        $GLOBALS['uams_test_slider'][] = ['can', $cap, $post];

        return $caps[$cap] ?? false;
    });
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
    when('update_post_meta')->alias(static function (int $post, string $key, mixed $value): void {
        $GLOBALS['uams_test_slider'][] = ['update', $post, $key, $value];
    });
}

it('hooks the box and the save handler', function (): void {
    $box = new UAMS_Home_Slider_Meta_Box;

    expect(has_action('add_meta_boxes', [$box, 'hs_add_meta_box']))->toBe(10)
        ->and(has_action('save_post', [$box, 'save']))->toBe(10);
});

describe('hs_add_meta_box', function (): void {
    it('adds the box to the home slider post type', function (): void {
        $added = [];
        when('add_meta_box')->alias(static function (mixed ...$args) use (&$added): void {
            $added[] = $args;
        });
        $box = new UAMS_Home_Slider_Meta_Box;

        $box->hs_add_meta_box('home_slider');

        expect($added)->toBe([['hs-meta', 'Home Slider Options', [$box, 'hs_meta_box_function'], 'home_slider', 'side', 'high']]);
    });

    it('adds nothing to another post type', function (string $type): void {
        when('add_meta_box')->alias(static function (): never {
            throw new RuntimeException('box added');
        });

        (new UAMS_Home_Slider_Meta_Box)->hs_add_meta_box($type);

        expect($type)->not->toBe('home_slider');
    })->with(['page', 'post', 'Home_Slider']);
});

it('prints the fields with their stored values', function (): void {
    $nonce = [];
    $meta = [];
    when('wp_nonce_field')->alias(static function (string $action, string $name) use (&$nonce): void {
        $nonce[] = [$action, $name];
    });
    when('get_post_meta')->alias(static function (int $id, string $key, bool $single) use (&$meta): string {
        $meta[] = [$id, $key, $single];

        return $key === 'slidelink' ? 'https://x.test/?a=1&b=2' : 'https://x.test/mobile.jpg';
    });

    $html = render(fn () => (new UAMS_Home_Slider_Meta_Box)->hs_meta_box_function((object) ['ID' => 12]));

    expect($nonce)->toBe([['mobileimage_nonce', 'mobileimage_name']])
        ->and($meta)->toBe([[12, 'mobileimage', true], [12, 'slidelink', true]])
        ->and($html)->toContain("<div><p><b>Slide Link</b><br /><input type='text' class='text' name='slidelink' value='A{https://x.test/?a=1&b=2}'></p>")
        ->and($html)->toContain("<p><b>Mobile Slider Image</b><br /><input type='text' class='meta-image' name='mobileimagetext' value='A{https://x.test/mobile.jpg}'><input type='button' class='button image-upload' value='Browse'></p>")
        ->and($html)->toContain("<div class='image-preview'><img src='U{https://x.test/mobile.jpg}' style='max-width: 140px;'></div></div>")
        ->and($html)->toContain('meta_image_frame = wp.media.frames.meta_image_frame = wp.media({');
});

describe('save', function (): void {
    it('returns the post id without the nonce field', function (): void {
        stubSliderSave();

        expect((new UAMS_Home_Slider_Meta_Box)->save(5))->toBe(5)
            ->and($GLOBALS['uams_test_slider'])->toBe([]);
    });

    it('returns the post id when the nonce is invalid', function (): void {
        stubSliderSave(false);
        $_POST = ['mobileimage_name' => 'abc', 'mobileimagetext' => 'x'];

        expect((new UAMS_Home_Slider_Meta_Box)->save(5))->toBe(5)
            ->and($GLOBALS['uams_test_slider'])->toBe([['nonce', 'abc', 'mobileimage_nonce']]);
    });

    it('requires edit_page for a home slider and saves nothing without it', function (): void {
        stubSliderSave(true, ['edit_post' => true]);
        $_POST = ['mobileimage_name' => 'abc', 'post_type' => 'home_slider', 'mobileimagetext' => 'x'];

        expect((new UAMS_Home_Slider_Meta_Box)->save(5))->toBe(5)
            ->and($GLOBALS['uams_test_slider'])->toBe([['nonce', 'abc', 'mobileimage_nonce'], ['can', 'edit_page', 5]]);
    });

    it('requires edit_post for any other post type and saves nothing without it', function (array $post): void {
        stubSliderSave(true, ['edit_page' => true]);
        $_POST = ['mobileimage_name' => 'abc', 'mobileimagetext' => 'x'] + $post;

        expect((new UAMS_Home_Slider_Meta_Box)->save(5))->toBe(5)
            ->and($GLOBALS['uams_test_slider'])->toBe([['nonce', 'abc', 'mobileimage_nonce'], ['can', 'edit_post', 5]]);
    })->with(['another type' => [['post_type' => 'page']], 'no type' => [[]]]);

    it('saves the sanitized mobile image and link of a home slider', function (): void {
        stubSliderSave(true, ['edit_page' => true]);
        $_POST = ['mobileimage_name' => 'abc', 'post_type' => 'home_slider', 'mobileimagetext' => 'm.jpg', 'slidelink' => 'https://x.test'];

        $result = (new UAMS_Home_Slider_Meta_Box)->save(5);

        expect($result)->toBeNull()
            ->and($GLOBALS['uams_test_slider'])->toBe([
                ['nonce', 'abc', 'mobileimage_nonce'],
                ['can', 'edit_page', 5],
                ['update', 5, 'mobileimage', 'T{m.jpg}'],
                ['update', 5, 'slidelink', 'T{https://x.test}'],
            ]);
    });

    it('saves only the fields that were posted', function (array $post, array $updates): void {
        stubSliderSave(true, ['edit_post' => true]);
        $_POST = ['mobileimage_name' => 'abc'] + $post;

        (new UAMS_Home_Slider_Meta_Box)->save(5);

        expect(array_slice($GLOBALS['uams_test_slider'], 2))->toBe($updates);
    })->with([
        'only the image' => [['mobileimagetext' => 'm.jpg'], [['update', 5, 'mobileimage', 'T{m.jpg}']]],
        'only the link' => [['slidelink' => 'l'], [['update', 5, 'slidelink', 'T{l}']]],
        'neither' => [[], []],
    ]);

    it('saves nothing during an autosave', function (): void {
        stubSliderSave(true, ['edit_post' => true, 'edit_page' => true]);
        $_POST = ['mobileimage_name' => 'abc', 'mobileimagetext' => 'x'];
        define('DOING_AUTOSAVE', true);

        expect((new UAMS_Home_Slider_Meta_Box)->save(5))->toBe(5)
            ->and($GLOBALS['uams_test_slider'])->toBe([['nonce', 'abc', 'mobileimage_nonce']]);
    });
});
