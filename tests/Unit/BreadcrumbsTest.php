<?php

/**
 * get_uams_breadcrumbs() and uams_breadcrumbs(), with WordPress mocked.
 *
 * Every trail starts with the UAMS home link and the site link. What follows depends on the kind
 * of page: the 404 page, a search, an author archive, the posts page, a category, a taxonomy, a
 * post type archive, a single post, or a page with its ancestors.
 *
 * @see inc/template-functions.php
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

use function Brain\Monkey\Functions\when;

const CRUMB_UAMS = '<li><a href="http://www.uams.edu" title="University of Arkansas for Medical Sciences">Home</a></li>';
const CRUMB_SITE = '<li><a href="U{https://site.test/}" title="A{Site}">H{Site}</a></li>';
const CRUMB_SITE_CURRENT = '<li class="current"><a href="U{https://site.test/}" title="A{Site}">H{Site}</a></li>';
const CRUMB_POST = '<li class="current"><span>H{Post <1>}</span></li>';

/**
 * Wrap crumb markup the way the function does.
 */
function trail(string $crumbs, string $site = CRUMB_SITE): string
{
    return "<nav class='uams-breadcrumbs' aria-label='breadcrumbs'><ul>".CRUMB_UAMS.$site.$crumbs.'</ul></nav>';
}

/**
 * Define the WordPress calls a trail makes, with quiet defaults, so a test states only the ones it
 * depends on. Brain Monkey keeps the first definition of a function, so a test that sets its own
 * expectation on a function names it in $skip and sets the expectation afterwards.
 *
 * @param  array<string, bool>  $conditionals
 * @param  array<string, mixed>  $values  Function name => return value, or a closure to alias.
 * @param  list<string>  $skip
 */
function crumbEnv(array $conditionals = [], array $values = [], array $skip = []): void
{
    stubEscaping();
    unset($GLOBALS['post']);
    wpConditionals($conditionals);

    $values = array_merge([
        'get_bloginfo' => 'Site',
        'get_the_ID' => 0,
        'get_queried_object_id' => 0,
        'get_option' => 0,
        'get_post_type' => false,
        'get_post_types' => [],
        'get_post_ancestors' => [],
    ], $values);

    foreach ($values as $name => $value) {
        if (in_array($name, $skip, true)) {
            continue;
        }

        $value instanceof Closure ? when($name)->alias($value) : when($name)->justReturn($value);
    }
}

/**
 * crumbEnv() for a single post with id 14 titled "Post <1>".
 *
 * @param  array<string, bool>  $conditionals
 * @param  array<string, mixed>  $values
 * @param  list<string>  $skip
 */
function singleEnv(array $conditionals = [], array $values = [], array $skip = []): void
{
    crumbEnv(
        array_merge(['is_single' => true], $conditionals),
        array_merge(['get_the_title' => 'Post <1>'], $values),
        $skip,
    );
    $GLOBALS['post'] = wpPost(['ID' => 14]);
}

/**
 * crumbEnv() for a page with id 30.
 *
 * @param  array<string, bool>  $conditionals
 * @param  array<string, mixed>  $values
 * @param  list<string>  $skip
 */
function pageEnv(array $conditionals = [], array $values = [], array $skip = []): void
{
    crumbEnv(array_merge(['is_page' => true], $conditionals), $values, $skip);
    $GLOBALS['post'] = wpPost(['ID' => 30]);
}

/**
 * A custom post type object, with the fields the trail reads.
 *
 * @return object{query_var: string, labels: object, rewrite: mixed}
 */
function providerType(mixed $rewrite = false, string $queryVar = 'providers'): object
{
    return (object) [
        'query_var' => $queryVar,
        'labels' => (object) ['menu_name' => 'Providers'],
        'rewrite' => $rewrite,
    ];
}

afterEach(function (): void {
    unset($GLOBALS['post']);
});

it('starts every trail with the UAMS and site links', function (): void {
    crumbEnv();

    expect(get_uams_breadcrumbs())->toBe(trail(''));
});

it('asks for the site name and escapes it for the title and the link text', function (): void {
    crumbEnv(skip: ['get_bloginfo']);
    Functions\expect('get_bloginfo')->once()->with('name')->andReturn('A <b> Site');

    expect(get_uams_breadcrumbs())->toContain('title="A{A <b> Site}">H{A <b> Site}</a>');
});

