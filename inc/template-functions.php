<?php

//
// UAMS Dropdown Menus
//

if (! function_exists('uams_content_class')) {
    /**
     * Echo the class attribute for the content area.
     *
     * @param  string  $class  Extra class passed to the filter.
     */
    function uams_content_class($class = ''): void // @pest-mutate-ignore: EmptyStringToNotEmpty
    {
        echo 'class="'.esc_attr(implode(' ', get_uams_content_class($class))).'"';
    }
}

if (! function_exists('get_uams_content_class')) {
    /**
     * Get the classes for the content area.
     *
     * @param  string  $class  Extra class passed to the filter.
     * @return array<int, string> The content classes.
     */
    function get_uams_content_class($class = '') // @pest-mutate-ignore: EmptyStringToNotEmpty
    {
        $classes = ['uams-content'];
        if (uams_has_sidebar()) {
            $classes[] = 'col-md-8';
        } else {
            $classes[] = 'col-md-12';
        }

        $classes = array_map(esc_attr(...), $classes);

        return apply_filters('uams_content_class', $classes, $class);
    }
}

if (! function_exists('uams_has_sidebar')) {
    function uams_has_sidebar(): bool
    {
        global $post;

        if (is_404()) {
            return false;
        }

        $post_id = ($post instanceof WP_Post) ? $post->ID : get_the_ID();
        $format = $post_id ? get_post_format($post_id) : false;

        // PHPStan reads these calls as always false after the checks above; the template tag test pins each branch, so the condition is kept as written.
        // @phpstan-ignore booleanOr.rightAlwaysFalse
        return ($format !== 'gallery') || is_archive() || is_search() || is_404();
    }
}

// The Unit tests load the real dropdown classes and make class_exists() answer for them, so the
// `UAMS_Dropdowns::LOCATION` fallbacks below read the same string whichever branch runs.
if (! function_exists('uams_dropdowns')) {
    function uams_dropdowns(): void
    {
        $location = class_exists('UAMS_Dropdowns') ? UAMS_Dropdowns::LOCATION : 'white-bar';
        $walker = class_exists('UAMS_Dropdowns_Walker_Menu') ? new UAMS_Dropdowns_Walker_Menu : '';

        echo '<nav id="reddiedrops" aria-label="Main menu"><div class="reddiedrops-inner container" role="application">';

        // wordpress-stubs type fallback_cb as callable|false; core also accepts an empty string, the long-standing way to
        // switch the fallback off.
        // @phpstan-ignore argument.type
        wp_nav_menu([
            'theme_location' => $location,
            'container' => false,
            'menu_class' => 'reddiedrops-nav',
            'fallback_cb' => '',
            'walker' => $walker,
        ]);

        echo '</div></nav>';
    }
}

if (! function_exists('uams_sidebar_menu')) {
    function uams_sidebar_menu(): void
    {
        echo sprintf('<nav id="desktop-relative" aria-label="mobile menu that is not visible in the desktop version">%s</nav>', uams_list_pages());
    }
}

if (! function_exists('uams_sidebar_menu_mobile')) {
    function uams_sidebar_menu_mobile(): void
    {
        echo sprintf('<nav id="mobile-relative" aria-label="mobile menu">%s</nav>', uams_list_pages());
    }
}

if (! function_exists('uams_mobile_menu')) {
    function uams_mobile_menu(): void
    {
        echo sprintf('<nav id="mobile-reddiedrops" aria-label="mobile menu">%s</nav>', uams_list_mobile_pages());
    }
}

if (! function_exists('uams_mobile_front_page_menu')) {
    /**
     * Echo the mobile front page menu.
     *
     * @param  string  $class  Optional class to add to the nav element.
     */
    function uams_mobile_front_page_menu($class = ''): void // @pest-mutate-ignore: EmptyStringToNotEmpty
    {
        $spacer = '';
        if (! empty($class)) {
            $class = ' '.sanitize_html_class($class);
            $spacer = '<div id="spacer"></div>';
        }

        echo sprintf('<nav id="mobile-reddiedrops" class="frontpage%s" aria-label="relative">%s%s</nav>', $class, $spacer, uams_list_front_page_menu_items());
    }
}

