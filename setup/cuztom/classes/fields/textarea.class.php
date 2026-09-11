<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Textarea extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_bundle     = true;
	public $_supports_ajax       = true;

	public $css_classes = array( 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$val = ( ! is_null( $value ) && '' !== $value ) ? $value : $this->default_value;
		return '<textarea ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . '>' . esc_textarea( (string) $val ) . '</textarea>' . $this->output_explanation();
	}
}
