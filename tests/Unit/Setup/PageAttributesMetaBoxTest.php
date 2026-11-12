<?php

/**
 * UAMS_Page_Attributes_Meta_Box, the replacement for the Page Attributes box and the handler that saves
 * its options, with WordPress mocked.
 *
 * @see setup/class.uams-page-attributes-meta-box.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-page-attributes-meta-box.php');
    stubEscaping();
    when('esc_html')->alias(static fn (string $value): string => 'H{'.$value.'}');
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('checked')->alias(static function (mixed $checked, mixed $current = true, bool $echo = true): string {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore cast.string, cast.string
        $result = (string) $checked === (string) $current ? "checked='checked'" : '';

        if ($echo) {
            echo $result;
        }

        return $result;
    });
    when('wp_nonce_field')->alias(static function (string $action, string $name): void {
        echo '[nonce '.$action.' '.$name.']';
    });
    $_POST = [];
});

afterEach(function (): void {
    $_POST = [];
});

/**
 * A post as the box receives it.
 *
 * @param  array<string, mixed>  $properties
 */
function pageBoxPost(array $properties = []): WP_Post
{
    return wpPost($properties + ['post_type' => 'page', 'ID' => 7, 'post_parent' => 3, 'menu_order' => 4, 'page_template' => '']);
}

/**
 * Collapse runs of whitespace, so a test compares markup and not indentation.
 */
function pageBoxSquish(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ', $html));
}

it('hooks the box, the save handler and the style', function (): void {
    $box = new UAMS_Page_Attributes_Meta_Box;

    expect(has_action('add_meta_boxes', [$box, 'replace_meta_box']))->toBe(10)
        ->and(has_action('save_post', [$box, 'save_postdata']))->toBe(10)
        ->and(has_action('admin_head', [$box, 'custom_style']))->toBe(10)
        ->and($box->HIDDEN)->toBe(['No Sidebar'])
        ->and(UAMS_Page_Attributes_Meta_Box::ID)->toBe('pageparentdiv')
        ->and(UAMS_Page_Attributes_Meta_Box::TITLE)->toBe('Page Attributes')
        ->and(UAMS_Page_Attributes_Meta_Box::POSTTYPE)->toBe('page')
        ->and(UAMS_Page_Attributes_Meta_Box::POSITION)->toBe('side')
        ->and(UAMS_Page_Attributes_Meta_Box::PRIORITY)->toBe('core');
});

it('replaces the core box with its own', function (): void {
    $calls = [];
    when('remove_meta_box')->alias(static function (mixed ...$args) use (&$calls): void {
        $calls[] = ['remove', ...$args];
    });
    when('add_meta_box')->alias(static function (mixed ...$args) use (&$calls): void {
        $calls[] = ['add', ...$args];
    });
    $box = new UAMS_Page_Attributes_Meta_Box;

    $box->replace_meta_box();

    expect($calls)->toBe([
        ['remove', 'pageparentdiv', 'page', 'side'],
        ['add', 'uamspageparentdiv', 'Page Attributes', [$box, 'page_attributes_meta_box'], 'page', 'side', 'core'],
    ]);
});

it('enqueues the admin template style', function (): void {
    $enqueued = [];
    when('wp_enqueue_style')->alias(static function (string $handle, string $src) use (&$enqueued): void {
        $enqueued[] = [$handle, $src];
    });

    (new UAMS_Page_Attributes_Meta_Box)->custom_style();

    expect($enqueued)->toBe([['uams-admin-template', 'https://site.test/theme/assets/admin/css/uams.admin.template.css']]);
});

describe('page_template_dropdown', function (): void {
    beforeEach(function (): void {
        when('get_post')->justReturn(pageBoxPost());
        when('get_page_templates')->justReturn([
            'Small Hero' => 'templates/small.php',
            'No Sidebar' => 'templates/no-sidebar.php',
            'Big Hero' => 'templates/big.php',
            'Custom' => 'templates/custom.php',
        ]);
    });

    it('lists the default and the templates in name order, with previews and the current one checked', function (): void {
        $html = render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_template_dropdown('templates/big.php'));

        expect($html)->toBe(
            "<div class='uams-admin-template'>"
            ."<p><input type='radio' name='page_template' value='default'  >Default Template</input> (<a id='enchanced-preview' href='#'>preview<span><img src='U{https://site.test/theme/assets/images/template-default.jpg}' alt='' width='300px' height='' /></span></a>)</p>"
            ."<p><input type='radio' name='page_template' value='A{templates/big.php}' checked='checked' >H{Big Hero}</input> (<a id='enchanced-preview' href='#'>preview<span><img src='U{https://site.test/theme/assets/images/template-big-hero.jpg}' alt='' width='300px' height='' /></span></a>)</p>"
            ."<p><input type='radio' name='page_template' value='A{templates/custom.php}'  >H{Custom}</input> </p>"
            ."<p><input type='radio' name='page_template' value='A{templates/small.php}'  >H{Small Hero}</input> (<a id='enchanced-preview' href='#'>preview<span><img src='U{https://site.test/theme/assets/images/template-small-hero.jpg}' alt='' width='300px' height='' /></span></a>)</p>"
            .'</div>'
        );
    });

    it('checks the default template', function (): void {
        $html = render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_template_dropdown('default'));

        expect($html)->toContain("value='default' checked='checked' >Default Template")
            ->and(substr_count($html, "checked='checked'"))->toBe(1);
    });

    it('skips the hidden templates', function (): void {
        $html = render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_template_dropdown());

        expect($html)->not->toContain('No Sidebar');
    });
});

