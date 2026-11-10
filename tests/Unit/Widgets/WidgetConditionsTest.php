<?php

/**
 * UAMS_Widget_Conditions, the show/hide rules attached to a widget, with WordPress mocked. The conditional
 * tags are stubbed false unless a test names them, so each case states only what it depends on.
 *
 * @see widgets/class.uams-widget-visibility.php
 */

declare(strict_types=1);

use function Brain\Monkey\Actions\expectAdded;
use function Brain\Monkey\Actions\expectDone;
use function Brain\Monkey\Functions\when;

require_once __DIR__.'/../../Support/theme-classes.php';

/**
 * Stub every conditional tag the rules ask, false unless overridden by name. An override is the value to
 * return, or a closure that receives the arguments of the call.
 *
 * @param  array<string, mixed>  $overrides
 */
function conditionTags(array $overrides = []): void
{
    $defaults = [
        'is_date' => false, 'is_month' => false, 'is_day' => false, 'is_year' => false,
        'is_404' => false, 'is_search' => false, 'is_archive' => false, 'is_home' => false,
        'is_front_page' => false, 'is_paged' => false, 'is_tag' => false, 'is_category' => false,
        'is_author' => false, 'is_singular' => false, 'is_page' => false, 'is_tax' => false,
        'current_theme_supports' => false, 'has_tag' => false, 'get_tag' => false,
        'has_category' => false, 'get_post_taxonomies' => [], 'has_term' => false,
        'get_the_terms' => false, 'is_wp_error' => false,
    ];

    foreach ($overrides + $defaults as $name => $value) {
        $value instanceof Closure ? when($name)->alias($value) : when($name)->justReturn($value);
    }
}

/**
 * The rules for one condition.
 *
 * @return array{conditions: array{action: string, rules: list<array{major: string, minor: string}>}}
 */
function conditionInstance(string $major, string $minor = '', string $action = 'show'): array
{
    return ['conditions' => ['action' => $action, 'rules' => [['major' => $major, 'minor' => $minor]]]];
}

/**
 * Run filter_widget() for the rule and report whether the widget stays.
 *
 * @param  array<string, mixed>  $tags
 */
function widgetStays(string $major, string $minor, array $tags, string $action = 'show'): bool
{
    conditionTags($tags);

    return UAMS_Widget_Conditions::filter_widget(conditionInstance($major, $minor, $action)) !== false;
}

beforeEach(function (): void {
    stubEscaping();
    $this->savedPost = $_POST;
    $this->savedRequest = $_REQUEST;
    $this->savedGlobals = [$GLOBALS['post'] ?? null, $GLOBALS['wp_query'] ?? null];
    $_POST = [];
    $_REQUEST = [];
    unset($GLOBALS['post'], $GLOBALS['wp_query']);

    when('__')->returnArg();
    when('_e')->alias(static function (string $text): void {
        echo $text;
    });
    when('esc_attr_e')->alias(static function (string $text): void {
        echo 'A{'.$text.'}';
    });
    when('esc_html_e')->alias(static function (string $text): void {
        echo 'H{'.$text.'}';
    });
    when('esc_html_x')->alias(static fn (string $text): string => 'H{'.$text.'}');
    when('_x')->alias(static fn (string $text): string => $text);
    when('_ex')->alias(static function (string $text): void {
        echo $text;
    });
    when('sanitize_text_field')->alias(static fn (string $text): string => 'T{'.$text.'}');
    when('selected')->alias(static function (mixed $selected, mixed $current = true, bool $echo = true): string {
        $result = (string) $selected === (string) $current ? ' SEL' : '';

        if ($echo) {
            echo $result;
        }

        return $result;
    });
});

afterEach(function (): void {
    $_POST = $this->savedPost;
    $_REQUEST = $this->savedRequest;
    [$GLOBALS['post'], $GLOBALS['wp_query']] = $this->savedGlobals;
});

