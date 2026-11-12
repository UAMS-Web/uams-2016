<?php

/**
 * Contact Card widget
 *
 * List of contacts
 *
 * @extends WP_Widget<array<string, mixed>>
 */
class UAMS_Widget_Contact extends WP_Widget
{
    const DEFAULT_LINK_TEXT = 'More';

    public function __construct()
    {
        parent::__construct(
            'uams-contact-list',
            __('Contact list', 'uams'),
            [
                'description' => __('Display important contact information', 'uams'),
                'classname' => 'contact-widget',
            ]
        );
    }

    public static function scripts(): void
    {
        wp_enqueue_script('contact-card', get_template_directory_uri().'/assets/admin/js/widgets/uams.contact-widget.js', ['jquery'], null, true);
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_media();
    }

    /**
     * @param  array<string, string>  $args  The sidebar arguments. A key can be missing when the widget renders outside a sidebar.
     */
    public function widget($args, $instance): void
    {
        $title = apply_filters('widget_title', empty($instance['title']) ? __('List', 'uams') : $instance['title']);
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $amount = empty($instance['amount']) ? 1 : absint($instance['amount']);

        $person_names = [];
        $person_titles = [];
        $person_phones = [];
        $person_emails = [];

        for ($i = 1; $i <= $amount; $i++) {
            $person_names[$i - 1] = $instance['person_name'.$i] ?? '';
            $person_titles[$i - 1] = $instance['person_title'.$i] ?? '';
            $person_phones[$i - 1] = $instance['person_phone'.$i] ?? '';
            $person_emails[$i - 1] = $instance['person_email'.$i] ?? '';
        }

        echo $args['before_widget'];
        ?>

		<div class="contact-widget-inner">
			<span>
				<?php if (! empty($title)) { ?>
					<h2 class="widgettitle"><?php echo esc_html($title); ?></h2>
				<?php } ?>
			</span>

			<?php
            foreach ($person_names as $num => $person_name) {
                if (! empty($person_name)) {
                    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                    // @phpstan-ignore argument.type
                    echo '<h3 class="person-name">'.esc_html($person_name).'</h3>';
                    if (! empty($person_titles[$num])) {
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore argument.type
                        echo '<p class="person-title">'.esc_html($person_titles[$num]).'</p>';
                    }
                    if (! empty($person_phones[$num])) {
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore argument.type, argument.type
                        echo '<p><a href="'.esc_attr('tel:'.preg_replace('/[^0-9+]/', '', $person_phones[$num])).'" class="person-phone">'.esc_html($person_phones[$num]).'</a></p>';
                    }
                    if (! empty($person_emails[$num])) {
                        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                        // @phpstan-ignore argument.type, argument.type
                        echo '<a href="'.esc_attr('mailto:'.antispambot($person_emails[$num])).'" class="person-email">'.esc_html(antispambot($person_emails[$num])).'</a>';
                    }
                }
            }
        ?>
		</div><!-- /.contact-widget-inner -->

		<?php
        echo $args['after_widget'];
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
        $instance = [];
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore argument.type
        $amount = ! empty($new_instance['amount']) ? absint($new_instance['amount']) : 1;
        $new_person = ! empty($new_instance['new_person']);

        $order = [];
        $position = [];

        if (isset($new_instance['position1'])) {
            for ($i = 1; $i <= $amount; $i++) {
                if (isset($new_instance['position'.$i]) && $new_instance['position'.$i] != -1) {
                    // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                    // @phpstan-ignore cast.int
                    $position[$i] = (int) $new_instance['position'.$i];
                } else {
                    $amount--;
                }
            }
            if ($position !== []) {
                asort($position);
                $order = array_keys($position);
                if ($new_person) {
                    $amount++;
                    $order[] = $amount;
                }
            }
        } elseif (! empty($new_instance['order'])) {
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $raw_order = explode(',', $new_instance['order']);
            foreach ($raw_order as $key => $order_str) {
                $num = strrpos($order_str, '-');
                if ($num !== false) {
                    $order[$key] = substr($order_str, $num + 1);
                } else {
                    $order[$key] = $order_str;
                }
            }
        }

        foreach ($order as $i => $item_num) {
            $idx = $i + 1;
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $instance['person_name'.$idx] = sanitize_text_field($new_instance['person_name'.$item_num] ?? '');
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $instance['person_title'.$idx] = sanitize_text_field($new_instance['person_title'.$item_num] ?? '');
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $instance['person_phone'.$idx] = sanitize_text_field($new_instance['person_phone'.$item_num] ?? '');
            // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
            // @phpstan-ignore argument.type
            $instance['person_email'.$idx] = sanitize_email($new_instance['person_email'.$item_num] ?? '');
        }

        $instance['amount'] = $amount;

        return $instance;
    }

