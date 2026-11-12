<?php

/**
 * The image cards widget and the recent posts widget, with WordPress mocked.
 *
 * @see widgets/class.uams-cards.php
 * @see widgets/class.uams-recent-posts.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

beforeEach(function (): void {
    stubEscaping();
    stubFunctionExists(['uams_get_permalink' => false]);
    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore cast.int
    when('absint')->alias(static fn (mixed $value): int => abs((int) $value));
    when('esc_url_raw')->alias(static fn (string $url): string => 'R{'.$url.'}');
    when('wp_kses_post')->alias(static fn (string $text): string => 'K{'.$text.'}');
    when('esc_textarea')->alias(static fn (string $text): string => 'X{'.$text.'}');
    when('wpautop')->alias(static fn (string $text): string => 'P{'.$text.'}');
    when('checked')->alias(static function (mixed $checked, mixed $current = true): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore cast.string, cast.string
        echo (string) preg_replace('/^A\{(.*)\}$/', '$1', (string) $checked) === (string) $current ? ' CHK' : '';
    });
    when('selected')->alias(static function (mixed $selected, mixed $current): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore cast.string, cast.string
        echo (string) $selected === (string) $current ? ' SEL' : '';
    });
});

/**
 * Make wp_get_recent_posts() return posts with these ids, and record what it was asked.
 *
 * @param  list<int>  $ids
 * @return ArrayObject<int, mixed>
 */
function stubRecentPosts(array $ids): ArrayObject
{
    $received = new ArrayObject;
    when('wp_get_recent_posts')->alias(static function (array $args, string $output) use ($ids, $received): array {
        $received[] = [$args, $output];

        return array_map(static fn (int $id): object => (object) ['ID' => $id], $ids);
    });

    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
    // @phpstan-ignore return.type
    return $received;
}

