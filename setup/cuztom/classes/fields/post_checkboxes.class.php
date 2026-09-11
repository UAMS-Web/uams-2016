<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Post_Checkboxes extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input' );
	public $posts;

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->args = array_merge(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
			),
			(array) $this->args
		);

		$this->default_value = (array) $this->default_value;
		$this->posts         = get_posts( $this->args );
		$this->after        .= '[]';
	}

	public function _output( $value, $object = null )
	{
		$output = '<div class="cuztom-checkboxes-wrap">';

		if ( is_array( $this->posts ) ) {
			foreach ( $this->posts as $post ) {
				$checked = false;
				if ( is_array( $value ) ) {
					$checked = in_array( (string) $post->ID, array_map( 'strval', $value ), true );
				} elseif ( '-1' !== (string) $value ) {
					$checked = in_array( (string) $post->ID, array_map( 'strval', $this->default_value ), true );
				}

				$element_id = $this->id . $this->after_id . '_' . Cuztom::uglify( $post->post_title );

				$output .= '<input type="checkbox" ' . $this->output_name() . ' ' . $this->output_id( $element_id ) . ' ' . $this->output_css_class() . ' value="' . esc_attr( $post->ID ) . '" ' . checked( $checked, true, false ) . ' /> ';
				$output .= '<label for="' . esc_attr( $element_id ) . '">' . esc_html( $post->post_title ) . '</label>';
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
