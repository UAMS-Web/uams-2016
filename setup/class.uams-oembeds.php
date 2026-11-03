<?php

// WordPress oEmbed max-width
$content_width ??= 750;

/**
 * Custom UAMS oEmbeds
 */
class UAMS_OEmbeds
{
    public function __construct()
    {
        add_action( 'init', [ $this, 'campus_map' ] );
    }

    public function campus_map(): void
    {
        wp_oembed_add_provider( 'http://uw.edu/maps/*', '//www.washington.edu/maps/api/oembed/place/' );
        wp_oembed_add_provider( 'http://www.washington.edu/maps/*', '//www.washington.edu/maps/api/oembed/place/' );
        wp_oembed_add_provider( 'https://uw.edu/maps/*', '//www.washington.edu/maps/api/oembed/place/' );
        wp_oembed_add_provider( 'https://www.washington.edu/maps/*', '//www.washington.edu/maps/api/oembed/place/' );
    }
}

new UAMS_OEmbeds();
