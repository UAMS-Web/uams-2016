<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Tab
{
	public $id;
	public $title;
	public $meta_type;
	public $fields = array();

	public function __construct( $title )
	{
		$this->id    = Cuztom::uglify( $title );
		$this->title = Cuztom::beautify( $title );
	}

	public function output( $post, $type )
	{
		$fields  = $this->fields;
		$post_id = ( $post instanceof WP_Post ) ? $post->ID : ( isset( $post->ID ) ? $post->ID : 0 );

		if ( 'accordion' === $type ) {
			echo '<h3>' . esc_html( $this->title ) . '</h3>';
		}

		echo '<div id="cuztom-' . esc_attr( $this->id ) . '">';

		if ( $fields instanceof Cuztom_Bundle ) {
			$fields->output( $post );
		} elseif ( is_array( $fields ) ) {
			echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cuztom-table">';
			foreach ( $fields as $id => $field ) {
				$value = ( 'user' === $this->meta_type ) ? get_user_meta( $post_id, $id, true ) : get_post_meta( $post_id, $id, true );

				if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
					echo '<tr>';
					echo '<th class="cuztom-th">';
					echo '<label for="' . esc_attr( $id ) . '" class="cuztom-label">' . esc_html( $field->label ) . '</label>';
					echo '<div class="cuztom-description">' . wp_kses_post( $field->description ) . '</div>';
					echo '</th>';
					echo '<td class="cuztom-td">';

					if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
						echo '<div class="cuztom-padding-wrap">';
						echo '<a class="button-secondary cuztom-button js-cuztom-add-field js-cuztom-add-sortable" href="#">';
						echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
						echo '</a>';
						echo '<ul class="js-cuztom-sortable cuztom-sortable">';
					}

					echo $field->output( $value, $post );

					if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
						echo '</ul></div>';
					}

					echo '</td>';
					echo '</tr>';
				} else {
					echo $field->output( $value, $post );
				}
			}
			echo '</table>';
		}

		echo '</div>';
	}
}
