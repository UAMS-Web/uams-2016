<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuztom notice class, to easily handle admin notices
 *
 * @author  Gijs Jorissen
 * @since   2.3
 */
#[\AllowDynamicProperties]
class Cuztom_Notice
{
	public $notice;
	public $type;

	public function __construct( $notice, $type = 'updated' )
	{
		$this->notice = $notice;
		$this->type   = $type;

		add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
	}

	public function add_admin_notice()
	{
		echo '<div class="' . esc_attr( $this->type ) . '">';
		echo '<p>' . wp_kses_post( $this->notice ) . '</p>';
		echo '</div>';
	}
}
