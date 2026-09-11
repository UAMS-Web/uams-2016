<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Hidden extends Cuztom_Field
{
	public $css_classes = array( 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$val = ( ! is_null( $value ) && '' !== $value ) ? $value : $this->default_value;
		return '<input type="hidden" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' value="' . esc_attr( (string) $val ) . '" ' . $this->output_data_attributes() . ' />' . $this->output_explanation();
	}
}
