<?php

/*
 * This shortcode lists out subpages, their author if show byline on posts option is set,
 * their excerpts if excerpts are enabled, and a link to the subpage.
 *
 * [subpage-list link='link text' tilebox=boolean]
 */

class UAMS_SubpageList
{
    public function __construct()
    {
        add_shortcode('subpage-list', [$this, 'list_subpages']);
    }

    /**
     * Render the list of subpages.
     *
     * @param  array<string, mixed>|string  $atts  The shortcode attributes.
     * @return string The list markup.
     */
    public function list_subpages($atts): string
    {
        $attributes = (object) shortcode_atts([
            'link' => 'Read more',
            'tilebox' => false,
            // WordPress passes an empty string for a shortcode with no attributes, and shortcode_atts() casts it to an array.
            // @phpstan-ignore argument.type
        ], $atts, 'subpage-list');

        $attributes->tilebox = filter_var($attributes->tilebox, FILTER_VALIDATE_BOOLEAN);

        // The stubs list child_of but not parent, which get_pages() documents and honors.
        // @phpstan-ignore argument.type
        $subpages = get_pages([
            'parent' => get_the_ID(),
            'sort_column' => 'post_title',
        ]);

        $output = '';

        // get_pages() returns false when there are no pages; wordpress-stubs type it as an array.
        // @phpstan-ignore function.alreadyNarrowedType
        if (! empty($subpages) && is_array($subpages)) {
            $tiles = 0;
            foreach ($subpages as $page) {
                $permalink = get_permalink($page->ID);
                $title = esc_html($page->post_title);

                if (! $attributes->tilebox) {
                    // The stubs type this value as possibly false; the call is unchanged.
                    // @phpstan-ignore argument.type
                    $output .= sprintf("<h2><a href='%s'>%s</a></h2>", esc_url($permalink), $title);
                    if (get_option('show_byline_on_posts')) {
                        // The stubs type this value as possibly false; the call is unchanged.
                        // @phpstan-ignore argument.type
                        $author_name = get_the_author_meta('display_name', $page->post_author);
                        $output .= sprintf("<div class='author-info'><p class='author-desc'><small>%s</small></p></div>", esc_html($author_name));
                    }

                    $output .= sprintf('<p>%s</p>', wp_kses_post($page->post_excerpt));
                    if (! empty($attributes->link)) {
                        // The stubs type this value as possibly false; the call is unchanged.
                        // @phpstan-ignore argument.type
                        $output .= sprintf("<a class='uams-btn btn-sm' href='%s'>%s</a>", esc_url($permalink), esc_html($attributes->link));
                    }
                } else {
                    if ($tiles === 0) {
                        $output .= "<div class='box-outer'><div class='box two'>";
                    }

                    $output .= "<div class='tile'>";
                    $output .= sprintf('<div>%s</div>', get_the_post_thumbnail($page->ID, 'half'));
                    // The stubs type this value as possibly false; the call is unchanged.
                    // @phpstan-ignore argument.type
                    $output .= sprintf("<h3><a href='%s'>%s</a></h3>", esc_url($permalink), $title);
                    if (get_option('show_byline_on_posts')) {
                        // The stubs type this value as possibly false; the call is unchanged.
                        // @phpstan-ignore argument.type
                        $author_name = get_the_author_meta('display_name', $page->post_author);
                        $output .= sprintf("<div class='author-info'><p class='author-desc'><small>%s</small></p></div>", esc_html($author_name));
                    }

                    $output .= sprintf('<p>%s</p>', wp_kses_post($page->post_excerpt));
                    $output .= '</div>';
                    $tiles++;

                    if ($tiles === 2) {
                        $output .= '</div></div>';
                        $tiles = 0;
                    }
                }
            }

            if ($tiles === 1) {
                $output .= "<div class='tile empty'></div></div></div>";
            }
        }

        return $output;
    }
}

new UAMS_SubpageList;
