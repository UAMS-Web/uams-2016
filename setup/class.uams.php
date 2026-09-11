<?php

/*
 *  This is the UAMS object that contains all the classes for our back-end functionality
 *  All classes should be accessible by UAMS::ClassName
 */

#[\AllowDynamicProperties]
class UAMS
{
    public function __construct()
    {
        $this->includes();
        $this->initialize();
    }

    private function includes()
    {
        $parent = get_template_directory() . '/setup/';

        require_once( $parent . 'class.install.php' );
        require_once( $parent . 'class.uams-scripts.php' );
        require_once( $parent . 'class.uams-styles.php' );
        require_once( $parent . 'class.uams-dropdowns.php' );
        require_once( $parent . 'class.images.php' );
        require_once( $parent . 'class.squish_bugs.php' );
        require_once( $parent . 'class.filters.php' );
        require_once( $parent . 'class.uams-oembeds.php' );
        require_once( $parent . 'class.googleapps.php' );
        require_once( $parent . 'class.mimes.php' );
        require_once( $parent . 'class.users.php' );
        require_once( $parent . 'class.dropdowns_walker.php' );
        require_once( $parent . 'class.uams-basic-custom-post.php' );
        require_once( $parent . 'class.uams-sidebar-menu-walker.php' );
        require_once( $parent . 'class.uams-quicklinks.php' );
        require_once( $parent . 'class.uams-iframes.php' );
        require_once( $parent . 'class.uams-shortcodes.php' );
        require_once( $parent . 'class.uams-media-credit.php' );
        require_once( $parent . 'class.uams-media-caption.php' );
        require_once( $parent . 'class.uams-replace-media.php' );
        require_once( $parent . 'class.uams-tinymce.php' );
        require_once( $parent . 'class.uams-enclosure.php' );
        require_once( $parent . 'class.uams-carousel.php' );
        require_once( $parent . 'class.uams-settings.php' );
        require_once( $parent . 'class.uams-page-attributes-meta-box.php' );

        if ( file_exists( $parent . 'custom-post-types.php' ) ) {
            require_once( $parent . 'custom-post-types.php' );
        }

        require_once( $parent . 'class.uams-acf.php' );
        require_once( get_template_directory() . '/inc/template-functions.php' );

        if ( file_exists( get_template_directory() . '/docs/class.uams-documentation.php' ) ) {
            require_once( get_template_directory() . '/docs/class.uams-documentation.php' );
        }

        $widget_files = glob( get_template_directory() . '/widgets/*.php' );
        if ( ! empty( $widget_files ) ) {
            foreach ( $widget_files as $filename ) {
                include_once $filename;
            }
        }
    }

    private function initialize()
    {
        $this->Install           = class_exists( 'UAMS_Install_Theme' ) ? new UAMS_Install_Theme() : null;
        $this->Scripts           = class_exists( 'UAMS_Scripts' ) ? new UAMS_Scripts() : null;
        $this->Styles            = class_exists( 'UAMS_Styles' ) ? new UAMS_Styles() : null;
        $this->Images            = class_exists( 'UAMS_Images' ) ? new UAMS_Images() : null;
        $this->SquishBugs        = class_exists( 'UAMS_SquishBugs' ) ? new UAMS_SquishBugs() : null;
        $this->Filters           = class_exists( 'UAMS_Filters' ) ? new UAMS_Filters() : null;
        $this->OEmbeds           = class_exists( 'UAMS_OEmbeds' ) ? new UAMS_OEmbeds() : null;
        $this->Mimes             = class_exists( 'UAMS_Mimes' ) ? new UAMS_Mimes() : null;
        $this->Users             = class_exists( 'UAMS_Users' ) ? new UAMS_Users() : null;
        $this->SidebarMenuWalker = class_exists( 'UAMS_Sidebar_Menu_Walker' ) ? new UAMS_Sidebar_Menu_Walker() : null;
        $this->Dropdowns         = class_exists( 'UAMS_Dropdowns' ) ? new UAMS_Dropdowns() : null;
        $this->Quicklinks        = class_exists( 'UAMS_QuickLinks' ) ? new UAMS_QuickLinks() : null;
        $this->Shortcodes        = class_exists( 'UAMS_Shortcodes' ) ? new UAMS_Shortcodes() : null;
        $this->MediaCredit       = class_exists( 'UAMS_Media_Credit' ) ? new UAMS_Media_Credit() : null;
        $this->MediaCaption      = class_exists( 'UAMS_Media_Caption' ) ? new UAMS_Media_Caption() : null;
        $this->ReplaceMedia      = class_exists( 'UAMS_Replace_Media' ) ? new UAMS_Replace_Media() : null;
        $this->TinyMCE           = class_exists( 'UAMS_TinyMCE' ) ? new UAMS_TinyMCE() : null;
        $this->IFrames           = class_exists( 'UAMS_Iframes' ) ? new UAMS_Iframes() : null;
        $this->GoogleApps        = class_exists( 'UAMS_GoogleApps' ) ? new UAMS_GoogleApps() : null;
        $this->Enclosure         = class_exists( 'UAMS_Enclosure' ) ? new UAMS_Enclosure() : null;
        $this->Carousel          = class_exists( 'UAMS_Carousel' ) ? new UAMS_Carousel() : null;
        $this->Settings          = class_exists( 'UAMS_Settings' ) ? new UAMS_Settings() : null;
    }
}
