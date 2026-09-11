<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Checkbox extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$is_checked = ! empty( $value ) ? checked( $value, 'on', false ) : checked( $this->default_value, 'on', false );

		return '<div class="cuztom-checkbox-wrap"><input type="checkbox" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' ' . $is_checked . ' /></div>' . $this->output_explanation();
	}

	public function save_value( $value )
	{
		return empty( $value ) ? '-1' : $value;
	}
}