describe('construction', function (): void {
    it('hooks the front end when the file loads', function (): void {
        when('is_admin')->justReturn(false);
        $hooks = [];
        expectAdded('widget_display_callback')->once()->whenHappen(static function () use (&$hooks): void {
            $hooks[] = 'widget_display_callback';
        });
        expectAdded('sidebars_widgets')->once()->whenHappen(static function () use (&$hooks): void {
            $hooks[] = 'sidebars_widgets';
        });

        require_once dirname(__DIR__, 3).'/widgets/class.uams-widget-visibility.php';

        expect($hooks)->toBe(['widget_display_callback', 'sidebars_widgets']);
    });

    it('hooks the front end for a visitor', function (): void {
        when('is_admin')->justReturn(false);

        new UAMS_Widget_Conditions;

        expect(has_action('widget_display_callback', [UAMS_Widget_Conditions::class, 'filter_widget']))->toBe(10)
            ->and(has_action('sidebars_widgets', [UAMS_Widget_Conditions::class, 'sidebars_widgets']))->toBe(10)
            ->and(has_action('sidebar_admin_setup', [UAMS_Widget_Conditions::class, 'widget_admin_setup']))->toBeFalse()
            ->and(has_filter('widget_update_callback', [UAMS_Widget_Conditions::class, 'widget_update']))->toBeFalse();
    });

    it('hooks the admin screens in the admin', function (): void {
        when('is_admin')->justReturn(true);

        new UAMS_Widget_Conditions;

        expect(has_action('sidebar_admin_setup', [UAMS_Widget_Conditions::class, 'widget_admin_setup']))->toBe(10)
            ->and(has_filter('widget_update_callback', [UAMS_Widget_Conditions::class, 'widget_update']))->toBe(10)
            ->and(has_action('in_widget_form', [UAMS_Widget_Conditions::class, 'widget_conditions_admin']))->toBe(10)
            ->and(has_action('wp_ajax_widget_conditions_options', [UAMS_Widget_Conditions::class, 'widget_conditions_options']))->toBe(10)
            ->and(has_action('widget_display_callback', [UAMS_Widget_Conditions::class, 'filter_widget']))->toBeFalse()
            ->and(has_action('sidebars_widgets', [UAMS_Widget_Conditions::class, 'sidebars_widgets']))->toBeFalse();
    });

    it('takes the update and form hooks with their argument counts', function (): void {
        when('is_admin')->justReturn(true);
        expectAdded('in_widget_form')->once()->with([UAMS_Widget_Conditions::class, 'widget_conditions_admin'], 10, 3);
        Brain\Monkey\Filters\expectAdded('widget_update_callback')->once()->with([UAMS_Widget_Conditions::class, 'widget_update'], 10, 3);

        new UAMS_Widget_Conditions;
    });
});

it('enqueues the admin styles and script', function (): void {
    $calls = [];
    when('get_template_directory_uri')->justReturn('https://site.test/theme');
    when('wp_enqueue_style')->alias(static function (mixed ...$args) use (&$calls): void {
        $calls[] = ['style', ...$args];
    });
    when('wp_enqueue_script')->alias(static function (mixed ...$args) use (&$calls): void {
        $calls[] = ['script', ...$args];
    });

    UAMS_Widget_Conditions::widget_admin_setup();

    expect($calls)->toBe([
        ['style', 'widget-conditions', 'https://site.test/theme/assets/admin/css/uams.widget-conditions.css'],
        ['script', 'widget-conditions', 'https://site.test/theme/assets/admin/js/uams.widget-conditions.js', ['jquery', 'jquery-ui-core'], '20140422', true],
        ['style', 'widget-cards', 'https://site.test/theme/assets/admin/css/uams.widgets-misc.css'],
    ]);
});

it('compares names without regard to case', function (): void {
    expect(UAMS_Widget_Conditions::strcasecmp_name((object) ['name' => 'apple'], (object) ['name' => 'Banana']))->toBeLessThan(0)
        ->and(UAMS_Widget_Conditions::strcasecmp_name((object) ['name' => 'Same'], (object) ['name' => 'same']))->toBe(0)
        ->and(UAMS_Widget_Conditions::strcasecmp_name((object) ['name' => 'b'], (object) ['name' => 'A']))->toBeGreaterThan(0);
});

