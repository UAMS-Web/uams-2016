<?php

//
//  Twitter widget
//
//  Uses the Twitter 1.1 API requiring oAuth authentication just
//  to read tweets. Because of the new Twitter request limit this
//  widget caches a user's latest tweets for a minute.
//

if (defined('TWITTER_OAUTH_TOKEN')) {

    /**
     * @extends WP_Widget<array<string, mixed>>
     */
    class UAMS_Widget_Twitter extends WP_Widget
    {
        const ID = 'uams-twitter-feed';

        const SHORTCODE = 'twitter';

        const NAME = 'UAMS Twitter Feed';

        const DESCRIPTION = 'Display your latest tweets';

        const CLASSNAME = 'twitter-feed-widget';

        const URL = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

        const AUTHOR_URL = 'https://api.twitter.com/1.1/users/show.json';

        const REQUESTMETHOD = 'GET';

        const GETFIELD = '?include_entities=true&include_rts=true&screen_name=%s&count=%u';

        const RETWEET_TEXT = '<small>Retweeted by <a href="//twitter.com/%s"> @%s</a></small>';

        // Coverage attributes no line to a constant default, so its mutants are reported uncovered, not
        // surviving. The update, form and widget tests assert the fallback of 5.
        // @pest-mutate-ignore: IncrementInteger,DecrementInteger
        const COUNT = 5;

        const ACCOUNT = 'twitter';

        // Coverage attributes no line to a constant default, so its mutants are reported uncovered, not
        // surviving. The shortcode test asserts the 60 second cache.
        // @pest-mutate-ignore: IncrementInteger,DecrementInteger
        const EXPIRES = 60;

        /** @var array<string, string> */
        // Coverage attributes no line to a property default, so its mutants are reported uncovered, not
        // surviving. The test that checks the settings handed to the API wrapper asserts all four.
        // @pest-mutate-ignore: RemoveArrayItem
        public static $SETTINGS = [
            'oauth_access_token' => TWITTER_OAUTH_TOKEN,
            'oauth_access_token_secret' => TWITTER_OAUTH_TOKEN_SECRET,
            'consumer_key' => TWITTER_CONSUMER_KEY,
            'consumer_secret' => TWITTER_CONSUMER_SECRET,
        ];

        public function __construct()
        {
            parent::__construct(
                self::ID,
                __(self::NAME, 'uams'),
                [
                    'description' => __(self::DESCRIPTION, 'uams'),
                    'classname' => self::CLASSNAME,
                ]
            );

            add_shortcode(self::SHORTCODE, [$this, 'shortcode']);
        }

        public function form($instance): void
        {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $title = isset($instance['title']) ? esc_attr($instance['title']) : self::NAME;
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $name = isset($instance['name']) ? esc_attr($instance['name']) : self::ACCOUNT;
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $count = isset($instance['count']) ? esc_attr($instance['count']) : self::COUNT;
            ?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'uams'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('name')); ?>"><?php _e('Twitter screen name:', 'uams'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('name')); ?>" name="<?php echo esc_attr($this->get_field_name('name')); ?>" type="text" value="<?php echo esc_attr($name); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('count')); ?>"><?php _e('Number of tweets to show:', 'uams'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('count')); ?>" name="<?php echo esc_attr($this->get_field_name('count')); ?>" type="text" value="<?php echo esc_attr($count); // @phpstan-ignore argument.type?>" />
		</p>
		<?php
        }

        /**
         * Sanitize the widget settings on save.
         *
         * @param  array<string, mixed>  $new_instance  The submitted settings.
         * @param  array<string, mixed>  $old_instance  The previously saved settings.
         * @return array<string, mixed>
         */
        public function update($new_instance, $old_instance)
        {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type, argument.type, argument.type
            return ['title' => sanitize_text_field($new_instance['title'] ?? ''), 'name' => sanitize_text_field($new_instance['name'] ?? ''), 'count' => ! empty($new_instance['count']) ? absint($new_instance['count']) : self::COUNT];
        }

        /**
         * Render the latest tweets shortcode.
         *
         * @param  array<string, mixed>|string  $atts  The shortcode attributes.
         * @return string
         */
        public function shortcode($atts)
        {
            $atts = shortcode_atts(
                [
                    'title' => '',
                    'name' => self::ACCOUNT,
                    'count' => self::COUNT,
                ],
                // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
                // @phpstan-ignore argument.type
                $atts,
                'twitter'
            );

            $tweets = $this->getLatestTweets($atts['name'], $atts['count']);

            if ($tweets === []) {
                return '';
            }

            $output = '<div class="widget uams-twitter">';

            if (! empty($atts['title'])) {
                $output .= '<h2 class="widgettitle">'.esc_html($atts['title']).'</h2>';
            }

            // The stubs type this value as possibly false; the call is unchanged.
            // @phpstan-ignore argument.type
            $output .= '<div class="twitter-feed" data-name="'.esc_attr($atts['name']).'" data-count="'.esc_attr($atts['count']).'">';

            foreach ($tweets as $tweet) {
                $tweet = (object) $tweet;
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore argument.type
                $author = esc_attr($tweet->author ?? '');
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore argument.type
                $img = esc_url($tweet->img ?? '');
                $text = $tweet->text ?? '';
                $rt = $tweet->retweet ?? '';

                $output .= '<div class="tweet">';
                $output .= '<a href="//twitter.com/'.$author.'"><img src="'.$img.'" alt="'.$author.'"/></a>';
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore binaryOp.invalid, binaryOp.invalid
                $output .= '<p><a href="//twitter.com/'.$author.'"><span>@'.esc_html($author).'</span></a> '.$text.' '.$rt.'</p>';
                $output .= '</div>';
            }

            $output .= '<a class="more" href="//twitter.com/'.esc_attr($atts['name']).'">More</a>';
            $output .= '</div>';
            $output .= '</div>';

            return $output;
        }

        /**
         * @param  array<string, string>  $args  The sidebar arguments. A key can be missing when the widget renders outside a sidebar.
         */
        public function widget($args, $instance): void
        {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $title = ! empty($instance['title']) ? esc_attr($instance['title']) : '';
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $count = ! empty($instance['count']) ? absint($instance['count']) : self::COUNT;
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $name = ! empty($instance['name']) ? esc_attr($instance['name']) : self::ACCOUNT;

            echo $args['before_widget'] ?? '';
            echo do_shortcode(sprintf('[twitter title="%s" count="%d" name="%s"]', $title, $count, $name));
            echo $args['after_widget'] ?? '';
        }

        /**
         * Fetch the latest tweets for an account, from the cache when it holds them.
         *
         * @param  string  $name  The account screen name.
         * @param  int|string  $count  How many tweets to fetch.
         * @return array<int|string, array<string, string|null>>
         */
        private function getLatestTweets($name = 'uams', $count = 5): array
        {
            $count = absint($count);
            $transientName = 'twitter-feed-'.sanitize_key($name).'-'.$count;
            $cached = get_transient($transientName);

            if ($cached === false) {
                if (! class_exists('TwitterAPIExchange')) {
                    return [];
                }

                $parameters = sprintf(self::GETFIELD, rawurlencode($name), $count);

                $twitter = new TwitterAPIExchange(self::$SETTINGS);
                $twitter->setGetfield($parameters)
                    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                    // @phpstan-ignore method.nonObject
                    ->buildOauth(self::URL, self::REQUESTMETHOD);

                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore argument.type
                $tweets = json_decode($twitter->performRequest());
                $latest = [];

                if (is_array($tweets)) {
                    foreach ($tweets as $index => $tweet) {
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject, property.nonObject, argument.type
                        $hasAuthor = (isset($tweet->entities->user_mentions) && count($tweet->entities->user_mentions) > 0);
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject, argument.type
                        $retweet = (isset($tweet->text) && str_starts_with($tweet->text, 'RT'));

                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject, offsetAccess.nonOffsetAccessible, property.nonObject, property.nonObject, property.nonObject
                        $latest[$index]['author'] = $retweet ? ($tweet->entities->user_mentions[0]->screen_name ?? '') : ($tweet->user->screen_name ?? '');

                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject, property.nonObject
                        $img_url = $tweet->user->profile_image_url_https ?? '';
                        if ($hasAuthor && ! empty($latest[$index]['author'])) {
                            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                            // @phpstan-ignore argument.type
                            $twitter->setGetfield('?screen_name='.rawurlencode($latest[$index]['author']))
                                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                                // @phpstan-ignore method.nonObject
                                ->buildOauth(self::AUTHOR_URL, self::REQUESTMETHOD);

                            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                            // @phpstan-ignore argument.type
                            $user = json_decode($twitter->performRequest());
                            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                            // @phpstan-ignore property.nonObject
                            if (! empty($user->profile_image_url_https)) {
                                $img_url = $user->profile_image_url_https;
                            }
                        }

                        $latest[$index]['img'] = $img_url;
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject
                        $latest[$index]['text'] = isset($tweet->text) ? $this->formatText($tweet->text) : '';
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore property.nonObject, argument.type, argument.type
                        $latest[$index]['retweet'] = ($retweet && ! empty($tweet->user->screen_name)) ? sprintf(self::RETWEET_TEXT, esc_attr($tweet->user->screen_name), esc_html($tweet->user->screen_name)) : '';
                    }
                }

                set_transient($transientName, wp_json_encode($latest), self::EXPIRES);

                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore return.type
                return $latest;
            }

            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $decoded = json_decode($cached, true);

            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore return.type
            return is_array($decoded) ? $decoded : [];
        }

        /**
         * Link the URLs, mentions and hashtags in a tweet.
         *
         * @param  mixed  $text  The tweet text; anything but a non-empty string gives an empty string.
         */
        private function formatText($text): ?string
        {
            if (empty($text) || ! is_string($text)) {
                return '';
            }

            $text = preg_replace(
                '/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&~\?\/.=]+/',
                '<a href="$0">$0</a>',
                $text
            );

            // Patchwork drops a line break from a multi-line preg_replace_callback() call, which shifts the line numbers
            // coverage attributes to everything below it. Keep these two calls on one line each.
            $text = preg_replace_callback('/[#]+[A-Za-z0-9-_]+/', [$this, 'encodeHashTag'], (string) $text);

            return preg_replace_callback('/[@]+[A-Za-z0-9-_]+/', [$this, 'normalizeScreenName'], (string) $text);
        }

        /**
         * Link a hashtag to its Twitter search.
         *
         * @param  array<int, string>  $hashTag  The regular expression match.
         */
        private function encodeHashTag($hashTag): string
        {
            $tag = $hashTag[0] ?? '';

            return '<a href="//twitter.com/search?q='.urlencode($tag).'"> '.esc_html($tag).' </a>';
        }

        /**
         * Link an at-mention to the account page.
         *
         * @param  array<int, string>  $screenname  The regular expression match.
         */
        private function normalizeScreenName($screenname): string
        {
            $name = isset($screenname[0]) ? str_replace('@', '', $screenname[0]) : '';

            return '<a href="//twitter.com/'.esc_attr($name).'">'.esc_html($screenname[0] ?? '').'</a>';
        }
    }

    if (file_exists(get_template_directory().'/assets/frameworks/TwitterAPIExchange.php')) {
        require_once get_template_directory().'/assets/frameworks/TwitterAPIExchange.php';
    }

    add_action('widgets_init', function (): void {
        register_widget('UAMS_Widget_Twitter');
    });

}