if (! function_exists('uams_list_pages')) {
    /**
     * Build the page list for the current page.
     *
     * @param  bool  $mobile  Whether the list is for the mobile menu.
     * @return string The list markup.
     */
    function uams_list_pages($mobile = false): string // @pest-mutate-ignore: FalseToTrue
    {
        global $UAMS, $post;

        if (! ($post instanceof WP_Post)) {
            return '';
        }

        $parent = ! empty($post->post_parent) ? get_post($post->post_parent) : false;
        $parent_id = ($parent instanceof WP_Post) ? $parent->ID : 0;
        $parent_post_parent = ($parent instanceof WP_Post) ? $parent->post_parent : 0;

        if (! $mobile && ! get_children(['post_parent' => $post->ID, 'post_status' => 'publish']) && $parent_id === $post->ID) {
            return '';
        }

        // Read only by the code after the wp_list_pages() check below, which PHPStan reads as unreachable (wordpress-stubs type
        // that call as void; see the function.void entry in phpstan.neon.dist).
        // @phpstan-ignore variable.unused
        $toggle = $mobile ? '<button class="uams-mobile-menu-toggle">Menu</button>' : '';
        // Read only by the code after the wp_list_pages() check below, which PHPStan reads as unreachable (wordpress-stubs type
        // that call as void; see the function.void entry in phpstan.neon.dist).
        // @phpstan-ignore variable.unused
        $class = $mobile ? 'uams-mobile-menu' : 'uams-sidebar-menu';

        $siblings = get_pages([
            'parent' => $parent_post_parent,
            'post_type' => 'page',
            'exclude' => $parent_id ? [$parent_id] : [],
        ]);

        $ids = (! is_front_page() && is_array($siblings)) ? array_map(fn ($sibling) => $sibling->ID, $siblings) : [];

        $walker = (isset($UAMS) && is_object($UAMS) && isset($UAMS->SidebarMenuWalker)) ? $UAMS->SidebarMenuWalker : '';

        $pages = wp_list_pages([
            'title_li' => '<a href="'.esc_url(home_url('/')).'" title="Home" class="homelink">Home</a>',
            'child_of' => $parent_post_parent,
            'exclude_tree' => $ids,
            'depth' => 3,
            'echo' => false,
            'walker' => $walker,
        ]);

        // wordpress-stubs type wp_list_pages() as void for this call, so these checks read as decided; core returns the markup,
        // and the tests pin the empty and non-string results.
        // @phpstan-ignore booleanOr.alwaysTrue, empty.variable, function.alreadyNarrowedType
        if (empty($pages) || ! is_string($pages)) {
            return '';
        }

        // Unreachable only because of the void return type noted above.
        // @phpstan-ignore deadCode.unreachable
        $bool = str_contains($pages, 'child-page-existance-tester');

        return ($bool && ! is_search()) ? sprintf('%s<ul class="%s first-level">%s</ul>', $toggle, esc_attr($class), $pages) : '';
    }
}