describe('filter_widget', function (): void {
    it('returns an instance without rules unchanged', function (array $instance): void {
        conditionTags();

        expect(UAMS_Widget_Conditions::filter_widget($instance))->toBe($instance);
    })->with([
        'no conditions' => [['title' => 'x']],
        'empty conditions' => [['conditions' => []]],
        'no rules' => [['conditions' => ['action' => 'hide']]],
        'empty rules' => [['conditions' => ['rules' => []]]],
        'rules not a list' => [['conditions' => ['rules' => 'x']]],
    ]);

    it('shows a widget when a date rule matches', function (string $minor, string $tag): void {
        expect(widgetStays('date', $minor, [$tag => true]))->toBeTrue()
            ->and(widgetStays('date', $minor, []))->toBeFalse();
    })->with([
        'any date archive' => ['', 'is_date'],
        'a month' => ['month', 'is_month'],
        'a day' => ['day', 'is_day'],
        'a year' => ['year', 'is_year'],
    ]);

    it('does not match a date rule with another kind of date', function (): void {
        expect(widgetStays('date', 'month', ['is_date' => true, 'is_day' => true, 'is_year' => true]))->toBeFalse()
            ->and(widgetStays('date', 'unknown', ['is_date' => true]))->toBeFalse();
    });

    it('shows a widget when a page rule matches', function (string $minor, string $tag): void {
        expect(widgetStays('page', $minor, [$tag => true]))->toBeTrue()
            ->and(widgetStays('page', $minor, []))->toBeFalse();
    })->with([
        'the 404 page' => ['404', 'is_404'],
        'search results' => ['search', 'is_search'],
        'an archive' => ['archive', 'is_archive'],
        'home' => ['home', 'is_home'],
        'the front page' => ['front', 'is_front_page'],
    ]);

    it('matches the posts page by the query flag', function (): void {
        $GLOBALS['wp_query'] = (object) ['is_posts_page' => true];
        expect(widgetStays('page', 'posts', []))->toBeTrue();

        $GLOBALS['wp_query'] = (object) ['is_posts_page' => false];
        expect(widgetStays('page', 'posts', []))->toBeFalse();

        $GLOBALS['wp_query'] = (object) [];
        expect(widgetStays('page', 'posts', []))->toBeFalse();

        unset($GLOBALS['wp_query']);
        expect(widgetStays('page', 'posts', []))->toBeFalse();
    });

    it('matches the front page only when it is not paged, unless infinite scroll is on', function (): void {
        expect(widgetStays('page', 'front', ['is_front_page' => true, 'is_paged' => true]))->toBeFalse()
            ->and(widgetStays('page', 'front', ['is_front_page' => true, 'is_paged' => false]))->toBeTrue()
            ->and(widgetStays('page', 'front', ['is_front_page' => true, 'is_paged' => true, 'current_theme_supports' => static fn (string $feature): bool => $feature === 'infinite-scroll']))->toBeTrue()
            ->and(widgetStays('page', 'front', ['is_front_page' => false, 'current_theme_supports' => true]))->toBeFalse();
    });

    it('matches a post type by singular view', function (string $minor, string $expectedType): void {
        $asked = [];
        $tags = ['is_singular' => static function (mixed $type = '') use (&$asked): bool {
            $asked[] = $type;

            return in_array($type, ['page', 'book', 'post'], true);
        }];

        expect(widgetStays('page', $minor, $tags))->toBeTrue()
            ->and($asked)->toBe([$expectedType]);
    })->with([
        'an empty minor is a page' => ['', 'page'],
        'a post type' => ['post_type-book', 'book'],
        'post is the post type' => ['post', 'post'],
    ]);

    it('matches a static page by its identifier', function (): void {
        $asked = [];

        $stays = widgetStays('page', '42', ['is_page' => static function (mixed $page) use (&$asked): bool {
            $asked[] = $page;

            return $page === '42';
        }]);

        expect($stays)->toBeTrue()
            ->and($asked)->toBe(['42']);
    });

    it('does not take an unrelated minor for a post type', function (): void {
        $asked = [];
        $tags = ['is_singular' => static function (mixed $type = '') use (&$asked): bool {
            $asked[] = $type;

            return true;
        }];

        expect(widgetStays('page', 'xpost_type-book', $tags))->toBeFalse()
            ->and($asked)->toBe([]);
    });

    describe('tag', function (): void {
        it('matches any tag archive without a minor', function (): void {
            expect(widgetStays('tag', '', ['is_tag' => true]))->toBeTrue()
                ->and(widgetStays('tag', '', []))->toBeFalse();
        });

        it('matches a post that has the tag', function (): void {
            expect(widgetStays('tag', 'news', ['is_singular' => true, 'has_tag' => true]))->toBeTrue()
                ->and(widgetStays('tag', 'news', ['is_singular' => false, 'has_tag' => true]))->toBeFalse()
                ->and(widgetStays('tag', 'news', ['is_singular' => true, 'has_tag' => false]))->toBeFalse();
        });

        it('matches the archive of the tag by its slug', function (): void {
            $asked = [];
            $tags = [
                'get_tag' => static fn (string $id): object => (object) ['slug' => 'news-'.$id],
                'is_tag' => static function (mixed $slug = '') use (&$asked): bool {
                    $asked[] = $slug;

                    return $slug === 'news-7';
                },
            ];

            expect(widgetStays('tag', '7', $tags))->toBeTrue()
                ->and($asked)->toBe(['news-7']);
        });

        it('does not match when the tag does not exist or is another archive', function (): void {
            expect(widgetStays('tag', '7', ['get_tag' => false, 'is_tag' => true]))->toBeFalse()
                ->and(widgetStays('tag', '7', ['get_tag' => (object) ['slug' => 'x'], 'is_tag' => false]))->toBeFalse();
        });
    });

    describe('category', function (): void {
        it('matches any category archive without a minor', function (): void {
            expect(widgetStays('category', '', ['is_category' => true]))->toBeTrue();
        });

        it('matches the archive of the category', function (): void {
            $asked = [];
            $tags = ['is_category' => static function (mixed $category = '') use (&$asked): bool {
                $asked[] = $category;

                return $category === '5';
            }];

            expect(widgetStays('category', '5', $tags))->toBeTrue()
                ->and($asked)->toBe(['5']);
        });

        it('matches a post in the category', function (): void {
            $tags = ['is_singular' => true, 'get_post_taxonomies' => ['category'], 'has_category' => true];

            expect(widgetStays('category', '5', $tags))->toBeTrue()
                ->and(widgetStays('category', '5', ['get_post_taxonomies' => ['post_tag']] + $tags))->toBeFalse()
                ->and(widgetStays('category', '5', ['is_singular' => false] + $tags))->toBeFalse()
                ->and(widgetStays('category', '5', ['has_category' => false] + $tags))->toBeFalse()
                ->and(widgetStays('category', '', ['is_category' => false] + $tags))->toBeFalse();
        });

        it('matches a category named by its identifier as a string only strictly', function (): void {
            expect(widgetStays('category', '5', ['is_singular' => true, 'get_post_taxonomies' => ['category', 'post_tag'], 'has_category' => true]))->toBeTrue();
        });
    });

    describe('author', function (): void {
        it('matches any author archive without a minor', function (): void {
            expect(widgetStays('author', '', ['is_author' => true]))->toBeTrue();
        });

        it('matches the archive of the author', function (): void {
            $asked = [];
            $tags = ['is_author' => static function (mixed $author = '') use (&$asked): bool {
                $asked[] = $author;

                return $author === '9';
            }];

            expect(widgetStays('author', '9', $tags))->toBeTrue()
                ->and($asked)->toBe(['9']);
        });

        it('matches a post by the author', function (): void {
            $GLOBALS['post'] = (object) ['post_author' => 9];

            expect(widgetStays('author', '9', ['is_singular' => true]))->toBeTrue()
                ->and(widgetStays('author', '8', ['is_singular' => true]))->toBeFalse()
                ->and(widgetStays('author', '9', ['is_singular' => false]))->toBeFalse();

            $GLOBALS['post'] = (object) [];

            expect(widgetStays('author', '9', ['is_singular' => true]))->toBeFalse();
        });
    });

    describe('taxonomy', function (): void {
        it('matches the archive of a term', function (): void {
            $asked = [];
            $tags = ['is_tax' => static function (mixed ...$args) use (&$asked): bool {
                $asked[] = $args;

                return true;
            }];

            expect(widgetStays('taxonomy', 'topic_tax_12', $tags))->toBeTrue()
                ->and($asked)->toBe([['topic', 12]]);
        });

        it('matches a post that has the term', function (): void {
            $asked = [];
            $tags = ['is_singular' => true, 'has_term' => static function (mixed ...$args) use (&$asked): bool {
                $asked[] = $args;

                return true;
            }];

            expect(widgetStays('taxonomy', 'topic_tax_12', $tags))->toBeTrue()
                ->and($asked)->toBe([[12, 'topic']])
                ->and(widgetStays('taxonomy', 'topic_tax_12', ['is_singular' => false] + $tags))->toBeFalse();
        });

        it('matches a post with any term of the taxonomy', function (): void {
            $GLOBALS['post'] = (object) ['ID' => 3];
            $asked = [];
            $tags = [
                'is_singular' => true,
                'get_the_terms' => static function (int $id, string $taxonomy) use (&$asked): array {
                    $asked[] = [$id, $taxonomy];

                    return [(object) ['term_id' => 1]];
                },
            ];

            expect(widgetStays('taxonomy', 'topic', $tags))->toBeTrue()
                ->and($asked)->toBe([[3, 'topic']]);
        });

        it('does not match without terms, with an error, or off a singular view', function (): void {
            $GLOBALS['post'] = (object) ['ID' => 3];

            expect(widgetStays('taxonomy', 'topic', ['is_singular' => true, 'get_the_terms' => []]))->toBeFalse()
                ->and(widgetStays('taxonomy', 'topic', ['is_singular' => true, 'get_the_terms' => false]))->toBeFalse()
                ->and(widgetStays('taxonomy', 'topic', ['is_singular' => true, 'get_the_terms' => [(object) []], 'is_wp_error' => true]))->toBeFalse()
                ->and(widgetStays('taxonomy', 'topic', ['is_singular' => false, 'get_the_terms' => [(object) []]]))->toBeFalse();
        });

        it('looks no terms up without a post or a taxonomy', function (): void {
            $asked = 0;
            $tags = ['is_singular' => true, 'get_the_terms' => static function () use (&$asked): array {
                $asked++;

                return [(object) []];
            }];

            expect(widgetStays('taxonomy', '', $tags))->toBeFalse()
                ->and($asked)->toBe(0);

            $GLOBALS['post'] = (object) [];

            expect(widgetStays('taxonomy', 'topic', $tags))->toBeFalse()
                ->and($asked)->toBe(0);
        });

        it('needs both a taxonomy and a term for the archive and the term checks', function (): void {
            expect(widgetStays('taxonomy', 'topic', ['is_tax' => true]))->toBeFalse()
                ->and(widgetStays('taxonomy', 'topic', ['is_singular' => true, 'has_term' => true]))->toBeFalse()
                ->and(widgetStays('taxonomy', '_tax_12', ['is_tax' => true, 'is_singular' => true, 'has_term' => true]))->toBeFalse();
        });
    });

    it('stops at the first rule that matches', function (): void {
        conditionTags(['is_404' => true, 'is_search' => static function (): never {
            throw new LogicException('The second rule must not be evaluated.');
        }]);
        $instance = ['conditions' => ['action' => 'show', 'rules' => [['major' => 'page', 'minor' => '404'], ['major' => 'page', 'minor' => 'search']]]];

        expect(UAMS_Widget_Conditions::filter_widget($instance))->toBe($instance);
    });

    it('moves on to the next rule when one does not match', function (): void {
        conditionTags(['is_search' => true]);
        $instance = ['conditions' => ['action' => 'show', 'rules' => [['major' => 'page', 'minor' => '404'], ['major' => 'page', 'minor' => 'search']]]];

        expect(UAMS_Widget_Conditions::filter_widget($instance))->toBe($instance);
    });

    it('ignores a rule without a major', function (): void {
        conditionTags();

        expect(UAMS_Widget_Conditions::filter_widget(['conditions' => ['action' => 'show', 'rules' => [['minor' => 'x']]]]))->toBeFalse()
            ->and(UAMS_Widget_Conditions::filter_widget(['conditions' => ['action' => 'hide', 'rules' => [['minor' => 'x']]]]))->toBe(['conditions' => ['action' => 'hide', 'rules' => [['minor' => 'x']]]]);
    });

    it('shows or hides by the action, which defaults to show', function (string $action, bool $matches, bool $stays): void {
        expect(widgetStays('page', '404', ['is_404' => $matches], $action))->toBe($stays);
    })->with([
        'show and match' => ['show', true, true],
        'show and no match' => ['show', false, false],
        'hide and match' => ['hide', true, false],
        'hide and no match' => ['hide', false, true],
    ]);

    it('shows when the action is missing and the rule matches', function (): void {
        conditionTags(['is_404' => true]);
        $instance = ['conditions' => ['rules' => [['major' => 'page', 'minor' => '404']]]];

        expect(UAMS_Widget_Conditions::filter_widget($instance))->toBe($instance);

        conditionTags();

        expect(UAMS_Widget_Conditions::filter_widget($instance))->toBeFalse();
    });

    it('leaves a widget alone for an action that is neither show nor hide', function (): void {
        expect(widgetStays('page', '404', ['is_404' => true], 'other'))->toBeTrue()
            ->and(widgetStays('page', '404', ['is_404' => false], 'other'))->toBeTrue();
    });
});