describe('UAMS_Widget_Cards', function (): void {
    it('registers the widget and the scripts when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-cards.php'))->toBe(['UAMS_Widget_Cards'])
            ->and(has_action('admin_enqueue_scripts', ['UAMS_Widget_Cards', 'scripts']))->toBe(10);
    });

    it('describes the widget', function (): void {
        $widget = new UAMS_Widget_Cards;

        expect($widget->id_base)->toBe('uams-widget-cards')
            ->and($widget->name)->toBe('UAMS Image Cards')
            ->and($widget->widget_options)->toBe(['description' => 'Choose from several styles of cards', 'classname' => 'cards-widget']);
    });

    it('enqueues the card script and the media library', function (): void {
        $calls = [];
        when('get_template_directory_uri')->justReturn('https://site.test/theme');
        when('wp_enqueue_script')->alias(static function (mixed ...$args) use (&$calls): void {
            $calls[] = $args;
        });
        when('wp_enqueue_media')->alias(static function () use (&$calls): void {
            $calls[] = ['media'];
        });

        UAMS_Widget_Cards::scripts();

        expect($calls)->toBe([
            ['single-card-image', 'https://site.test/theme/assets/admin/js/widgets/uams.card-widget.js', ['jquery'], null, true],
            ['jquery-ui-autocomplete'],
            ['media'],
        ]);
    });

    it('sanitizes every field on update', function (): void {
        $new = ['title' => 'Hi', 'text' => 'Body', 'image' => '-5', 'src' => 's', 'link' => 'l', 'link-text' => 'More', 'radio_card' => 'styled-card'];

        expect((new UAMS_Widget_Cards)->update($new, []))->toBe([
            'title' => 'T{Hi}', 'text' => 'K{Body}', 'image' => 5, 'src' => 'R{s}', 'link' => 'R{l}',
            'link-text' => 'T{More}', 'radio_card' => 'T{styled-card}',
        ])
            ->and((new UAMS_Widget_Cards)->update([], []))->toBe([
                'title' => 'T{}', 'text' => 'K{}', 'image' => 0, 'src' => 'R{}', 'link' => 'R{}',
                'link-text' => 'T{}', 'radio_card' => 'T{default-card}',
            ]);
    });

    describe('form', function (): void {
        beforeEach(function (): void {
            when('get_stylesheet_directory_uri')->justReturn('https://site.test/child');
            when('wp_get_attachment_url')->alias(static fn (mixed $id): mixed => $id === 'A{7}' ? '/image-7.jpg' : false);
        });

        it('prints the stored values with the card style checked', function (): void {
            $instance = ['title' => 'Hi', 'text' => 'Body', 'image' => '7', 'src' => '/src.jpg', 'link' => '/l', 'link-text' => 'Go', 'radio_card' => 'styled-card'];

            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Cards)->form($instance)));

            expect($html)->toContain('name="A{name-title}" type="text" value="A{A{Hi}}"')
                ->and($html)->toContain('<img src="U{/image-7.jpg}" width="100%" class="wp-get-posts-image" />')
                ->and($html)->toContain('name="A{name-image}" type="hidden" value="A{A{7}}"/>')
                ->and($html)->toContain('name="A{name-src}" type="hidden" value="A{A{/src.jpg}}"/>')
                ->and($html)->toContain('name="A{name-text}">X{A{Body}}</textarea>')
                ->and($html)->toContain('name="A{name-link}" type="text" value="A{A{/l}}" />')
                ->and($html)->toContain('name="A{name-link-text}" type="text" value="A{A{Go}}" />')
                ->and($html)->toContain('<img src="U{https://site.test/child}/assets/images/widget-card-default.jpg" alt="" />')
                ->and($html)->toContain('<img src="U{https://site.test/child}/assets/images/widget-card-styled.jpg" alt="" />')
                ->and($html)->toContain('name="A{name-radio_card}" value="default-card" />')
                ->and($html)->toContain('name="A{name-radio_card}" value="styled-card" CHK />');
        });

        it('prints the defaults without stored values', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Cards)->form([])));

            expect($html)->toContain('type="text" value="A{Image Widget}"')
                ->and($html)->toContain('name="A{name-link-text}" type="text" value="A{Read more}" />')
                ->and($html)->toContain('<img src="U{}" width="100%"')
                ->and($html)->toContain('name="A{name-radio_card}" value="default-card" CHK />')
                ->and($html)->toContain('name="A{name-text}">X{}</textarea>')
                ->and(substr_count($html, 'CHK'))->toBe(1);
        });
    });

    describe('widget', function (): void {
        beforeEach(function (): void {
            when('wp_get_attachment_image_src')->alias(static fn (int $id, string $size): array => ['/img-'.$id.'-'.$size.'.jpg']);
        });

        it('prints the card with the image, the linked title, the text and the link', function (): void {
            $instance = ['title' => 'Hi', 'text' => 'Body', 'image' => '7', 'link' => '/l', 'link-text' => 'Go', 'radio_card' => 'styled-card'];

            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Cards)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], $instance)));

            expect($html)->toBe(
                '<aside> <div class="A{styled-card}"> <div class="card-image" style="background-image:url(U{/img-7-large.jpg})"></div> '
                .'<div class="card-body"> <span> <h3> <a href="U{/l}" class="pic-title"> H{Hi} </a> </h3> '
                .'<span class="udub-slant"><span></span></span> P{Body} '
                .'<a href="U{/l}" class="pic-text-more uams-btn btn-sm btn-red">H{Go}</a> </span> </div> </div> </aside>'
            );
        });

        it('prints a bare card with the defaults', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Cards)->widget(['before_widget' => '', 'after_widget' => ''], [])));

            expect($html)->toBe('<div class="A{default-card}"> <div class="card-body"> <span> P{} <br/> </span> </div> </div>');
        });

        it('uses the default link text when the stored text is empty', function (): void {
            $html = render(fn () => (new UAMS_Widget_Cards)->widget(['before_widget' => '', 'after_widget' => ''], ['link' => '/l', 'link-text' => '']));

            expect($html)->toContain('>H{More}</a>');
        });

        it('prints a title without a link when there is none', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Widget_Cards)->widget(['before_widget' => '', 'after_widget' => ''], ['title' => 'Hi'])));

            expect($html)->toContain('<h3> H{Hi} </h3>')
                ->and($html)->not->toContain('pic-title');
        });

        it('leaves out the image when it has no source', function (): void {
            when('wp_get_attachment_image_src')->justReturn([]);

            $html = render(fn () => (new UAMS_Widget_Cards)->widget(['before_widget' => '', 'after_widget' => ''], ['image' => '7']));

            expect($html)->not->toContain('card-image');
        });
    });
});

