<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Multi_Select extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input cuztom-select cuztom-multi-select' );

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->default_value = (array) $this->default_value;
		$this->after        .= '[]';
	}

	public function _output( $value, $object = null )
	{
		$output = '<select ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' multiple="multiple">';

		if ( isset( $this->args['show_option_none'] ) ) {
			$selected_none = false;
			if ( is_array( $value ) ) {
				$selected_none = in_array( '0', array_map( 'strval', $value ), true );
			} elseif ( '-1' !== (string) $value ) {
				$selected_none = in_array( '0', array_map( 'strval', $this->default_value ), true );
			}
			$output .= '<option value="0" ' . ( $selected_none ? 'selected="selected"' : '' ) . '>' . esc_html( $this->args['show_option_none'] ) . '</option>';
		}

		if ( is_array( $this->options ) ) {
			foreach ( $this->options as $slug => $name ) {
				$selected = false;
				if ( is_array( $value ) ) {
					$selected = in_array( (string) $slug, array_map( 'strval', $value ), true );
				} elseif ( '-1' !== (string) $value ) {
					$selected = in_array( (string) $slug, array_map( 'strval', $this->default_value ), true );
				}

				$output .= '<option value="' . esc_attr( $slug ) . '" ' . ( $selected ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
			}
		}

		$output .= '</select>';
		$output .= $this->output_explanation();

		return $output;
	}

	public function save_value( $value )
	{
		return empty( $value ) ? '-1' : $value;
	}
}
