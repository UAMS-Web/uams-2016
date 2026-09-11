<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuztom Field Class
 *
 * @author  Gijs Jorissen
 * @since   0.3.3
 */
#[\AllowDynamicProperties]
class Cuztom_Field
{
	public $id                   = '';
	public $type                 = '';
	public $name                 = '';
	public $label                = '';
	public $description          = '';
	public $explanation          = '';
	public $default_value        = '';
	public $options              = array();
	public $args                 = array();
	public $underscore           = true;
	public $required             = false;
	public $repeatable           = false;
	public $ajax                 = false;

	public $parent               = '';
	public $meta_type            = '';
	public $in_bundle            = false;

	public $show_admin_column    = false;
	public $admin_column_sortable = false;
	public $admin_column_filter  = false;

	public $data_attributes      = array();
	public $css_classes          = array();

	public $pre                  = '';
	public $after                = '';
	public $pre_id               = '';
	public $after_id             = '';

	public $_supports_repeatable = false;
	public $_supports_bundle     = false;
	public $_supports_ajax       = false;

	public function __construct( $field, $parent )
	{
		$this->type                  = isset( $field['type'] ) ? $field['type'] : $this->type;
		$this->name                  = isset( $field['name'] ) ? $field['name'] : $this->name;
		$this->label                 = isset( $field['label'] ) ? $field['label'] : $this->label;
		$this->description           = isset( $field['description'] ) ? $field['description'] : $this->description;
		$this->explanation           = isset( $field['explanation'] ) ? $field['explanation'] : $this->explanation;
		$this->default_value         = isset( $field['default_value'] ) ? $field['default_value'] : $this->default_value;
		$this->options               = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : $this->options;
		$this->args                  = isset( $field['args'] ) && is_array( $field['args'] ) ? $field['args'] : $this->args;
		$this->underscore            = isset( $field['underscore'] ) ? (bool) $field['underscore'] : $this->underscore;
		$this->required              = isset( $field['required'] ) ? (bool) $field['required'] : $this->required;
		$this->repeatable            = isset( $field['repeatable'] ) ? (bool) $field['repeatable'] : $this->repeatable;
		$this->ajax                  = isset( $field['ajax'] ) ? (bool) $field['ajax'] : $this->ajax;
		$this->css_classes           = isset( $field['css_classes'] ) && is_array( $field['css_classes'] ) ? array_merge( $this->css_classes, $field['css_classes'] ) : $this->css_classes;

		$this->show_admin_column     = isset( $field['show_admin_column'] ) ? (bool) $field['show_admin_column'] : $this->show_admin_column;
		$this->admin_column_sortable = isset( $field['admin_column_sortable'] ) ? (bool) $field['admin_column_sortable'] : $this->admin_column_sortable;
		$this->admin_column_filter   = isset( $field['admin_column_filter'] ) ? (bool) $field['admin_column_filter'] : $this->admin_column_filter;

		$this->parent = $parent;
		$this->id     = isset( $field['id'] ) ? $field['id'] : $this->build_id( $this->name, $parent );
	}

	public function output( $value, $object = null )
	{
		if ( $this->repeatable && $this->_supports_repeatable ) {
			return $this->_repeatable_output( $value );
		} elseif ( $this->ajax && $this->_supports_ajax ) {
			return $this->_ajax_output( $value );
		} else {
			return $this->_output( $value );
		}
	}

	public function _output( $value )
	{
		$val = ( ! is_null( $value ) && '' !== $value ) ? $value : $this->default_value;
		return '<input type="text" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' value="' . esc_attr( (string) $val ) . '" ' . $this->output_data_attributes() . ' />' . $this->output_explanation();
	}