describe('page_attributes_meta_box', function (): void {
    beforeEach(function (): void {
        $this->dropdown = [];
        $dropdown = &$this->dropdown;
        when('apply_filters')->alias(static function (string $hook, mixed $args, mixed $post) use (&$dropdown): mixed {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore property.nonObject
            $dropdown[] = [$hook, $args, $post->ID];

            return $args;
        });
        when('wp_dropdown_pages')->alias(static fn (array $args): string => '<select>'.$args['selected'].'</select>');
        when('get_page_templates')->justReturn([]);
        when('get_post_meta')->alias(static fn (int $id, string $key, bool $single): string => match ($key) {
            'parent' => 'on',
            'sidebar' => '',
            'breadcrumb' => 'on',
            default => 'wrong',
        });
    });

    it('prints the parent, sidebar, breadcrumb and order options of a hierarchical page', function (): void {
        $asked = [];
        when('get_post_type_object')->alias(static function (string $type) use (&$asked): object {
            $asked[] = $type;

            return (object) ['hierarchical' => true];
        });
        $post = pageBoxPost();

        $html = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box($post)));

        expect($asked)->toBe(['page'])
            ->and($this->dropdown)->toBe([['page_attributes_dropdown_pages_args', [
                'post_type' => 'page',
                'exclude_tree' => 7,
                'selected' => 3,
                'name' => 'parent_id',
                'show_option_none' => '(no parent)',
                'sort_column' => 'menu_order, post_title, sidebar, parent',
                'echo' => 0,
            ], 7]])
            ->and($html)->toBe(pageBoxSquish(
                '<p><strong>Parent</strong></p> <label class="screen-reader-text" for="parent_id">Parent</label> '
                .'<select>3</select>[nonce parent_nonce parent_name] '
                .'<p><input type="checkbox" id="parent_id" name="parentcheck" value="on" checked=\'checked\' />Hide from menu</p> '
                .'[nonce sidebar_nonce sidebar_name][nonce breadcrumb_nonce breadcrumb_name] '
                .'<p><strong>Sidebar</strong></p> <label class="screen-reader-text" for="sidebar">Sidebar</label> '
                .'<p><input type="checkbox" id="sidebar_id" name="sidebarcheck" value="on"  />No Sidebar</p> '
                .'<p><strong>Breadcrumbs</strong></p> <label class="screen-reader-text" for="breadcrumbs">Breadcrumbs</label> '
                .'<p><input type="checkbox" id="breadcrumb_id" name="breadcrumbcheck" value="on" checked=\'checked\' />Hide Breadcrumbs</p> '
                .'<p><strong>Order</strong></p> '
                .'<p><label class="screen-reader-text" for="menu_order">Order</label><input name="menu_order" type="text" size="4" id="menu_order" value="A{4}" /></p> '
                .'<p>Need help? Use the Help tab in the upper right of your screen.</p>'
            ));
    });

    it('leaves out the parent options when the post type is not hierarchical or has no parents to pick', function (mixed $type, string $pages): void {
        when('get_post_type_object')->justReturn($type);
        when('wp_dropdown_pages')->justReturn($pages);

        $html = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box(pageBoxPost())));

        expect($html)->not->toContain('Parent')
            ->and($html)->toContain('<strong>Sidebar</strong>');
    })->with([
        'not hierarchical' => [(object) ['hierarchical' => false], '<select></select>'],
        'no post type object' => [null, '<select></select>'],
        'no pages' => [(object) ['hierarchical' => true], ''],
    ]);

    it('prints the template choices of a page that has templates', function (): void {
        when('get_post_type_object')->justReturn();
        when('get_page_templates')->justReturn(['Big Hero' => 'templates/big.php']);
        when('get_post')->justReturn(pageBoxPost());

        $withTemplate = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box(pageBoxPost(['page_template' => 'templates/big.php']))));
        $withDefault = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box(pageBoxPost())));

        expect($withTemplate)->toContain('<p><strong>Template</strong></p> <label class="screen-reader-text" for="page_template">Page Template</label> <div class=\'uams-admin-template\'>')
            ->and($withTemplate)->toContain("value='A{templates/big.php}' checked='checked' >H{Big Hero}")
            ->and($withDefault)->toContain("value='default' checked='checked' >Default Template");
    });

    it('leaves out the template choices for another post type or a page without templates', function (string $type): void {
        when('get_post_type_object')->justReturn();
        when('get_page_templates')->justReturn($type === 'page' ? [] : ['Big Hero' => 'templates/big.php']);

        $html = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box(pageBoxPost(['post_type' => $type]))));

        expect($html)->not->toContain('Template')
            ->and($html)->toContain('<strong>Order</strong>');
    })->with(['a page without templates' => ['page'], 'another post type' => ['post']]);

    it('leaves out the help line for another post type', function (): void {
        when('get_post_type_object')->justReturn();

        $html = pageBoxSquish(render(fn () => (new UAMS_Page_Attributes_Meta_Box)->page_attributes_meta_box(pageBoxPost(['post_type' => 'post']))));

        expect($html)->toEndWith('<p></p>');
    });
});

