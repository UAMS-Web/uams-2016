<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Yesno extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$id_yes = $this->id . $this->after_id . '_yes';
		$id_no  = $this->id . $this->after_id . '_no';

		$output  = '<div class="cuztom-checkbox-wrap">';
		$output .= '<input type="radio" ' . $this->output_name() . ' ' . $this->output_id( $id_yes ) . ' ' . $this->output_css_class() . ' value="yes" ' . ( ! empty( $value ) ? checked( $value, 'yes', false ) : checked( $this->default_value, 'yes', false ) ) . ' /> ';
		$output .= '<label class="cuztom-label" ' . $this->output_for_attribute( $id_yes ) . '>' . esc_html__( 'Yes', 'cuztom' ) . '</label>';
		$output .= '<br />';
		$output .= '<input type="radio" ' . $this->output_name() . ' ' . $this->output_id( $id_no ) . ' ' . $this->output_css_class() . ' value="no" ' . ( ! empty( $value ) ? checked( $value, 'no', false ) : checked( $this->default_value, 'no', false ) ) . ' /> ';
		$output .= '<label class="cuztom-label" ' . $this->output_for_attribute( $id_no ) . '>' . esc_html__( 'No', 'cuztom' ) . '</label>';
		$output .= '</div>';

		$output .= $this->output_explanation();

		return $output;
	}
}