it('marks the site link current on the front page', function (): void {
    crumbEnv(['is_front_page' => true]);

    expect(get_uams_breadcrumbs())->toBe(trail('', CRUMB_SITE_CURRENT));
});

it('prints the 404 crumb', function (): void {
    crumbEnv(['is_404' => true]);

    expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>Ooops!</span></li>'));
});

it('prints the search crumb with the escaped query', function (): void {
    crumbEnv(['is_search' => true], ['get_search_query' => 'flu']);

    expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>Search results for H{flu}</span></li>'));
});

describe('the author archive', function (): void {
    it('prints the author name', function (): void {
        crumbEnv(['is_author' => true], ['get_queried_object' => (object) ['display_name' => 'Ada']]);

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span> Author: H{Ada}</span></li>'));
    });

    it('prints an empty name without an author', function (mixed $author): void {
        crumbEnv(['is_author' => true], ['get_queried_object' => $author]);

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span> Author: H{}</span></li>'));
    })->with([
        'no queried object' => [null],
        'an object without a display name' => [(object) ['user_login' => 'ada']],
    ]);
});

describe('the posts page', function (): void {
    it('prints the posts page title', function (): void {
        crumbEnv(values: [
            'get_queried_object_id' => 9,
            'get_option' => static fn (string $name): int => $name === 'page_for_posts' ? 9 : 0,
        ]);
        Functions\expect('get_the_title')->once()->with(9)->andReturn('Blog');

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span> H{Blog} </span></li>'));
    });

    it('prints nothing when no posts page is set, even though the ids match', function (): void {
        crumbEnv();
        Functions\expect('get_the_title')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });

    it('prints nothing when the posts page is another page', function (): void {
        crumbEnv(values: ['get_queried_object_id' => 4, 'get_option' => 9]);
        Functions\expect('get_the_title')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });
});

describe('a post type archive', function (): void {
    it('prints the post type menu name', function (): void {
        crumbEnv(['is_post_type_archive' => true], ['get_post_type' => 'provider']);
        Functions\expect('get_post_type_object')->once()->with('provider')->andReturn(providerType());

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{Providers}</span></li>'));
    });

    it('prints nothing when the post type is unknown', function (): void {
        crumbEnv(['is_post_type_archive' => true], ['get_post_type_object' => null]);

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });

    it('leaves the menu name to the term crumb on a taxonomy archive', function (): void {
        crumbEnv(
            ['is_post_type_archive' => true, 'is_tax' => true],
            ['get_query_var' => 'x', 'get_term_by' => false],
        );
        Functions\expect('get_post_type_object')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });
});

describe('a category', function (): void {
    it('prints the category name', function (): void {
        crumbEnv(['is_category' => true]);
        Functions\expect('get_query_var')->once()->with('cat')->andReturn(5);
        Functions\expect('get_category')->once()->with(5)->andReturn((object) ['term_id' => 77]);
        Functions\expect('get_cat_name')->once()->with(77)->andReturn('News');

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{News}</span></li>'));
    });

    it('prints nothing when the category is missing or an error', function (mixed $category): void {
        crumbEnv(['is_category' => true], ['get_query_var' => 5, 'get_category' => $category]);
        Functions\expect('get_cat_name')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    })->with([
        'no category' => [null],
        'an error' => [fn () => new WP_Error],
    ]);
});