if (! function_exists('uams_list_mobile_pages')) {
    function uams_list_mobile_pages(): string
    {
        if (! is_front_page()) {
            $isMenuEmpty = uams_list_pages(true);
            $alwaysMobile = get_option('use_main_menu_on_mobile');
            if (empty($isMenuEmpty) && $alwaysMobile) {
                return uams_list_front_page_menu_items();
            }

            return $isMenuEmpty;
        }

        $locations = get_nav_menu_locations();
        $location = class_exists('UAMS_Dropdowns') ? UAMS_Dropdowns::LOCATION : 'white-bar';

        if (empty($locations[$location])) {
            return '';
        }

        $menu = wp_get_nav_menu_object($locations[$location]);
        if (! $menu) {
            return '';
        }

        $items = wp_get_nav_menu_items($menu->term_id);
        // wp_get_nav_menu_items() returns false for a menu without items; wordpress-stubs type it as an array.
        // @phpstan-ignore function.alreadyNarrowedType
        if (empty($items) || ! is_array($items)) {
            return '';
        }

        $toggle = '<button class="uams-mobile-menu-toggle">Menu</button>';
        $ids = [];

        foreach ($items as $item) {
            if ((int) $item->menu_item_parent !== 0) {
                continue;
            }

            $ids[] = $item->object_id;
        }

        if ($ids === []) {
            return '';
        }

        global $UAMS;
        $walker = (isset($UAMS) && is_object($UAMS) && isset($UAMS->SidebarMenuWalker)) ? $UAMS->SidebarMenuWalker : '';

        // wordpress-stubs type 'include' as an array; core also accepts the comma-separated list of ids this passes.
        // @phpstan-ignore argument.type
        $pages = wp_list_pages([
            'title_li' => '<a href="'.esc_url(home_url('/')).'" title="Home" class="homelink">Home</a>',
            'include' => implode(',', $ids),
            'sort_order' => 'menu_order',
            'depth' => 1,
            'echo' => false,
            'walker' => $walker,
        ]);

        // Same void return type as above.
        // @phpstan-ignore ternary.alwaysFalse
        return $pages ? sprintf('%s<ul class="uams-mobile-menu first-level">%s</ul>', $toggle, $pages) : '';
    }
}

if (! function_exists('uams_list_front_page_menu_items')) {
    function uams_list_front_page_menu_items(): string
    {
        $location = class_exists('UAMS_Dropdowns') ? UAMS_Dropdowns::LOCATION : 'white-bar';
        $toggle = '<button class="uams-mobile-menu-toggle">Menu</button>';

        // wordpress-stubs type fallback_cb as callable|false; core also accepts an empty string, the long-standing way to
        // switch the fallback off.
        // @phpstan-ignore argument.type
        $items = wp_nav_menu([
            'title_li' => '<a href="'.esc_url(home_url('/')).'" title="Home" class="homelink">Home</a>',
            'theme_location' => $location,
            'depth' => 3,
            'container_class' => '',
            'menu_class' => '',
            'fallback_cb' => '',
            'echo' => false,
        ]);

        return $items ? sprintf('%s<ul class="uams-mobile-menu first-level">%s</ul>', $toggle, $items) : '';
    }
}

