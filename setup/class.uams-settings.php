<?php

class UAMS_Settings
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'setup_sections' ) );
        add_action( 'admin_init', array( $this, 'setup_options' ) );
    }

    public function setup_sections()
    {
        $this->make_setting_pages();
        $this->add_setting_sections();
    }

    public function setup_options()
    {
        $this->register_settings();
        $this->add_settings_fields();
    }

    public function make_setting_pages()
    {
        // No pages currently
    }

    public function add_setting_sections()
    {
        // No sections currently
    }

    public function register_settings()
    {
        register_setting( 'general', 'primary_uams_site', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'general', 'overly_long_title', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'reading', 'show_byline_on_posts', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'reading', 'show_category_on_posts', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'general', 'use_main_menu_on_mobile', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'general', 'google_tag_manager_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    }

    public function add_settings_fields()
    {
        add_settings_field( 'primary_uams_site', 'Is this a primary UAMS site (logo on the left)?', array( $this, 'primary_uams_site_callback' ), 'general' );
        add_settings_field( 'overly_long_title', 'Does your site title take two lines on desktop?', array( $this, 'overly_long_title_callback' ), 'general' );
        add_settings_field( 'show_byline_on_posts', 'Show bylines on single posts and archives?', array( $this, 'show_byline_on_posts_callback' ), 'reading' );
        add_settings_field( 'show_category_on_posts', 'Show categories on single posts?', array( $this, 'show_category_on_posts_callback' ), 'reading' );
        add_settings_field( 'use_main_menu_on_mobile', 'Use the main menu on mobile as default?', array( $this, 'use_main_menu_on_mobile_callback' ), 'general' );
        add_settings_field( 'google_tag_manager_id', 'Google Tag Manager ID:', array( $this, 'google_tag_manager_id_callback' ), 'general' );
    }

    public function primary_uams_site_callback()
    {
        echo "<input name='primary_uams_site' type='checkbox' value='1'" . checked( 1, (int) get_option( 'primary_uams_site' ), false ) . " /> (yes if checked)";
    }

    public function overly_long_title_callback()
    {
        echo "<input name='overly_long_title' type='checkbox' value='1'" . checked( 1, (int) get_option( 'overly_long_title' ), false ) . " /> (yes if checked)";
    }

    public function show_byline_on_posts_callback()
    {
        echo "<input name='show_byline_on_posts' type='checkbox' value='1'" . checked( 1, (int) get_option( 'show_byline_on_posts' ), false ) . " /> (yes if checked)";
    }

    public function show_category_on_posts_callback()
    {
        echo "<input name='show_category_on_posts' type='checkbox' value='1'" . checked( 1, (int) get_option( 'show_category_on_posts' ), false ) . " /> (yes if checked)";
    }

    public function use_main_menu_on_mobile_callback()
    {
        echo "<input name='use_main_menu_on_mobile' type='checkbox' value='1'" . checked( 1, (int) get_option( 'use_main_menu_on_mobile' ), false ) . " /> (yes if checked)";
    }

    public function google_tag_manager_id_callback()
    {
        $gtm = esc_attr( (string) get_option( 'google_tag_manager_id' ) );
        echo "<input name='google_tag_manager_id' type='text' size='20' value='{$gtm}' /> (Leave Blank for Default)";
    }
}

new UAMS_Settings();
