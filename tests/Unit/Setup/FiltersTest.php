<?php

/**
 * UAMS_Filters, the filters the theme adds, with WordPress mocked.
 *
 * @see setup/class.filters.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.filters.php');
    stubEscaping();
    when('is_multisite')->justReturn(false);
});

/**
 * An error object that holds messages by code, as WP_Error does.
 *
 * @param  array<string, list<string>>  $errors
 */
function userSignupErrors(array $errors): WP_Error
{
    return new class($errors) extends WP_Error
    {
        /**
         * @param  array<string, list<string>>  $errors
         */
        public function __construct(public array $errors) {}

        public function get_error_message(string $code = ''): string
        {
            return $this->errors[$code][0] ?? '';
        }
    };
}

it('adds its filters', function (): void {
    $filters = new UAMS_Filters;

    expect(has_filter('italics', [$filters, 'italicize']))->toBe(10)
        ->and(has_filter('abbreviation', [$filters, 'abbreviate']))->toBe(10)
        ->and(has_filter('widget_text', 'do_shortcode'))->toBe(10)
        ->and(has_filter('the_excerpt', 'do_shortcode'))->toBe(10)
        ->and(has_filter('body_class', [$filters, 'better_template_name_body_class']))->toBe(10)
        ->and(has_filter('widget_categories_dropdown_args', [$filters, 'custom_widget_classes']))->toBe(10)
        ->and(has_filter('excerpt_more', '__return_false'))->toBe(10)
        ->and(has_filter('the_excerpt', [$filters, 'excerpt_more_override']))->toBe(10)
        ->and(has_filter('post_mime_types', [$filters, 'modify_post_mime_types']))->toBe(10)
        ->and(has_filter('excerpt_more', [$filters, 'custom_excerpt_more']))->toBe(10)
        ->and(has_filter('next_posts_link_attributes', [$filters, 'posts_link_attributes_right']))->toBe(10)
        ->and(has_filter('previous_posts_link_attributes', [$filters, 'posts_link_attributes_left']))->toBe(10)
        ->and(has_filter('wpmu_validate_user_signup', [$filters, 'short_user_names']))->toBe(10)
        ->and(has_filter('body_class', [$filters, 'add_site_title_body_class']))->toBeFalse();
});

it('adds the site title body class on a multisite only', function (): void {
    when('is_multisite')->justReturn(true);
    $filters = new UAMS_Filters;

    expect(has_filter('body_class', [$filters, 'add_site_title_body_class']))->toBe(10);
});

it('gives the paging links their button classes', function (): void {
    $filters = new UAMS_Filters;

    expect($filters->posts_link_attributes_right())->toBe('class="uams-btn btn-sm"')
        ->and($filters->posts_link_attributes_left())->toBe('class="uams-btn btn-sm btn-left"')
        ->and($filters->custom_excerpt_more('[&hellip;]'))->toBe('...');
});

it('lists PDFs among the media library types', function (): void {
    when('__')->returnArg();
    when('_n_noop')->alias(static fn (string $singular, string $plural): array => [$singular, $plural]);

    $types = (new UAMS_Filters)->modify_post_mime_types(['image' => ['Images']]);

    expect($types)->toBe([
        'image' => ['Images'],
        'application/pdf' => [
            'PDF',
            'Manage PDFs',
            ['PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>'],
        ],
    ]);
});

it('adds a body class from the path of a site in the network', function (mixed $classes, mixed $details, array $expected): void {
    $asked = [];
    when('get_current_blog_id')->justReturn(3);
    when('get_blog_details')->alias(static function (int $id) use (&$asked, $details): mixed {
        $asked[] = $id;

        return $details;
    });

    expect((new UAMS_Filters)->add_site_title_body_class($classes))->toBe($expected)
        ->and($asked)->toBe([3]);
})->with([
    'a nested site' => [['home'], (object) ['path' => '/blog/dept/unit/'], ['home', 'site-S{dept-unit}']],
    'empty path segments are dropped' => [['home'], (object) ['path' => '/blog//dept/'], ['home', 'site-S{dept}']],
    'only the network prefix' => [['home'], (object) ['path' => '/blog/'], ['home']],
    'an empty path' => [['home'], (object) ['path' => ''], ['home']],
    'no site details' => [['home'], false, ['home']],
    'classes that are not an array' => ['home', (object) ['path' => '/blog/dept/'], ['site-S{dept}']],
]);