describe('a taxonomy archive', function (): void {
    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skip
     */
    function taxEnv(array $values = [], array $skip = [], bool $archive = false): void
    {
        crumbEnv(
            ['is_tax' => true, 'is_post_type_archive' => $archive],
            array_merge(['get_query_var' => static fn (string $name): string => $name === 'term' ? 'flu' : 'topic'], $values),
            $skip,
        );
    }

    it('prints the taxonomy and term names', function (): void {
        taxEnv();
        Functions\expect('get_term_by')->once()->with('slug', 'flu', 'topic')->andReturn((object) ['name' => 'Flu']);
        Functions\expect('get_taxonomy')->once()->with('topic')->andReturn((object) ['labels' => (object) ['name' => 'Topics']]);

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{Topics}: H{Flu}</span></li>'));
    });

    it('prints nothing without the taxonomy, the term, or with an error term', function (Closure $term, Closure $taxonomy): void {
        taxEnv(['get_term_by' => $term(), 'get_taxonomy' => $taxonomy()]);

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    })->with([
        'no taxonomy' => [fn () => (object) ['name' => 'Flu'], fn () => false],
        'no term' => [fn () => false, fn () => (object) ['labels' => (object) ['name' => 'Topics']]],
        'an error term' => [fn () => new WP_Error, fn () => (object) ['labels' => (object) ['name' => 'Topics']]],
    ]);

    it('prints only the term name on a post type archive', function (): void {
        taxEnv(['get_term_by' => (object) ['name' => 'Flu']], archive: true);
        Functions\expect('get_taxonomy')->never();
        Functions\expect('get_post_type_object')->never();

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{Flu}</span></li>'));
    });

    it('prints nothing on a post type archive without a usable term', function (Closure $term): void {
        taxEnv(['get_term_by' => $term()], archive: true);

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    })->with([
        'no term' => [fn () => false],
        'an error term' => [fn () => new WP_Error],
    ]);
});

describe('a single post', function (): void {
    it('prints the post title as the current crumb', function (): void {
        singleEnv(skip: ['get_the_title', 'get_post_ancestors']);
        Functions\expect('get_the_title')->once()->with(14)->andReturn('Post <1>');
        Functions\expect('get_post_ancestors')->once()->with(14)->andReturn([]);

        expect(get_uams_breadcrumbs())->toBe(trail(CRUMB_POST));
    });

    it('takes the post id from the current post when the global is not a post', function (): void {
        singleEnv(skip: ['get_the_title', 'get_the_ID']);
        $GLOBALS['post'] = null;
        Functions\expect('get_the_ID')->andReturn(15);
        Functions\expect('get_the_title')->once()->with(15)->andReturn('Other');

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{Other}</span></li>'));
    });

    it('prints no title crumb without a post id', function (): void {
        singleEnv();
        $GLOBALS['post'] = null;
        Functions\expect('get_post_ancestors')->never();
        Functions\expect('get_the_title')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });

    it('links the first category of the post before its title', function (): void {
        singleEnv(['has_category' => true], ['get_cat_name' => 'News & Notes']);
        Functions\expect('get_the_category')->once()->with(14)->andReturn([
            (object) ['term_id' => 3],
            (object) ['term_id' => 4],
        ]);
        Functions\expect('get_category_link')->once()->with(3)->andReturn('/cat/news/');

        expect(get_uams_breadcrumbs())->toBe(trail(
            '<li><a href="U{/cat/news/}" title="A{News & Notes}">H{News & Notes}</a></li>'.CRUMB_POST
        ));
    });

    it('prints no category link when the post has no categories', function (): void {
        singleEnv(['has_category' => true], ['get_the_category' => []]);
        Functions\expect('get_category_link')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(CRUMB_POST));
    });

    it('does not ask for categories when the post has none', function (): void {
        singleEnv();
        Functions\expect('get_the_category')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(CRUMB_POST));
    });

    it('does not ask for categories when there is no post id', function (): void {
        singleEnv(['has_category' => true]);
        $GLOBALS['post'] = null;
        Functions\expect('get_the_category')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });

    describe('of a custom post type', function (): void {
        /**
         * @param  array<string, mixed>  $values
         * @param  list<string>  $skip
         */
        function providerEnv(array $values = [], array $skip = []): void
        {
            singleEnv(values: array_merge([
                'get_post_type' => 'provider',
                'get_post_types' => ['provider' => 'provider'],
            ], $values), skip: $skip);
        }

        it('links the post type archive', function (): void {
            providerEnv(['get_post_type_object' => providerType()], ['get_post_type']);
            Functions\expect('get_post_type')->andReturn('provider');
            Functions\expect('get_post_type_archive_link')->once()->with('providers')->andReturn('/providers/');

            expect(get_uams_breadcrumbs())->toBe(trail(
                '<li><a href="U{/providers/}" title="A{Providers}">H{Providers}</a></li>'.CRUMB_POST
            ));
        });

        it('links the rewrite slug when the archive has no link', function (): void {
            providerEnv([
                'get_post_type_object' => providerType(['slug' => 'our-providers']),
                'get_post_type_archive_link' => false,
            ]);

            expect(get_uams_breadcrumbs())->toBe(trail(
                '<li><a href="U{https://site.test/our-providers/}" title="A{Providers}">H{Providers}</a></li>'.CRUMB_POST
            ));
        });

        it('links the rewrite slug when the post type has no query var', function (): void {
            providerEnv(['get_post_type_object' => providerType(['slug' => 'our-providers'], '')]);
            Functions\expect('get_post_type_archive_link')->never();

            expect(get_uams_breadcrumbs())->toContain('U{https://site.test/our-providers/}');
        });

        it('prints no post type link without an archive or a rewrite slug', function (mixed $rewrite): void {
            providerEnv([
                'get_post_type_object' => providerType($rewrite),
                'get_post_type_archive_link' => '',
            ]);

            expect(get_uams_breadcrumbs())->toBe(trail(CRUMB_POST));
        })->with([
            'no rewrite' => [false],
            'a rewrite without a slug' => [['slug' => '']],
        ]);

        it('prints no post type link when the post type is unknown', function (): void {
            providerEnv(['get_post_type_object' => null]);

            expect(get_uams_breadcrumbs())->toBe(trail(CRUMB_POST));
        });
    });
});