if (! function_exists('get_uams_breadcrumbs')) {
    function get_uams_breadcrumbs(): string
    {
        global $post;

        $post_id = ($post instanceof WP_Post) ? $post->ID : get_the_ID();
        $ancestors = $post_id ? array_reverse(get_post_ancestors($post_id)) : [];

        $site_title = get_bloginfo('name');
        $html = '<li><a href="http://www.uams.edu" title="University of Arkansas for Medical Sciences">Home</a></li>';
        $html .= '<li'.(is_front_page() ? ' class="current"' : '').'><a href="'.esc_url(home_url('/')).'" title="'.esc_attr($site_title).'">'.esc_html($site_title).'</a></li>';

        if (is_404()) {
            $html .= '<li class="current"><span>Ooops!</span></li>';
        } elseif (is_search()) {
            $html .= '<li class="current"><span>Search results for '.esc_html(get_search_query()).'</span></li>';
        } elseif (is_author()) {
            $author = get_queried_object();
            $name = ($author && isset($author->display_name)) ? $author->display_name : '';
            $html .= '<li class="current"><span> Author: '.esc_html($name).'</span></li>';
        } elseif (get_queried_object_id() === (int) get_option('page_for_posts') && get_option('page_for_posts')) {
            $html .= '<li class="current"><span> '.esc_html(get_the_title(get_queried_object_id())).' </span></li>';
        }

        if (is_category() || is_tax() || is_single() || is_post_type_archive()) {
            if (is_post_type_archive() && ! is_tax()) {
                // The stubs type this value as possibly false; the call is unchanged.
                // @phpstan-ignore argument.type
                $posttype = get_post_type_object(get_post_type());
                if ($posttype) {
                    $html .= '<li class="current"><span>'.esc_html($posttype->labels->menu_name).'</span></li>';
                }
            }

            if (is_category()) {
                $category = get_category(get_query_var('cat'));
                if ($category && ! is_wp_error($category)) {
                    $html .= '<li class="current"><span>'.esc_html(get_cat_name($category->term_id)).'</span></li>';
                }
            }

            if (is_tax() && ! is_post_type_archive()) {
                $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
                $tax = get_taxonomy(get_query_var('taxonomy'));
                if ($tax && $term && ! is_wp_error($term)) {
                    $html .= '<li class="current"><span>'.esc_html($tax->labels->name).': '.esc_html($term->name).'</span></li>';
                }
            }

            if (is_tax() && is_post_type_archive()) {
                $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
                if ($term && ! is_wp_error($term)) {
                    $html .= '<li class="current"><span>'.esc_html($term->name).'</span></li>';
                }
            }

            if (is_single()) {
                if (has_category() && $post_id) {
                    $thecat = get_the_category($post_id);
                    if (! empty($thecat)) {
                        $category = array_shift($thecat);
                        $html .= '<li><a href="'.esc_url(get_category_link($category->term_id)).'" title="'.esc_attr(get_cat_name($category->term_id)).'">'.esc_html(get_cat_name($category->term_id)).'</a></li>';
                    }
                }

                if (uams_is_custom_post_type()) {
                    // The stubs type this value as possibly false; the call is unchanged.
                    // @phpstan-ignore argument.type
                    $posttype = get_post_type_object(get_post_type());
                    if ($posttype) {
                        // The empty() check leaves a string; core types query_var as string|bool.
                        // @phpstan-ignore argument.type
                        $archive_link = ! empty($posttype->query_var) ? get_post_type_archive_link($posttype->query_var) : '';
                        if (! empty($archive_link)) {
                            $html .= '<li><a href="'.esc_url($archive_link).'" title="'.esc_attr($posttype->labels->menu_name).'">'.esc_html($posttype->labels->menu_name).'</a></li>';
                        } elseif (is_array($posttype->rewrite) && ! empty($posttype->rewrite['slug'])) {
                            $html .= '<li><a href="'.esc_url(site_url('/'.$posttype->rewrite['slug'].'/')).'" title="'.esc_attr($posttype->labels->menu_name).'">'.esc_html($posttype->labels->menu_name).'</a></li>';
                        }
                    }
                }

                if ($post_id) {
                    $html .= '<li class="current"><span>'.esc_html(get_the_title($post_id)).'</span></li>';
                }
            }
        } elseif (is_page()) {
            if ((! is_home() || ! is_front_page()) && $post_id) {
                $ancestors[] = $post_id;
            }

            if (! is_front_page()) {
                $filtered = array_values(array_filter($ancestors));
                $count = count($filtered);
                foreach ($filtered as $index => $ancestor) {
                    $page = get_post($ancestor);
                    if (! ($page instanceof WP_Post)) {
                        continue;
                    }

                    $is_last = ($index + 1 === $count);
                    $url = get_permalink($page->ID);
                    $title_attr = esc_attr($page->post_title);

                    if ($is_last) {
                        $html .= '<li class="current"><span>'.esc_html($page->post_title).'</span></li>';
                    } else {
                        // The stubs type this value as possibly false; the call is unchanged.
                        // @phpstan-ignore argument.type
                        $html .= '<li><a href="'.esc_url($url).'" title="'.$title_attr.'">'.esc_html($page->post_title).'</a></li>';
                    }
                }
            }
        }

        return "<nav class='uams-breadcrumbs' aria-label='breadcrumbs'><ul>$html</ul></nav>";
    }
}

if (! function_exists('uams_breadcrumbs')) {
    function uams_breadcrumbs(): void
    {
        echo get_uams_breadcrumbs();
    }
}

if (! function_exists('uams_thumbnail_url')) {
    /**
     * Echo the URL of the current post's thumbnail.
     *
     * @param  string|array<int, int>  $size  The image size name or a width and height pair.
     */
    function uams_thumbnail_url($size = 'original'): void
    {
        echo esc_url(uams_get_thumbnail_url($size));
    }
}

