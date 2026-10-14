<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Wysiwyg extends Cuztom_Field
{
	public $_supports_ajax   = true;
	public $_supports_bundle = true;

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->args = array_merge(
			array(
				'textarea_name' => 'cuztom[' . $this->id . ']',
				'editor_class'  => '',
			),
			(array) $this->args
		);

		$this->args['editor_class'] .= ' cuztom-input';
	}

	public function _output( $value, $object = null )
	{
		$this->args['textarea_name'] = 'cuztom' . $this->pre . '[' . $this->id . ']' . $this->after;

		$editor_id      = sanitize_key( $this->pre_id . $this->id . $this->after_id );
		$editor_content = ( ! empty( $value ) ? $value : $this->default_value );

		ob_start();
		wp_editor( (string) $editor_content, $editor_id, $this->args );
		$output = ob_get_clean();

		$output .= $this->output_explanation();

		return $output;
	}
}
