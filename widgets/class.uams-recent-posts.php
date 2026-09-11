<?php

class UAMS_Recent_Posts extends WP_Widget
{
	const ID    = 'uams-recent';
	const TITLE = 'UAMS Recent Posts';
	const DESC  = 'A widget that shows recent posts on your blog';
	const ITEMS = 5;
	const FETCH = 20;
	const DAYS  = 14;

	public function __construct()
	{
		parent::__construct(
			self::ID,
			__( self::TITLE, 'uams' ),
			array(
				'description' => __( self::DESC, 'uams' ),
				'classname'   => self::ID,
			)
		);
	}

	public function widget( $args, $instance )
	{
		$title = ! empty( $instance['title'] ) ? $instance['title'] : self::TITLE;
		$items = ! empty( $instance['items'] ) ? absint( $instance['items'] ) : self::ITEMS;
		$more  = ! empty( $instance['more'] ) ? (bool) $instance['more'] : false;

		$recent = wp_get_recent_posts(
			array(
				'numberposts' => $items,
				'post_status' => 'publish',
			),
			OBJECT
		);

		$title = apply_filters( 'widget_title', $title );

		if ( empty( $recent ) ) {
			return;
		}

		echo $args['before_widget'];
		?>

		<h2><?php echo esc_html( $title ); ?>
			<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
				 width="25.526px" height="24.609px" viewBox="0 0 25.526 24.609" enable-background="new 0 0 25.526 24.609" xml:space="preserve">
				<g>
					<g>
						<path fill="#20A2ED" d="M12.763,0c-6.617,0-12,5.383-12,12c0,6.617,5.383,12,12,12s12-5.383,12-12C24.763,5.383,19.38,0,12.763,0z
							 M12.763,21.818c-5.414,0-9.818-4.405-9.818-9.818s4.404-9.818,9.818-9.818S22.582,6.586,22.582,12S18.177,21.818,12.763,21.818z
							 M13.854,7.638h-2.182v6.545h2.182v-0.001h3.272V12h-3.272V7.638z"/>
					</g>
				</g>
			</svg>
		</h2>

		<ul class="recent-posts">
			<?php foreach ( $recent as $post ) : 
				$permalink = function_exists( 'uams_get_permalink' ) ? uams_get_permalink( $post->ID ) : get_permalink( $post->ID );
			?>
				<li>
					<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
						<a class="widget-thumbnail" href="<?php echo esc_url( $permalink ); ?>" title="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
							<?php echo get_the_post_thumbnail( $post->ID, 'thumbnail' ); ?>
						</a>
					<?php endif; ?>

					<a class="widget-link" href="<?php echo esc_url( $permalink ); ?>" title="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
						<?php echo esc_html( get_the_title( $post->ID ) ); ?>
						<p><small><?php echo esc_html( $this->humanTime( $post->ID ) ); ?> ago</small></p>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( get_option( 'page_for_posts' ) && $more ) : ?>
			<a class="more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php _e( 'More', 'uams' ); ?></a>
		<?php endif; ?>

		<?php
		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance )
	{
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['items'] = ! empty( $new_instance['items'] ) ? absint( $new_instance['items'] ) : self::ITEMS;
		$instance['more']  = ! empty( $new_instance['more'] );

		return $instance;
	}

	public function form( $instance )
	{
		$title = $instance['title'] ?? self::TITLE;
		$items = ! empty( $instance['items'] ) ? absint( $instance['items'] ) : self::ITEMS;
		$more  = ! empty( $instance['more'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php _e( 'Number of items to display:', 'uams' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>">
				<?php for ( $i = 1; $i <= self::ITEMS; $i++ ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $items, $i ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
		</p>

		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'more' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'more' ) ); ?>" <?php checked( $more, true ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'more' ) ); ?>"><?php _e( 'Display more link', 'uams' ); ?></label>
		</p>
		<?php
	}

	public function humanTime( $post_id )
	{
		return human_time_diff( get_the_time( 'U', $post_id ), time() );
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Recent_Posts' );
} );