describe('sidebars_widgets', function (): void {
    beforeEach(function (): void {
        conditionTags(['is_404' => true]);
    });

    /**
     * @return array<string, mixed>
     */
    function widgetSettings(string $action): array
    {
        return [1 => ['conditions' => ['action' => $action, 'rules' => [['major' => 'page', 'minor' => '404']]]]];
    }

    it('removes a numbered widget whose rules hide it', function (): void {
        when('get_option')->alias(static fn (string $name): mixed => $name === 'widget_text' ? widgetSettings('hide') : false);

        $areas = UAMS_Widget_Conditions::sidebars_widgets(['main' => ['text-1', 'other-2'], 'side' => ['text-1']]);

        expect($areas)->toBe(['main' => [1 => 'other-2'], 'side' => []]);
    });

    it('keeps a numbered widget whose rules show it', function (): void {
        when('get_option')->alias(static fn (string $name): mixed => widgetSettings('show'));

        expect(UAMS_Widget_Conditions::sidebars_widgets(['main' => ['text-1']]))->toBe(['main' => ['text-1']]);
    });

    it('keeps a widget with no settings of its own', function (): void {
        when('get_option')->justReturn([2 => ['title' => 'x']]);

        expect(UAMS_Widget_Conditions::sidebars_widgets(['main' => ['text-1', 'text-2']]))->toBe(['main' => ['text-1', 'text-2']]);
    });

    it('reads the settings of each kind of widget once', function (): void {
        $reads = [];
        when('get_option')->alias(static function (string $name) use (&$reads): mixed {
            $reads[] = $name;

            return false;
        });

        UAMS_Widget_Conditions::sidebars_widgets(['main' => ['text-1', 'text-2', 'meta-3'], 'side' => ['text-4']]);

        expect($reads)->toBe(['widget_text', 'widget_meta']);
    });

    it('filters a widget with an id that has no number as a whole', function (): void {
        $hidden = ['conditions' => ['action' => 'hide', 'rules' => [['major' => 'page', 'minor' => '404']]]];
        when('get_option')->alias(static fn (string $name): mixed => $name === 'widget_calendar' ? $hidden : false);

        $areas = UAMS_Widget_Conditions::sidebars_widgets(['main' => ['calendar', 'search']]);

        expect($areas)->toBe(['main' => [1 => 'search']]);
    });

    it('keeps an unnumbered widget with empty settings', function (): void {
        when('get_option')->justReturn([]);

        expect(UAMS_Widget_Conditions::sidebars_widgets(['main' => ['calendar']]))->toBe(['main' => ['calendar']]);
    });

    it('skips empty areas and the inactive one', function (): void {
        $reads = 0;
        when('get_option')->alias(static function () use (&$reads): mixed {
            $reads++;

            return false;
        });

        $areas = ['empty' => [], 'wp_inactive_widgets' => ['text-1'], 'main' => ['text-2']];

        expect(UAMS_Widget_Conditions::sidebars_widgets($areas))->toBe($areas)
            ->and($reads)->toBe(1);
    });

    it('keeps the number of a widget id that has a dash', function (): void {
        $reads = [];
        when('get_option')->alias(static function (string $name) use (&$reads): mixed {
            $reads[] = $name;

            return [12 => widgetSettings('hide')[1]];
        });

        $areas = UAMS_Widget_Conditions::sidebars_widgets(['main' => ['my-text-12', 'my-text-x']]);

        expect($areas)->toBe(['main' => [1 => 'my-text-x']])
            ->and($reads)->toBe(['widget_my-text', 'widget_my-text-x']);
    });
});

