<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a Taxonomy for a Post Type
 *
 * @param  string          $name
 * @param  string|array    $post_type
 * @param  array           $args
 * @param  array           $labels
 * @return Cuztom_Taxonomy
 *
 * @author Gijs Jorissen
 * @since  0.8
 */
function register_cuztom_taxonomy( $name, $post_type = null, $args = array(), $labels = array() )
{
	$taxonomy = new Cuztom_Taxonomy( $name, $post_type, $args, $labels );

	return $taxonomy;
}

/**
 * Get term meta
 * 
 * @param   int|string $term Can be the id or the slug of the term
 * @param   string     $taxonomy
 * @param   string     $key
 * @return  mixed
 *
 * @author  Gijs Jorissen
 * @since   2.5
 */
function get_cuztom_term_meta( $term, $taxonomy, $key = null )
{
	if ( empty( $taxonomy ) || empty( $term ) ) {
		return false;
	}

	if ( ! is_numeric( $term ) ) {
		$term_obj = get_term_by( 'slug', $term, $taxonomy );
		if ( ! ( $term_obj instanceof WP_Term ) ) {
			return false;
		}
		$term = $term_obj->term_id;
	}

	$meta = get_option( 'term_meta_' . $taxonomy . '_' . (int) $term );

	if ( ! is_array( $meta ) ) {
		return $key ? '' : false;
	}

	if ( ! empty( $key ) ) {
		return isset( $meta[ $key ] ) ? $meta[ $key ] : '';
	}

	return $meta;
}

/**
 * Output term meta
 * 
 * @param   int|string $term Can be the id or the slug of the term
 * @param   string     $taxonomy
 * @param   string     $key
 *
 * @author  Gijs Jorissen
 * @since   2.5
 */
function the_cuztom_term_meta( $term, $taxonomy, $key = null )
{
	if ( empty( $term ) || empty( $taxonomy ) ) {
		return false;
	}

	$meta = get_cuztom_term_meta( $term, $taxonomy, $key );

	if ( is_scalar( $meta ) ) {
		echo esc_html( (string) $meta );
	}
}