it('strips the template prefix and suffix from page template body classes', function (): void {
    when('is_page_template')->justReturn(true);

    expect((new UAMS_Filters)->better_template_name_body_class(['page', 'templatestemplate-home-php', 'templatestemplate-x', 'y-php']))
        ->toBe(['page', 'home', 'x', 'y']);
});

it('leaves body classes alone off a page template', function (): void {
    when('is_page_template')->justReturn(false);

    expect((new UAMS_Filters)->better_template_name_body_class(['templatestemplate-home-php']))->toBe(['templatestemplate-home-php'])
        ->and((new UAMS_Filters)->better_template_name_body_class('page'))->toBe([]);
});

it('gives the category dropdown its classes', function (): void {
    expect((new UAMS_Filters)->custom_widget_classes(['show_count' => 1]))
        ->toBe(['show_count' => 1, 'class' => 'uams-select uams-select-wp']);
});

describe('italicize', function (): void {
    it('returns content that is empty or not a string as a string', function (mixed $content, string $expected): void {
        when('get_option')->justReturn('UAMS');

        expect((new UAMS_Filters)->italicize($content))->toBe($expected);
    })->with([
        'null' => [null, ''],
        'empty string' => ['', ''],
        'zero string' => ['0', '0'],
        'an integer' => [5, '5'],
    ]);

    it('returns the content when no word is set', function (string $option): void {
        $asked = [];
        when('get_option')->alias(static function (string $name, mixed $default = false) use (&$asked, $option): string {
            $asked[] = [$name, $default];

            return $option;
        });

        expect((new UAMS_Filters)->italicize('UAMS Health'))->toBe('UAMS Health')
            ->and($asked)->toBe([['italicized_words', '']]);
    })->with(['empty' => [''], 'zero' => ['0'], 'only spaces' => ['   ']]);

    it('italicizes each listed word as a whole word, ignoring case', function (): void {
        when('get_option')->justReturn(' uams  a.b ');

        expect((new UAMS_Filters)->italicize('UAMS and uamsx and a.b and axb'))
            ->toBe('<em>UAMS</em> and uamsx and <em>a.b</em> and axb');
    });

    it('italicizes an ampersand in each of its spellings', function (): void {
        when('get_option')->justReturn('& go');

        expect((new UAMS_Filters)->italicize('A &#038; B & C &amp; D go'))
            ->toBe('A <em>&</em> B <em>&</em> C <em>&</em> D <em>go</em>');
    });

    it('leaves an ampersand alone when it is not listed', function (): void {
        when('get_option')->justReturn('go');

        expect((new UAMS_Filters)->italicize('A & B'))->toBe('A & B');
    });
});

it('replaces the title with the abbreviation when one is set', function (?string $option, string $expected): void {
    $asked = [];
    when('get_option')->alias(static function (string $name) use (&$asked, $option): ?string {
        $asked[] = $name;

        return $option;
    });

    expect((new UAMS_Filters)->abbreviate('Full Title'))->toBe($expected)
        ->and($asked)->toBe(['abbreviation']);
})->with([
    'set' => ['UAMS', 'UAMS'],
    'empty' => ['', 'Full Title'],
    'unset' => [null, 'Full Title'],
]);

it('adds a read more link to an excerpt', function (): void {
    when('uams_get_permalink')->justReturn('https://site.test/post');

    expect((new UAMS_Filters)->excerpt_more_override('Text'))
        ->toBe('Text<div><a class="more" href="U{https://site.test/post}">Read more</a></div>');
});

describe('short_user_names', function (): void {
    beforeEach(function (): void {
        when('__')->returnArg();
    });

    it('returns a result that carries no error object untouched', function (array $result): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        expect((new UAMS_Filters)->short_user_names($result))->toBe($result);
    })->with([
        'no errors key' => [['user_name' => 'ab']],
        'errors that are not an error object' => [['errors' => 'x']],
    ]);

    it('returns the result when the user name error is absent or another message', function (array $errors): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $result = ['errors' => userSignupErrors($errors)];

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore property.nonObject
        expect((new UAMS_Filters)->short_user_names($result)['errors']->errors)->toBe($errors);
    })->with([
        'no user name error' => [['user_email' => ['Bad email.']]],
        'another message' => [['user_name' => ['Username is reserved.']]],
    ]);

    it('removes the error about a short user name and keeps the others', function (): void {
        $errors = userSignupErrors([
            'user_name' => ['Username must be at least 4 characters.'],
            'user_email' => ['Bad email.'],
        ]);

        $result = (new UAMS_Filters)->short_user_names(['errors' => $errors]);

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore property.nonObject
        expect($result['errors']->errors)->toBe(['user_email' => ['Bad email.']]);
    });
});