if (! function_exists('uams_get_thumbnail_url')) {
    /**
     * Get the URL of the current post's thumbnail.
     *
     * @param  string|array<int, int>  $size  The image size name or a width and height pair.
     * @return string The thumbnail URL, or an empty string.
     */
    function uams_get_thumbnail_url($size = 'original')
    {
        // The stubs type this value as possibly false; the call is unchanged.
        // @phpstan-ignore argument.type
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id(), $size, true);

        return is_array($thumbnail) ? $thumbnail[0] : '';
    }
}

if (! function_exists('is_pdf')) {
    function is_pdf(): bool
    {
        return get_post_mime_type() === 'application/pdf';
    }
}

if (! function_exists('uams_get_sticky_posts')) {
    /**
     * Get the sticky posts.
     *
     * @param  array<string, mixed>|string  $args  Arguments merged over the sticky post defaults.
     * @return array<int, WP_Post|int> The matching posts.
     */
    function uams_get_sticky_posts($args = [])
    {
        $stickyposts = get_option('sticky_posts');
        $defaults = ['post__in' => ! empty($stickyposts) ? $stickyposts : [0]];
        $options = wp_parse_args($args, $defaults);

        // The arguments come from the caller through wp_parse_args(), so PHPStan cannot narrow them to the stub's get_posts() shape.
        // @phpstan-ignore argument.type
        return get_posts($options);
    }
}

if (! function_exists('uams_is_custom_post_type')) {
    function uams_is_custom_post_type(): bool
    {
        $type = get_post_type();

        return $type && array_key_exists($type, get_post_types(['_builtin' => false]));
    }
}

if (! function_exists('uams_site_title')) {
    function uams_site_title(): void
    {
        $classes = '';
        if (get_option('overly_long_title')) {
            $classes .= ' long-title';
        }

        $blog_name = get_bloginfo('name');
        echo '<a href="'.esc_url(home_url('/')).'" title="'.esc_attr(str_replace('   ', ' ', $blog_name)).'" tabindex="-1" aria-hidden="true" class="uams-site-title'.esc_attr($classes).'">'.str_replace('   ', ' <br/>', esc_html($blog_name)).'</a>';
    }
}

if (! function_exists('uams_page_title')) {
    function uams_page_title(): void
    {
        $classes = 'uams-page-title';
        if (get_option('overly_long_title')) {
            $classes .= ' long-title';
        }

        $title = get_the_title();
        echo '<a href="" title="'.esc_attr($title).'"><div class="'.esc_attr($classes).'">'.esc_html($title).'</div></a>';
    }
}

if (! function_exists('uams_get_permalink')) {
    /**
     * Get the permalink for a post, preferring a custom link on link-format posts.
     *
     * @param  int|WP_Post|null  $post  The post or post ID. Defaults to the current post.
     * @return string|false The permalink, or an empty string when there is no post.
     */
    function uams_get_permalink($post = null)
    {
        if (empty($post)) {
            $postID = get_the_ID();
        } else {
            $post_obj = get_post($post);
            $postID = ($post_obj instanceof WP_Post) ? $post_obj->ID : 0;
        }

        if (! $postID) {
            return '';
        }

        $external_url = get_post_meta($postID, 'post_custom_link', true);
        $post_format = get_post_format($postID);

        if (! empty($external_url) && ($post_format === 'link')) {
            return $external_url;
        }

        return get_permalink($postID);
    }
}

if (! function_exists('text_cut')) {
    /**
     * Trim text to a length at a word boundary.
     *
     * @param  string  $text  The text to trim.
     * @param  int  $length  The target length.
     * @param  bool  $dots  Whether to append an ellipsis when the text is cut.
     * @return string The trimmed text.
     */
    function text_cut($text, $length = 27, $dots = true): string // @pest-mutate-ignore: TrueToFalse,DecrementInteger,IncrementInteger
    {
        $text = trim(preg_replace('#[\s\n\r\t]{2,}#', ' ', (string) $text));
        $text_temp = $text;

        if (strlen($text) > $length) {
            while ($length > 0 && substr($text, $length, 1) !== ' ') {
                $length--;
            }

            $text = substr($text, 0, $length);
        }

        return $text.(($dots && $text !== '' && strlen($text_temp) > $length) ? '...' : '');
    }
}
