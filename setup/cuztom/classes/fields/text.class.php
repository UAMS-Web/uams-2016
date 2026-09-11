<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Text extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_bundle     = true;
	public $_supports_ajax       = true;

	public $css_classes = array( 'cuztom-input' );

	public function save_value( $value )
	{
		if ( is_array( $value ) ) {
			array_walk_recursive( $value, array( $this, 'do_htmlspecialchars' ) );
		} else {
			$value = htmlspecialchars( (string) $value );
		}

		return $value;
	}

	public function do_htmlspecialchars( &$value )
	{
		$value = htmlspecialchars( (string) $value );
	}
}
