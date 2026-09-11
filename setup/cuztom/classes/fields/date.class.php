<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Date extends Cuztom_Field
{
	public $_supports_ajax   = true;
	public $_supports_bundle = true;

	public $css_classes     = array( 'js-cuztom-datepicker', 'cuztom-datepicker', 'datepicker', 'cuztom-input' );
	public $data_attributes = array( 'date-format' => null );

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->data_attributes['date-format'] = $this->parse_date_format( isset( $this->args['date_format'] ) ? $this->args['date_format'] : 'm/d/Y' );
	}

	public function _output( $value, $object = null )
	{
		$date_val = '';
		if ( ! empty( $value ) ) {
			$timestamp = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
			if ( false !== $timestamp ) {
				$format   = isset( $this->args['date_format'] ) ? $this->args['date_format'] : 'm/d/Y';
				$date_val = date( $format, $timestamp );
			}
		}

		$display_val = ! empty( $date_val ) ? $date_val : $this->default_value;

		return '<input type="text" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' value="' . esc_attr( (string) $display_val ) . '" ' . $this->output_data_attributes() . ' />' . $this->output_explanation();
	}

	public function save_value( $value )
	{
		if ( empty( $value ) ) {
			return '';
		}
		$time = strtotime( (string) $value );
		return false !== $time ? $time : '';
	}

	public function parse_date_format( $php_format )
	{
		$matching = array(
			'd' => 'dd', 'D' => 'D', 'j' => 'd', 'l' => 'DD',
			'N' => '',   'S' => '',  'w' => '', 'z' => 'o',
			'W' => '',   'F' => 'MM','m' => 'mm','M' => 'M',
			'n' => 'm',  't' => '',  'L' => '', 'o' => '',
			'Y' => 'yy', 'y' => 'y',  'a' => 'tt','A' => 'TT',
			'B' => '',   'g' => 'h', 'G' => 'H','h' => 'hh',
			'H' => 'HH', 'i' => 'mm','s' => 'ss','u' => 'c',
			'c' => 'Z'
		);

		$jqueryui_format = '';
		$escaping        = false;
		$len             = strlen( (string) $php_format );

		for ( $i = 0; $i < $len; $i++ ) {
			$char = $php_format[ $i ];
			if ( '\\' === $char ) {
				$i++;
				if ( isset( $php_format[ $i ] ) ) {
					if ( $escaping ) {
						$jqueryui_format .= $php_format[ $i ];
					} else {
						$jqueryui_format .= '\'' . $php_format[ $i ];
					}
					$escaping = true;
				}
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping         = false;
				}

				if ( isset( $matching[ $char ] ) ) {
					$jqueryui_format .= $matching[ $char ];
				} else {
					$jqueryui_format .= $char;
				}
			}
		}

		return $jqueryui_format;
	}
}
