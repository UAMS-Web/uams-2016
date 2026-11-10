<?php

/**
 * UAMS_Users, the user profile fields, the editor capabilities and the editor's admin menu, with
 * WordPress mocked.
 *
 * @see setup/class.users.php
 */

declare(strict_types=1);

use function Brain\Monkey\Filters\expectAdded;
use function Brain\Monkey\Functions\when;

if (! class_exists('WP_Role')) {
    /**
     * A role that holds capabilities and records the ones added to it.
     */
    class WP_Role
    {
        /** @var list<string> */
        public array $added = [];

        /**
         * @param  list<string>  $has
         */
        public function __construct(public array $has = []) {}

        public function has_cap(string $cap): bool
        {
            return in_array($cap, $this->has, true);
        }

        public function add_cap(string $cap): void
        {
            $this->added[] = $cap;
        }
    }
}

if (! class_exists('WP_User')) {
    class WP_User
    {
        /** @var list<string>|null */
        public $roles = [];
    }
}

/**
 * A user with the given roles.
 *
 * @param  list<string>|null  $roles
 */
function userWithRoles(?array $roles): WP_User
{
    $user = new WP_User;
    $user->roles = $roles;

    return $user;
}

beforeEach(function (): void {
    when('get_role')->justReturn();
    loadShortcodeFile('class.users.php');
});

afterEach(function (): void {
    unset($GLOBALS['submenu']);
});

it('adds the profile fields filter with one argument and the admin menu action', function (): void {
    $users = new UAMS_Users;
    expectAdded('user_contactmethods')->once()->with([$users, 'additional_contact_fields'], 10, 1);

    new UAMS_Users;

    expect(has_filter('user_contactmethods', [$users, 'additional_contact_fields']))->toBe(10)
        ->and(has_action('admin_menu', [$users, 'custom_admin_menu']))->toBe(10);
});

describe('the editor role', function (): void {
    it('asks for the editor role', function (): void {
        $asked = [];
        when('get_role')->alias(static function (string $role) use (&$asked): null {
            $asked[] = $role;

            return null;
        });

        new UAMS_Users;

        expect($asked)->toBe(['editor']);
    });

    it('gives the editor the capabilities it lacks', function (): void {
        $role = new WP_Role(['edit_theme_options', 'gravityforms_view_entries']);
        when('get_role')->justReturn($role);

        new UAMS_Users;

        expect($role->added)->toBe([
            'gravityforms_edit_forms',
            'gravityforms_delete_forms',
            'gravityforms_create_form',
            'gravityforms_edit_entries',
            'gravityforms_delete_entries',
            'gravityforms_view_settings',
            'gravityforms_edit_settings',
            'gravityforms_export_entries',
            'gravityforms_view_entry_notes',
            'gravityforms_edit_entry_notes',
        ]);
    });

    it('adds every capability to a role that has none', function (): void {
        $role = new WP_Role;
        when('get_role')->justReturn($role);

        new UAMS_Users;

        expect($role->added)->toHaveCount(12)
            ->and($role->added[0])->toBe('edit_theme_options')
            ->and($role->added[11])->toBe('gravityforms_edit_entry_notes');
    });

    it('adds nothing to a role that has them all', function (): void {
        $role = new WP_Role(['edit_theme_options', 'gravityforms_edit_forms', 'gravityforms_delete_forms', 'gravityforms_create_form', 'gravityforms_view_entries', 'gravityforms_edit_entries', 'gravityforms_delete_entries', 'gravityforms_view_settings', 'gravityforms_edit_settings', 'gravityforms_export_entries', 'gravityforms_view_entry_notes', 'gravityforms_edit_entry_notes']);
        when('get_role')->justReturn($role);

        new UAMS_Users;

        expect($role->added)->toBe([]);
    });
});

it('adds the extra profile fields and removes the old messengers', function (mixed $methods, array $expected): void {
    expect((new UAMS_Users)->additional_contact_fields($methods))->toBe($expected);
})->with([
    'the defaults' => [
        ['url' => 'Website', 'aim' => 'AIM', 'yim' => 'Yahoo IM', 'jabber' => 'Jabber / Google Talk'],
        ['url' => 'Website', 'affiliation' => 'Affiliation', 'phone' => 'Phone Number', 'office' => 'Office', 'twitter' => 'Twitter', 'facebook' => 'Facebook'],
    ],
    'not an array' => ['x', ['affiliation' => 'Affiliation', 'phone' => 'Phone Number', 'office' => 'Office', 'twitter' => 'Twitter', 'facebook' => 'Facebook']],
]);

describe('custom_admin_menu', function (): void {
    /**
     * Record what the menu removal asks for.
     *
     * @return ArrayObject<int, array{string, string}>
     */
    function recordSubmenuRemovals(): ArrayObject
    {
        $removed = new ArrayObject;
        when('remove_submenu_page')->alias(static function (string $menu, string $sub) use ($removed): void {
            $removed[] = [$menu, $sub];
        });

        return $removed;
    }

    it('removes the themes entries from the editor menu', function (): void {
        $editor = new WP_User;
        $editor->roles = ['editor'];

        when('wp_get_current_user')->justReturn($editor);
        $removed = recordSubmenuRemovals();
        $GLOBALS['submenu'] = ['themes.php' => [5 => 'Themes', 6 => 'Customize', 15 => 'Header', 20 => 'Editor'], 'other.php' => [6 => 'Keep']];

        (new UAMS_Users)->custom_admin_menu();

        expect($removed->getArrayCopy())->toBe([['themes.php', 'themes.php']])
            ->and($GLOBALS['submenu'])->toBe(['themes.php' => [5 => 'Themes', 20 => 'Editor'], 'other.php' => [6 => 'Keep']]);
    });

    it('removes only the entries that exist', function (array $themes, array $expected): void {
        $editor = new WP_User;
        $editor->roles = ['author', 'editor'];

        when('wp_get_current_user')->justReturn($editor);
        recordSubmenuRemovals();
        $GLOBALS['submenu'] = ['themes.php' => $themes];

        (new UAMS_Users)->custom_admin_menu();

        expect($GLOBALS['submenu'])->toBe(['themes.php' => $expected]);
    })->with([
        'only 6' => [[6 => 'Customize'], []],
        'only 15' => [[15 => 'Header'], []],
        'neither' => [[5 => 'Themes'], [5 => 'Themes']],
    ]);

    it('leaves the menu alone for anyone who is not an editor', function (mixed $user): void {
        when('wp_get_current_user')->justReturn($user);
        $removed = recordSubmenuRemovals();
        $GLOBALS['submenu'] = ['themes.php' => [6 => 'Customize', 15 => 'Header']];

        (new UAMS_Users)->custom_admin_menu();

        expect($removed->getArrayCopy())->toBe([])
            ->and($GLOBALS['submenu'])->toBe(['themes.php' => [6 => 'Customize', 15 => 'Header']]);
    })->with([
        'an author' => [fn (): WP_User => userWithRoles(['author'])],
        'no roles' => [fn (): WP_User => new WP_User],
        'roles that are not set' => [fn (): WP_User => userWithRoles(null)],
        'not a user' => [false],
    ]);
});
