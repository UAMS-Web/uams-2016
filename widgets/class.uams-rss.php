<?php
/**
 * UAMS RSS Widget and shortcode
 *  - Only difference between this and WP one is this shows images
 */

class UAMS_RSS extends WP_Widget
{
	const ID          = 'uams-widget-rss';
	const NAME        = 'UAMS RSS';
	const DESCRIPTION = 'Similar to the Wordpress RSS widget but allows a blurb before the RSS feed is listed.';
	const ITEMS       = 10;

	private static $WIDGET_DEFAULTS = array(
		'url'          => '',
		'title'        => '',
		'text'         => '',
		'items'        => 10,
		'show_summary' => true,
		'show_author'  => true,
		'show_date'    => true,
		'show_image'   => true,
	);

	private static $SHORTCODE_DEFAULTS = array(
		'url'        => null,
		'number'     => 5,
		'title'      => null,
		'heading'    => 'h3',
		'span'       => 4,
		'show_image' => true,
		'show_date'  => true,
		'show_more'  => true,
		'show_desc'  => false,
		'has_blurb'  => false,
		'more'       => null,
	);

	public function __construct()
	{
		add_shortcode( 'rss', array( $this, 'uams_rss_shortcode' ) );

		parent::__construct(
			self::ID,
			__( self::NAME, 'uams' ),
			array(
				'description' => __( self::DESCRIPTION, 'uams' ),
				'classname'   => self::ID,
			)
		);
	}

	public function form( $instance )
	{
		$inputs = wp_parse_args( (array) $instance, self::$WIDGET_DEFAULTS );

		$title     = esc_attr( $inputs['title'] );
		$text      = esc_textarea( $inputs['text'] );
		$url       = esc_url( $inputs['url'] );
		$items     = ( $inputs['items'] < 1 || 20 < $inputs['items'] ) ? self::ITEMS : (int) $inputs['items'];
		$show_date = ! empty( $inputs['show_date'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Give the feed a title (optional):', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Featured blurb:', 'uams' ); ?></label>
			<textarea class="widefat" style="resize:vertical" rows="8" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo $text; ?></textarea>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php _e( 'Enter the RSS feed URL here:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="text" value="<?php echo $url; ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php _e( 'Number of items to display:', 'uams' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>">
				<?php
				for ( $i = 1; $i <= 20; ++$i ) {
					echo "<option value='$i' " . selected( $items, $i, false ) . ">$i</option>";
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>"><?php _e( 'Show the date?', 'uams' ); ?></label>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_date' ) ); ?>" <?php checked( $show_date, true ); ?> />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance                 = array();
		$instance['url']          = esc_url_raw( strip_tags( $new_instance['url'] ?? '' ) );
		$instance['title']        = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['items']        = ! empty( $new_instance['items'] ) ? (int) $new_instance['items'] : self::ITEMS;
		$instance['show_image']   = ! empty( $new_instance['show_image'] ) ? 1 : 0;
		$instance['show_summary'] = ! empty( $new_instance['show_summary'] ) ? 1 : 0;
		$instance['show_author']  = ! empty( $new_instance['show_author'] ) ? 1 : 0;
		$instance['show_date']    = ! empty( $new_instance['show_date'] );

		if ( current_user_can( 'unfiltered_html' ) ) {
			$instance['text'] = $new_instance['text'] ?? '';
		} else {
			$instance['text'] = wp_kses_post( $new_instance['text'] ?? '' );
		}

		return $instance;
	}

	public function widget( $args, $instance )
	{
		$title     = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$text      = $instance['text'] ?? '';
		$url       = $instance['url'] ?? '';
		$items     = ! empty( $instance['items'] ) ? (int) $instance['items'] : self::ITEMS;
		$show_date = ! empty( $instance['show_date'] );

		$content = '<span></span>';

		if ( ! empty( $title ) ) {
			$before_title = $args['before_title'] ?? '<h3>';
			$after_title  = $args['after_title'] ?? '</h3>';
			$content     .= $before_title . esc_html( $title ) . $after_title;
		}

		$content .= empty( $text ) ? '' : '<div class="featured">' . $text . '</div>';

		$date_string = $show_date ? 'true' : 'false';
		$has_blurb   = empty( $text ) ? 'false' : 'true';

		$content .= do_shortcode( "[rss url=\"{$url}\" number=\"{$items}\" show_date=\"{$date_string}\" has_blurb=\"{$has_blurb}\"]" );

		echo ( $args['before_widget'] ?? '' ) . $content . ( $args['after_widget'] ?? '' );
	}

	public function uams_rss_shortcode( $atts )
	{
		$params = shortcode_atts( self::$SHORTCODE_DEFAULTS, $atts );

		if ( empty( $params['url'] ) || is_feed() ) {
			return '';
		}

		$has_blurb = filter_var( $params['has_blurb'], FILTER_VALIDATE_BOOLEAN );
		$content   = $has_blurb ? '<span></span>' : '';

		$rss = fetch_feed( wp_specialchars_decode( $params['url'] ) );

		if ( ! is_wp_error( $rss ) ) {
			$url       = empty( $params['more'] ) ? $rss->get_permalink() : $params['more'];
			$maxitems  = (int) $params['number'];
			$rss_items = $rss->get_items( 0, $maxitems );

			$content .= '<ul class="uams-widget-rss">';

			foreach ( $rss_items as $item ) {
				$item_title = $item->get_title();
				$link       = $item->get_link();
				$attr       = esc_attr( strip_tags( $item_title ) );

				$enclosure = $item->get_enclosure();
				$src       = ( $enclosure && isset( $enclosure->link ) ) ? $enclosure->link : '';

				$image = '';
				if ( ! empty( $src ) && 'false' !== (string) $params['show_image'] ) {
					$image = "<a class='widget-thumbnail' href='" . esc_url( $link ) . "' title='$attr'><img src='" . esc_url( $src ) . "' alt='$attr' /></a>";
				}

				$date = '';
				if ( 'false' !== (string) $params['show_date'] ) {
					$raw_date = $item->get_date();
					if ( $raw_date ) {
						$date = human_time_diff( strtotime( $raw_date ) ) . ' ago';
					}
				}

				$desc = '';
				if ( 'true' === (string) $params['show_desc'] ) {
					$desc = $item->get_description();
					if ( 'false' !== (string) $params['show_date'] ) {
						$desc = "<br style='line-height:2;'>" . $desc;
					}
				}

				$rendered_title = "<a class='widget-link' href='" . esc_url( $link ) . "' title='$attr'>$attr<span>$date$desc</span></a>";

				$content .= "<li>{$image}{$rendered_title}</li>";
			}

			$content .= '</ul>';

			if ( 'false' !== (string) $params['show_more'] && ! empty( $url ) ) {
				$content .= '<a class="widget-more more" href="' . esc_url( $url ) . '">More</a>';
			}
		}

		return $content;
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_RSS' );
} );