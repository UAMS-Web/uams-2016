<?php

/**
 * The blogroll widget and its shortcode, with WordPress mocked.
 *
 * @see widgets/class.uams-blogroll.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubShortcodeApi();
    stubFunctionExists(['uams_get_permalink' => false]);
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore cast.int
    when('absint')->alias(static fn (mixed $value): int => abs((int) $value));
    when('checked')->alias(static function (mixed $checked, mixed $current): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore cast.string, cast.string
        echo (string) preg_replace('/^A\{(.*)\}$/', '$1', (string) $checked) === (string) $current ? ' CHK' : '';
    });
    when('get_permalink')->alias(static fn (int $id): string => '/post-'.$id.'/');
    when('has_excerpt')->alias(static fn (int $id): bool => $id === 2);
    when('wp_trim_words')->alias(static fn (string $text): string => 'TR{'.$text.'}');
    when('wpautop')->alias(static fn (string $text): string => 'P{'.$text.'}');
    when('get_the_post_thumbnail')->alias(static fn (int $id, string $size, array $attr): string => 'IMG{'.$id.','.$size.','.$attr['alt'].'}');
    when('get_post_thumbnail_id')->alias(static fn (int $id): int => 90 + $id);
    when('get_post_meta')->alias(static fn (int $id, string $key, bool $single): string => 'alt'.$id.$key.(int) $single);
    when('get_the_author_meta')->alias(static fn (string $field, int $author): string => $field.$author);
    when('get_option')->alias(static fn (string $name): string => $name === 'date_format' ? 'Y-m-d' : '');
    when('get_the_time')->alias(static fn (string $format, int $id): string => 'T'.$format.$id);
    when('has_post_thumbnail')->alias(static fn (int $id): bool => $id === 1);
    when('wp_get_attachment_image_src')->alias(static fn (int $id, string $size): array => ['/img-'.$id.'-'.$size.'.jpg']);
});

/**
 * A post, with the fields the blogroll reads.
 *
 * @param  array<string, mixed>  $properties
 */
function blogPost(array $properties = []): object
{
    return (object) ($properties + [
        'ID' => 1,
        'post_title' => 'First',
        'post_excerpt' => 'Excerpt',
        'post_content' => 'Content',
        'post_author' => 4,
    ]);
}

/**
 * Make get_posts() return the posts, and record the arguments it was given.
 *
 * @param  list<object>  $posts
 * @return ArrayObject<int, mixed>
 */
function stubBlogPosts(array $posts): ArrayObject
{
    $received = new ArrayObject;
    when('get_posts')->alias(static function (array $params) use ($posts, $received): array {
        $received[] = $params;

        return $posts;
    });

    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore return.type
    return $received;
}

describe('construction', function (): void {
    it('registers the widget when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-blogroll.php'))->toBe(['UAMS_Blogroll']);
    });

    it('describes the widget and registers the shortcode', function (): void {
        $shortcodes = recordShortcodes();

        $widget = new UAMS_Blogroll;

        expect($widget->id_base)->toBe('blogroll')
            ->and($widget->name)->toBe('UAMS Blogroll')
            ->and($widget->widget_options)->toBe(['classname' => 'uams-blogroll', 'description' => 'Pull and display your sites blog posts'])
            ->and($shortcodes->getArrayCopy())->toBe([['blogroll', [$widget, 'shortcode']]]);
    });
});

describe('widget', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('runs the shortcode with the stored settings between the wrappers', function (): void {
        expectApplied('widget_title')->once()->with('Blog');

        $html = render(fn () => (new UAMS_Blogroll)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], ['title' => 'Blog', 'number' => '3', 'radio_style' => 'card']));

        expect($html)->toBe('<aside><h2>H{Blog}</h2>D{[blogroll number=3 style=A{card}/]}</aside>');
    });

    it('falls back to the default title, count and style', function (): void {
        $html = render(fn () => (new UAMS_Blogroll)->widget(['before_widget' => '', 'after_widget' => ''], []));

        expect($html)->toBe('<h2>H{UAMS Blogroll}</h2>D{[blogroll number=2 style=default/]}');
    });

    it('leaves out the title when the filter empties it', function (): void {
        when('apply_filters')->justReturn('');

        $html = render(fn () => (new UAMS_Blogroll)->widget(['before_widget' => '', 'after_widget' => ''], ['title' => 'Blog']));

        expect($html)->toBe('D{[blogroll number=2 style=default/]}');
    });

    it('escapes the style attribute', function (): void {
        $html = render(fn () => (new UAMS_Blogroll)->widget(['before_widget' => '', 'after_widget' => ''], ['radio_style' => 'mini']));

        expect($html)->toContain('style=A{mini}/]');
    });
});

describe('update', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('sanitizes the settings', function (): void {
        $result = (new UAMS_Blogroll)->update(['title' => 'Blog', 'number' => '-4', 'radio_style' => 'card'], ['kept' => 1]);

        expect($result)->toBe(['kept' => 1, 'title' => 'T{Blog}', 'number' => 4, 'radio_style' => 'T{card}']);
    });

    it('falls back to the defaults for empty settings', function (array $new): void {
        $result = (new UAMS_Blogroll)->update($new + ['title' => ''], []);

        expect($result['number'])->toBe(2)
            ->and($result['radio_style'])->toBe('default');
    })->with([
        'missing' => [[]],
        'empty' => [['number' => '', 'radio_style' => '']],
        'zero' => [['number' => '0', 'radio_style' => '0']],
    ]);
});

