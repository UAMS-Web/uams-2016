<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Accordion
{
	public $id;
	public $meta_type;
	public $tabs = array();

	public function __construct( $id )
	{
		$this->id = $id;
	}

	public function output( $post )
	{
		$tabs = (array) $this->tabs;

		echo '<div class="js-cuztom-accordion">';
		foreach ( $tabs as $title => $tab ) {
			if ( is_object( $tab ) && method_exists( $tab, 'output' ) ) {
				$tab->output( $post, 'accordion' );
			}
		}
		echo '</div>';
	}
}
