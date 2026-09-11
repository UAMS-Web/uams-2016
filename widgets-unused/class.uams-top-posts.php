<?php

//       Name: UAMS Top Posts
//       Description: A widget that shows top posts and most recent posts on your blog

if ( ! class_exists( 'UAMS_Top_Posts' ) ) :

class UAMS_Top_Posts extends WP_Widget
{
	const ID    = 'uams-top-posts';
	const TITLE = 'UAMS Top Posts';
	const DESC  = 'A widget that shows top posts on your blog';
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
		$title = apply_filters( 'widget_title', ! empty( $instance['title'] ) ? $instance['title'] : self::TITLE );
		$items = ! empty( $instance['items'] ) ? absint( $instance['items'] ) : self::ITEMS;

		$popular = array();
		if ( $this->jetpackInstalled() ) {
			$raw_stats = stats_get_csv( 'postviews', array( 'days' => self::DAYS, 'limit' => self::FETCH ) );
			$popular   = $this->filterResults( is_array( $raw_stats ) ? $raw_stats : array() );
		}

		if ( empty( $popular ) ) {
			return;
		}

		echo $args['before_widget'] ?? '';
		?>

		<h2><?php echo esc_html( $title ); ?>
			<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
				 width="24.02px" height="16.794px" viewBox="0 0 24.02 16.794" enable-background="new 0 0 24.02 16.794" xml:space="preserve">
				<path fill="#F58433" d="M21.351,8c0,0.736,0.598,1.334,1.334,1.334c0.737,0,1.335-0.598,1.335-1.334V1.334
					c0-0.088-0.009-0.176-0.026-0.262c-0.008-0.039-0.023-0.077-0.034-0.115c-0.013-0.045-0.023-0.09-0.041-0.133
					c-0.02-0.046-0.046-0.088-0.069-0.131c-0.018-0.033-0.032-0.067-0.053-0.099c-0.099-0.146-0.225-0.272-0.371-0.37
					c-0.031-0.021-0.066-0.036-0.1-0.054c-0.043-0.023-0.084-0.05-0.13-0.069c-0.044-0.018-0.089-0.027-0.134-0.041
					c-0.038-0.011-0.075-0.025-0.115-0.034C22.861,0.009,22.773,0,22.685,0h-6.672c-0.737,0-1.334,0.597-1.334,1.334
					c0,0.736,0.597,1.333,1.334,1.333h3.451l-8.789,8.781l-3.06-3.058c-0.521-0.521-1.366-0.521-1.888,0L0.39,13.724
					c-0.521,0.521-0.521,1.365,0,1.885C0.651,15.87,0.993,16,1.334,16c0.341,0,0.683-0.13,0.943-0.391l4.394-4.391l3.061,3.058
					c0.521,0.521,1.365,0.521,1.887,0l9.732-9.724V8z"/>
			</svg>
		</h2>

		<ul class="popular-posts">
			<?php 
			foreach ( $popular as $index => $post ) : 
				if ( $index >= $items ) break;
				$post_id        = (int) ( $post->post_id ?? $post->ID );
				$post_permalink = get_permalink( $post_id );
				$post_title     = get_the_title( $post_id );
			?>
				<li>
					<?php if ( has_post_thumbnail( $post_id ) ) : ?>
						<a class="widget-thumbnail" href="<?php echo esc_url( $post_permalink ); ?>" title="<?php echo esc_attr( $post_title ); ?>">
							<?php echo get_the_post_thumbnail( $post_id, 'thumbnail' ); ?>
						</a>
					<?php endif; ?>

					<a class="widget-link" href="<?php echo esc_url( $post_permalink ); ?>" title="<?php echo esc_attr( $post_title ); ?>">
						<?php echo esc_html( $post_title ); ?>
						<p><small><?php echo esc_html( $this->convertViews( $post->views ?? 0 ) ); ?></small></p>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		echo $args['after_widget'] ?? '';
	}

	public function update( $new_instance, $old_instance )
	{
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['items'] = ! empty( $new_instance['items'] ) ? absint( $new_instance['items'] ) : self::ITEMS;
		return $instance;
	}

	public function form( $instance )
	{
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : self::TITLE;
		$items = ! empty( $instance['items'] ) ? absint( $instance['items'] ) : self::ITEMS;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php _e( 'Number of items to display:', 'uams' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>">
				<?php for ( $i = 1; $i <= 20; $i++ ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $items, $i ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
		</p>
		<?php
	}

	public function filterResults( $results )
	{
		$popular = array();
		foreach ( (array) $results as $post ) {
			if ( isset( $post['post_id'] ) && 'post' === get_post_type( $post['post_id'] ) && $post['post_id'] > 0 ) {
				$popular[] = (object) $post;
			}
		}
		return $popular;
	}

	public function convertViews( $views )
	{
		$views = (int) $views;
		$formatted = $views >= 1000 ? floor( $views / 1000 ) . 'K' : (string) $views;
		return $formatted . ' ' . _n( 'view', 'views', $views, 'uams' );
	}

	public function jetpackInstalled()
	{
		return function_exists( 'stats_get_csv' );
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Top_Posts' );
} );

endif;