describe('save_postdata', function (): void {
    beforeEach(function (): void {
        $GLOBALS['uams_test_page_box'] = [];
        when('get_post_type')->alias(static function (int $id): string {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_page_box'][] = ['type', $id];

            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore return.type
            return $GLOBALS['uams_test_page_type'] ?? 'page';
        });
        when('check_admin_referer')->alias(static function (string $action, string $name): bool {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_page_box'][] = ['referer', $action, $name];

            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore return.type
            return $GLOBALS['uams_test_page_referer'] ?? true;
        });
        when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
        when('update_post_meta')->alias(static function (int $id, string $key, mixed $value): void {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_page_box'][] = ['update', $id, $key, $value];
        });
        when('delete_post_meta')->alias(static function (int $id, string $key): void {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $GLOBALS['uams_test_page_box'][] = ['delete', $id, $key];
        });
    });

    afterEach(function (): void {
        unset($GLOBALS['uams_test_page_type'], $GLOBALS['uams_test_page_referer']);
    });

    it('returns the id as an integer and saves nothing for another post type', function (): void {
        $GLOBALS['uams_test_page_type'] = 'post';
        $_POST = ['sidebar_name' => 'n', 'sidebarcheck' => 'on'];

        expect((new UAMS_Page_Attributes_Meta_Box)->save_postdata('9'))->toBe(9)
            ->and($GLOBALS['uams_test_page_box'])->toBe([['type', 9]]);
    });

    it('defaults to post zero', function (): void {
        expect((new UAMS_Page_Attributes_Meta_Box)->save_postdata())->toBe(0)
            ->and($GLOBALS['uams_test_page_box'])->toBe([['type', 0]]);
    });

    it('saves nothing without a nonce field', function (): void {
        $_POST = ['sidebarcheck' => 'on', 'breadcrumbcheck' => 'on', 'parentcheck' => 'on'];

        expect((new UAMS_Page_Attributes_Meta_Box)->save_postdata(9))->toBe(9)
            ->and($GLOBALS['uams_test_page_box'])->toBe([['type', 9]]);
    });

    it('saves nothing when the referer check fails', function (): void {
        $GLOBALS['uams_test_page_referer'] = false;
        $_POST = ['sidebar_name' => 'n', 'sidebarcheck' => 'on', 'breadcrumb_name' => 'n', 'parent_name' => 'n'];

        (new UAMS_Page_Attributes_Meta_Box)->save_postdata(9);

        expect($GLOBALS['uams_test_page_box'])->toBe([
            ['type', 9],
            ['referer', 'sidebar_nonce', 'sidebar_name'],
            ['referer', 'breadcrumb_nonce', 'breadcrumb_name'],
            ['referer', 'parent_nonce', 'parent_name'],
        ]);
    });

    it('saves each option that is posted', function (): void {
        $_POST = [
            'sidebar_name' => 'n', 'sidebarcheck' => 'on',
            'breadcrumb_name' => 'n', 'breadcrumbcheck' => 'yes',
            'parent_name' => 'n', 'parentcheck' => 'maybe',
        ];

        (new UAMS_Page_Attributes_Meta_Box)->save_postdata(9);

        expect($GLOBALS['uams_test_page_box'])->toBe([
            ['type', 9],
            ['referer', 'sidebar_nonce', 'sidebar_name'],
            ['update', 9, 'sidebar', 'T{on}'],
            ['referer', 'breadcrumb_nonce', 'breadcrumb_name'],
            ['update', 9, 'breadcrumb', 'T{yes}'],
            ['referer', 'parent_nonce', 'parent_name'],
            ['update', 9, 'parent', 'T{maybe}'],
        ]);
    });

    it('deletes each option whose checkbox is not posted', function (): void {
        $_POST = ['sidebar_name' => 'n', 'breadcrumb_name' => 'n', 'parent_name' => 'n'];

        (new UAMS_Page_Attributes_Meta_Box)->save_postdata(9);

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type, argument.type
        expect(array_filter($GLOBALS['uams_test_page_box'], static fn (array $call): bool => $call[0] === 'delete'))->toBe([
            2 => ['delete', 9, 'sidebar'],
            4 => ['delete', 9, 'breadcrumb'],
            6 => ['delete', 9, 'parent'],
        ]);
    });
});
