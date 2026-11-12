<?php

/**
 * Adds Affiliations, Office, Twitter, and Facebook to user profiles
 * Removes yim, aim and jabber from user profiles
 */
class UAMS_Users
{
    public function __construct()
    {
        add_filter('user_contactmethods', [$this, 'additional_contact_fields'], 10, 1);

        $role = get_role('editor');
        if ($role instanceof WP_Role) {
            $caps = [
                'edit_theme_options',
                'gravityforms_edit_forms',
                'gravityforms_delete_forms',
                'gravityforms_create_form',
                'gravityforms_view_entries',
                'gravityforms_edit_entries',
                'gravityforms_delete_entries',
                'gravityforms_view_settings',
                'gravityforms_edit_settings',
                'gravityforms_export_entries',
                'gravityforms_view_entry_notes',
                'gravityforms_edit_entry_notes',
            ];

            foreach ($caps as $cap) {
                if (! $role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }

        add_action('admin_menu', [$this, 'custom_admin_menu']);
    }

    /**
     * @return mixed[]
     */
    public function additional_contact_fields($contactmethods): array
    {
        $contactmethods = is_array($contactmethods) ? $contactmethods : [];

        $contactmethods['affiliation'] = 'Affiliation';
        $contactmethods['phone'] = 'Phone Number';
        $contactmethods['office'] = 'Office';
        $contactmethods['twitter'] = 'Twitter';
        $contactmethods['facebook'] = 'Facebook';

        unset($contactmethods['yim'], $contactmethods['aim'], $contactmethods['jabber']);

        return $contactmethods;
    }

    public function custom_admin_menu(): void
    {
        $user = wp_get_current_user();

        // The filter argument is documented as WP_User; the check is kept in case a caller passes another value.
        // @phpstan-ignore instanceof.alwaysTrue
        if ($user instanceof WP_User && in_array('editor', (array) $user->roles, true)) {
            remove_submenu_page('themes.php', 'themes.php');

            global $submenu;
            if (isset($submenu['themes.php'][6])) {
                unset($submenu['themes.php'][6]);
            }

            if (isset($submenu['themes.php'][15])) {
                unset($submenu['themes.php'][15]);
            }
        }
    }
}

new UAMS_Users;
