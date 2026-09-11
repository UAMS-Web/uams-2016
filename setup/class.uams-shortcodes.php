<?php

/*
 * This is the object that holds all the UAMS shortcodes
 * Shortcodes are how we get functionality in content for power users
 */

#[\AllowDynamicProperties]
class UAMS_Shortcodes
{
    public $tile_box;
    public $button;
    public $youtube;
    public $subpage_list;
    public $accordion;
    public $maps;
    public $tabs;
    public $bootstrap;
    public $custommenu;
    public $cards;

    public function __construct()
    {
        $this->includes();
        $this->initialize();
    }

    private function includes()
    {
        require_once get_template_directory() . '/setup/class.tile-box-shortcode.php';
        require_once get_template_directory() . '/setup/class.button-shortcode.php';
        if ( file_exists( get_template_directory() . '/setup/class.youtube-shortcode.php' ) ) {
            require_once get_template_directory() . '/setup/class.youtube-shortcode.php';
        }
        require_once get_template_directory() . '/setup/class.subpage-list-shortcode.php';
        require_once get_template_directory() . '/setup/class.accordion-shortcode.php';
        require_once get_template_directory() . '/setup/class.maps-shortcode.php';
        require_once get_template_directory() . '/setup/class.tabs-shortcode.php';
        require_once get_template_directory() . '/setup/class.grid-shortcode.php';
        require_once get_template_directory() . '/setup/class.menu-shortcode.php';
        require_once get_template_directory() . '/setup/class.cards-shortcode.php';
    }

    private function initialize()
    {
        $this->tile_box     = class_exists( 'TileBox' ) ? new TileBox() : null;
        $this->button       = class_exists( 'UAMS_Button' ) ? new UAMS_Button() : null;
        $this->youtube      = class_exists( 'UAMS_YouTube' ) ? new UAMS_YouTube() : null;
        $this->subpage_list = class_exists( 'UAMS_SubpageList' ) ? new UAMS_SubpageList() : null;
        $this->accordion    = class_exists( 'UAMS_AccordionShortcode' ) ? new UAMS_AccordionShortcode() : null;
        $this->maps         = class_exists( 'UAMS_Map' ) ? new UAMS_Map() : null;
        $this->tabs         = class_exists( 'UAMS_TabsShortcode' ) ? new UAMS_TabsShortcode() : null;
        $this->bootstrap    = class_exists( 'UAMS_GridShortcode' ) ? new UAMS_GridShortcode() : null;
        $this->custommenu   = class_exists( 'UAMS_MenuShortcode' ) ? new UAMS_MenuShortcode() : null;
        $this->cards        = class_exists( 'UAMS_CardsShortcode' ) ? new UAMS_CardsShortcode() : null;
    }
}
