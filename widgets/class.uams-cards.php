<?php

/**
 * Image Cards widget
 *
 * Text paragraph and a single image are displayed
 */

class UAMS_Widget_Cards extends WP_Widget
{
	const DEFAULT_LINK_TEXT = 'More';

	public function __construct()
	{
		parent::__construct(
			'uams-widget-cards',
			__( 'UAMS Image Cards', 'uams' ),
			array(
				'description' => __( 'Choose from several styles of cards', 'uams' ),
				'classname'   => 'cards-widget',
			)
		);
	}

	public static function scripts()
	{
		wp_enqueue_script( 'single-card-image', get_template_directory_uri() . '/assets/admin/js/widgets/uams.card-widget.js', array( 'jquery' ), null, true );
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
		$radio    = isset( $instance['radio_card'] ) ? esc_attr( $instance['radio_card'] ) : 'default-card';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?> <small><b>(Search and autofill by typing a title)</b></small></label>
			<input data-posttype="post" class="widefat wp-get-posts" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<div class="card-image-preview wp-get-posts-image-preview" style="width:33%; display:block;">
				<img src="<?php echo esc_url( wp_get_attachment_url( $image ) ?: '' ); ?>" width="100%" class="wp-get-posts-image" />
			</div>

			<a class="select-a-card-image button" href="#">Select an Image</a>
			<input id="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>" class="wp-get-posts-imageID" name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>" type="hidden" value="<?php echo esc_attr( $image ); ?>"/>
			<input id="<?php echo esc_attr( $this->get_field_id( 'src' ) ); ?>" class="wp-get-posts-image site-panels-image-fix" name="<?php echo esc_attr( $this->get_field_name( 'src' ) ); ?>" type="hidden" value="<?php echo esc_attr( $src ); ?>"/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Featured text:', 'uams' ); ?></label>
			<textarea class="widefat wp-get-posts-excerpt" style="resize:vertical" rows="5" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"><?php _e( 'Link:', 'uams' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>" class="widefat wp-get-posts-url" name="<?php echo esc_attr( $this->get_field_name( 'link' ) ); ?>" type="text" value="<?php echo esc_attr( $link ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link-text' ) ); ?>"><?php _e( 'Link text:', 'uams' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'link-text' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'link-text' ) ); ?>" type="text" value="<?php echo esc_attr( $linktext ); ?>" />
		</p>

		<div class="card-labels">
			<p>
				<label>Default card (<a id="default-preview" href="#">preview<span><img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/widget-card-default.jpg" alt="" /></span></a>)
					<input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'radio_card' ) ); ?>" value="default-card" <?php checked( $radio, 'default-card' ); ?> />
				</label>
			</p>
			<p>
				<label>Styled card (<a id="styled-preview" href="#">preview<span><img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/widget-card-styled.jpg" alt="" /></span></a>)
					<input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'radio_card' ) ); ?>" value="styled-card" <?php checked( $radio, 'styled-card' ); ?> />
				</label>
			</p>
		</div>
		<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance               = array();
		$instance['title']      = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['text']       = wp_kses_post( $new_instance['text'] ?? '' );
		$instance['image']      = absint( $new_instance['image'] ?? 0 );
		$instance['src']        = esc_url_raw( $new_instance['src'] ?? '' );
		$instance['link']       = esc_url_raw( $new_instance['link'] ?? '' );
		$instance['link-text']  = sanitize_text_field( $new_instance['link-text'] ?? '' );
		$instance['radio_card'] = sanitize_text_field( $new_instance['radio_card'] ?? 'default-card' );

		return $instance;
	}

	public function widget( $args, $instance )
	{
		$title     = $instance['title'] ?? '';
		$text      = $instance['text'] ?? '';
		$image     = ! empty( $instance['image'] ) ? absint( $instance['image'] ) : 0;
		$link      = $instance['link'] ?? '';
		$radio     = $instance['radio_card'] ?? 'default-card';
		$linktext  = ! empty( $instance['link-text'] ) ? $instance['link-text'] : self::DEFAULT_LINK_TEXT;

		echo $args['before_widget'];
		?>
		<div class="<?php echo esc_attr( $radio ); ?>">
			<?php if ( $image ) : 
				$the_image = wp_get_attachment_image_src( $image, 'large' );
				if ( ! empty( $the_image[0] ) ) : ?>
					<div class="card-image" style="background-image:url(<?php echo esc_url( $the_image[0] ); ?>)"></div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="card-body">
				<span>
					<?php if ( ! empty( $title ) ) : ?>
						<h3>
							<?php if ( ! empty( $link ) ) : ?><a href="<?php echo esc_url( $link ); ?>" class="pic-title"><?php endif; ?>
							<?php echo esc_html( $title ); ?>
							<?php if ( ! empty( $link ) ) : ?></a><?php endif; ?>
						</h3>
						<span class="udub-slant"><span></span></span>
					<?php endif; ?>

					<?php echo wpautop( $text ); ?>

					<?php if ( ! empty( $link ) && ! empty( $linktext ) ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="pic-text-more uams-btn btn-sm btn-red"><?php echo esc_html( $linktext ); ?></a>
					<?php else : ?>
						<br/>
					<?php endif; ?>
				</span>
			</div>
		</div>
		<?php
		echo $args['after_widget'];
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Widget_Cards' );
} );
add_action( 'admin_enqueue_scripts', array( 'UAMS_Widget_Cards', 'scripts' ) );