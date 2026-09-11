<?php
/**
 * This is where all the JS files are registered
 *    - get_template_directory_uri() gives you the url to the parent theme
 *    - get_stylesheet_directory_uri() gives you the url to the child theme
 */

class UAMS_Scripts
{
	public $SCRIPTS;

	public function __construct()
	{
		$this->SCRIPTS = array_merge(
			array(
				'jquery'  => array(
					'id'      => 'jquery',
					'url'     => 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js',
					'deps'    => array(),
					'version' => '3.5.1',
					'admin'   => false,
					'footer'  => false,
				),
				'migrate' => array(
					'id'      => 'migrate',
					'url'     => 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.2/jquery-migrate.min.js',
					'deps'    => array( 'jquery' ),
					'version' => '3.3.2',
					'admin'   => false,
					'footer'  => false,
				),
				'site'    => array(
					'id'        => 'site',
					'url'       => get_template_directory_uri() . '/js/site' . $this->dev_script() . '.js',
					'deps'      => array( 'backbone' ),
					'version'   => '1.0.3',
					'admin'     => false,
					'style_dir' => site_url(),
					'footer'    => false,
				),
				'alert'   => array(
					'id'      => 'alert',
					'url'     => '//www.uams.edu/web/alert/uamsalert.js',
					'deps'    => array( 'jquery' ),
					'version' => '1.0.1',
					'admin'   => false,
					'footer'  => true,
				),
				'admin'   => array(
					'id'      => 'wp.admin',
					'url'     => get_template_directory_uri() . '/assets/admin/js/admin.js',
					'deps'    => array( 'jquery' ),
					'version' => '1.0',
					'admin'   => true,
					'footer'  => false,
				),
			),
			$this->get_child_theme_scripts()
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'uams_register_default_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'uams_localize_default_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'uams_enqueue_default_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'uams_enqueue_admin_scripts' ) );
		add_action( 'customize_controls_init', array( $this, 'uams_customizer_preview' ) );
	}

	public function uams_customizer_preview()
	{
		wp_enqueue_script(
			'uams-themecustomize',
			get_template_directory_uri() . '/js/uams.themecustomizer.js',
			array( 'jquery', 'customize-controls' ),
			false,
			true
		);
	}

	public function uams_register_default_scripts()
	{
		wp_deregister_script( 'jquery' );

		foreach ( $this->SCRIPTS as $script ) {
			wp_register_script(
				$script['id'],
				$script['url'],
				$script['deps'] ?? array(),
				$script['version'] ?? false,
				$script['footer'] ?? false
			);
		}
	}

	public function uams_localize_default_scripts()
	{
		foreach ( $this->SCRIPTS as $script ) {
			if ( ! empty( $script['style_dir'] ) ) {
				wp_localize_script( $script['id'], 'style_dir', $script['style_dir'] );
			}
		}
	}

	public function uams_enqueue_default_scripts()
	{
		foreach ( $this->SCRIPTS as $script ) {
			if ( empty( $script['admin'] ) ) {
				wp_enqueue_script( $script['id'] );
			}
		}
	}

	public function uams_enqueue_admin_scripts()
	{
		if ( ! is_admin() ) {
			return;
		}

		foreach ( $this->SCRIPTS as $script ) {
			if ( ! empty( $script['admin'] ) ) {
				wp_register_script(
					$script['id'],
					$script['url'],
					$script['deps'] ?? array(),
					$script['version'] ?? false,
					$script['footer'] ?? false
				);

				wp_enqueue_script( $script['id'] );
			}
		}
	}

	private function get_child_theme_scripts()
	{
		return is_array( $this->SCRIPTS ) ? $this->SCRIPTS : array();
	}

	public function dev_script()
	{
		return is_user_logged_in() ? '.dev' : '';
	}
}