    public function form($instance): void
    {
        $instance = wp_parse_args((array) $instance, ['title' => '', 'amount' => 1]);
        $title = esc_attr($instance['title']);
        $amount = empty($instance['amount']) ? 1 : absint($instance['amount']);

        $person_names = [];
        $person_titles = [];
        $person_phones = [];
        $person_emails = [];

        for ($i = 1; $i <= $amount; $i++) {
            $person_names[$i] = $instance['person_name'.$i] ?? '';
            $person_titles[$i] = $instance['person_title'.$i] ?? '';
            $person_phones[$i] = $instance['person_phone'.$i] ?? '';
            $person_emails[$i] = $instance['person_email'.$i] ?? '';
        }
        ?>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'uams'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<ul class="uams-contact-instructions">
			<li class="hide-if-no-js"><?php echo __('Reorder the list items by clicking and dragging the name.', 'uams'); ?></li>
			<li class="hide-if-no-js"><?php echo __("To remove an item, simply click the 'Remove' button.", 'uams'); ?></li>
			<li class="hide-if-js"><?php echo __("Reorder or delete an item by using the 'Position/Action' table below.", 'uams'); ?></li>
			<li class="hide-if-js"><?php echo __("To add a new item, check the 'Add New Item' box and save the widget.", 'uams'); ?></li>
		</ul>
		<div class="the-people">
			<div class="uams-contact-list">
				<?php foreach ($person_names as $num => $person_name) {
				    $p_name = esc_attr($person_name);
				    $p_title = esc_attr($person_titles[$num]);
				    $p_phone = esc_attr($person_phones[$num]);
				    $p_email = esc_attr($person_emails[$num]);
				    ?>
					<div id="<?php echo esc_attr($this->get_field_id((string) $num)); ?>" class="list-item">
						<h5 class="moving-handle"><span class="number"><?php echo $num; ?></span>. <span class="person-title"><?php echo $p_name; ?></span><a class="uams-contact-action hide-if-no-js"></a></h5>
						<div class="uams-contact-edit-item">
							<label for="<?php echo esc_attr($this->get_field_id('person_name'.$num)); ?>"><?php echo __('Name:', 'uams'); ?></label>
							<input class="widefat" id="<?php echo esc_attr($this->get_field_id('person_name'.$num)); ?>" name="<?php echo esc_attr($this->get_field_name('person_name'.$num)); ?>" type="text" value="<?php echo $p_name; ?>" />

							<label for="<?php echo esc_attr($this->get_field_id('person_title'.$num)); ?>"><?php echo __('Title:', 'uams'); ?></label>
							<input class="widefat" id="<?php echo esc_attr($this->get_field_id('person_title'.$num)); ?>" name="<?php echo esc_attr($this->get_field_name('person_title'.$num)); ?>" type="text" value="<?php echo $p_title; ?>" />

							<label for="<?php echo esc_attr($this->get_field_id('person_phone'.$num)); ?>"><?php echo __('Phone:', 'uams'); ?></label>
							<input class="widefat" id="<?php echo esc_attr($this->get_field_id('person_phone'.$num)); ?>" name="<?php echo esc_attr($this->get_field_name('person_phone'.$num)); ?>" type="text" value="<?php echo $p_phone; ?>" />

							<label for="<?php echo esc_attr($this->get_field_id('person_email'.$num)); ?>"><?php echo __('Email:', 'uams'); ?></label>
							<input class="widefat" id="<?php echo esc_attr($this->get_field_id('person_email'.$num)); ?>" name="<?php echo esc_attr($this->get_field_name('person_email'.$num)); ?>" type="text" value="<?php echo $p_email; ?>" />

							<a class="uams-contact-delete remove button hide-if-no-js"><?php echo __('Remove', 'uams'); ?></a><br/>
						</div>
					</div>
				<?php } ?>
			</div>

			<div class="uams-contact-row hide-if-no-js">
				<a class="uams-contact-add button button-primary"><?php echo __('Add Item', 'uams'); ?></a>
			</div>

			<input type="hidden" id="<?php echo esc_attr($this->get_field_id('amount')); ?>" class="amount" name="<?php echo esc_attr($this->get_field_name('amount')); ?>" value="<?php echo $amount; ?>" />
			<input type="hidden" id="<?php echo esc_attr($this->get_field_id('order')); ?>" class="order" name="<?php echo esc_attr($this->get_field_name('order')); ?>" value="<?php echo esc_attr(implode(',', range(1, max(1, $amount)))); ?>" />
		</div>
		<?php
    }
}

add_action('widgets_init', function (): void {
    register_widget('UAMS_Widget_Contact');
});
add_action('admin_enqueue_scripts', ['UAMS_Widget_Contact', 'scripts']);
