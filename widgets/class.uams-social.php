<?php

/**
 * Social Icons widget
 *
 * Social Media Icons are displayed
 */

class UAMS_Social_Icons extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'uams-widget-social',
			__( 'UAMS Social Icons', 'uams' ),
			array(
				'description' => __( 'Display social media icons with links to the appropriate account', 'uams' ),
				'classname'   => 'social-widget',
			)
		);
	}

	public function form( $instance )
	{
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : 'Connect with us:';
		$facebook  = isset( $instance['facebook'] ) ? esc_url( $instance['facebook'] ) : '';
		$twitter   = isset( $instance['twitter'] ) ? esc_url( $instance['twitter'] ) : '';
		$instagram = isset( $instance['instagram'] ) ? esc_url( $instance['instagram'] ) : '';
		$youtube   = isset( $instance['youtube'] ) ? esc_url( $instance['youtube'] ) : '';
		$linkedin  = isset( $instance['linkedin'] ) ? esc_url( $instance['linkedin'] ) : '';
		$pinterest = isset( $instance['pinterest'] ) ? esc_url( $instance['pinterest'] ) : '';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?> <small><b>Appears above the icons</b></small></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'facebook' ) ); ?>"><?php _e( 'Facebook URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'facebook' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'facebook' ) ); ?>" type="text" value="<?php echo $facebook; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'twitter' ) ); ?>"><?php _e( 'Twitter URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'twitter' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'twitter' ) ); ?>" type="text" value="<?php echo $twitter; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'instagram' ) ); ?>"><?php _e( 'Instagram URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'instagram' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'instagram' ) ); ?>" type="text" value="<?php echo $instagram; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'youtube' ) ); ?>"><?php _e( 'YouTube URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'youtube' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'youtube' ) ); ?>" type="text" value="<?php echo $youtube; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'linkedin' ) ); ?>"><?php _e( 'LinkedIn URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'linkedin' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'linkedin' ) ); ?>" type="text" value="<?php echo $linkedin; ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'pinterest' ) ); ?>"><?php _e( 'Pinterest URI:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pinterest' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pinterest' ) ); ?>" type="text" value="<?php echo $pinterest; ?>" />
		</p>

		<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance              = array();
		$instance['title']     = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['facebook']  = esc_url_raw( $new_instance['facebook'] ?? '' );
		$instance['twitter']   = esc_url_raw( $new_instance['twitter'] ?? '' );
		$instance['instagram'] = esc_url_raw( $new_instance['instagram'] ?? '' );
		$instance['youtube']   = esc_url_raw( $new_instance['youtube'] ?? '' );
		$instance['linkedin']  = esc_url_raw( $new_instance['linkedin'] ?? '' );
		$instance['pinterest'] = esc_url_raw( $new_instance['pinterest'] ?? '' );

		return $instance;
	}

	public function widget( $args, $instance )
	{
		$title     = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$facebook  = ! empty( $instance['facebook'] ) ? $instance['facebook'] : '';
		$twitter   = ! empty( $instance['twitter'] ) ? $instance['twitter'] : '';
		$instagram = ! empty( $instance['instagram'] ) ? $instance['instagram'] : '';
		$youtube   = ! empty( $instance['youtube'] ) ? $instance['youtube'] : '';
		$linkedin  = ! empty( $instance['linkedin'] ) ? $instance['linkedin'] : '';
		$pinterest = ! empty( $instance['pinterest'] ) ? $instance['pinterest'] : '';

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo '<h2 class="widgettitle">' . esc_html( $title ) . '</h2>';
			echo '<span class="udub-slant"><span></span></span>';
		}
		?>

		<nav aria-label="social networks">
			<ul class="widget-social">
				<?php if ( ! empty( $facebook ) ) : ?>
					<li><a class="facebook" title="Facebook" href="<?php echo esc_url( $facebook ); ?>">Facebook</a></li>
				<?php endif; ?>
				<?php if ( ! empty( $twitter ) ) : ?>
					<li><a class="twitter" title="Twitter" href="<?php echo esc_url( $twitter ); ?>">Twitter</a></li>
				<?php endif; ?>
				<?php if ( ! empty( $instagram ) ) : ?>
					<li><a class="instagram" title="Instagram" href="<?php echo esc_url( $instagram ); ?>">Instagram</a></li>
				<?php endif; ?>
				<?php if ( ! empty( $youtube ) ) : ?>
					<li><a class="youtube" title="YouTube" href="<?php echo esc_url( $youtube ); ?>">YouTube</a></li>
				<?php endif; ?>
				<?php if ( ! empty( $linkedin ) ) : ?>
					<li><a class="linkedin" title="LinkedIn" href="<?php echo esc_url( $linkedin ); ?>">LinkedIn</a></li>
				<?php endif; ?>
				<?php if ( ! empty( $pinterest ) ) : ?>
					<li><a class="pinterest" title="Pinterest" href="<?php echo esc_url( $pinterest ); ?>">Pinterest</a></li>
				<?php endif; ?>
			</ul>
		</nav>

		<?php
		echo $args['after_widget'];
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Social_Icons' );
} );