<?php

if( ! defined( 'ABSPATH' ) ) exit;

class Cuztom_Field_Multi_Select extends Cuztom_Field
{
	var $_supports_bundle		= true;

	var $css_classes 			= array( 'cuztom-input cuztom-select cuztom-multi-select' );

	function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->default_value 	= (array) $this->default_value;
		$this->after 		   .= '[]';
	}

	function _output( $value )
	{
		$output = '<select ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' multiple="true">';
			if( isset( $this->args['show_option_none'] ) ) {
				$selected = '';
				$output .= '<option value="0" ';
				if (is_array($value)) {
				    $output .= in_array(0, $value) ? 'selected="selected"' : '';
				} else {
				    $output .= ($value != '-1' && in_array(0, (array)$this->default_value)) ? 'selected="selected"' : '';
				}
				$output .= '>' . $this->args['show_option_none'] . '</option>';
			}
			if( is_array( $this->options ) )
			{
				foreach( $this->options as $slug => $name )
				{
					$output .= '<option value="' . $slug . '" ';
					if (is_array($value)) {
					    $output .= in_array($slug, $value) ? 'selected="selected"' : '';
					} else {
					    $output .= ($value != '-1' && in_array($slug, (array)$this->default_value)) ? 'selected="selected"' : '';
					}
					$output .= '>' . $name . '</option>';
				}
			}
		$output .= '</select>';

		$output .= $this->output_explanation();

		return $output;
	}

	function save_value( $value )
	{
		return empty( $value ) ? '-1' : $value;
	}
}