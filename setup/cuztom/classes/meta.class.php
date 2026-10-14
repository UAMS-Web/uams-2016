<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuztom Meta for handling meta data
 *
 * @author  Gijs Jorissen
 * @since   1.5
 */
#[\AllowDynamicProperties]
class Cuztom_Meta
{
	public $id;
	public $title;
	public $callback;
	public $data;
	public $fields = array();
	public $description;

	public function __construct( $title )
	{
		if ( is_array( $title ) ) {
			$this->title       = Cuztom::beautify( $title[0] );
			$this->description = isset( $title[1] ) ? $title[1] : '';
		} else {
			$this->title = Cuztom::beautify( $title );
		}
	}

	public function callback( $object, $data = array() )
	{
		wp_nonce_field( 'cuztom_meta', 'cuztom_nonce' );

		$data      = $this->data;
		$meta_type = $this->get_meta_type();
		$obj_id    = ( 'post' === $meta_type ) ? get_the_ID() : ( isset( $object->ID ) ? $object->ID : 0 );

		if ( ! empty( $data ) ) {
			echo '<input type="hidden" name="cuztom[__activate]" />';
			echo '<div class="cuztom" data-object-id="' . esc_attr( $obj_id ) . '" data-meta-type="' . esc_attr( $meta_type ) . '">';

			if ( ! empty( $this->description ) ) {
				echo '<p class="cuztom-box-description">' . esc_html( $this->description ) . '</p>';
			}

			if ( ( $data instanceof Cuztom_Tabs ) || ( $data instanceof Cuztom_Accordion ) || ( $data instanceof Cuztom_Bundle ) ) {
				$data->output( $object );
			} else {
				echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cuztom-table">';

				foreach ( $data as $id_name => $field ) {
					$value = $this->is_meta_type( 'user' ) ? get_user_meta( $obj_id, $id_name, true ) : get_post_meta( $obj_id, $id_name, true );

					if ( ! ( $field instanceof Cuztom_Field_Hidden ) ) {
						echo '<tr>';
						echo '<th class="cuztom-th">';
						echo '<label for="' . esc_attr( $id_name ) . '" class="cuztom_label">' . esc_html( $field->label ) . '</label>';
						echo ! empty( $field->required ) ? ' <span class="cuztom-required">*</span>' : '';
						echo '<div class="cuztom-description description">' . wp_kses_post( $field->description ) . '</div>';
						echo '</th>';
						echo '<td class="cuztom-td">';

						if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
							echo '<a class="button-secondary cuztom-button js-cuztom-add-field js-cuztom-add-sortable" href="#">';
							echo sprintf( '+ %s', esc_html__( 'Add', 'cuztom' ) );
							echo '</a>';
							echo '<ul class="js-cuztom-sortable cuztom-sortable cuztom_repeatable_wrap">';
							echo $field->output( $value, $object );
							echo '</ul>';
						} else {
							echo $field->output( $value, $object );
						}

						echo '</td>';
						echo '</tr>';
					} else {
						echo $field->output( $value, $object );
					}
				}

				echo '</table>';
			}

			echo '</div>';
		}
	}

	public function save( $object_id, $values )
	{
		if ( empty( $this->data ) || ! isset( $_POST['cuztom'] ) || ! is_array( $values ) ) {
			return;
		}

		if ( $this->data instanceof Cuztom_Bundle ) {
			$bundle = $this->data;
			if ( isset( $values[ $bundle->id ] ) ) {
				$bundle->save( $object_id, $values[ $bundle->id ] );
			}
		} elseif ( $this->data instanceof Cuztom_Tabs || $this->data instanceof Cuztom_Accordion ) {
			if ( ! empty( $this->data->tabs ) && is_array( $this->data->tabs ) ) {
				foreach ( $this->data->tabs as $tab ) {
					if ( isset( $tab->fields ) && $tab->fields instanceof Cuztom_Bundle ) {
						$bundle = $tab->fields;
						if ( isset( $values[ $bundle->id ] ) ) {
							$bundle->save( $object_id, $values[ $bundle->id ] );
						}
					} elseif ( ! empty( $tab->fields ) && is_array( $tab->fields ) ) {
						foreach ( $tab->fields as $field ) {
							if ( is_object( $field ) && method_exists( $field, 'save' ) && isset( $values[ $field->id ] ) ) {
								$field->save( $object_id, $values[ $field->id ] );
							}
						}
					}
				}
			}
		} elseif ( is_array( $this->data ) ) {
			foreach ( $this->data as $id_name => $field ) {
				if ( is_object( $field ) && method_exists( $field, 'save' ) && isset( $values[ $id_name ] ) ) {
					$field->save( $object_id, $values[ $id_name ] );
				}
			}
		}
	}

	public function get_meta_type()
	{
		switch ( get_class( $this ) ) {
			case 'Cuztom_Meta_Box':
				return 'post';
			case 'Cuztom_User_Meta':
				return 'user';
			case 'Cuztom_Term_Meta':
				return 'term';
			default:
				return false;
		}
	}

	public function is_meta_type( $meta_type )
	{
		return $this->get_meta_type() === $meta_type;
	}

	public static function is_tabs( $data )
	{
		return isset( $data[0] ) && ( ! is_array( $data[0] ) ) && ( 'tabs' === $data[0] );
	}

	public static function is_accordion( $data )
	{
		return isset( $data[0] ) && ( ! is_array( $data[0] ) ) && ( 'accordion' === $data[0] );
	}

	public static function is_bundle( $data )
	{
		return isset( $data[0] ) && ( ! is_array( $data[0] ) ) && ( 'bundle' === $data[0] );
	}

	public function build( $data, $parent = null )
	{
		$return = array();

		if ( is_array( $data ) && ! empty( $data ) ) {
			if ( self::is_tabs( $data ) || self::is_accordion( $data ) ) {
				$tabs            = self::is_tabs( $data ) ? new Cuztom_Tabs( $this->id ) : new Cuztom_Accordion( $this->id );
				$tabs->meta_type = $this->get_meta_type();

				if ( isset( $data[1] ) && is_array( $data[1] ) ) {
					foreach ( $data[1] as $title => $fields ) {
						$tab            = new Cuztom_Tab( $title );
						$tab->meta_type = $this->get_meta_type();

						if ( isset( $fields[0] ) && self::is_bundle( $fields[0] ) ) {
							$tab->fields = $this->build( $fields[0] );
						} elseif ( is_array( $fields ) ) {
							foreach ( $fields as $field ) {
								if ( ! isset( $field['type'] ) ) {
									continue;
								}
								$class = 'Cuztom_Field_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $field['type'] ) ) );
								if ( class_exists( $class ) ) {
									$field_obj                    = new $class( $field, $this->id );
									$field_obj->meta_type         = $this->get_meta_type();
									$this->fields[ $field_obj->id ] = $field_obj;
									$tab->fields[ $field_obj->id ]  = $field_obj;
								}
							}
						}

						$tabs->tabs[ $title ] = $tab;
					}
				}

				$return = $tabs;
			} elseif ( self::is_bundle( $data ) ) {
				$bundle = new Cuztom_Bundle( $this->id, $data );

				if ( isset( $data[1] ) && is_array( $data[1] ) ) {
					foreach ( $data[1] as $field ) {
						if ( ! isset( $field['type'] ) ) {
							continue;
						}
						$class = 'Cuztom_Field_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $field['type'] ) ) );
						if ( class_exists( $class ) ) {
							$field_obj                      = new $class( $field, '' );
							$field_obj->ajax                = false;
							$field_obj->meta_type           = $this->get_meta_type();
							$field_obj->in_bundle           = true;
							$this->fields[ $field_obj->id ]   = $field_obj;
							$bundle->fields[ $field_obj->id ] = $field_obj;
							$bundle->meta_type              = $this->get_meta_type();
						}
					}
				}

				$return = $bundle;
			} else {
				foreach ( $data as $field ) {
					if ( ! isset( $field['type'] ) ) {
						continue;
					}
					$class = 'Cuztom_Field_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $field['type'] ) ) );
					if ( class_exists( $class ) ) {
						$field_obj                    = new $class( $field, $this->id );
						$field_obj->meta_type         = $this->get_meta_type();
						$this->fields[ $field_obj->id ] = $field_obj;
						$return[ $field_obj->id ]       = $field_obj;
					}
				}
			}
		}

		return $return;
	}

	public static function edit_form_tag()
	{
		echo ' enctype="multipart/form-data"';
	}
}
