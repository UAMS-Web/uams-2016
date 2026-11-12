<?php

// Basic Wrapper for a custom post type. Allows child themes to easily make a custom post type
class UAMS_Custom_Post
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array<string, mixed>
     */
    public $args = [];

    /**
     * @var array<mixed, mixed>
     */
    public $taxonomy = [];

    /**
     * @var string
     */
    public $post_label = 'Post';

    /**
     * Store the post type configuration and hook its registration.
     *
     * @param  array<string, mixed>  $args  The `name`, and optionally `args`, `labels`, `post_label` and `taxonomy`.
     */
    public function __construct($args)
    {
        if (empty($args['name'])) {
            return;
        }

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore assign.propertyType
        $this->name = $args['name'];
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore assign.propertyType
        $this->args = isset($args['args']) && is_array($args['args']) ? $args['args'] : [];

        if (isset($args['labels'])) {
            $this->args['labels'] = $args['labels'];
        } elseif (! isset($this->args['labels'])) {
            if (isset($args['post_label'])) {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore assign.propertyType
                $this->post_label = $args['post_label'];
            }

            $this->args['labels'] = $this->label_gen();
        }

        add_action('init', [$this, 'register_post']);

        if (isset($args['taxonomy']) && is_array($args['taxonomy'])) {
            $this->taxonomy = $args['taxonomy'];
            add_action('init', [$this, 'add_custom_taxonomy']);
        }
    }

    /**
     * Build the post type labels from the post label.
     *
     * @return array<string, string>
     */
    public function label_gen(): array
    {
        return [
            'name' => __($this->post_label.'s'),
            'singular_name' => __($this->post_label),
            'all_items' => __($this->post_label.'s'),
            'menu_name' => __($this->post_label.'s'),
            'add_new' => _x('Add New', $this->post_label),
            'add_new_item' => __('Add New '.$this->post_label),
            'edit_item' => __('Edit '.$this->post_label),
            'new_item' => __('New '.$this->post_label),
            'view_item' => __('View '.$this->post_label),
            'search_items' => __('Search '.$this->post_label.'s'),
            'not_found' => __('No '.$this->post_label.'s found'),
            'not_found_in_trash' => __('No '.$this->post_label.'s found in Trash'),
        ];
    }

    public function register_post(): void
    {
        if (! post_type_exists($this->name)) {
            // $this->args is the open array the child themes fill; register_post_type() documents a closed shape.
            // @phpstan-ignore argument.type
            register_post_type($this->name, $this->args);
        }
    }

    public function add_custom_taxonomy(): void
    {
        if (empty($this->taxonomy['name'])) {
            return;
        }

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        if (! taxonomy_exists($this->taxonomy['name'])) {
            if (isset($this->taxonomy['labels'])) {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $this->taxonomy['args']['labels'] = $this->taxonomy['labels'];
            }

            $tax_args = isset($this->taxonomy['args']) && is_array($this->taxonomy['args']) ? $this->taxonomy['args'] : [];
            // The labels and arguments are assembled from the post type configuration, so PHPStan cannot narrow them to the stub's shape.
            // @phpstan-ignore argument.type, argument.type
            register_taxonomy($this->taxonomy['name'], [$this->name], $tax_args);
        } elseif (! in_array($this->taxonomy['name'], get_object_taxonomies($this->name), true)) {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            register_taxonomy_for_object_type($this->taxonomy['name'], $this->name);
        }
    }
}
