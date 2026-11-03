<?php

class UAMS_Sidebar
{

  const NAME          = 'Sidebar';
  
  const ID            = 'sidebar';
  
  const DESCRIPTION   = 'Right column widgets';
  
  const BEFORE_WIDGET = '<div role="navigation" aria-label="sidebar_navigation" id="%1$s" class="widget %2$s">';
  
  const AFTER_WIDGET  = '</div>';

  public function __construct()
  {
    add_action( 'widgets_init', [ $this, 'register_sidebar' ] );
  }

  public function register_sidebar(): void
  {
    register_sidebar([
      'name'          => self::NAME,
      'id'            => self::ID,
      'description'   => self::DESCRIPTION,
      'before_widget' => self::BEFORE_WIDGET,
      'after_widget'  => self::AFTER_WIDGET
    ]);
  }

}

new UAMS_Sidebar;
