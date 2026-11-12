<?php

/**
 * The small hook classes in setup/: UAMS_Install_Theme, UAMS_Mimes, UAMS_SquishBugs, UAMS_TinyMCE
 * and UAMS_Enclosure, with WordPress mocked.
 *
 * @see setup/class.install.php
 * @see setup/class.mimes.php
 * @see setup/class.squish_bugs.php
 * @see setup/class.uams-tinymce.php
 * @see setup/class.uams-enclosure.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.install.php');
    loadShortcodeFile('class.mimes.php');
    loadShortcodeFile('class.squish_bugs.php');
    loadShortcodeFile('class.uams-tinymce.php');
    loadShortcodeFile('class.uams-enclosure.php');
    stubEscaping();
});

describe('UAMS_Install_Theme', function (): void {
    it('hooks the setup and the embed defaults', function (): void {
        $install = new UAMS_Install_Theme;

        expect(has_filter('embed_defaults', [$install, 'uams_setup_embed_defaults']))->toBe(1)
            ->and(has_action('after_setup_theme', [$install, 'uams_setup']))->toBe(10);
    });

    it('declares theme support and the default header', function (): void {
        $support = [];
        $headers = [];
        when('add_theme_support')->alias(static function (string $feature, mixed $args = true) use (&$support): void {
            $support[] = [$feature, $args];
        });
        when('register_default_headers')->alias(static function (array $registered) use (&$headers): void {
            $headers[] = $registered;
        });

        (new UAMS_Install_Theme)->uams_setup();

        expect($support)->toBe([
            ['automatic-feed-links', true],
            ['post-thumbnails', true],
            ['post-formats', ['gallery', 'link', 'audio']],
            ['custom-header', [
                'default-image' => '%s/assets/headers/uams-pattern-grey.png',
                'random-default' => false,
                'header-text' => false,
                'width' => 1600,
                'height' => 343,
                'uploads' => true,
            ]],
        ])->and($headers)->toBe([[
            'blossoms' => [
                'url' => '%s/assets/headers/uams-pattern-grey.png',
                'thumbnail_url' => '%s/assets/headers/uams-pattern-grey-thumb.jpg',
                'description' => 'UAMS Pattern',
            ],
        ]]);
    });

    it('sets the embed size to the content width and a capped height', function (mixed $dimensions, array $expected): void {
        expect((new UAMS_Install_Theme)->uams_setup_embed_defaults($dimensions))->toBe($expected)
            ->and(UAMS_Install_Theme::WIDTH)->toBe(750);
    })->with([
        'defaults' => [['width' => 500, 'height' => 750], ['width' => 750, 'height' => 1000]],
        'dimensions that are not an array' => ['x', ['width' => 750, 'height' => 1000]],
    ]);
});

describe('UAMS_Mimes', function (): void {
    it('hooks the upload types and lists PostScript', function (): void {
        $mimes = new UAMS_Mimes;

        expect(has_filter('upload_mimes', [$mimes, 'uw_add_custom_upload_mimes']))->toBe(10)
            ->and($mimes->MIMES)->toBe(['ai|eps' => 'application/postscript']);
    });

    it('adds PostScript to the allowed types', function (mixed $existing, array $expected): void {
        expect((new UAMS_Mimes)->uw_add_custom_upload_mimes($existing))->toBe($expected);
    })->with([
        'existing types' => [['jpg' => 'image/jpeg'], ['jpg' => 'image/jpeg', 'ai|eps' => 'application/postscript']],
        'types that are not an array' => ['x', ['ai|eps' => 'application/postscript']],
    ]);
});

describe('UAMS_SquishBugs', function (): void {
    afterEach(function (): void {
        unset($_GET['s']);
    });

    it('hooks the query filter', function (): void {
        $bugs = new UAMS_SquishBugs;

        expect(has_filter('pre_get_posts', [$bugs, 'uams_search_query_filter']))->toBe(10);
    });

    it('treats an empty search of the main query as a search', function (): void {
        $_GET['s'] = '';
        $query = new class
        {
            public bool $is_search = false;

            public bool $is_home = true;

            public function is_main_query(): bool
            {
                return true;
            }
        };

        // A minimal query stand-in.
        // @phpstan-ignore argument.type
        $result = (new UAMS_SquishBugs)->uams_search_query_filter($query);

        expect($result)->toBe($query)
            ->and($query->is_search)->toBeTrue()
            ->and($query->is_home)->toBeFalse();
    });

    it('leaves the query alone otherwise', function (?string $search, bool $isMain): void {
        if ($search !== null) {
            $_GET['s'] = $search;
        }

        $query = new class($isMain)
        {
            public bool $is_search = false;

            public bool $is_home = true;

            public function __construct(private readonly bool $main) {}

            public function is_main_query(): bool
            {
                return $this->main;
            }
        };

        // A minimal query stand-in.
        // @phpstan-ignore argument.type
        (new UAMS_SquishBugs)->uams_search_query_filter($query);

        expect($query->is_search)->toBeFalse()
            ->and($query->is_home)->toBeTrue();
    })->with([
        'no search parameter' => [null, true],
        'a search term' => ['term', true],
        'an empty search of another query' => ['', false],
    ]);
});

describe('UAMS_TinyMCE', function (): void {
    it('hooks the editor settings', function (): void {
        $tiny = new UAMS_TinyMCE;

        expect(has_filter('tiny_mce_before_init', [$tiny, 'buttons']))->toBe(10);
    });

    it('removes the justify full button from the second row', function (): void {
        $settings = (new UAMS_TinyMCE)->buttons(['theme_advanced_buttons2' => 'bold,justifyfull,italic', 'other' => 'x']);

        expect($settings)->toBe(['theme_advanced_buttons2' => 'bold,italic', 'other' => 'x']);
    });

    it('leaves settings without buttons to remove', function (mixed $settings): void {
        expect((new UAMS_TinyMCE)->buttons($settings))->toBe($settings);
    })->with([
        'not an array' => ['x'],
        'no second row' => [['other' => 'x']],
        'an empty second row' => [['theme_advanced_buttons2' => '']],
        'a second row without the button' => [['theme_advanced_buttons2' => 'bold,italic']],
    ]);
});

describe('UAMS_Enclosure', function (): void {
    beforeEach(function (): void {
        $this->dir = sys_get_temp_dir().'/uams-enclosure-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/2026', 0777, true);
        file_put_contents($this->dir.'/2026/thumb.jpg', str_repeat('x', 123));
    });

    afterEach(function (): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        @unlink($this->dir.'/2026/thumb.jpg');
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        @rmdir($this->dir.'/2026');
        @rmdir($this->dir);
    });

    it('hooks the feed item', function (): void {
        $enclosure = new UAMS_Enclosure;

        expect(has_action('rss2_item', [$enclosure, 'add_post_featured_image_as_rss_item_enclosure']))->toBe(10);
    });

    it('adds nothing for a post without a featured image', function (): void {
        when('has_post_thumbnail')->justReturn(false);

        expect(render(fn () => (new UAMS_Enclosure)->add_post_featured_image_as_rss_item_enclosure()))->toBe('');
    });

    it('adds nothing when the image has no intermediate size', function (mixed $size): void {
        when('has_post_thumbnail')->justReturn(true);
        when('apply_filters')->alias(static fn (string $hook, mixed $value): mixed => $value);
        when('get_the_ID')->justReturn(7);
        when('get_post_thumbnail_id')->justReturn(9);
        when('image_get_intermediate_size')->justReturn($size);

        expect(render(fn () => (new UAMS_Enclosure)->add_post_featured_image_as_rss_item_enclosure()))->toBe('');
    })->with([
        'false' => [false],
        'no url' => [['path' => '2026/thumb.jpg']],
        'no path' => [['url' => 'https://site.test/thumb.jpg']],
        'an empty url' => [['url' => '', 'path' => '2026/thumb.jpg']],
        'an empty path' => [['url' => 'https://site.test/thumb.jpg', 'path' => '']],
    ]);

    it('adds the enclosure with the size of the file', function (): void {
        $seen = [];
        when('has_post_thumbnail')->justReturn(true);
        when('apply_filters')->alias(static function (string $hook, mixed $value) use (&$seen): mixed {
            $seen[] = [$hook, $value];

            return 'medium';
        });
        when('get_the_ID')->justReturn(7);
        when('get_post_thumbnail_id')->alias(static function (int $post) use (&$seen): int {
            $seen[] = ['post', $post];

            return 9;
        });
        when('image_get_intermediate_size')->alias(static function (int $id, string $size) use (&$seen): array {
            $seen[] = ['size', $id, $size];

            return ['url' => 'https://site.test/2026/thumb.jpg', 'path' => '2026/thumb.jpg'];
        });
        when('wp_upload_dir')->justReturn(['basedir' => $this->dir]);
        when('path_join')->alias(static fn (string $base, string $path): string => $base.'/'.$path);
        when('get_post_mime_type')->justReturn('image/jpeg');

        $output = render(fn () => (new UAMS_Enclosure)->add_post_featured_image_as_rss_item_enclosure());

        expect($output)->toBe('<enclosure url="U{https://site.test/2026/thumb.jpg}" length="123" type="A{image/jpeg}" />')
            ->and($seen)->toBe([['rss_enclosure_image_size', 'thumbnail'], ['post', 7], ['size', 9, 'medium']]);
    });

    it('reports a length of zero when the file is missing', function (): void {
        when('has_post_thumbnail')->justReturn(true);
        when('apply_filters')->alias(static fn (string $hook, mixed $value): mixed => $value);
        when('get_the_ID')->justReturn(7);
        when('get_post_thumbnail_id')->justReturn(9);
        when('image_get_intermediate_size')->justReturn(['url' => 'https://site.test/gone.jpg', 'path' => '2026/gone.jpg']);
        when('wp_upload_dir')->justReturn(['basedir' => $this->dir]);
        when('path_join')->alias(static fn (string $base, string $path): string => $base.'/'.$path);
        when('get_post_mime_type')->justReturn(false);

        $output = render(fn () => (new UAMS_Enclosure)->add_post_featured_image_as_rss_item_enclosure());

        expect($output)->toBe('<enclosure url="U{https://site.test/gone.jpg}" length="0" type="A{}" />');
    });
});
