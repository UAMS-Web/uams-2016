<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_User_Select extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_ajax       = true;
	public $_supports_bundle     = true;

	public $dropdown;
	public $value;

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->args = array_merge(
			array(
				'orderby' => 'ID',
				'class'   => '',
			),
			(array) $this->args
		);

		$this->args['class'] .= ' cuztom-input cuztom-select cuztom-user-select';
		$this->args['echo']   = 0;
	}

	public function _output( $value, $object = null )
	{
		$this->args['name']     = 'cuztom' . $this->pre . '[' . $this->id . ']' . $this->after . ( $this->repeatable ? '[]' : '' );
		$this->args['id']       = $this->id . $this->after_id;
		$this->args['selected'] = ( ! empty( $value ) ? $value : $this->default_value );
		$this->dropdown         = wp_dropdown_users( $this->args );

		$output  = (string) $this->dropdown;
		$output .= $this->output_explanation();

		return $output;
	}
}
