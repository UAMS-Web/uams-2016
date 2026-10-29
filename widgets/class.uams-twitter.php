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
		'consumer_key'               => TWITTER_CONSUMER_KEY,
		'consumer_secret'            => TWITTER_CONSUMER_SECRET,
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
			$atts
		);

		$tweets = $this->getLatestTweets( $atts['name'], $atts['count'] );

		if ( ! is_array( $tweets ) ) {
			return '';
		}

		$output = '<div class="widget uams-twitter">';

		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h2 class="widgettitle">' . esc_html( $atts['title'] ) . '</h2>';
		}

		$output .= '<div class="twitter-feed" data-name="' . esc_attr( $atts['name'] ) . '" data-count="' . esc_attr( $atts['count'] ) . '">';

		foreach ( $tweets as $tweet ) {
			$tweet = (object) $tweet;
			$output .= '<div class="tweet">';
			$output .= '<a href="//twitter.com/' . esc_attr( $tweet->author ) . '"><img src="' . esc_url( $tweet->img ) . '" alt="' . esc_attr( $tweet->author ) . '"/></a>';
			$output .= '<p><a href="//twitter.com/' . esc_attr( $tweet->author ) . '"><span>@' . esc_html( $tweet->author ) . '</span></a> ' . $tweet->text . ' ' . $tweet->retweet . '</p>';
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
		echo do_shortcode( sprintf( '[twitter title="%s" count="%s" name="%s"]', $title, $count, $name ) );
		echo $args['after_widget'] ?? '';
	}

	private function getLatestTweets( $name = 'uams', $count = 5 )
	{
		$transientName = 'twitter-feed-' . $name . '-' . $count;
		$cached        = get_transient( $transientName );

		if ( false === $cached ) {
			if ( ! class_exists( 'TwitterAPIExchange' ) ) {
				return array();
			}

			$parameters = sprintf( self::GETFIELD, $name, $count );

			$twitter = new TwitterAPIExchange( self::$SETTINGS );
			$twitter->setGetfield( $parameters )
					->buildOauth( self::URL, self::REQUESTMETHOD );

			$tweets = json_decode( $twitter->performRequest() );
			$latest = array();

			if ( is_array( $tweets ) ) {
				foreach ( $tweets as $index => $tweet ) {
					$hasAuthor = ( isset( $tweet->entities->user_mentions ) && count( $tweet->entities->user_mentions ) > 0 );
					$retweet   = ( isset( $tweet->text ) && 0 === strpos( $tweet->text, 'RT' ) );

					$latest[ $index ]['author'] = $retweet ? $tweet->entities->user_mentions[0]->screen_name : ( $tweet->user->screen_name ?? '' );

					$img_url = $tweet->user->profile_image_url_https ?? '';
					if ( $hasAuthor ) {
						$twitter->setGetfield( '?screen_name=' . $latest[ $index ]['author'] )
								->buildOauth( self::AUTHOR_URL, self::REQUESTMETHOD );

						$user = json_decode( $twitter->performRequest() );
						if ( ! empty( $user->profile_image_url_https ) ) {
							$img_url = $user->profile_image_url_https;
						}
					}

					$latest[ $index ]['img']     = $img_url;
					$latest[ $index ]['text']    = isset( $tweet->text ) ? $this->formatText( $tweet->text ) : '';
					$latest[ $index ]['retweet'] = ( $retweet && ! empty( $tweet->user->screen_name ) ) ? sprintf( self::RETWEET_TEXT, $tweet->user->screen_name, $tweet->user->screen_name ) : '';
				}
			}

			set_transient( $transientName, json_encode( $latest ), self::EXPIRES );
			return $latest;
		}

		return json_decode( $cached );
	}

	private function formatText( $text )
	{
		$text = preg_replace(
			'/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&~\?\/.=]+/',
			'<a href="$0">$0</a>',
			$text
		);

		$text = preg_replace_callback(
			'/[#]+[A-Za-z0-9-_]+/',
			array( $this, 'encodeHashTag' ),
			$text
		);

		$text = preg_replace_callback(
			'/[@]+[A-Za-z0-9-_]+/',
			array( $this, 'normalizeScreenName' ),
			$text
		);

		return $text;
	}

	private function encodeHashTag( $hashTag )
	{
		return '<a href="//twitter.com/search?q=' . urlencode( $hashTag[0] ) . '"> ' . esc_html( $hashTag[0] ) . ' </a>';
	}

	private function normalizeScreenName( $screenname )
	{
		return '<a href="//twitter.com/' . esc_attr( str_replace( '@', '', $screenname[0] ) ) . '">' . esc_html( $screenname[0] ) . '</a>';
	}
}

if ( file_exists( get_template_directory() . '/assets/frameworks/TwitterAPIExchange.php' ) ) {
	require_once get_template_directory() . '/assets/frameworks/TwitterAPIExchange.php';
}

add_action( 'widgets_init', function() {
	register_widget( 'UAMS_Widget_Twitter' );
} );

endif;