describe('widget_update', function (): void {
    it('returns the instance unchanged without posted conditions', function (array $post): void {
        $_POST = $post;

        expect(UAMS_Widget_Conditions::widget_update(['title' => 'x'], [], []))->toBe(['title' => 'x']);
    })->with([
        'nothing' => [[]],
        'no action' => [['conditions' => ['rules_major' => ['page']]]],
        'no rules' => [['conditions' => ['action' => 'show']]],
        'rules not a list' => [['conditions' => ['action' => 'show', 'rules_major' => 'page']]],
    ]);

    it('stores the posted rules, skipping empty ones, and announces a first save', function (): void {
        $_POST = ['conditions' => ['action' => 'hide', 'rules_major' => ['page', '', 'tag'], 'rules_minor' => ['404', 'x', '7']]];
        expectDone('widget_conditions_save')->once();

        $instance = UAMS_Widget_Conditions::widget_update(['title' => 'x'], [], []);

        expect($instance)->toBe(['title' => 'x', 'conditions' => [
            'action' => 'T{hide}',
            'rules' => [['major' => 'T{page}', 'minor' => 'T{404}'], ['major' => 'T{tag}', 'minor' => 'T{7}']],
        ]]);
    });

    it('stores an empty minor when none was posted for the rule', function (): void {
        $_POST = ['conditions' => ['action' => 'show', 'rules_major' => ['page'], 'rules_minor' => []]];

        $instance = UAMS_Widget_Conditions::widget_update([], [], []);

        expect($instance['conditions']['rules'])->toBe([['major' => 'T{page}', 'minor' => '']]);
    });

    it('announces a change to the rules', function (): void {
        $_POST = ['conditions' => ['action' => 'show', 'rules_major' => ['page'], 'rules_minor' => ['404']]];
        $old = ['conditions' => ['action' => 'T{show}', 'rules' => [['major' => 'T{page}', 'minor' => 'T{search}']]]];
        expectDone('widget_conditions_save')->once();

        UAMS_Widget_Conditions::widget_update([], [], $old);
    });

    it('announces nothing when the rules are the same', function (): void {
        $_POST = ['conditions' => ['action' => 'show', 'rules_major' => ['page'], 'rules_minor' => ['404']]];
        $old = ['conditions' => ['action' => 'T{show}', 'rules' => [['major' => 'T{page}', 'minor' => 'T{404}']]]];
        expectDone('widget_conditions_save')->never();
        expectDone('widget_conditions_delete')->never();

        UAMS_Widget_Conditions::widget_update([], [], $old);
    });

    it('drops the conditions and announces the deletion when every rule is empty', function (): void {
        $_POST = ['conditions' => ['action' => 'show', 'rules_major' => ['', '']]];
        expectDone('widget_conditions_delete')->once();
        expectDone('widget_conditions_save')->never();

        $instance = UAMS_Widget_Conditions::widget_update(['title' => 'x', 'conditions' => ['rules' => []]], [], ['conditions' => ['rules' => [1]]]);

        expect($instance)->toBe(['title' => 'x']);
    });

    it('announces nothing when there were no conditions and still are none', function (): void {
        $_POST = ['conditions' => ['action' => 'show', 'rules_major' => ['']]];
        expectDone('widget_conditions_delete')->never();
        expectDone('widget_conditions_save')->never();

        expect(UAMS_Widget_Conditions::widget_update(['title' => 'x'], [], []))->toBe(['title' => 'x']);
    });
});

