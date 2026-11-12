<?php

/**
 * From the Walker class.
 * This removes the classnames and adds accessibility tags
 */
#[AllowDynamicProperties]
class UAMS_Dropdowns_Walker_Menu extends Walker_Nav_Menu
{
    // Coverage attributes no line to a property default, so its mutant is reported uncovered, not
    // surviving. 'opens an empty current id before any item' asserts the value.
    // @pest-mutate-ignore: EmptyStringToNotEmpty
    private $CURRENT = '';

    public function __construct()
    {
        // Constructor stub preserved
    }

    public function start_lvl(&$output, $depth = 0, $args = null): void
    {
        $indent = str_repeat("\t", (int) $depth);
        if ($depth > 0) {
            $output .= "\n{$indent}<ul class=\"sub-menu\">";
        } else {
            $output .= "\n{$indent}<ul role=\"group\" id=\"menu-".esc_attr($this->CURRENT).'" aria-labelledby="'.esc_attr($this->CURRENT).'" aria-expanded="false" class="reddiedrops-menu">';
        }

        $output .= "\n";
    }

    public function end_lvl(&$output, $depth = 0, $args = null): void
    {
        $indent = str_repeat("\t", (int) $depth);
        $output .= "{$indent}</ul>\n";
    }

    public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output)
    {
        if (is_object($element)) {
            $element->has_children = ! empty($children_elements[$element->ID]);
        }

        // Walker::display_element() returns nothing in core, so this result is always null; the override
        // hands it on as it always has, and the walker test pins that hand-over.
        // @phpstan-ignore staticMethod.void, return.void
        return parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }

    /**
     * @param  object  $item  A navigation menu item as wp_setup_nav_menu_item() returns it.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void
    {
        $this->CURRENT = ! empty($item->post_name) ? $item->post_name : sanitize_title($item->title);
        $title = ! empty($item->title) ? $item->title : $item->post_title;
        $title = strip_tags((string) $title);

        $has_children = ! empty($item->has_children);
        $controls = ($depth === 0 && $has_children) ? ' aria-controls="menu-'.esc_attr($this->CURRENT).'" aria-expanded="false" aria-haspopup="true"' : '';
        $indent = ($depth) ? str_repeat("\t", (int) $depth) : '';

        $first_class = (! empty($item->classes) && is_array($item->classes)) ? reset($item->classes) : '';
        $classes = ($depth === 0) ? array_filter(['reddiedrops-item', $first_class]) : [];
        $class_names = implode(' ', apply_filters('nav_menu_css_class', $classes, $item));

        $li_classnames = ! empty($class_names) ? ' class="'.esc_attr($class_names).'"' : '';
        $li_attributes = ($depth === 0) ? ' ' : '';

        $output .= $indent.'<li '.$li_attributes.$li_classnames.'>';

        $attributes = ! empty($item->attr_title) ? ' title="'.esc_attr($item->attr_title).'"' : '';
        $attributes .= ! empty($item->description) ? ' title="'.esc_attr($title.' - '.strip_tags((string) $item->description)).'"' : '';
        $attributes .= ! empty($item->target) ? ' target="'.esc_attr($item->target).'"' : '';
        $attributes .= ! empty($item->xfn) ? ' rel="'.esc_attr($item->xfn).'"' : '';
        $attributes .= ! empty($item->url) ? ' href="'.esc_url($item->url).'"' : '';

        if ($depth === 0 && $has_children) {
            $attributes .= ' class="dropdown-toggle"';
        }

        if ($depth > 0) {
            $attributes .= ' tabindex="-1"';
        }

        $attributes .= ' title="'.esc_attr($title).'"';
        $attributes .= $controls;
        $attributes .= ' id="'.esc_attr($this->CURRENT).'"';

        $before = (is_object($args) && isset($args->before)) ? $args->before : '';
        $after = (is_object($args) && isset($args->after)) ? $args->after : '';
        $link_before = (is_object($args) && isset($args->link_before)) ? $args->link_before : '';
        $link_after = (is_object($args) && isset($args->link_after)) ? $args->link_after : '';

        $item_output = $before;
        $item_output .= '<a'.$attributes.'>';
        $item_output .= $link_before.apply_filters('the_title', $title, $item->ID).$link_after;
        $item_output .= '</a>';
        $item_output .= $after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}
