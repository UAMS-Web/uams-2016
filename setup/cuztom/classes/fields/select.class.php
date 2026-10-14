<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Select extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_ajax       = true;
	public $_supports_bundle     = true;

	public $css_classes     = array( 'cuztom-input cuztom-select' );
	public $data_attributes = array( 'default-value' => null );

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->data_attributes['default-value'] = $this->default_value;
	}

	public function _output( $value, $object = null )
	{
		$output = '<select ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' ' . $this->output_data_attributes() . '>';

		if ( isset( $this->args['show_option_none'] ) ) {
			$output .= '<option value="0" ' . ( empty( $value ) ? 'selected="selected"' : '' ) . '>' . esc_html( $this->args['show_option_none'] ) . '</option>';
		}

		if ( is_array( $this->options ) ) {
			foreach ( $this->options as $slug => $name ) {
				$selected = ( ! is_null( $value ) && '' !== $value ) ? selected( (string) $slug, (string) $value, false ) : selected( (string) $this->default_value, (string) $slug, false );
				$output  .= '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
			}
		}

		$output .= '</select>';
		$output .= $this->output_explanation();

		return $output;
	}
}
