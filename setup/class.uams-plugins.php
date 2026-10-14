<?php
/**
 * Register required plugins via TGMPA
 */

require_once get_template_directory() . '/setup/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'uams_2016_register_required_plugins' );

function uams_2016_register_required_plugins() {
    $plugins = array(
        array(
            'name'     => 'Advanced Custom Fields PRO',
            'slug'     => 'advanced-custom-fields-pro',
            'source'   => get_template_directory() . '/plugins/advanced-custom-fields-pro.zip',
            'required' => true,
        ),
    );

    $config = array(
        'id'           => 'uams-2016',
        'default_path' => '',
        'menu'         => 'tgmpa-install-plugins',
        'parent_slug'  => 'themes.php',
        'capability'   => 'edit_theme_options',
        'has_notices'  => true,
        'dismissable'  => true,
        'dismiss_msg'  => '',
        'is_automatic' => true,
        'message'      => '',
    );

    tgmpa( $plugins, $config );
}