describe('UAMS_Recent_Posts', function (): void {
    it('registers the widget when the file loads', function (): void {
        expect(loadWidgetFile('class.uams-recent-posts.php'))->toBe(['UAMS_Recent_Posts']);
    });

    it('describes the widget', function (): void {
        $widget = new UAMS_Recent_Posts;

        expect($widget->id_base)->toBe('uams-recent')
            ->and($widget->name)->toBe('UAMS Recent Posts')
            ->and($widget->widget_options)->toBe(['description' => 'A widget that shows recent posts on your blog', 'classname' => 'uams-recent']);
    });

    it('says how long ago a post was published', function (): void {
        $received = [];
        when('get_the_time')->alias(static fn (string $format, int $id): int => 1000 + $id);
        when('human_time_diff')->alias(static function (int $from, int $to) use (&$received): string {
            $received = [$from, $to];

            return '3 days';
        });

        $before = time();

        expect((new UAMS_Recent_Posts)->humanTime(5))->toBe('3 days')
            ->and($received[0])->toBe(1005)
            ->and($received[1])->toBeGreaterThanOrEqual($before)
            ->and($received[1])->toBeLessThanOrEqual(time());
    });

    it('sanitizes the settings on update', function (array $new, array $expected): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect((new UAMS_Recent_Posts)->update($new, []))->toBe($expected);
    })->with([
        'all' => [['title' => 'Hi', 'items' => '-3', 'more' => '1'], ['title' => 'T{Hi}', 'items' => 3, 'more' => true]],
        'none' => [[], ['title' => 'T{}', 'items' => 5, 'more' => false]],
        'zero' => [['items' => '0', 'more' => '0'], ['title' => 'T{}', 'items' => 5, 'more' => false]],
    ]);

    describe('form', function (): void {
        it('prints the stored settings', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Recent_Posts)->form(['title' => 'Hi', 'items' => '3', 'more' => '1'])));

            expect($html)->toContain('name="A{name-title}" type="text" value="A{Hi}"')
                ->and($html)->toContain('<option value="1" >1</option> <option value="2" >2</option> <option value="3" SEL>3</option> <option value="4" >4</option> <option value="5" >5</option>')
                ->and($html)->toContain('type="checkbox" id="A{id-more}" name="A{name-more}" CHK />')
                ->and($html)->toContain('<label for="A{id-more}">Display more link</label>');
        });

        it('prints the defaults without settings', function (): void {
            $html = squishWidgetHtml(render(fn () => (new UAMS_Recent_Posts)->form([])));

            expect($html)->toContain('type="text" value="A{UAMS Recent Posts}"')
                ->and($html)->toContain('<option value="5" SEL>5</option>')
                ->and($html)->not->toContain('CHK')
                ->and(substr_count($html, ' SEL'))->toBe(1);
        });
    });

    describe('widget', function (): void {
        beforeEach(function (): void {
            when('get_permalink')->alias(static fn (int $id): string => '/post-'.$id.'/');
            when('get_the_title')->alias(static fn (int $id): string => 'Title '.$id);
            when('has_post_thumbnail')->alias(static fn (int $id): bool => $id === 1);
            when('get_the_post_thumbnail')->alias(static fn (int $id, string $size): string => 'IMG{'.$id.','.$size.'}');
            when('human_time_diff')->justReturn('2 hours');
            when('get_the_time')->justReturn(1);
            when('get_option')->alias(static fn (string $name): mixed => $name === 'page_for_posts' ? 9 : false);
        });

        it('asks for the number of posts wanted, and defaults to five', function (): void {
            $received = stubRecentPosts([]);
            $widget = new UAMS_Recent_Posts;

            render(fn () => $widget->widget(['before_widget' => '', 'after_widget' => ''], ['items' => '3']));
            render(fn () => $widget->widget(['before_widget' => '', 'after_widget' => ''], []));

            expect($received->getArrayCopy())->toBe([
                [['numberposts' => 3, 'post_status' => 'publish'], OBJECT],
                [['numberposts' => 5, 'post_status' => 'publish'], OBJECT],
            ]);
        });

        it('prints nothing when there are no posts', function (): void {
            stubRecentPosts([]);

            expect(render(fn () => (new UAMS_Recent_Posts)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], [])))->toBe('');
        });

        it('applies the title filter to the title given', function (string $title, string $expected): void {
            stubRecentPosts([]);
            expectApplied('widget_title')->once()->with($expected);

            render(fn () => (new UAMS_Recent_Posts)->widget(['before_widget' => '', 'after_widget' => ''], $title === '' ? [] : ['title' => $title]));
        })->with([
            'a title' => ['Latest', 'Latest'],
            'the default' => ['', 'UAMS Recent Posts'],
        ]);

        it('lists the posts with a thumbnail where there is one', function (): void {
            stubRecentPosts([1, 2]);

            $html = squishWidgetHtml(render(fn () => (new UAMS_Recent_Posts)->widget(['before_widget' => '<aside>', 'after_widget' => '</aside>'], ['title' => 'Latest'])));

            expect($html)->toStartWith('<aside> <h2>H{Latest} <svg')
                ->and($html)->toContain('</svg> </h2> <ul class="recent-posts"> <li> '
                    .'<a class="widget-thumbnail" href="U{/post-1/}" title="A{Title 1}"> IMG{1,thumbnail} </a> '
                    .'<a class="widget-link" href="U{/post-1/}" title="A{Title 1}"> H{Title 1} <p><small>H{2 hours} ago</small></p> </a> </li> '
                    .'<li> <a class="widget-link" href="U{/post-2/}" title="A{Title 2}"> H{Title 2} <p><small>H{2 hours} ago</small></p> </a> </li> </ul>')
                ->and($html)->not->toContain('class="more"')
                ->and($html)->toEndWith('</aside>');
        });

        it('uses the permalink function of the theme when it exists', function (): void {
            stubRecentPosts([2]);
            stubFunctionExists(['uams_get_permalink' => true]);
            when('uams_get_permalink')->alias(static fn (int $id): string => '/theme-'.$id.'/');

            $html = render(fn () => (new UAMS_Recent_Posts)->widget(['before_widget' => '', 'after_widget' => ''], []));

            expect($html)->toContain('href="U{/theme-2/}"');
        });

        it('links to the posts page when asked', function (): void {
            stubRecentPosts([2]);

            $html = squishWidgetHtml(render(fn () => (new UAMS_Recent_Posts)->widget(['before_widget' => '', 'after_widget' => ''], ['more' => true])));

            expect($html)->toContain('<a class="more" href="U{/post-9/}">More</a>');
        });

        it('leaves out the link without a posts page or when not asked', function (): void {
            stubRecentPosts([2]);
            $widget = new UAMS_Recent_Posts;

            expect(render(fn () => $widget->widget(['before_widget' => '', 'after_widget' => ''], ['more' => false])))->not->toContain('class="more"');

            when('get_option')->justReturn(0);

            expect(render(fn () => $widget->widget(['before_widget' => '', 'after_widget' => ''], ['more' => true])))->not->toContain('class="more"');
        });
    });
});
