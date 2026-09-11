<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Post_Select extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_ajax       = true;
	public $_supports_bundle     = true;

	public $css_classes = array( 'cuztom-input cuztom-select cuztom-post-select' );
	public $posts;

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->args = array_merge(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'cache_results'  => false,
				'no_found_rows'  => true,
			),
			(array) $this->args
		);

		$this->posts = get_posts( $this->args );
	}

	public function _output( $value, $object = null )
	{
		$output = '<select ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . '>';

		if ( isset( $this->args['show_option_none'] ) ) {
			$output .= '<option value="0" ' . ( empty( $value ) ? 'selected="selected"' : '' ) . '>' . esc_html( $this->args['show_option_none'] ) . '</option>';
		}

		if ( is_array( $this->posts ) ) {
			foreach ( $this->posts as $post ) {
				$selected = ! empty( $value ) ? selected( (string) $post->ID, (string) $value, false ) : selected( (string) $this->default_value, (string) $post->ID, false );
				$output  .= '<option value="' . esc_attr( $post->ID ) . '" ' . $selected . '>' . esc_html( $post->post_title ) . '</option>';
			}
		}

		$output .= '</select>';
		$output .= $this->output_explanation();

		return $output;
	}
}
