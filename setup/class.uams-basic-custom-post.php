<?php
// Basic Wrapper for a custom post type. Allows child themes to easily make a custom post type
class UAMS_Custom_Post {

    public $name;
    public $args     = array();
    public $taxonomy = array();
    public $post_label = 'Post';

    public function __construct( $args ) {
        if ( empty( $args['name'] ) ) {
            return;
        }
        $this->name = $args['name'];
        $this->args = isset( $args['args'] ) && is_array( $args['args'] ) ? $args['args'] : array();

        if ( isset( $args['labels'] ) ) {
            $this->args['labels'] = $args['labels'];
        } elseif ( ! isset( $this->args['labels'] ) ) {
            if ( isset( $args['post_label'] ) ) {
                $this->post_label = $args['post_label'];
            }
            $this->args['labels'] = $this->label_gen();
        }

        add_action( 'init', array( $this, 'register_post' ) );

        if ( isset( $args['taxonomy'] ) && is_array( $args['taxonomy'] ) ) {
            $this->taxonomy = $args['taxonomy'];
            add_action( 'init', array( $this, 'add_custom_taxonomy' ) );
        }
    }

    public function label_gen() {
        return array(
            'name'               => __( $this->post_label . 's' ),
            'singular_name'      => __( $this->post_label ),
            'all_items'          => __( $this->post_label . 's' ),
            'menu_name'          => __( $this->post_label . 's' ),
            'add_new'            => _x( 'Add New', $this->post_label ),
            'add_new_item'       => __( 'Add New ' . $this->post_label ),
            'edit_item'          => __( 'Edit ' . $this->post_label ),
            'new_item'           => __( 'New ' . $this->post_label ),
            'view_item'          => __( 'View ' . $this->post_label ),
            'search_items'       => __( 'Search ' . $this->post_label . 's' ),
            'not_found'          => __( 'No ' . $this->post_label . 's found' ),
            'not_found_in_trash' => __( 'No ' . $this->post_label . 's found in Trash' ),
        );
    }

    public function register_post() {
        if ( ! post_type_exists( $this->name ) ) {
            register_post_type( $this->name, $this->args );
        }
    }

    public function add_custom_taxonomy() {
        if ( empty( $this->taxonomy['name'] ) ) {
            return;
        }

        if ( ! taxonomy_exists( $this->taxonomy['name'] ) ) {
            if ( isset( $this->taxonomy['labels'] ) ) {
                $this->taxonomy['args']['labels'] = $this->taxonomy['labels'];
            }
            $tax_args = isset( $this->taxonomy['args'] ) && is_array( $this->taxonomy['args'] ) ? $this->taxonomy['args'] : array();
            register_taxonomy( $this->taxonomy['name'], array( $this->name ), $tax_args );
        } elseif ( ! in_array( $this->taxonomy['name'], get_object_taxonomies( $this->name ), true ) ) {
            register_taxonomy_for_object_type( $this->taxonomy['name'], $this->name );
        }
    }
}