describe('form', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('prints the stored settings with the style checked', function (): void {
        $html = squishWidgetHtml(render(fn () => (new UAMS_Blogroll)->form(['title' => 'Blog', 'number' => '-3', 'radio_style' => 'card'])));

        expect($html)->toContain('name="A{name-title}" type="text" value="A{A{Blog}}"')
            ->and($html)->toContain('name="A{name-number}" type="text" value="A{3}" size="3"')
            ->and($html)->toContain('<input id="A{id-radio_style}_default" type="radio" name="A{name-radio_style}" value="default" />')
            ->and($html)->toContain('<input id="A{id-radio_style}_card" type="radio" name="A{name-radio_style}" value="card" CHK />')
            ->and($html)->toContain('<input id="A{id-radio_style}_mini" type="radio" name="A{name-radio_style}" value="mini" />')
            ->and($html)->toContain('Default style')
            ->and($html)->toContain('Card style')
            ->and($html)->toContain('Mini style')
            ->and($html)->toContain('<label for="A{id-title}">Title:</label>')
            ->and($html)->toContain('<label for="A{id-number}">Number of posts to show:</label>');
    });

    it('prints the defaults without settings', function (array $instance): void {
        $html = squishWidgetHtml(render(fn () => (new UAMS_Blogroll)->form($instance)));

        expect($html)->toContain('type="text" value="A{UAMS Blogroll}"')
            ->and($html)->toContain('type="text" value="A{2}" size="3"')
            ->and($html)->toContain('value="default" CHK />')
            ->and(substr_count($html, 'CHK'))->toBe(1);
    })->with([
        'missing' => [[]],
        'empty' => [['title' => '', 'number' => '']],
    ]);
});