describe('a page', function (): void {
    it('prints the ancestors as links, oldest first, then the page as current', function (): void {
        pageEnv(values: [
            'get_post' => static fn (int $id): WP_Post => wpPost(['ID' => $id, 'post_title' => "Page {$id} <x>"]),
            'get_permalink' => static fn (int $id): string => "/page-{$id}/",
        ], skip: ['get_post_ancestors']);
        Functions\expect('get_post_ancestors')->once()->with(30)->andReturn([20, 10]);

        expect(get_uams_breadcrumbs())->toBe(trail(
            '<li><a href="U{/page-10/}" title="A{Page 10 <x>}">H{Page 10 <x>}</a></li>'
            .'<li><a href="U{/page-20/}" title="A{Page 20 <x>}">H{Page 20 <x>}</a></li>'
            .'<li class="current"><span>H{Page 30 <x>}</span></li>'
        ));
    });

    it('prints only the current crumb for a top-level page', function (): void {
        pageEnv(values: [
            'get_post_ancestors' => [],
            'get_post' => wpPost(['ID' => 30, 'post_title' => 'About']),
            'get_permalink' => '/about/',
        ]);

        expect(get_uams_breadcrumbs())->toBe(trail('<li class="current"><span>H{About}</span></li>'));
    });

    it('prints no page trail on the front page', function (): void {
        pageEnv(['is_front_page' => true], ['get_post_ancestors' => [10]]);
        Functions\expect('get_post')->never();

        expect(get_uams_breadcrumbs())->toBe(trail('', CRUMB_SITE_CURRENT));
    });

    it('skips a falsy ancestor id', function (): void {
        pageEnv(values: [
            'get_post_ancestors' => [0, 10],
            'get_permalink' => '/p/',
        ], skip: []);
        Functions\expect('get_post')->twice()->andReturnUsing(
            static fn (int $id): WP_Post => wpPost(['ID' => $id, 'post_title' => "P{$id}"])
        );

        expect(get_uams_breadcrumbs())->toBe(trail(
            '<li><a href="U{/p/}" title="A{P10}">H{P10}</a></li><li class="current"><span>H{P30}</span></li>'
        ));
    });

    it('skips an ancestor that is not a post, and the page then has no current crumb', function (): void {
        pageEnv(values: [
            'get_post_ancestors' => [10],
            'get_post' => static fn (int $id): ?WP_Post => $id === 30 ? null : wpPost(['ID' => $id, 'post_title' => 'P10']),
            'get_permalink' => '/p/',
        ]);

        expect(get_uams_breadcrumbs())->toBe(trail('<li><a href="U{/p/}" title="A{P10}">H{P10}</a></li>'));
    });

    it('prints no page trail without a post id', function (): void {
        pageEnv();
        $GLOBALS['post'] = null;
        Functions\expect('get_post')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });

    it('prints no page trail when the request is none of the kinds above', function (): void {
        crumbEnv();
        Functions\expect('get_post')->never();

        expect(get_uams_breadcrumbs())->toBe(trail(''));
    });
});

it('echoes the trail', function (): void {
    crumbEnv();

    uams_breadcrumbs();
})->expectOutputString(trail(''));
