<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Term_Checkboxes extends Cuztom_Field
{
	public $_supports_bundle = true;
	public $css_classes      = array( 'cuztom-input' );
	public $terms;

	public function __construct( $field, $parent )
	{
		parent::__construct( $field, $parent );

		$this->args = array_merge(
			array(
				'taxonomy' => 'category',
			),
			(array) $this->args
		);

		$this->default_value = (array) $this->default_value;
		add_action( 'init', array( $this, 'get_taxonomy_terms' ) );
		$this->after .= '[]';
	}

	public function _output( $value, $object = null )
	{
		$output = '<div class="cuztom-checkboxes-wrap">';

		if ( is_array( $this->terms ) && ! is_wp_error( $this->terms ) ) {
			foreach ( $this->terms as $term ) {
				$checked = false;
				if ( is_array( $value ) ) {
					$checked = in_array( (string) $term->term_id, array_map( 'strval', $value ), true );
				} elseif ( '-1' !== (string) $value ) {
					$checked = in_array( (string) $term->term_id, array_map( 'strval', $this->default_value ), true );
				}

				$element_id = $this->id . $this->after_id . '_' . Cuztom::uglify( $term->name );

				$output .= '<input type="checkbox" ' . $this->output_name() . ' ' . $this->output_id( $element_id ) . ' ' . $this->output_css_class() . ' value="' . esc_attr( $term->term_id ) . '" ' . checked( $checked, true, false ) . ' /> ';
				$output .= '<label for="' . esc_attr( $element_id ) . '">' . esc_html( $term->name ) . '</label>';
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

	public function get_taxonomy_terms()
	{
		$taxonomy    = isset( $this->args['taxonomy'] ) ? $this->args['taxonomy'] : 'category';
		$this->terms = get_terms( array_merge( (array) $this->args, array( 'taxonomy' => $taxonomy ) ) );
	}
}