describe('shortcode', function (): void {
    beforeEach(function (): void {
        when('add_shortcode')->justReturn(true);
    });

    it('asks for the posts with the attributes and their defaults', function (): void {
        $received = stubBlogPosts([]);

        (new UAMS_Blogroll)->shortcode([]);

        expect($received->getArrayCopy())->toBe([[
            'excerpt' => 'true', 'trim' => 'false', 'image' => 'hide', 'author' => 'show', 'titletag' => 'h2',
            'post_type' => 'post', 'number' => 5, 'category' => '', 'category_name' => '', 'mini' => false,
            'style' => 'default', 'date' => 'show', 'numberposts' => 5,
        ]])
            ->and(shortcodeAttsNames())->toBe(['']);
    });

    it('asks for as many posts as the number given', function (): void {
        $received = stubBlogPosts([]);

        (new UAMS_Blogroll)->shortcode(['number' => '3', 'category' => '7']);

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        expect($received[0]['numberposts'])->toBe('3')
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            ->and($received[0]['category'])->toBe('7');
    });

    it('prints an empty list for no posts', function (string $style, string $expected): void {
        stubBlogPosts([]);

        expect((new UAMS_Blogroll)->shortcode(['style' => $style]))->toBe($expected);
    })->with([
        'default' => ['default', '<ul class="shortcode-blogroll "></ul>'],
        'mini' => ['mini', '<ul class="shortcode-blogroll-mini "></ul>'],
        'card' => ['card', '<ul class="shortcode-blogroll cards"></ul>'],
    ]);

    it('prints the mini list when mini is set', function (): void {
        stubBlogPosts([]);

        expect((new UAMS_Blogroll)->shortcode(['mini' => true]))->toBe('<ul class="shortcode-blogroll-mini "></ul>');
    });

    it('prints a post in the default style', function (): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode([]))->toBe(
            '<ul class="shortcode-blogroll "><li><span><h2><a href="U{/post-1/}">H{First}</a><span class="date">TY-m-d1</span></h2>'
            .'<p class="author-info">H{display_name4}</p><span></span>P{Content}</span></li></ul>'
        );
    });

    it('uses the permalink function of the theme when it exists', function (): void {
        stubFunctionExists(['uams_get_permalink' => true]);
        when('uams_get_permalink')->alias(static fn (int $id): string => '/theme-'.$id.'/');
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode([]))->toContain('href="U{/theme-1/}"');
    });

    it('uses the excerpt of a post that has one, and trims it on request', function (): void {
        stubBlogPosts([blogPost(['ID' => 2])]);

        expect((new UAMS_Blogroll)->shortcode([]))->toContain('P{Excerpt}')
            ->and((new UAMS_Blogroll)->shortcode(['trim' => 'true']))->toContain('P{TR{Excerpt}}')
            ->and((new UAMS_Blogroll)->shortcode(['trim' => 'show']))->toContain('P{TR{Excerpt}}');
    });

    it('applies the text filter to the content of a post without an excerpt', function (): void {
        expectApplied('widget_text')->once()->with('Content');
        stubBlogPosts([blogPost()]);

        (new UAMS_Blogroll)->shortcode([]);
    });

    it('leaves out the excerpt unless it is asked for', function (string $value): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode(['excerpt' => $value]))->not->toContain('P{');
    })->with(['false', 'hide', 'yes', '1']);

    it('shows the image and aligns it when asked', function (string $value): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode(['image' => $value]))
            ->toContain('<span class="pull-left">IMG{1,thumbnail,alt91_wp_attachment_image_alt1}</span>');
    })->with(['show', 'true']);

    it('leaves out the author unless it is asked for', function (): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode(['author' => 'hide']))->not->toContain('author-info');
    });

    it('leaves out the date unless it is asked for', function (): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode(['date' => 'hide']))->toContain('<span class="date"></span>')
            ->and((new UAMS_Blogroll)->shortcode(['date' => 'true']))->toContain('<span class="date">TY-m-d1</span>');
    });

    it('wraps the title in the tag given', function (): void {
        stubBlogPosts([blogPost()]);

        expect((new UAMS_Blogroll)->shortcode(['titletag' => 'h4']))->toContain('<h4><a href="U{/post-1/}">H{First}</a>')->toContain('</h4>');
    });

    describe('mini style', function (): void {
        beforeEach(function (): void {
            stubBlogPosts([blogPost()]);
        });

        it('prints the thumbnail, the link and the byline', function (): void {
            expect((new UAMS_Blogroll)->shortcode(['style' => 'mini', 'image' => 'show']))->toBe(
                '<ul class="shortcode-blogroll-mini "><li><a class=\'widget-thumbnail\' href=\'U{/post-1/}\'>IMG{1,thumbnail,alt91_wp_attachment_image_alt1}</a>'
                ."<a class='widget-link' href='U{/post-1/}'>H{First}<span><small>TY-m-d1 | H{display_name4}</small></span></a></li></ul>"
            );
        });

        it('prints the date alone, the author alone, or no byline', function (): void {
            $blogroll = new UAMS_Blogroll;

            expect($blogroll->shortcode(['style' => 'mini', 'author' => 'hide']))->toContain('<span><small>TY-m-d1</small></span>')
                ->and($blogroll->shortcode(['style' => 'mini', 'date' => 'hide']))->toContain('<span><small>H{display_name4}</small></span>')
                ->and($blogroll->shortcode(['style' => 'mini', 'date' => 'hide', 'author' => 'hide']))->toContain('H{First}<span></span></a>');
        });
    });

    describe('card style', function (): void {
        it('prints the card with the image, the byline, the excerpt and the link', function (): void {
            stubBlogPosts([blogPost()]);

            expect((new UAMS_Blogroll)->shortcode(['style' => 'card']))->toBe(
                '<ul class="shortcode-blogroll cards"><div class="cards-widget"><div class="default-card">'
                .'<div class="card-image" style="background-image:url(U{/img-91-large.jpg})"></div>'
                .'<div class="card-body"><h3><a href="U{/post-1/}" class="pic-title">H{First}</a></h3>'
                .'<p><small>TY-m-d1 | H{display_name4}</small></p>'
                .'<div class="card-excerpt">P{Content}</div>'
                .'<a href="U{/post-1/}" class="read-more uams-btn btn-sm btn-red">Read More</a>'
                .'</div></div></div></ul>'
            );
        });

        it('leaves out the image for a post without a thumbnail or a source', function (): void {
            stubBlogPosts([blogPost(['ID' => 2])]);
            expect((new UAMS_Blogroll)->shortcode(['style' => 'card']))->not->toContain('card-image');

            stubBlogPosts([blogPost()]);
            when('wp_get_attachment_image_src')->justReturn([]);
            expect((new UAMS_Blogroll)->shortcode(['style' => 'card']))->not->toContain('card-image');
        });

        it('prints the date alone, the author alone, or no byline', function (): void {
            stubBlogPosts([blogPost()]);
            $blogroll = new UAMS_Blogroll;

            expect($blogroll->shortcode(['style' => 'card', 'author' => 'hide']))->toContain('<p><small>TY-m-d1</small></p>')
                ->and($blogroll->shortcode(['style' => 'card', 'date' => 'hide']))->toContain('<p><small>H{display_name4}</small></p>')
                ->and($blogroll->shortcode(['style' => 'card', 'date' => 'hide', 'author' => 'hide']))->toContain('</h3><div class="card-excerpt">');
        });

        it('prints a title without a link when the post has no permalink', function (): void {
            when('get_permalink')->justReturn('');
            stubBlogPosts([blogPost()]);

            $html = (new UAMS_Blogroll)->shortcode(['style' => 'card']);

            expect($html)->toContain('<h3>H{First}</h3>')
                ->and($html)->toContain('</div><br/></div></div></div>')
                ->and($html)->not->toContain('read-more');
        });

        it('prints a linked empty title as a link around nothing', function (): void {
            stubBlogPosts([blogPost(['post_title' => ''])]);

            $html = (new UAMS_Blogroll)->shortcode(['style' => 'card']);

            expect($html)->toContain('<h3>H{}</a></h3>');
        });
    });
});
