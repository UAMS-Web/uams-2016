<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Color extends Cuztom_Field
{
	public $_supports_ajax   = true;
	public $_supports_bundle = true;

	public $css_classes = array( 'js-cuztom-colorpicker', 'cuztom-colorpicker', 'colorpicker', 'cuztom-input' );
}
