<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Radios extends Cuztom_Field
{
	public $_supports_bundle = true;

	public $css_classes     = array( 'cuztom-input' );
	public $data_attributes = array( 'default-value' => null );

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->data_attributes['default-value']  = $this->default_value;
		$this->after                            .= '[]';
	}

	public function _output( $value, $object = null )
	{
		$output = '';

		$output .= '<div class="cuztom-checkboxes-wrap" ' . $this->output_data_attributes() . '>';
		if ( is_array( $this->options ) ) {
			$unserialized = maybe_unserialize( $value );
			$val_array    = is_array( $unserialized ) ? $unserialized : array( $value );

			foreach ( $this->options as $slug => $name ) {
				$checked = ! empty( $value ) ? in_array( (string) $slug, array_map( 'strval', $val_array ), true ) : ( (string) $this->default_value === (string) $slug );

				$output .= '<input type="radio" ' . $this->output_name() . ' ' . $this->output_id( $this->id . $this->after_id . '_' . Cuztom::uglify( $slug ) ) . ' ' . $this->output_css_class() . ' value="' . esc_attr( $slug ) . '" ' . checked( $checked, true, false ) . ' /> ';
				$output .= '<label ' . $this->output_for_attribute( $this->id . $this->after_id . '_' . Cuztom::uglify( $slug ) ) . '>' . esc_html( $name ) . '</label>';
				$output .= '<br />';
			}
		}
		$output .= '</div>';
		$output .= $this->output_explanation();

		return $output;
	}
}