describe('widget_conditions_options', function (): void {
    beforeEach(function (): void {
        $this->died = 0;
        when('sanitize_text_field')->alias(static fn (string $text): string => $text);
        when('wp_die')->alias(function (): void {
            $this->died++;
        });
    });

    it('prints the options for the requested rule and ends the request', function (): void {
        $_REQUEST = ['major' => 'date', 'minor' => 'day'];

        $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options()));

        expect($html)->toContain('<option value="day" SEL>Daily archives</option>')
            ->and($this->died)->toBe(1);
    });

    it('defaults to no rule', function (): void {
        $html = render(fn () => UAMS_Widget_Conditions::widget_conditions_options());

        expect($html)->toBe('')
            ->and($this->died)->toBe(1);
    });
});

describe('widget_conditions_options_echo', function (): void {
    it('prints nothing for an unknown rule', function (): void {
        expect(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('unknown')))->toBe('')
            ->and(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo()))->toBe('');
    });

    it('lists the categories by name, with the chosen one selected', function (): void {
        $asked = [];
        when('get_categories')->alias(static function (array $args) use (&$asked): array {
            $asked[] = $args;

            return [(object) ['term_id' => 2, 'name' => 'Zeta'], (object) ['term_id' => 3, 'name' => 'alpha']];
        });

        $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('category', '3')));

        expect($html)->toBe('<option value="">All category pages</option> <option value="A{3}" SEL>H{alpha}</option> <option value="A{2}" >H{Zeta}</option>')
            ->and($asked)->toBe([['number' => 1000, 'orderby' => 'count', 'order' => 'DESC']]);
    });

    it('lists the authors', function (): void {
        $asked = [];
        when('get_users')->alias(static function (array $args) use (&$asked): array {
            $asked[] = $args;

            return [(object) ['ID' => 4, 'display_name' => 'Ann']];
        });

        $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('author', '4')));

        expect($html)->toBe('<option value="">All author pages</option> <option value="A{4}" SEL>H{Ann}</option>')
            ->and($asked)->toBe([['orderby' => 'name', 'exclude_admin' => true]]);
    });

    it('lists the tags by name', function (): void {
        $asked = [];
        when('get_tags')->alias(static function (array $args) use (&$asked): array {
            $asked[] = $args;

            return [(object) ['term_id' => 8, 'name' => 'b'], (object) ['term_id' => 9, 'name' => 'A']];
        });

        $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('tag', '8')));

        expect($html)->toBe('<option value="">All tag pages</option> <option value="A{9}" >H{A}</option> <option value="A{8}" SEL>H{b}</option>')
            ->and($asked)->toBe([['number' => 1000, 'orderby' => 'count', 'order' => 'DESC']]);
    });

    it('lists the kinds of date archive', function (string $minor, string $selected): void {
        $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('date', $minor)));

        expect(substr_count($html, 'SEL'))->toBe(1)
            ->and($html)->toContain('<option value="'.$selected.'" SEL>');
    })->with([
        'all' => ['', ''],
        'day' => ['day', 'day'],
        'month' => ['month', 'month'],
        'year' => ['year', 'year'],
    ]);

    describe('page', function (): void {
        beforeEach(function (): void {
            when('get_post_types')->alias(static fn (array $args, string $output): array => [
                (object) ['name' => 'page', 'labels' => (object) ['singular_name' => 'Page']],
                (object) ['name' => 'post', 'labels' => (object) ['singular_name' => 'Post']],
            ]);
            when('wp_dropdown_pages')->alias(static fn (array $args): string => '<select name="x"><option value="42">About</option><option value="43">Contact</option></select>');
        });

        it('lists the special pages, the post types and the static pages', function (): void {
            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('page', 'search')));

            expect($html)->toContain('<option value="front" >Front page</option>')
                ->and($html)->toContain('<option value="posts" >Posts page</option>')
                ->and($html)->toContain('<option value="archive" >Archive page</option>')
                ->and($html)->toContain('<option value="404" >404 error page</option>')
                ->and($html)->toContain('<option value="search" SEL>Search results</option>')
                ->and($html)->toContain('<optgroup label="A{Post type:}">')
                ->and($html)->toContain('<option value="A{post_type-page}" >H{Page}</option>')
                ->and($html)->toContain('<option value="A{post_type-post}" >H{Post}</option>')
                ->and($html)->toContain('<optgroup label="A{Static page:}"> <option value="42">About</option><option value="43">Contact</option> </optgroup>');
        });

        it('selects the page type when there is no minor, and the post type for post', function (string $minor, string $selected): void {
            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('page', $minor)));

            expect($html)->toContain('<option value="A{'.$selected.'}" SEL>');
        })->with([
            'no minor' => ['', 'post_type-page'],
            'post' => ['post', 'post_type-post'],
        ]);

        it('marks the chosen static page', function (): void {
            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('page', '43')));

            expect($html)->not->toContain('<option value="42" selected="selected">')
                ->and($html)->not->toContain('selected="selected"');
        });

        it('marks a static page that is stored as the minor', function (): void {
            when('esc_attr')->alias(static fn (string $value): string => $value);

            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('page', '43')));

            expect($html)->toContain('<option value="43" selected="selected">Contact</option>')
                ->and($html)->toContain('<option value="42">About</option>');
        });
    });

    describe('taxonomy', function (): void {
        beforeEach(function (): void {
            when('get_taxonomies')->alias(static fn (array $args, string $output): array => [
                (object) ['name' => 'topic', 'labels' => (object) ['name' => 'Topics']],
                (object) ['name' => 'audience', 'labels' => (object) ['name' => 'Audiences']],
            ]);
            when('get_terms')->alias(static fn (array $args): array|WP_Error => $args['taxonomy'] === 'topic'
                ? [(object) ['term_id' => 5, 'name' => 'Heart']]
                : new WP_Error);
        });

        it('lists each taxonomy by name with its terms', function (): void {
            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('taxonomy', 'topic_tax_5')));

            expect($html)->toBe(
                '<option value="">All taxonomy pages</option> '
                .'<optgroup label="A{Audiences:}"> <option value="A{audience}" >All H{audience} pages</option> </optgroup> '
                .'<optgroup label="A{Topics:}"> <option value="A{topic}" >All H{topic} pages</option> '
                .'<option value="A{topic_tax_5}" SEL>H{Heart}</option> </optgroup>'
            );
        });

        it('asks for the terms of each taxonomy', function (): void {
            $asked = [];
            when('get_terms')->alias(static function (array $args) use (&$asked): array {
                $asked[] = $args;

                return [];
            });

            render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('taxonomy'));

            expect($asked)->toBe([
                ['taxonomy' => 'audience', 'number' => 250, 'hide_empty' => false],
                ['taxonomy' => 'topic', 'number' => 250, 'hide_empty' => false],
            ]);
        });

        it('selects the taxonomy itself', function (): void {
            $html = squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('taxonomy', 'audience')));

            expect($html)->toContain('<option value="A{audience}" SEL>')
                ->and(substr_count($html, 'SEL'))->toBe(1);
        });

        it('leaves out terms that are not a list', function (): void {
            when('get_terms')->justReturn('nope');

            $html = render(fn () => UAMS_Widget_Conditions::widget_conditions_options_echo('taxonomy'));

            expect($html)->not->toContain('_tax_');
        });
    });
});

