<?php

/**
 * functions.php, run with WordPress mocked.
 *
 * The file declares global functions and runs file-scope code, so it can be included once per
 * process. `loadThemeFunctions()` includes it on first use, with the template directory pointed at
 * a fixture root (tests/Support/theme-root) that stands in for `setup/class.uams.php` and the
 * bundled ACF Pro, and records what the file-scope code registered. Every test reads that record
 * or calls a function the include declared.
 *
 * @see functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

/**
 * Include functions.php once, and return what its file-scope code did.
 *
 * @return array{hooks: list<array{string, list<mixed>}>, actions: list<list<mixed>>, acf_includes: int, uams: mixed}
 */
function loadThemeFunctions(): array
{
    static $record = null;

    if ($record !== null) {
        return $record;
    }

    $hooks = [];
    $actions = [];
    $GLOBALS['uams_fixture_acf_included'] = 0;

    stubClassExists(['acf' => false]);
    when('get_template_directory')->justReturn(__DIR__.'/../Support/theme-root');
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
    when('add_action')->alias(static function (mixed ...$args) use (&$hooks): void {
        $hooks[] = ['add_action', $args];
    });
    when('add_filter')->alias(static function (mixed ...$args) use (&$hooks): void {
        $hooks[] = ['add_filter', $args];
    });
    when('do_action')->alias(static function (mixed ...$args) use (&$actions): void {
        $actions[] = $args;
    });

    include dirname(__DIR__, 2).'/functions.php';

    return $record = [
        'hooks' => $hooks,
        'actions' => $actions,
        'acf_includes' => $GLOBALS['uams_fixture_acf_included'],
        'uams' => $UAMS ?? null,
    ];
}

beforeEach(function (): void {
    loadThemeFunctions();
    when('get_template_directory')->justReturn(__DIR__.'/../Support/theme-root');
    when('get_template_directory_uri')->justReturn('https://t.test/theme');
});

describe('functions.php at include', function (): void {
    it('registers every hook, in order, with its priority and argument count', function (): void {
        expect(loadThemeFunctions()['hooks'])->toBe([
            ['add_action', ['admin_enqueue_scripts', 'webcenter_scripts']],
            ['add_filter', ['acf/settings/path', 'my_acf_settings_path']],
            ['add_filter', ['acf/settings/dir', 'my_acf_settings_dir']],
            ['add_filter', ['body_class', 'uams_custom_field_body_class']],
            ['add_action', ['after_setup_theme', 'woocommerce_support']],
            ['add_filter', ['allowed_http_origins', 'add_allowed_origins']],
            ['add_action', ['wp_enqueue_scripts', 'custom_load_font_awesome']],
            ['add_filter', ['script_loader_tag', 'add_defer_attribute', 10, 2]],
            ['add_action', ['gform_after_submission', 'access_entry_via_field', 10, 2]],
            ['add_filter', ['embed_oembed_html', 'wrap_oembed_html', 99, 4]],
            ['add_filter', ['video_embed_html', 'wrap_oembed_html', 10, 4]],
        ]);
    });

    it('builds the UAMS object and offers it to child themes', function (): void {
        $record = loadThemeFunctions();

        expect($record['uams'])->toBeInstanceOf(UAMS::class)
            ->and($record['actions'])->toBe([['extend_uams_object', $record['uams']]]);
    });

    it('includes the bundled ACF once, only when the plugin is missing', function (): void {
        expect(loadThemeFunctions()['acf_includes'])->toBe(1)
            ->and(function_exists('my_acf_settings_path'))->toBeTrue()
            ->and(function_exists('my_acf_settings_dir'))->toBeTrue();
    });
});

describe('setup_uams_object', function (): void {
    it('returns a new UAMS object and runs the extension action with it', function (): void {
        $received = null;
        when('do_action')->alias(static function (string $hook, UAMS $uams) use (&$received): void {
            $received = [$hook, $uams];
        });

        $uams = setup_uams_object();

        expect($uams)->toBeInstanceOf(UAMS::class)->and($received)->toBe(['extend_uams_object', $uams]);
    });
});

describe('webcenter_scripts', function (): void {
    it('enqueues the color picker script when the color picker is enqueued', function (): void {
        $enqueued = [];
        when('wp_script_is')->alias(static fn (string $handle, string $list): bool => [$handle, $list] === ['wp-color-picker', 'enqueued']);
        when('wp_enqueue_script')->alias(static function (string ...$args) use (&$enqueued): void {
            $enqueued[] = $args;
        });

        webcenter_scripts();

        expect($enqueued)->toBe([['webcenter-scripts', 'https://t.test/theme/assets/admin/js/custom-color.js']]);
    });

    it('enqueues nothing otherwise', function (): void {
        $enqueued = 0;
        when('wp_script_is')->justReturn(false);
        when('wp_enqueue_script')->alias(static function () use (&$enqueued): void {
            $enqueued++;
        });

        webcenter_scripts();

        expect($enqueued)->toBe(0);
    });
});

describe('the bundled ACF paths', function (): void {
    it('points ACF at the theme copy', function (): void {
        expect(my_acf_settings_path('/old/'))->toBe(__DIR__.'/../Support/theme-root/_includes/acf-pro/')
            ->and(my_acf_settings_dir('/old/'))->toBe('https://t.test/theme/_includes/acf-pro/');
    });
});

