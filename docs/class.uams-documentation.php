<?php

/**
 * UAMS WordPress documentation page
 *
 * This class provides the documentation for all widgets and shortcodes the UAMS 2016 theme provides
 */
class UAMS_Documentation
{
  const PAGE_TITLE = 'Documentation';
  const MENU_TITLE = 'Documentation';
  const CAPABILITY = 'read';
  const SLUG       = 'uams-documentation';

  public function __construct()
  {
    add_action( 'admin_menu', array( $this, 'add_documentation_page' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_markdown_script' ) );
  }

  public function add_documentation_page()
  {
    add_menu_page( 
      self::PAGE_TITLE, 
      self::MENU_TITLE, 
      self::CAPABILITY, 
      self::SLUG, 
      array( $this, 'load_documentation_template' ) 
    );
  }

  public function enqueue_markdown_script( $hook_suffix )
  {
    // Restrict asset loading strictly to this admin page
    if ( false === strpos( (string) $hook_suffix, self::SLUG ) ) {
      return;
    }

    wp_enqueue_style( 'uams-documentation', get_template_directory_uri() . '/assets/admin/css/uams.documentation.css', array(), null );
    wp_enqueue_script( 'showdown', 'https://cdnjs.cloudflare.com/ajax/libs/showdown/1.8.6/showdown.min.js', array(), '1.8.6', true );
  }

  public function load_documentation_template()
  {
    get_template_part( 'docs/documentation' );
  }
}

new UAMS_Documentation();
