<?php

/**
 * UAMS_Custom_Post, the wrapper child themes use to register a custom post type and its taxonomy,
 * with WordPress mocked.
 *
 * @see setup/class.uams-basic-custom-post.php
 */

declare(strict_types=1);

use function Brain\Monkey\Functions\when;

beforeEach(function (): void {
    loadShortcodeFile('class.uams-basic-custom-post.php');
    when('__')->alias(static fn (string $text): string => '__('.$text.')');
    when('_x')->alias(static fn (string $text, string $context): string => '_x('.$text.'|'.$context.')');
});

/**
 * The labels label_gen() builds for a post label, with the translation stubs' markers.
 *
 * @return array<string, string>
 */
function expectedLabels(string $label): array
{
    return [
        'name' => '__('.$label.'s)',
        'singular_name' => '__('.$label.')',
        'all_items' => '__('.$label.'s)',
        'menu_name' => '__('.$label.'s)',
        'add_new' => '_x(Add New|'.$label.')',
        'add_new_item' => '__(Add New '.$label.')',
        'edit_item' => '__(Edit '.$label.')',
        'new_item' => '__(New '.$label.')',
        'view_item' => '__(View '.$label.')',
        'search_items' => '__(Search '.$label.'s)',
        'not_found' => '__(No '.$label.'s found)',
        'not_found_in_trash' => '__(No '.$label.'s found in Trash)',
    ];
}

describe('the constructor', function (): void {
    it('does nothing without a name', function (array $args): void {
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $post = new UAMS_Custom_Post($args);

        expect($post->name)->toBeNull()
            ->and($post->args)->toBe([])
            ->and(has_action('init', [$post, 'register_post']))->toBeFalse();
    })->with(['no name' => [[]], 'an empty name' => [['name' => '']]]);

    it('registers on init with generated labels by default', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event']);

        expect($post->name)->toBe('event')
            ->and($post->post_label)->toBe('Post')
            ->and($post->args)->toBe(['labels' => expectedLabels('Post')])
            ->and($post->taxonomy)->toBe([])
            ->and(has_action('init', [$post, 'register_post']))->toBe(10)
            ->and(has_action('init', [$post, 'add_custom_taxonomy']))->toBeFalse();
    });

    it('keeps the arguments it is given and the post label for the labels', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event', 'args' => ['public' => true], 'post_label' => 'Event']);

        expect($post->args)->toBe(['public' => true, 'labels' => expectedLabels('Event')])
            ->and($post->post_label)->toBe('Event');
    });

    it('ignores arguments that are not an array', function (): void {
        expect((new UAMS_Custom_Post(['name' => 'event', 'args' => 'x']))->args)->toBe(['labels' => expectedLabels('Post')]);
    });

    it('uses the labels it is given over the arguments and the generated ones', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event', 'args' => ['labels' => ['name' => 'A']], 'labels' => ['name' => 'B'], 'post_label' => 'Event']);

        expect($post->args)->toBe(['labels' => ['name' => 'B']])
            ->and($post->post_label)->toBe('Post');
    });

    it('keeps labels set in the arguments', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event', 'args' => ['labels' => ['name' => 'A']], 'post_label' => 'Event']);

        expect($post->args)->toBe(['labels' => ['name' => 'A']])
            ->and($post->post_label)->toBe('Post');
    });

    it('registers the taxonomy on init when one is given as an array', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic']]);

        expect($post->taxonomy)->toBe(['name' => 'topic'])
            ->and(has_action('init', [$post, 'add_custom_taxonomy']))->toBe(10);
    });

    it('ignores a taxonomy that is not an array', function (): void {
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => 'topic']);

        expect($post->taxonomy)->toBe([])
            ->and(has_action('init', [$post, 'add_custom_taxonomy']))->toBeFalse();
    });
});

it('builds the labels from the post label', function (): void {
    $post = new UAMS_Custom_Post(['name' => 'event']);
    $post->post_label = 'Story';

    expect($post->label_gen())->toBe(expectedLabels('Story'));
});

