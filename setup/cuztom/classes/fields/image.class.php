<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_Image extends Cuztom_Field
{
	public $_supports_repeatable = true;
	public $_supports_ajax       = true;
	public $_supports_bundle     = true;

	public $css_classes = array( 'cuztom-hidden', 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$image = '';

		if ( ! empty( $value ) ) {
			$preview_size = ! empty( $this->args['preview_size'] ) ? $this->args['preview_size'] : apply_filters( 'cuztom_preview_size', 'medium' );
			$src_info     = wp_get_attachment_image_src( (int) $value, $preview_size );

			if ( is_array( $src_info ) && ! empty( $src_info[0] ) ) {
				$image = '<img src="' . esc_url( $src_info[0] ) . '" alt="" />';
			}
		}

		$preview_attr = '';
		if ( ! empty( $this->args['preview_size'] ) ) {
			$preview_val  = is_array( $this->args['preview_size'] ) ? wp_json_encode( $this->args['preview_size'] ) : $this->args['preview_size'];
			$preview_attr = ' data-cuztom-media-preview-size="' . esc_attr( $preview_val ) . '"';
		}

		$output  = '<input type="hidden" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' value="' . esc_attr( ! empty( $value ) ? $value : '' ) . '" />';
		$output .= sprintf( '<input id="upload-image-button" type="button" class="button js-cuztom-upload" data-cuztom-media-type="image"%s value="%s" />', $preview_attr, esc_attr__( 'Select image', 'cuztom' ) );
		$output .= ( ! empty( $value ) ? sprintf( '<a href="#" class="js-cuztom-remove-media cuztom-remove-media">%s</a>', esc_html__( 'Remove current image', 'cuztom' ) ) : '' );
		$output .= '<span class="cuztom-preview">' . $image . '</span>';
		$output .= $this->output_explanation();

		return $output;
	}
}
