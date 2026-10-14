<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Field_File extends Cuztom_Field
{
	public $_supports_ajax   = true;
	public $_supports_bundle = true;

	public $css_classes = array( 'cuztom-hidden', 'cuztom-input' );

	public function _output( $value, $object = null )
	{
		$file = '';

		if ( ! empty( $value ) ) {
			$attachment = self::get_attachment_by_url( $value );
			$mime       = '';
			$name       = '';

			if ( is_object( $attachment ) ) {
				$mime = str_replace( '/', '_', (string) $attachment->post_mime_type );
				$name = $attachment->post_title;
			}

			$file = '<span class="cuztom-mime mime-' . esc_attr( $mime ) . '"><a target="_blank" href="' . esc_url( $value ) . '">' . esc_html( $name ) . '</a></span>';
		}

		$output  = '<input type="hidden" ' . $this->output_name() . ' ' . $this->output_id() . ' ' . $this->output_css_class() . ' value="' . esc_attr( ! empty( $value ) ? $value : '' ) . '" />';
		$output .= sprintf( '<input id="upload-file-button" type="button" class="button js-cuztom-upload" data-cuztom-media-type="file" value="%s" />', esc_attr__( 'Select file', 'cuztom' ) );
		$output .= ( ! empty( $value ) ? sprintf( '<a href="#" class="js-cuztom-remove-media cuztom-remove-media">%s</a>', esc_html__( 'Remove current file', 'cuztom' ) ) : '' );
		$output .= '<span class="cuztom-preview">' . $file . '</span>';
		$output .= $this->output_explanation();

		return $output;
	}

	public static function get_attachment_by_url( $url )
	{
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_title, post_mime_type FROM {$wpdb->posts} WHERE guid = %s LIMIT 1;", (string) $url ) );
	}
}
