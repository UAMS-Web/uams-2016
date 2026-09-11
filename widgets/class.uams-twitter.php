<?php

//
//  Twitter widget
//
//  Uses the Twitter 1.1 API requiring oAuth authentication just
//  to read tweets. Because of the new Twitter request limit this
//  widget caches a user's latest tweets for a minute.
//

if ( defined( 'TWITTER_OAUTH_TOKEN' ) ) :

class UAMS_Widget_Twitter extends WP_Widget
{
	const ID          = 'uams-twitter-feed';
	const SHORTCODE   = 'twitter';
	const NAME        = 'UAMS Twitter Feed';
	const DESCRIPTION = 'Display your latest tweets';
	const CLASSNAME   = 'twitter-feed-widget';

	const URL           = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
	const AUTHOR_URL    = 'https://api.twitter.com/1.1/users/show.json';
	const REQUESTMETHOD = 'GET';
	const GETFIELD      = '?include_entities=true&include_rts=true&screen_name=%s&count=%u';
	const RETWEET_TEXT  = '<small>Retweeted by <a href="//twitter.com/%s"> @%s</a></small>';

	const COUNT   = 5;
	const ACCOUNT = 'twitter';
	const EXPIRES = 60;

	public static $SETTINGS = array(
		'oauth_access_token'        => TWITTER_OAUTH_TOKEN,
		'oauth_access_token_secret' => TWITTER_OAUTH_TOKEN_SECRET,
		'consumer_key'              => TWITTER_CONSUMER_KEY,
		'consumer_secret'           => TWITTER_CONSUMER_SECRET,
	);

	public function __construct()
	{
		parent::__construct(
			self::ID,
			__( self::NAME, 'uams' ),
			array(
				'description' => __( self::DESCRIPTION, 'uams' ),
				'classname'   => self::CLASSNAME,
			)
		);

		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
	}