describe('register_post', function (): void {
    it('registers a post type that does not exist', function (): void {
        $registered = [];
        when('post_type_exists')->justReturn(false);
        when('register_post_type')->alias(static function (string $name, array $args) use (&$registered): void {
            $registered[] = [$name, $args];
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'labels' => ['name' => 'E']]);

        $post->register_post();

        expect($registered)->toBe([['event', ['labels' => ['name' => 'E']]]]);
    });

    it('leaves a post type that exists', function (): void {
        $asked = [];
        when('post_type_exists')->alias(static function (string $name) use (&$asked): bool {
            $asked[] = $name;

            return true;
        });
        when('register_post_type')->alias(static function (): never {
            throw new RuntimeException('registered');
        });

        (new UAMS_Custom_Post(['name' => 'event']))->register_post();

        expect($asked)->toBe(['event']);
    });
});

describe('add_custom_taxonomy', function (): void {
    it('does nothing without a taxonomy name', function (array $taxonomy): void {
        when('taxonomy_exists')->alias(static function (): never {
            throw new RuntimeException('asked');
        });
        $post = new UAMS_Custom_Post(['name' => 'event']);
        $post->taxonomy = $taxonomy;

        $post->add_custom_taxonomy();

        expect($post->taxonomy)->toBe($taxonomy);
    })->with(['none' => [[]], 'empty' => [['name' => '']]]);

    it('registers a taxonomy that does not exist, with its labels', function (): void {
        $registered = [];
        when('taxonomy_exists')->justReturn(false);
        when('register_taxonomy')->alias(static function (string $name, array $types, array $args) use (&$registered): void {
            $registered[] = [$name, $types, $args];
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic', 'labels' => ['name' => 'Topics'], 'args' => ['hierarchical' => true]]]);

        $post->add_custom_taxonomy();

        expect($registered)->toBe([['topic', ['event'], ['hierarchical' => true, 'labels' => ['name' => 'Topics']]]]);
    });

    it('registers a taxonomy given only its name', function (): void {
        $registered = [];
        when('taxonomy_exists')->justReturn(false);
        when('register_taxonomy')->alias(static function (string $name, array $types, array $args) use (&$registered): void {
            $registered[] = [$name, $types, $args];
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic']]);

        $post->add_custom_taxonomy();

        expect($registered)->toBe([['topic', ['event'], []]]);
    });

    it('ignores taxonomy arguments that are not an array', function (): void {
        $registered = [];
        when('taxonomy_exists')->justReturn(false);
        when('register_taxonomy')->alias(static function (string $name, array $types, array $args) use (&$registered): void {
            $registered[] = $args;
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic', 'args' => 'x']]);

        $post->add_custom_taxonomy();

        expect($registered)->toBe([[]]);
    });

    it('attaches an existing taxonomy that the post type lacks', function (): void {
        $attached = [];
        when('taxonomy_exists')->justReturn(true);
        when('get_object_taxonomies')->alias(static fn (string $type): array => $type === 'event' ? ['category'] : []);
        when('register_taxonomy_for_object_type')->alias(static function (string $taxonomy, string $type) use (&$attached): void {
            $attached[] = [$taxonomy, $type];
        });
        when('register_taxonomy')->alias(static function (): never {
            throw new RuntimeException('registered');
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic']]);

        $post->add_custom_taxonomy();

        expect($attached)->toBe([['topic', 'event']]);
    });

    it('leaves an existing taxonomy the post type has', function (): void {
        when('taxonomy_exists')->justReturn(true);
        when('get_object_taxonomies')->justReturn(['category', 'topic']);
        when('register_taxonomy_for_object_type')->alias(static function (): never {
            throw new RuntimeException('attached');
        });
        $post = new UAMS_Custom_Post(['name' => 'event', 'taxonomy' => ['name' => 'topic']]);

        $post->add_custom_taxonomy();

        expect($post->taxonomy)->toBe(['name' => 'topic']);
    });
});