	public function _repeatable_output( $value )
	{
		$this->after = '[]';
		$output      = '';

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$output .= '<li class="cuztom-field cuztom-sortable-item js-cuztom-sortable-item"><div class="cuztom-handle-sortable js-cuztom-handle-sortable"></div>' . $this->_output( $item ) . ( count( $value ) > 1 ? '<div class="js-cuztom-remove-sortable cuztom-remove-sortable"></div>' : '' ) . '</li>';
			}
		} else {
			$output .= '<li class="cuztom-field cuztom-sortable-item js-cuztom-sortable-item"><div class="cuztom-handle-sortable js-cuztom-handle-sortable"></div>' . $this->_output( $value ) . ( $this->repeatable ? '</li>' : '' );
		}

		return $output;
	}

	public function _ajax_output( $value )
	{
		$output  = $this->_output( $value );
		$output .= '<a class="cuztom-ajax-save js-cuztom-ajax-save button-secondary" href="#">' . esc_html__( 'Save', 'cuztom' ) . '</a>';
		return $output;
	}

	public function save( $object_id, $value )
	{
		$value = $this->save_value( $value );

		if ( 'user' === $this->meta_type ) {
			update_user_meta( $object_id, $this->id, $value );
		} elseif ( 'post' === $this->meta_type ) {
			update_post_meta( $object_id, $this->id, $value );
		} elseif ( 'term' === $this->meta_type ) {
			return $value;
		}

		return false;
	}

	public function save_value( $value )
	{
		return $value;
	}

	public function ajax_save()
	{
		if ( isset( $_POST['cuztom'] ) && is_array( $_POST['cuztom'] ) ) {
			$object_id = isset( $_POST['cuztom']['object_id'] ) ? (int) $_POST['cuztom']['object_id'] : 0;
			$field_id  = isset( $_POST['cuztom']['field_id'] ) ? sanitize_key( $_POST['cuztom']['field_id'] ) : '';
			$value     = isset( $_POST['cuztom']['value'] ) ? $_POST['cuztom']['value'] : '';
			$meta_type = isset( $_POST['cuztom']['meta_type'] ) ? sanitize_key( $_POST['cuztom']['meta_type'] ) : '';

			if ( empty( $object_id ) || empty( $field_id ) ) {
				wp_die();
			}

			if ( 'user' === $meta_type ) {
				update_user_meta( $object_id, $field_id, $value );
			} elseif ( 'post' === $meta_type ) {
				update_post_meta( $object_id, $field_id, $value );
			}
		}

		wp_die();
	}

	public function output_name( $overwrite = null )
	{
		return $overwrite ? 'name="' . esc_attr( $overwrite ) . '"' : 'name="cuztom' . esc_attr( $this->pre ) . '[' . esc_attr( $this->id ) . ']' . esc_attr( $this->after ) . '"';
	}

	public function output_id( $overwrite = null )
	{
		return $overwrite ? 'id="' . esc_attr( $overwrite ) . '"' : 'id="' . esc_attr( $this->pre_id . $this->id . $this->after_id ) . '"';
	}

	public function output_css_class( $extra = array() )
	{
		$classes = array_merge( (array) $this->css_classes, (array) $extra );
		return 'class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '"';
	}

	public function output_data_attributes( $extra = array() )
	{
		$output = '';

		foreach ( array_merge( (array) $this->data_attributes, (array) $extra ) as $attribute => $value ) {
			if ( ! is_null( $value ) ) {
				$output .= 'data-' . esc_attr( $attribute ) . '="' . esc_attr( $value ) . '" ';
			} elseif ( ! $value && isset( $this->args[ Cuztom::uglify( $attribute ) ] ) ) {
				$output .= 'data-' . esc_attr( $attribute ) . '="' . esc_attr( $this->args[ Cuztom::uglify( $attribute ) ] ) . '" ';
			}
		}

		return trim( $output );
	}

	public function output_for_attribute( $for = null )
	{
		return $for ? 'for="' . esc_attr( $for ) . '"' : '';
	}

	public function output_explanation()
	{
		return ( ! $this->repeatable && ! empty( $this->explanation ) ) ? '<em class="cuztom-explanation">' . esc_html( $this->explanation ) . '</em>' : '';
	}

	public function build_id( $name, $parent )
	{
		return apply_filters( 'cuztom_build_id', ( $this->underscore ? '_' : '' ) . ( ! empty( $parent ) ? Cuztom::uglify( $parent ) . '_' : '' ) . Cuztom::uglify( (string) $name ) );
	}
}
