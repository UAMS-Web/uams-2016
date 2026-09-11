<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Bundle
{
	public $id;
	public $meta_type;
	public $fields        = array();
	public $default_value = '';

	public function __construct( $id, $data = array() )
	{
		$this->default_value = isset( $data['default_value'] ) ? $data['default_value'] : $this->default_value;
		$this->id            = isset( $id ) ? $this->build_id( $id ) : $this->id;
	}

	public function output( $post )
	{
		$post_id = ( $post instanceof WP_Post ) ? $post->ID : ( isset( $post->ID ) ? $post->ID : 0 );

		echo '<div class="padding-wrap">';
		echo '<a class="button-secondary cuztom-button js-cuztom-add-sortable js-cuztom-add-bundle cuztom-add-sortable" href="#">';
		echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
		echo '</a>';

		echo '<ul class="js-cuztom-sortable cuztom-sortable js-cuztom-bundle" data-cuztom-sortable-type="bundle">';

		$meta = ( 'user' === $this->meta_type ) ? get_user_meta( $post_id, $this->id, true ) : get_post_meta( $post_id, $this->id, true );

		if ( ! empty( $meta ) && is_array( $meta ) && isset( $meta[0] ) ) {
			$i = 0;
			foreach ( $meta as $bundle ) {
				echo '<li class="cuztom-sortable-item js-cuztom-sortable-item">';
				echo '<div class="cuztom-handle-sortable js-cuztom-handle-sortable"></div>';
				echo '<fieldset>';
				echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cuztom-table">';

				foreach ( $this->fields as $id => $field ) {
					$field->pre      = '[' . $this->id . '][' . $i . ']';
					$field->after_id = '_' . $i;
					$value           = isset( $meta[ $i ][ $id ] ) ? $meta[ $i ][ $id ] : '';

					if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
						echo '<tr>';
						echo '<th class="cuztom-th">';
						echo '<label for="' . esc_attr( $id . $field->after_id ) . '" class="cuztom-label">' . esc_html( $field->label ) . '</label>';
						echo '<div class="cuztom-description">' . wp_kses_post( $field->description ) . '</div>';
						echo '</th>';
						echo '<td class="cuztom-td">';

						if ( ! empty( $field->_supports_bundle ) ) {
							if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
								echo '<a class="button-secondary cuztom-button js-cuztom-add-field js-cuztom-add-sortable" href="#">';
								echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
								echo '</a>';
								echo '<ul class="js-cuztom-sortable cuztom-sortable cuztom_repeatable_wrap">';
								echo $field->output( $value, $post );
								echo '</ul>';
							} else {
								echo $field->output( $value, $post );
							}
						} else {
							echo '<em>' . esc_html__( "This input type doesn't support the bundle functionality (yet).", 'cuztom' ) . '</em>';
						}

						echo '</td>';
						echo '</tr>';
					} else {
						echo $field->output( $value, $post );
					}
				}

				echo '</table>';
				echo '</fieldset>';
				echo count( $meta ) > 1 ? '<div class="cuztom-remove-sortable js-cuztom-remove-sortable"></div>' : '';
				echo '</li>';
				$i++;
			}
		} elseif ( ! empty( $this->default_value ) && is_array( $this->default_value ) ) {
			$i = 0;
			foreach ( $this->default_value as $default ) {
				echo '<li class="cuztom-sortable-item js-cuztom-sortable-item">';
				echo '<div class="cuztom-handle-sortable cuztom-handle-bundle js-cuztom-handle-sortable"></div>';
				echo '<fieldset>';
				echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cuztom-table">';

				$y = 0;
				foreach ( $this->fields as $id => $field ) {
					$field->pre           = '[' . $this->id . '][' . $i . ']';
					$field->after_id      = '_' . $i;
					$field->default_value = isset( $this->default_value[ $i ][ $y ] ) ? $this->default_value[ $i ][ $y ] : '';
					$value                = '';

					if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
						echo '<tr>';
						echo '<th class="cuztom-th">';
						echo '<label for="' . esc_attr( $id . $field->after_id ) . '" class="cuztom-label">' . esc_html( $field->label ) . '</label>';
						echo '<div class="cuztom-description">' . wp_kses_post( $field->description ) . '</div>';
						echo '</th>';
						echo '<td class="cuztom-td">';

						if ( ! empty( $field->_supports_bundle ) ) {
							if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
								echo '<a class="button-secondary cuztom-button js-cuztom-add-field js-cuztom-add-sortable" href="#">';
								echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
								echo '</a>';
								echo '<ul class="js-cuztom-sortable cuztom-sortable cuztom_repeatable_wrap">';
								echo $field->output( $value, $post );
								echo '</ul>';
							} else {
								echo $field->output( $value, $post );
							}
						} else {
							echo '<em>' . esc_html__( "This input type doesn't support the bundle functionality (yet).", 'cuztom' ) . '</em>';
						}

						echo '</td>';
						echo '</tr>';
					} else {
						echo $field->output( $value, $post );
					}
					$y++;
				}

				echo '</table>';
				echo '</fieldset>';
				echo '</li>';
				$i++;
			}
		} else {
			echo '<li class="cuztom-sortable-item js-cuztom-sortable-item">';
			echo '<div class="cuztom-handle-sortable cuztom-handle-bundle js-cuztom-handle-sortable"></div>';
			echo '<fieldset>';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cuztom-table">';

			foreach ( $this->fields as $id => $field ) {
				$field->pre      = '[' . $this->id . '][0]';
				$field->after_id = '_0';
				$value           = '';

				if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
					echo '<tr>';
					echo '<th class="cuztom-th">';
					echo '<label for="' . esc_attr( $id . $field->after_id ) . '" class="cuztom-label">' . esc_html( $field->label ) . '</label>';
					echo '<div class="cuztom-description">' . wp_kses_post( $field->description ) . '</div>';
					echo '</th>';
					echo '<td class="cuztom-td">';

					if ( ! empty( $field->_supports_bundle ) ) {
						if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
							echo '<a class="button-secondary cuztom-button js-cuztom-add-field js-cuztom-add-sortable" href="#">';
							echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
							echo '</a>';
							echo '<ul class="js-cuztom-sortable cuztom-sortable cuztom_repeatable_wrap">';
							echo $field->output( $value, $post );
							echo '</ul>';
						} else {
							echo $field->output( $value, $post );
						}
					} else {
						echo '<em>' . esc_html__( "This input type doesn't support the bundle functionality (yet).", 'cuztom' ) . '</em>';
					}

					echo '</td>';
					echo '</tr>';
				} else {
					echo $field->output( $value, $post );
				}
			}

			echo '</table>';
			echo '</fieldset>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}

	public function save( $object_id, $values )
	{
		$values = apply_filters( "cuztom_" . $this->meta_type . "_meta_save_bundle_{$this->id}", $values, $this, $object_id );
		$values = apply_filters( 'cuztom_' . $this->meta_type . '_meta_save_bundle', $values, $this, $object_id );

		if ( ! is_array( $values ) ) {
			return;
		}

		$values = array_values( $values );

		foreach ( $values as $row_id => $row ) {
			if ( is_array( $row ) ) {
				foreach ( $row as $id => $value ) {
					if ( isset( $this->fields[ $id ] ) && is_object( $this->fields[ $id ] ) ) {
						$values[ $row_id ][ $id ] = $this->fields[ $id ]->save_value( $value );
					}
				}
			}
		}

		if ( 'user' === $this->meta_type ) {
			delete_user_meta( $object_id, $this->id );
			update_user_meta( $object_id, $this->id, $values );
		} else {
			delete_post_meta( $object_id, $this->id );
			update_post_meta( $object_id, $this->id, $values );
		}
	}

	public function build_id( $id )
	{
		$id = (string) $id;
		if ( 0 !== strpos( $id, '_' ) ) {
			$id = '_' . $id;
		}
		return $id;
	}
}
