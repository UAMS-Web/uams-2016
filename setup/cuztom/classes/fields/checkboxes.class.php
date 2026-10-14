<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Checkboxes extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input' );

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->default_value = (array) $this->default_value;
		$this->after        .= '[]';
	}

	public function _output( $value, $object = null )
	{
		$output = '<div class="cuztom-padding-wrap cuztom-checkboxes-wrap">';

		if ( is_array( $this->options ) ) {
			foreach ( $this->options as $slug => $name ) {
				$checked = false;
				if ( is_array( $value ) ) {
					$checked = in_array( $slug, $value, true );
				} elseif ( '-1' !== (string) $value ) {
					$checked = in_array( $slug, (array) $this->default_value, true );
				}

				$output .= '<input type="checkbox" ' . $this->output_name() . ' ' . $this->output_id( $this->id . $this->after_id . '_' . Cuztom::uglify( $slug ) ) . ' ' . $this->output_css_class() . ' value="' . esc_attr( $slug ) . '" ' . checked( $checked, true, false ) . ' /> ';
				$output .= '<label ' . $this->output_for_attribute( $this->id . $this->after_id . '_' . Cuztom::uglify( $slug ) ) . '>' . esc_html( $name ) . '</label>';
				$output .= '<br />';
			}
		}

		$output .= '</div>';
		$output .= $this->output_explanation();

		return $output;
	}

	public function save_value( $value )
	{
		return empty( $value ) ? '-1' : $value;
	}
}