	public function form( $instance )
	{
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : self::NAME;
		$name  = isset( $instance['name'] ) ? esc_attr( $instance['name'] ) : self::ACCOUNT;
		$count = isset( $instance['count'] ) ? esc_attr( $instance['count'] ) : self::COUNT;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'name' ) ); ?>"><?php _e( 'Twitter screen name:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'name' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'name' ) ); ?>" type="text" value="<?php echo esc_attr( $name ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php _e( 'Number of tweets to show:', 'uams' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="text" value="<?php echo esc_attr( $count ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['name']  = sanitize_text_field( $new_instance['name'] ?? '' );
		$instance['count'] = ! empty( $new_instance['count'] ) ? absint( $new_instance['count'] ) : self::COUNT;

		return $instance;
	}

	public function shortcode( $atts )
	{
		$atts = shortcode_atts(
			array(
				'title' => '',
				'name'  => self::ACCOUNT,
				'count' => self::COUNT,
			),
			$atts,
			'twitter'
		);

		$tweets = $this->getLatestTweets( $atts['name'], $atts['count'] );

		if ( ! is_array( $tweets ) || empty( $tweets ) ) {
			return '';
		}

		$output = '<div class="widget uams-twitter">';

		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h2 class="widgettitle">' . esc_html( $atts['title'] ) . '</h2>';
		}

		$output .= '<div class="twitter-feed" data-name="' . esc_attr( $atts['name'] ) . '" data-count="' . esc_attr( $atts['count'] ) . '">';

		foreach ( $tweets as $tweet ) {
			$tweet = (object) $tweet;
			$author = esc_attr( $tweet->author ?? '' );
			$img    = esc_url( $tweet->img ?? '' );
			$text   = $tweet->text ?? '';
			$rt     = $tweet->retweet ?? '';

			$output .= '<div class="tweet">';
			$output .= '<a href="//twitter.com/' . $author . '"><img src="' . $img . '" alt="' . $author . '"/></a>';
			$output .= '<p><a href="//twitter.com/' . $author . '"><span>@' . esc_html( $author ) . '</span></a> ' . $text . ' ' . $rt . '</p>';
			$output .= '</div>';
		}

		$output .= '<a class="more" href="//twitter.com/' . esc_attr( $atts['name'] ) . '">More</a>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	public function widget( $args, $instance )
	{
		$title = ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : self::COUNT;
		$name  = ! empty( $instance['name'] ) ? esc_attr( $instance['name'] ) : self::ACCOUNT;

		echo $args['before_widget'] ?? '';
		echo do_shortcode( sprintf( '[twitter title="%s" count="%d" name="%s"]', $title, $count, $name ) );
		echo $args['after_widget'] ?? '';
	}

	private function getLatestTweets( $name = 'uams', $count = 5 )
	{
		$count         = absint( $count );
		$transientName = 'twitter-feed-' . sanitize_key( $name ) . '-' . $count;
		$cached        = get_transient( $transientName );

		if ( false === $cached ) {
			if ( ! class_exists( 'TwitterAPIExchange' ) ) {
				return array();
			}

			$parameters = sprintf( self::GETFIELD, rawurlencode( $name ), $count );

			$twitter = new TwitterAPIExchange( self::$SETTINGS );
			$twitter->setGetfield( $parameters )
					->buildOauth( self::URL, self::REQUESTMETHOD );

			$tweets = json_decode( $twitter->performRequest() );
			$latest = array();

			if ( is_array( $tweets ) ) {
				foreach ( $tweets as $index => $tweet ) {
					$hasAuthor = ( isset( $tweet->entities->user_mentions ) && count( $tweet->entities->user_mentions ) > 0 );
					$retweet   = ( isset( $tweet->text ) && 0 === strpos( $tweet->text, 'RT' ) );

					$latest[ $index ]['author'] = $retweet ? ( $tweet->entities->user_mentions[0]->screen_name ?? '' ) : ( $tweet->user->screen_name ?? '' );

					$img_url = $tweet->user->profile_image_url_https ?? '';
					if ( $hasAuthor && ! empty( $latest[ $index ]['author'] ) ) {
						$twitter->setGetfield( '?screen_name=' . rawurlencode( $latest[ $index ]['author'] ) )
								->buildOauth( self::AUTHOR_URL, self::REQUESTMETHOD );

						$user = json_decode( $twitter->performRequest() );
						if ( ! empty( $user->profile_image_url_https ) ) {
							$img_url = $user->profile_image_url_https;
						}
					}

					$latest[ $index ]['img']     = $img_url;
					$latest[ $index ]['text']    = isset( $tweet->text ) ? $this->formatText( $tweet->text ) : '';
					$latest[ $index ]['retweet'] = ( $retweet && ! empty( $tweet->user->screen_name ) ) ? sprintf( self::RETWEET_TEXT, esc_attr( $tweet->user->screen_name ), esc_html( $tweet->user->screen_name ) ) : '';
				}
			}

			set_transient( $transientName, wp_json_encode( $latest ), self::EXPIRES );
			return $latest;
		}

		$decoded = json_decode( $cached, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private function formatText( $text )
	{
		if ( empty( $text ) || ! is_string( $text ) ) {
			return '';
		}

		$text = preg_replace(
			'/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&~\?\/.=]+/',
			'<a href="$0">$0</a>',
			$text
		);

		$text = preg_replace_callback(
			'/[#]+[A-Za-z0-9-_]+/',
			array( $this, 'encodeHashTag' ),
			(string) $text
		);

		$text = preg_replace_callback(
			'/[@]+[A-Za-z0-9-_]+/',
			array( $this, 'normalizeScreenName' ),
			(string) $text
		);

		return $text;
	}

	private function encodeHashTag( $hashTag )
	{
		$tag = isset( $hashTag[0] ) ? $hashTag[0] : '';
		return '<a href="//twitter.com/search?q=' . urlencode( $tag ) . '"> ' . esc_html( $tag ) . ' </a>';
	}

	private function normalizeScreenName( $screenname )
	{
		$name = isset( $screenname[0] ) ? str_replace( '@', '', $screenname[0] ) : '';
		return '<a href="//twitter.com/' . esc_attr( $name ) . '">' . esc_html( isset( $screenname[0] ) ? $screenname[0] : '' ) . '</a>';
	}
}

if ( file_exists( get_template_directory() . '/assets/frameworks/TwitterAPIExchange.php' ) ) {
	require_once get_template_directory() . '/assets/frameworks/TwitterAPIExchange.php';
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Widget_Twitter' );
} );

endif;
