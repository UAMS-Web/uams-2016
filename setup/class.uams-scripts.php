<?php
/**
 * This is where all the JS files are registered
 *    - get_template_directory_uri() gives you the url to the parent theme
 *    - get_stylesheet_directory_uri() gives you the url to the child theme
 */

class UAMS_Scripts
{
	/**
     * @var mixed[]
     */
    public $SCRIPTS;

	public function __construct()
	{
		$this->SCRIPTS = array_merge(
			[
				'jquery'  => [
					'id'      => 'jquery',
					'url'     => 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js',
					'deps'    => [],
					'version' => '3.5.1',
					'admin'   => false,
					'footer'  => false,
				],
				'migrate' => [
					'id'      => 'migrate',
					'url'     => 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.2/jquery-migrate.min.js',
					'deps'    => [ 'jquery' ],
					'version' => '3.3.2',
					'admin'   => false,
					'footer'  => false,
				],
				'site'    => [
					'id'        => 'site',
					'url'       => get_template_directory_uri() . '/js/site' . $this->dev_script() . '.js',
					'deps'      => [ 'backbone' ],
					'version'   => '1.0.3',
					'admin'     => false,
					'style_dir' => site_url(),
					'footer'    => false,
				],
				'alert'   => [
					'id'      => 'alert',
					'url'     => '//www.uams.edu/web/alert/uamsalert.js',
					'deps'    => [ 'jquery' ],
					'version' => '1.0.1',
					'admin'   => false,
					'footer'  => true,
				],
				'admin'   => [
					'id'      => 'wp.admin',
					'url'     => get_template_directory_uri() . '/assets/admin/js/admin.js',
					'deps'    => [ 'jquery' ],
					'version' => '1.0',
					'admin'   => true,
					'footer'  => false,
				],
			],
			$this->get_child_theme_scripts()
		);

		add_action( 'wp_enqueue_scripts', [ $this, 'uams_register_default_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'uams_localize_default_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'uams_enqueue_default_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'uams_enqueue_admin_scripts' ] );
		add_action( 'customize_controls_init', [ $this, 'uams_customizer_preview' ] );
	}

	public function uams_customizer_preview(): void
	{
		wp_enqueue_script(
			'uams-themecustomize',
			get_template_directory_uri() . '/js/uams.themecustomizer.js',
			[ 'jquery', 'customize-controls' ],
			false,
			true
		);
	}

	public function uams_register_default_scripts(): void
	{
		wp_deregister_script( 'jquery' );

		foreach ( $this->SCRIPTS as $script ) {
			wp_register_script(
				$script['id'],
				$script['url'],
				$script['deps'] ?? [],
				$script['version'] ?? false,
				$script['footer'] ?? false
			);
		}
	}

	public function uams_localize_default_scripts(): void
	{
		foreach ( $this->SCRIPTS as $script ) {
			if ( ! empty( $script['style_dir'] ) ) {
				wp_localize_script( $script['id'], 'style_dir', $script['style_dir'] );
			}
		}
	}

	public function uams_enqueue_default_scripts(): void
	{
		foreach ( $this->SCRIPTS as $script ) {
			if ( empty( $script['admin'] ) ) {
				wp_enqueue_script( $script['id'] );
			}
		}
	}

	public function uams_enqueue_admin_scripts(): void
	{
		if ( ! is_admin() ) {
			return;
		}

		foreach ( $this->SCRIPTS as $script ) {
			if ( ! empty( $script['admin'] ) ) {
				wp_register_script(
					$script['id'],
					$script['url'],
					$script['deps'] ?? [],
					$script['version'] ?? false,
					$script['footer'] ?? false
				);

				wp_enqueue_script( $script['id'] );
			}
		}
	}

	private function get_child_theme_scripts(): array
	{
		return is_array( $this->SCRIPTS ) ? $this->SCRIPTS : [];
	}

	public function dev_script(): string
	{
		return is_user_logged_in() ? '.dev' : '';
	}
}