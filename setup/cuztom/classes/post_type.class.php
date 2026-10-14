<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Type class used to register post types
 *
 * @author  Gijs Jorissen
 * @since   0.1
 */
#[\AllowDynamicProperties]
class Cuztom_Post_Type
{
	public $name;
	public $title;
	public $plural;
	public $args            = array();
	public $labels          = array();
	public $add_features    = array();
	public $remove_features = array();

	public function __construct( $name, $args = array(), $labels = array() )
	{
		if ( ! empty( $name ) ) {
			if ( is_array( $name ) ) {
				$this->name   = Cuztom::uglify( $name[0] );
				$this->title  = Cuztom::beautify( $name[0] );
				$this->plural = isset( $name[1] ) ? Cuztom::beautify( $name[1] ) : Cuztom::pluralize( $this->title );
			} else {
				$this->name   = Cuztom::uglify( $name );
				$this->title  = Cuztom::beautify( $name );
				$this->plural = Cuztom::pluralize( $this->title );
			}

			$this->args            = is_array( $args ) ? $args : array();
			$this->labels          = is_array( $labels ) ? $labels : array();
			$this->add_features    = array();
			$this->remove_features = array();

			if ( ! post_type_exists( $this->name ) ) {
				$this->register_post_type();
			}
		}
	}

	public function register_post_type()
	{
		$labels = array_merge(
			array(
				'name'               => sprintf( _x( '%s', 'post type general name', 'cuztom' ), $this->plural ),
				'singular_name'      => sprintf( _x( '%s', 'post type singular title', 'cuztom' ), $this->title ),
				'menu_name'          => sprintf( __( '%s', 'cuztom' ), $this->plural ),
				'all_items'          => sprintf( __( 'All %s', 'cuztom' ), $this->plural ),
				'add_new'            => sprintf( _x( 'Add New', '%s', 'cuztom' ), $this->title ),
				'add_new_item'       => sprintf( __( 'Add New %s', 'cuztom' ), $this->title ),
				'edit_item'          => sprintf( __( 'Edit %s', 'cuztom' ), $this->title ),
				'new_item'           => sprintf( __( 'New %s', 'cuztom' ), $this->title ),
				'view_item'          => sprintf( __( 'View %s', 'cuztom' ), $this->title ),
				'items_archive'      => sprintf( __( '%s Archive', 'cuztom' ), $this->title ),
				'search_items'       => sprintf( __( 'Search %s', 'cuztom' ), $this->plural ),
				'not_found'          => sprintf( __( 'No %s found', 'cuztom' ), $this->plural ),
				'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'cuztom' ), $this->plural ),
				'parent_item_colon'  => sprintf( __( '%s Parent', 'cuztom' ), $this->title ),
			),
			$this->labels
		);

		$args = array_merge(
			array(
				'label'       => sprintf( __( '%s', 'cuztom' ), $this->plural ),
				'labels'      => $labels,
				'public'      => true,
				'supports'    => array( 'title', 'editor' ),
				'has_archive' => sanitize_title( $this->plural ),
			),
			$this->args
		);

		register_post_type( $this->name, $args );
	}

	public function add_taxonomy( $name, $args = array(), $labels = array() )
	{
		if ( class_exists( 'Cuztom_Taxonomy' ) ) {
			new Cuztom_Taxonomy( $name, $this->name, $args, $labels );
		}
		return $this;
	}

	public function add_meta_box( $id, $title, $fields = array(), $context = 'normal', $priority = 'default' )
	{
		if ( class_exists( 'Cuztom_Meta_Box' ) ) {
			new Cuztom_Meta_Box( $id, $title, $this->name, $fields, $context, $priority );
		}
		return $this;
	}

	public function add_post_type_support( $feature )
	{
		add_post_type_support( $this->name, $feature );
		return $this;
	}

	public function remove_post_type_support( $features )
	{
		foreach ( (array) $features as $feature ) {
			remove_post_type_support( $this->name, $feature );
		}
		return $this;
	}

	public function post_type_supports( $feature )
	{
		return post_type_supports( $this->name, $feature );
	}
}
