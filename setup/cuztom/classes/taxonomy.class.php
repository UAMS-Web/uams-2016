<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates custom taxonomies
 *
 * @author  Gijs Jorissen
 * @since   0.2
 */
#[\AllowDynamicProperties]
class Cuztom_Taxonomy
{
	public $name;
	public $title;
	public $plural;
	public $labels    = array();
	public $args      = array();
	public $post_type = array();

	public function __construct( $name, $post_type = null, $args = array(), $labels = array() )
	{
		if ( ! empty( $name ) ) {
			$this->post_type = (array) $post_type;

			if ( is_array( $name ) ) {
				$this->name   = Cuztom::uglify( $name[0] );
				$this->title  = Cuztom::beautify( $name[0] );
				$this->plural = isset( $name[1] ) ? Cuztom::beautify( $name[1] ) : Cuztom::pluralize( $this->title );
			} else {
				$this->name   = Cuztom::uglify( $name );
				$this->title  = Cuztom::beautify( $name );
				$this->plural = Cuztom::pluralize( $this->title );
			}

			$this->labels = is_array( $labels ) ? $labels : array();
			$this->args   = is_array( $args ) ? $args : array();

			if ( ! taxonomy_exists( $this->name ) ) {
				$is_reserved = Cuztom::is_reserved_term( $this->name );
				if ( is_wp_error( $is_reserved ) ) {
					if ( class_exists( 'Cuztom_Notice' ) ) {
						new Cuztom_Notice( $is_reserved->get_error_message(), 'error' );
					}
				} else {
					$this->register_taxonomy();
				}
			} else {
				$this->register_taxonomy_for_object_type();
			}

			if ( ! empty( $args['show_admin_column'] ) ) {
				foreach ( $this->post_type as $pt ) {
					add_filter( 'manage_' . $pt . '_posts_columns', array( $this, 'add_column' ) );
					add_action( 'manage_' . $pt . '_posts_custom_column', array( $this, 'add_column_content' ), 10, 2 );

					if ( ! empty( $args['admin_column_sortable'] ) ) {
						add_action( 'manage_edit-' . $pt . '_sortable_columns', array( $this, 'add_sortable_column' ), 10, 2 );
					}
				}

				if ( ! empty( $args['admin_column_filter'] ) ) {
					add_action( 'restrict_manage_posts', array( $this, '_post_filter' ) );
					add_filter( 'parse_query', array( $this, '_post_filter_query' ) );
				}
			}
		}
	}

	public function register_taxonomy()
	{
		$labels = array_merge(
			array(
				'name'              => sprintf( _x( '%s', 'taxonomy general name', 'cuztom' ), $this->plural ),
				'singular_name'     => sprintf( _x( '%s', 'taxonomy singular name', 'cuztom' ), $this->title ),
				'search_items'      => sprintf( __( 'Search %s', 'cuztom' ), $this->plural ),
				'all_items'         => sprintf( __( 'All %s', 'cuztom' ), $this->plural ),
				'parent_item'       => sprintf( __( 'Parent %s', 'cuztom' ), $this->title ),
				'parent_item_colon' => sprintf( __( 'Parent %s:', 'cuztom' ), $this->title ),
				'edit_item'         => sprintf( __( 'Edit %s', 'cuztom' ), $this->title ),
				'update_item'       => sprintf( __( 'Update %s', 'cuztom' ), $this->title ),
				'add_new_item'      => sprintf( __( 'Add New %s', 'cuztom' ), $this->title ),
				'new_item_name'     => sprintf( __( 'New %s Name', 'cuztom' ), $this->title ),
				'menu_name'         => sprintf( __( '%s', 'cuztom' ), $this->plural ),
			),
			$this->labels
		);

		$args = array_merge(
			array(
				'label'             => sprintf( __( '%s', 'cuztom' ), $this->plural ),
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'_builtin'          => false,
				'show_admin_column' => false,
			),
			$this->args
		);

		register_taxonomy( $this->name, $this->post_type, $args );
	}

	public function register_taxonomy_for_object_type()
	{
		register_taxonomy_for_object_type( $this->name, $this->post_type );
	}

	public function add_term_meta( $data = array(), $locations = array( 'add_form', 'edit_form' ) )
	{
		if ( class_exists( 'Cuztom_Term_Meta' ) ) {
			new Cuztom_Term_Meta( $this->name, $data, $locations );
		}
		return $this;
	}

	public function add_column( $columns )
	{
		$columns = is_array( $columns ) ? $columns : array();
		unset( $columns['date'] );

		$columns[ $this->name ] = $this->title;
		$columns['date']        = __( 'Date', 'cuztom' );

		return $columns;
	}

	public function add_column_content( $column, $post_id )
	{
		if ( $column === $this->name ) {
			$terms = wp_get_post_terms( (int) $post_id, $this->name, array( 'fields' => 'names' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				echo esc_html( implode( ', ', (array) $terms ) );
			}
		}
	}

	public function add_sortable_column( $columns )
	{
		$columns = is_array( $columns ) ? $columns : array();
		$columns[ 'taxonomy-' . $this->name ] = $this->title;
		return $columns;
	}

	public function _post_filter()
	{
		global $typenow, $wp_query;

		if ( in_array( $typenow, $this->post_type, true ) ) {
			wp_dropdown_categories( array(
				'show_option_all' => sprintf( __( 'Show all %s', 'cuztom' ), $this->plural ),
				'taxonomy'        => $this->name,
				'name'            => $this->name,
				'orderby'         => 'name',
				'selected'        => isset( $wp_query->query[ $this->name ] ) ? $wp_query->query[ $this->name ] : '',
				'hierarchical'    => true,
				'show_count'      => true,
				'hide_empty'      => true,
			) );
		}
	}

	public function _post_filter_query( $query )
	{
		global $pagenow;
		$vars = &$query->query_vars;

		if ( 'edit.php' === $pagenow && isset( $vars[ $this->name ] ) && is_numeric( $vars[ $this->name ] ) && $vars[ $this->name ] ) {
			$term = get_term_by( 'id', (int) $vars[ $this->name ], $this->name );
			if ( $term && ! is_wp_error( $term ) ) {
				$vars[ $this->name ] = $term->slug;
			}
		}

		return $vars;
	}
}
