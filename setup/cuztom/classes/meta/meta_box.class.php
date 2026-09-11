<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the meta boxes
 *
 * @author  Gijs Jorissen
 * @since   0.2
 */
#[\AllowDynamicProperties]
class Cuztom_Meta_Box extends Cuztom_Meta
{
	public $context;
	public $priority;
	public $post_types = array();

	public function __construct( $id, $title, $post_type, $data = array(), $context = 'normal', $priority = 'default' )
	{
		if ( ! empty( $title ) ) {
			parent::__construct( $title );

			$this->id         = $id;
			$this->post_types = (array) $post_type;
			$this->context    = $context;
			$this->priority   = $priority;

			if ( Cuztom::is_wp_callback( $data ) ) {
				$this->callback = $data;
			} else {
				$this->callback = array( $this, 'callback' );
				$this->data     = $this->build( $data );

				foreach ( $this->post_types as $pt ) {
					add_filter( 'manage_' . $pt . '_posts_columns', array( $this, 'add_column' ) );
					add_action( 'manage_' . $pt . '_posts_custom_column', array( $this, 'add_column_content' ), 10, 2 );
					add_action( 'manage_edit-' . $pt . '_sortable_columns', array( $this, 'add_sortable_column' ), 10, 2 );
				}

				add_action( 'save_post', array( $this, 'save_post' ) );
				add_action( 'post_edit_form_tag', array( $this, 'edit_form_tag' ) );
			}

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		}
	}

	public function add_meta_box()
	{
		foreach ( $this->post_types as $post_type ) {
			add_meta_box(
				$this->id,
				$this->title,
				$this->callback,
				$post_type,
				$this->context,
				$this->priority
			);
		}
	}

	public function save_post( $post_id )
	{
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! ( isset( $_POST['cuztom_nonce'] ) && wp_verify_nonce( $_POST['cuztom_nonce'], 'cuztom_meta' ) ) ) {
			return;
		}

		$current_pt = get_post_type( $post_id );
		if ( ! in_array( $current_pt, array_merge( $this->post_types, array( 'revision' ) ), true ) ) {
			return;
		}

		$pt_obj = get_post_type_object( $current_pt );
		if ( ! $pt_obj || ! current_user_can( $pt_obj->cap->edit_post, $post_id ) ) {
			return;
		}

		$values = ( isset( $_POST['cuztom'] ) && is_array( $_POST['cuztom'] ) ) ? $_POST['cuztom'] : array();

		if ( ! empty( $values ) ) {
			parent::save( $post_id, $values );
		}
	}

	public function save( $post_id, $values )
	{
		if ( empty( $this->fields ) || ! is_array( $this->fields ) ) {
			return;
		}

		foreach ( $this->fields as $id => $field ) {
			if ( ! empty( $field->in_bundle ) ) {
				continue;
			}

			$value = isset( $values[ $id ] ) ? $values[ $id ] : '';
			$value = apply_filters( "cuztom_post_meta_save_{$field->type}", apply_filters( 'cuztom_post_meta_save', $value, $field, $post_id ), $field, $post_id );

			$field->save( $post_id, $value );
		}
	}

	public function add_column( $columns )
	{
		$columns = is_array( $columns ) ? $columns : array();
		unset( $columns['date'] );

		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
			foreach ( $this->fields as $id_name => $field ) {
				if ( ! empty( $field->show_admin_column ) ) {
					$columns[ $id_name ] = $field->label;
				}
			}
		}

		$columns['date'] = __( 'Date', 'cuztom' );
		return $columns;
	}

	public function add_column_content( $column, $post_id )
	{
		$meta = get_post_meta( (int) $post_id, $column, true );

		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
			foreach ( $this->fields as $id_name => $field ) {
				if ( $column === $id_name ) {
					if ( ! empty( $field->repeatable ) && ! empty( $field->_supports_repeatable ) ) {
						echo esc_html( implode( ', ', (array) $meta ) );
					} else {
						if ( $field instanceof Cuztom_Field_Image ) {
							echo wp_get_attachment_image( (int) $meta, array( 100, 100 ) );
						} elseif ( $field instanceof Cuztom_Field_Radios ) {
							$option_key = is_array( $meta ) && isset( $meta[0] ) ? $meta[0] : $meta;
							echo isset( $field->options[ $option_key ] ) ? esc_html( $field->options[ $option_key ] ) : '';
						} else {
							echo esc_html( (string) $meta );
						}
					}
					break;
				}
			}
		}
	}

	public function add_sortable_column( $columns )
	{
		$columns = is_array( $columns ) ? $columns : array();

		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
			foreach ( $this->fields as $id_name => $field ) {
				if ( ! empty( $field->admin_column_sortable ) ) {
					$columns[ $id_name ] = $field->label;
				}
			}
		}

		return $columns;
	}
}
