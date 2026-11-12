<?php

//   #UAMS Horizontal Rule Widget
//   This widget styles custom intro text.

/**
 * @extends WP_Widget<array<string, mixed>>
 */
class UAMS_Intro_Text extends WP_Widget
{
    const ID = 'uams-intro-text';

    const TITLE = 'UAMS Intro Text';

    const DESCRIPTION = 'Italicized block of intro text.';

    public function __construct()
    {
        add_shortcode('intro', [$this, 'intro_shortcode']);

        parent::__construct(
            self::ID,
            __(self::TITLE, 'uams'),
            [
                'description' => __(self::DESCRIPTION, 'uams'),
                'classname' => self::ID,
            ]
        );
    }

    /**
     * @param  array<string, string>  $args  The sidebar arguments. A key can be missing when the widget renders outside a sidebar.
     */
    public function widget($args, $instance): void
    {
        $intro_content = ! empty($instance['introContent']) ? $instance['introContent'] : '';

        if (empty($intro_content)) {
            return;
        }

        $before_widget = $args['before_widget'] ?? '';
        $after_widget = $args['after_widget'] ?? '';

        echo $before_widget;
        echo '<p class="intro">'.esc_html($intro_content).'</p>';
        echo $after_widget;
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
        return ['introContent' => sanitize_text_field($new_instance['introContent'] ?? '')];
    }

    public function form($instance): void
    {
        $intro_content = isset($instance['introContent']) ? esc_attr($instance['introContent']) : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('introContent')); ?>"><?php _e('Intro text:', 'uams'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('introContent')); ?>" name="<?php echo esc_attr($this->get_field_name('introContent')); ?>" type="text" value="<?php echo $intro_content; ?>" />
        </p>
        <?php
    }

    /**
     * Render the intro text shortcode.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @param  string|null  $content  The enclosed content.
     * @return string
     */
    public function intro_shortcode($atts, $content = null)
    {
        $content = trim((string) $content);

        return ! empty($content) ? sprintf('<p class="intro">%s</p>', esc_html($content)) : '';
    }
}

add_action('widgets_init', function (): void {
    register_widget('UAMS_Intro_Text');
});