describe('widget_conditions_admin', function (): void {
    beforeEach(function (): void {
        when('get_taxonomies')->justReturn(['topic' => 'topic']);
        when('get_categories')->justReturn([]);
        when('get_users')->justReturn([]);
        when('get_tags')->justReturn([]);
        when('get_post_types')->justReturn([]);
        when('wp_dropdown_pages')->justReturn('');
    });

    /**
     * @param  array<string, mixed>  $instance
     */
    function conditionsForm(array $instance): string
    {
        return squishWidgetHtml(render(fn () => UAMS_Widget_Conditions::widget_conditions_admin(null, null, $instance)));
    }

    it('prints one empty rule with the show action and a hidden panel', function (): void {
        $html = conditionsForm([]);

        expect($html)->toContain('<div class="widget-conditional widget-conditional-hide">')
            ->and($html)->toContain('name="widget-conditions-visible" value="0" />')
            ->and($html)->toContain('<a href="#" class="button display-options">Visibility</a>')
            ->and($html)->toContain('<select name="conditions[action]"><option value="show" SEL>H{Show}</option><option value="hide" >H{Hide}</option></select> if:')
            ->and(substr_count($html, '<div class="condition">'))->toBe(1)
            ->and($html)->toContain('<option value="" SEL>H{-- Select --}</option>')
            ->and($html)->toContain('<select class="conditions-rule-minor" name="conditions[rules_minor][]" disabled="disabled" data-loading-text="A{Loading...}">')
            ->and($html)->toContain('<option value="taxonomy" >');
    });

    it('prints the stored action and rules', function (): void {
        $instance = ['conditions' => ['action' => 'hide', 'rules' => [['major' => 'date', 'minor' => 'day'], ['major' => 'tag']]]];

        $html = conditionsForm($instance);

        expect($html)->toContain('<option value="show" >H{Show}</option><option value="hide" SEL>H{Hide}</option>')
            ->and(substr_count($html, '<div class="condition">'))->toBe(2)
            ->and($html)->toContain('<option value="date" SEL>')
            ->and($html)->toContain('name="conditions[rules_minor][]" data-loading-text=')
            ->and($html)->not->toContain('disabled="disabled" data-loading-text="A{Loading...}"> <option value="" >All date')
            ->and($html)->toContain('<option value="day" SEL>Daily archives</option>');
    });

    it('prints the default rule when the stored rules are empty or not a list', function (mixed $rules): void {
        $html = conditionsForm(['conditions' => ['rules' => $rules]]);

        expect(substr_count($html, '<div class="condition">'))->toBe(1)
            ->and($html)->toContain('disabled="disabled"');
    })->with(['empty' => [[]], 'a string' => ['x'], 'null' => [null]]);

    it('uses the default conditions when the stored ones are not a list', function (): void {
        $html = conditionsForm(['conditions' => 'x']);

        expect($html)->toContain('<option value="show" SEL>');
    });

    it('opens the panel and drops the visibility button when it was open', function (): void {
        $_POST = ['widget-conditions-visible' => '1'];

        $html = conditionsForm([]);

        expect($html)->toContain('<div class="widget-conditional ">')
            ->and($html)->toContain('name="widget-conditions-visible" value="1" />')
            ->and($html)->not->toContain('display-options');
    });

    it('closes the panel when it was posted closed', function (): void {
        $_POST = ['widget-conditions-visible' => ''];

        $html = conditionsForm([]);

        expect($html)->toContain('widget-conditional-hide')
            ->and($html)->toContain('value="0" />')
            ->and($html)->not->toContain('display-options');
    });

    it('offers taxonomy rules only when the site has custom taxonomies', function (): void {
        when('get_taxonomies')->justReturn([]);

        expect(conditionsForm([]))->not->toContain('value="taxonomy"');
    });
});
