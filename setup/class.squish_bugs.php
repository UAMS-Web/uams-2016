<?php

class UAMS_SquishBugs
{
    public function __construct()
    {
        // http://core.trac.wordpress.org/ticket/11330
        add_filter('pre_get_posts', [$this, 'uams_search_query_filter']);
    }

    /**
     * Treat an empty search as a search request.
     *
     * @param  WP_Query  $query  The query.
     * @return WP_Query The query.
     */
    public function uams_search_query_filter($query)
    {
        if (isset($_GET['s']) && empty($_GET['s']) && $query->is_main_query()) {
            $query->is_search = true;
            $query->is_home = false;
        }

        return $query;
    }
}

new UAMS_SquishBugs;
