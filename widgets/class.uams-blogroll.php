<?php

class UAMS_Blogroll extends WP_Widget
{
    const NAME = 'UAMS Blogroll';

    const ID = 'blogroll';

    const CLASSNAME = 'uams-blogroll';

    const DESCRIPTION = 'Pull and display your sites blog posts';

    // Coverage attributes no line to a constant default, so its mutants are reported uncovered, not
    // surviving. The widget and form tests assert the fallback of 2.
    // @pest-mutate-ignore: IncrementInteger,DecrementInteger
    const LIMIT = 2;

    public function __construct()
    {
        parent::__construct(
            self::ID,
            __(self::NAME, 'uams'),
            [
                'classname' => self::CLASSNAME,
                'description' => __(self::DESCRIPTION, 'uams'),
            ]
        );

        add_shortcode(self::ID, [$this, 'shortcode']);
    }

    public function form($instance): void
    {
        $title = empty($instance['title']) ? self::NAME : esc_attr($instance['title']);
        $number = empty($instance['number']) ? self::LIMIT : absint($instance['number']);
        $style = isset($instance['radio_style']) ? esc_attr($instance['radio_style']) : 'default';
        ?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'uams'); ?></label>
			<input id="<?php echo esc_attr($this->get_field_id('title')); ?>" class="widefat" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php _e('Number of posts to show:', 'uams'); ?></label>
			<input id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" type="text" value="<?php echo esc_attr($number); // @phpstan-ignore argument.type (core accepts the integer limit)?>" size="3">
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_default">
				<input id="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_default" type="radio" name="<?php echo esc_attr($this->get_field_name('radio_style')); ?>" value="default" <?php checked($style, 'default'); ?> />
				Default style
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_card">
				<input id="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_card" type="radio" name="<?php echo esc_attr($this->get_field_name('radio_style')); ?>" value="card" <?php checked($style, 'card'); ?> />
				Card style
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_mini">
				<input id="<?php echo esc_attr($this->get_field_id('radio_style')); ?>_mini" type="radio" name="<?php echo esc_attr($this->get_field_name('radio_style')); ?>" value="mini" <?php checked($style, 'mini'); ?> />
				Mini style
			</label>
		</p>
		<?php
    }

    public function update($new_instance, $instance)
    {
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['number'] = empty($new_instance['number']) ? self::LIMIT : absint($new_instance['number']);
        $instance['radio_style'] = empty($new_instance['radio_style']) ? 'default' : sanitize_text_field($new_instance['radio_style']);

        return $instance;
    }

    /**
     * @param  array<string, string>  $args  The sidebar arguments. A key can be missing when the widget renders outside a sidebar.
     */
    public function widget($args, $instance): void
    {
        $title = ! empty($instance['title']) ? $instance['title'] : self::NAME;
        $number = ! empty($instance['number']) ? absint($instance['number']) : self::LIMIT;
        $radio_style = ! empty($instance['radio_style']) ? esc_attr($instance['radio_style']) : 'default';

        $title = apply_filters('widget_title', $title);

        echo $args['before_widget'];
        if (! empty($title)) {
            echo '<h2>'.esc_html($title).'</h2>';
        }

        echo do_shortcode('['.self::ID.' number='.$number.' style='.$radio_style.'/]');

        echo $args['after_widget'];
    }

    public function shortcode($atts)
    {
        $params = shortcode_atts(
            [
                'excerpt' => 'true',
                'trim' => 'false',
                'image' => 'hide',
                'author' => 'show',
                'titletag' => 'h2',
                'post_type' => 'post',
                'number' => 5,
                'category' => '',
                'category_name' => '',
                'mini' => false,
                'style' => 'default',
                'date' => 'show',
            ],
            $atts
        );

        // wordpress-stubs shape shortcode_atts() to the defaults above, so PHPStan reads the key as never present; the check is
        // kept as written.
        // @phpstan-ignore function.impossibleType
        if (! array_key_exists('numberposts', $params)) {
            $params['numberposts'] = $params['number'];
        }

        // The arguments come from shortcode_atts() plus the shortcode's own keys, which PHPStan cannot narrow to the stub's get_posts() shape.
        // @phpstan-ignore argument.type
        $posts = get_posts($params);

        $params = (object) $params;
        $mini = $params->mini;
        $style = $params->style;

        if ($style === 'mini') {
            $mini = true;
        }

        $html = '';

        foreach ($posts as $post) {
            $link = function_exists('uams_get_permalink') ? uams_get_permalink($post->ID) : get_permalink($post->ID);

            $excerpt = '';
            if ($this->is_true($params->excerpt)) {
                $excerpt = has_excerpt($post->ID) ? $post->post_excerpt : apply_filters('widget_text', $post->post_content);

                if ($this->is_true($params->trim)) {
                    $excerpt = wp_trim_words($excerpt);
                }

                $excerpt = wpautop($excerpt);
            }

            $image = '';
            $class = '';
            if ($this->is_true($params->image)) {
                $image = get_the_post_thumbnail(
                    $post->ID,
                    'thumbnail',
                    [
                        'alt' => get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true),
                    ]
                );
                $class = ' class="pull-left"';
            }

            $author = $this->is_true($params->author) ? '<p class="author-info">'.esc_html(get_the_author_meta('display_name', $post->post_author)).'</p>' : '';
            $author_mini = $this->is_true($params->author) ? esc_html(get_the_author_meta('display_name', $post->post_author)) : '';

            $date = '';
            if ($this->is_true($params->date)) {
                $date = get_the_time(get_option('date_format'), $post->ID);
            }

            if (! empty($author_mini) && ! empty($date)) {
                $byline = sprintf('<small>%s | %s</small>', $date, $author_mini);
                $card_byline = sprintf('<p><small>%s | %s</small></p>', $date, $author_mini);
            } elseif (empty($author_mini) && empty($date)) {
                $byline = '';
                $card_byline = '';
            } else {
                $byline = sprintf('<small>%s%s</small>', $date, $author_mini);
                $card_byline = sprintf('<p><small>%s%s</small></p>', $date, $author_mini);
            }

            if ($mini) {
                $html .= sprintf(
                    "<li><a class='widget-thumbnail' href='%s'>%s</a><a class='widget-link' href='%s'>%s<span>%s</span></a></li>",
                    esc_url($link),
                    $image,
                    esc_url($link),
                    esc_html($post->post_title),
                    $byline
                );
            } elseif ($style === 'card') {
                $html .= '<div class="cards-widget"><div class="default-card">';
                if (has_post_thumbnail($post->ID)) {
                    $img_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
                    if (! empty($img_src[0])) {
                        $html .= '<div class="card-image" style="background-image:url('.esc_url($img_src[0]).')"></div>';
                    }
                }
                $html .= '<div class="card-body"><h3>';
                if (! empty($link) && ! empty($post->post_title)) {
                    $html .= '<a href="'.esc_url($link).'" class="pic-title">';
                }
                $html .= esc_html($post->post_title);
                $html .= (! empty($link)) ? '</a>' : '';
                $html .= '</h3>';
                $html .= $card_byline;
                $html .= '<div class="card-excerpt">'.$excerpt.'</div>';
                if (! empty($link)) {
                    $html .= '<a href="'.esc_url($link).'" class="read-more uams-btn btn-sm btn-red">Read More</a>';
                } else {
                    $html .= '<br/>';
                }
                $html .= '</div></div></div>';
            } else {
                $html .= "<li><span><{$params->titletag}><a href=\"".esc_url($link).'">'.esc_html($post->post_title)."</a><span class=\"date\">{$date}</span></{$params->titletag}>{$author}<span{$class}>{$image}</span>{$excerpt}</span></li>";
            }
        }

        $miniclass = $mini ? '-mini' : '';
        $cardsclass = $style === 'card' ? 'cards' : '';

        return "<ul class=\"shortcode-blogroll{$miniclass} {$cardsclass}\">{$html}</ul>";
    }

    private function is_true($attribute): bool
    {
        return in_array($attribute, ['show', 'true'], true);
    }
}

add_action('widgets_init', function (): void {
    register_widget('UAMS_Blogroll');
});
