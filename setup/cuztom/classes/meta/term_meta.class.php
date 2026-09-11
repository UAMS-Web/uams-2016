<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the term meta
 *
 * @author  Gijs Jorissen
 * @since   2.5
 */
#[\AllowDynamicProperties]
class Cuztom_Term_Meta extends Cuztom_Meta
{
	public $taxonomies = array();
	public $data;
	public $fields     = array();
	public $locations  = array();

	public function __construct( $taxonomy, $data = array(), $locations = array( 'add_form', 'edit_form' ) )
	{
		$this->taxonomies = (array) $taxonomy;
		$this->locations  = (array) $locations;

		$this->data = $this->build( $data );

		foreach ( $this->taxonomies as $tax ) {
			if ( in_array( 'add_form', $this->locations, true ) ) {
				add_action( $tax . '_add_form_fields', array( $this, 'add_form_fields' ) );
				add_action( 'created_' . $tax, array( $this, 'save_term' ) );
			}

			if ( in_array( 'edit_form', $this->locations, true ) ) {
				add_action( $tax . '_edit_form_fields', array( $this, 'edit_form_fields' ) );
				add_action( 'edited_' . $tax, array( $this, 'save_term' ) );
			}

			add_filter( 'manage_edit-' . $tax . '_columns', array( $this, 'add_column' ) );
			add_filter( 'manage_' . $tax . '_custom_column', array( $this, 'add_column_content' ), 10, 3 );
		}
	}

	public function add_form_fields( $taxonomy )
	{
		echo '<input type="hidden" name="cuztom[__activate]" />';

		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			foreach ( $this->data as $id_name => $field ) {
				$value = '';

				if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
					echo '<div class="form-field">';
					echo '<label for="' . esc_attr( $id_name ) . '" class="cuztom_label">' . esc_html( $field->label ) . '</label>';
					echo $field->output( $value );

					if ( ! empty( $field->description ) ) {
						echo '<p class="cuztom-description">' . wp_kses_post( $field->description ) . '</p>';
					}
					echo '</div>';
				} else {
					echo $field->output( $value );
				}
			}
		}
	}

	public function edit_form_fields( $term )
	{
		$value = function_exists( 'get_cuztom_term_meta' ) ? get_cuztom_term_meta( $term->term_id, $term->taxonomy ) : array();
		$value = is_array( $value ) ? $value : array();

		echo '<input type="hidden" name="cuztom[__activate]" />';

		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			foreach ( $this->data as $id_name => $field ) {
				$field_value = isset( $value[ $id_name ] ) ? $value[ $id_name ] : '';

				if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
					echo '<tr class="cuztom form-field">';
					echo '<th scope="row" valign="top">';
					echo '<label for="' . esc_attr( $id_name ) . '" class="cuztom_label">' . esc_html( $field->label ) . '</label>';
					echo '</th>';
					echo '<td class="cuztom-td">';
					echo $field->output( $field_value );
					if ( ! empty( $field->description ) ) {
						echo '<p class="description cuztom-description">' . wp_kses_post( $field->description ) . '</p>';
					}
					echo '</td>';
					echo '</tr>';
				} else {
					echo $field->output( $field_value );
				}
			}
		}
	}

	public function save_term( $term_id )
	{
		if ( ! empty( $this->data ) && isset( $_POST['cuztom'] ) && is_array( $_POST['cuztom'] ) ) {
			$data     = array();
			$values   = wp_unslash( $_POST['cuztom'] );
			$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : '';

			if ( empty( $taxonomy ) ) {
				$term = get_term( (int) $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$taxonomy = $term->taxonomy;
				}
			}

			if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
				foreach ( $this->fields as $id_name => $field ) {
					$val            = isset( $values[ $field->id ] ) ? $values[ $field->id ] : '';
					$data[ $id_name ] = $field->save_value( $val );
				}
			}

			if ( ! empty( $taxonomy ) ) {
				update_option( 'term_meta_' . $taxonomy . '_' . (int) $term_id, $data );
			}
		}
	}

	public function add_column( $columns )
	{
		$columns = is_array( $columns ) ? $columns : array();

		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
			foreach ( $this->fields as $id_name => $field ) {
				if ( ! empty( $field->show_admin_column ) ) {
					$columns[ $id_name ] = $field->label;
				}
			}
		}

		return $columns;
	}

	public function add_column_content( $row, $column, $term_id )
	{
		$screen = get_current_screen();

		if ( $screen && ! empty( $screen->taxonomy ) ) {
			$taxonomy = $screen->taxonomy;
			$meta     = function_exists( 'get_cuztom_term_meta' ) ? get_cuztom_term_meta( $term_id, $taxonomy, $column ) : '';

			if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
				foreach ( $this->fields as $id_name => $field ) {
					if ( $column === $id_name ) {
						if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
							echo esc_html( implode( ', ', (array) $meta ) );
						} else {
							if ( $field instanceof Cuztom_Field_Image ) {
								echo wp_get_attachment_image( (int) $meta, array( 100, 100 ) );
							} else {
								echo esc_html( (string) $meta );
							}
						}
						break;
					}
				}
			}
		}
	}
}
