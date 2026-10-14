<?php

/**
 * Single image widget
 *
 * Text paragraph and a single image are displayed
 */

class UAMS_Widget_Single_Image extends WP_Widget
{
	const DEFAULT_LINK_TEXT = 'More';

	public function __construct()
	{
		parent::__construct(
			'pic-text',
			__( 'Single Image', 'uams' ),
			array(
				'description' => __( 'Display an image with some featured text.', 'uams' ),
				'classname'   => 'pic-text-widget',
			)
		);
	}

	public static function scripts()
	{
		wp_enqueue_script( 'single-image', get_template_directory_uri() . '/assets/admin/js/widgets/uams.single-image-widget.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_media();
	}

	public function form( $instance )
	{
		$title    = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : 'Image Widget';
		$text     = isset( $instance['text'] ) ? esc_attr( $instance['text'] ) : '';
		$image    = isset( $instance['image'] ) ? esc_attr( $instance['image'] ) : '';
		$src      = isset( $instance['src'] ) ? esc_attr( $instance['src'] ) : '';
		$link     = isset( $instance['link'] ) ? esc_attr( $instance['link'] ) : '';
		$linktext = isset( $instance['link-text'] ) ? esc_attr( $instance['link-text'] ) : 'Read more';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?> <small><b>(Search and autofill by typing a title)</b></small></label>
			<input data-posttype="post" class="widefat wp-get-posts" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<div class="image-preview wp-get-posts-image-preview" style="width:33%; display:block;">
				<img src="<?php echo esc_url( wp_get_attachment_url( (int) $image ) ?: '' ); ?>" width="100%" class="wp-get-posts-image" alt="" />
			</div>

			<a class="select-an-image button" href="#">Select an Image</a>
			<input id="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>" class="wp-get-posts-imageID" name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>" type="hidden" value="<?php echo esc_attr( $image ); ?>"/>
			<input id="<?php echo esc_attr( $this->get_field_id( 'src' ) ); ?>" class="wp-get-posts-image site-panels-image-fix" name="<?php echo esc_attr( $this->get_field_name( 'src' ) ); ?>" type="hidden" value="<?php echo esc_attr( $src ); ?>"/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Featured text:', 'uams' ); ?></label>
			<textarea class="widefat wp-get-posts-excerpt" style="resize:vertical" rows="5" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"><?php _e( 'Link:', 'uams' ); ?></label>
			<input class="widefat wp-get-posts-url" id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link' ) ); ?>" type="text" value="<?php echo esc_attr( $link ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link-text' ) ); ?>"><?php _e( 'Link text:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link-text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link-text' ) ); ?>" type="text" value="<?php echo esc_attr( $linktext ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance              = array();
		$instance['title']     = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['text']      = wp_kses_post( $new_instance['text'] ?? '' );
		$instance['image']     = absint( $new_instance['image'] ?? 0 );
		$instance['src']       = esc_url_raw( $new_instance['src'] ?? '' );
		$instance['link']      = esc_url_raw( $new_instance['link'] ?? '' );
		$instance['link-text'] = sanitize_text_field( $new_instance['link-text'] ?? self::DEFAULT_LINK_TEXT );

		return $instance;
	}

	public function widget( $args, $instance )
	{
		$title    = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$text     = ! empty( $instance['text'] ) ? $instance['text'] : '';
		$image    = ! empty( $instance['image'] ) ? absint( $instance['image'] ) : 0;
		$link     = ! empty( $instance['link'] ) ? esc_url( $instance['link'] ) : '';
		$linktext = ! empty( $instance['link-text'] ) ? $instance['link-text'] : self::DEFAULT_LINK_TEXT;

		echo $args['before_widget'] ?? '';

		if ( $image ) {
			echo wp_get_attachment_image( $image, 'single-image-widget', false, array( 'alt' => esc_attr( $title ) ) );
		}
		?>
		<span>
			<?php if ( ! empty( $title ) ) : ?>
				<h3>
					<?php if ( ! empty( $link ) ) : ?>
						<a href="<?php echo $link; ?>" class="pic-title"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h3>
			<?php endif; ?>

			<?php echo wpautop( $text ); ?>

			<?php if ( ! empty( $link ) ) : ?>
				<a href="<?php echo $link; ?>" class="pic-text-more"><?php echo esc_html( $linktext ); ?></a>
			<?php else : ?>
				<br/>
			<?php endif; ?>
		</span>
		<?php
		echo $args['after_widget'] ?? '';
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Widget_Single_Image' );
} );
add_action( 'admin_enqueue_scripts', array( 'UAMS_Widget_Single_Image', 'scripts' ) );