describe('uams_custom_field_body_class', function (): void {
    /**
     * @param  array<string, mixed>  $meta
     */
    function bodyClass(array $classes, bool $page, mixed $actionMenu, mixed $primary): array
    {
        $GLOBALS['post'] = (object) ['ID' => 7];
        when('is_page')->justReturn($page);
        when('get_post_meta')->alias(static fn (int $id, string $key, bool $single): mixed => [$id, $key, $single] === [7, 'action_menu_active', true] ? $actionMenu : '');
        when('get_option')->alias(static fn (string $name): mixed => $name === 'primary_uams_site' ? $primary : false);

        return uams_custom_field_body_class($classes);
    }

    it('adds the action bar class on a page with the action menu on', function (): void {
        expect(bodyClass(['page'], true, '1', false))->toBe(['page', 'action-bar']);
    });

    it('adds the action bar class when the value is the number one', function (): void {
        expect(bodyClass([], true, 1, false))->toBe(['action-bar']);
    });

    it('adds no action bar class off a page, or when the menu is off', function (bool $page, mixed $value): void {
        expect(bodyClass(['x'], $page, $value, false))->toBe(['x']);
    })->with([
        'not a page' => [false, '1'],
        'menu off' => [true, '0'],
        'menu unset' => [true, ''],
    ]);

    it('adds the primary class for the primary site', function (): void {
        expect(bodyClass(['x'], false, '', '1'))->toBe(['x', 'uams-primary']);
    });

    it('adds both classes, the action bar first', function (): void {
        expect(bodyClass([], true, '1', '1'))->toBe(['action-bar', 'uams-primary']);
    });
});

describe('woocommerce_support', function (): void {
    it('declares WooCommerce support', function (): void {
        $declared = [];
        when('add_theme_support')->alias(static function (string $feature) use (&$declared): void {
            $declared[] = $feature;
        });

        woocommerce_support();

        expect($declared)->toBe(['woocommerce']);
    });
});

describe('add_allowed_origins', function (): void {
    it('appends the UAMS origins to the existing ones', function (): void {
        expect(add_allowed_origins(['https://a.test']))->toBe(['https://a.test', 'http://www.uams.edu', 'http://www.uams.dev']);
    });
});

describe('custom_load_font_awesome', function (): void {
    it('enqueues the Font Awesome script and the version 4 shims in the footer', function (): void {
        $enqueued = [];
        when('get_bloginfo')->alias(static fn (string $show): string => $show === 'template_directory' ? 'https://t.test/theme' : '');
        when('wp_enqueue_script')->alias(static function (mixed ...$args) use (&$enqueued): void {
            $enqueued[] = $args;
        });

        custom_load_font_awesome();

        expect($enqueued)->toBe([
            ['font-awesome-all', 'https://t.test/theme/js/fontawesome-all.min.js', [], null, true],
            ['font-awesome-v4', 'https://t.test/theme/js/fontawesome-v4-shims.min.js', [], null, true],
        ]);
    });
});

describe('add_defer_attribute', function (): void {
    it('defers the Font Awesome scripts', function (string $handle): void {
        expect(add_defer_attribute('<script src="x.js"></script>', $handle))->toBe('<script defer src="x.js"></script>');
    })->with(['font-awesome-all', 'font-awesome-light', 'font-awesome-brands', 'font-awesome-v4']);

    it('leaves other scripts alone', function (): void {
        expect(add_defer_attribute('<script src="x.js"></script>', 'jquery'))->toBe('<script src="x.js"></script>');
    });

    it('matches the handle exactly', function (): void {
        expect(add_defer_attribute('<script src="x.js"></script>', 'font-awesome'))->toBe('<script src="x.js"></script>');
    });
});

describe('access_entry_via_field', function (): void {
    it('prints a script that pushes the submission to the data layer', function (): void {
        ob_start();
        access_entry_via_field(['source_url' => 'https://x.test/form', 'id' => 5], ['id' => 3, 'title' => 'Contact']);
        $html = (string) ob_get_clean();

        expect($html)->toBe(
            '<script>var entryField = {"source_url":"https:\/\/x.test\/form","id":5};'
            .'var formData = {"id":3,"title":"Contact"};'
            .'var dataLayer = window.dataLayer || [];'
            ."dataLayer.push({'event':'formSubmit','formTitle': 'Submitted', 'submissionID': formData['title'] + ' ('+formData['id']+') | ' + entryField['source_url'], 'formFields':entryField });"
            .'console.log(dataLayer)</script>'
        );
    });
});

describe('wrap_oembed_html', function (): void {
    it('wraps video from the known hosts in a player region', function (string $url): void {
        expect(wrap_oembed_html('<iframe></iframe>', $url))->toBe(
            '<div class="nc-video-player" role="region" aria-label="video" tabindex="-1"><div class="tube-wrapper"><iframe></iframe></div></div>'
        );
    })->with([
        'https://youtube.com/watch?v=1',
        'https://www.youtube.com/watch?v=1',
        'https://youtu.be/1',
        'https://www.youtu.be/1',
        'https://vimeo.com/1',
    ]);

    it('leaves other embeds alone', function (): void {
        expect(wrap_oembed_html('<iframe></iframe>', 'https://example.org/v'))->toBe('<iframe></iframe>');
    });

    it('leaves the embed alone without a URL, or with one that is not a string', function (mixed $url): void {
        expect(wrap_oembed_html('<iframe></iframe>', $url))->toBe('<iframe></iframe>');
    })->with(['empty' => [''], 'an array' => [['https://youtu.be/1']], 'null' => [null], 'an integer' => [5]]);

    it('takes the URL, attributes and post id as optional arguments', function (): void {
        expect(wrap_oembed_html('<iframe></iframe>'))->toBe('<iframe></iframe>');
    });
});
