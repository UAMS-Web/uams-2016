<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
class Cuztom_Tabs
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

		echo '<div class="js-cuztom-tabs cuztom-tabs">';
		echo '<ul>';
		foreach ( $tabs as $title => $tab ) {
			if ( is_object( $tab ) ) {
				echo '<li><a href="#cuztom-' . esc_attr( $tab->id ) . '">' . esc_html( $tab->title ) . '</a></li>';
			}
		}
		echo '</ul>';

		foreach ( $tabs as $title => $tab ) {
			if ( is_object( $tab ) && method_exists( $tab, 'output' ) ) {
				$tab->output( $post, 'tabs' );
			}
		}
		echo '</div>';
	}
}
