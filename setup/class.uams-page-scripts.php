<?php

/*
 *  The per-page header and footer scripts: the `custom_header_script` and `custom_footer_script`
 *  post meta that header.php and footer.php print unescaped, as the Custom CSS/JS fields in
 *  class.uams-acf.php describe them.
 *
 *  The ACF group shows those fields to administrators only, but the keys are plain post meta, so
 *  the classic Custom Fields box, or a crafted save, could set them for any user who can edit the
 *  post. This class ties the keys to `unfiltered_html`, the capability WordPress requires for
 *  script in post content: the keys are protected, so the Custom Fields box neither lists nor
 *  accepts them, and a write by a user without the capability is refused before it is stored.
 */

class UAMS_Page_Scripts
{
    /**
     * The meta keys, as the ACF fields name them.
     */
    public const KEYS = ['custom_header_script', 'custom_footer_script'];

    /**
     * The capability a user needs to write either key.
     */
    public const CAPABILITY = 'unfiltered_html';

    public function __construct()
    {
        add_action('init', [self::class, 'register_meta']);
        add_filter('is_protected_meta', [self::class, 'protect_meta'], 10, 3);
        add_filter('add_post_metadata', [self::class, 'refuse_unprivileged_write'], 10, 3);
        add_filter('update_post_metadata', [self::class, 'refuse_unprivileged_write'], 10, 3);
    }

    /**
     * Declare the keys to WordPress: one string on any post type, not exposed over REST, and
     * written only by a user with the capability. The `auth_callback` is what the
     * `edit_post_meta` and `add_post_meta` capability checks consult.
     */
    public static function register_meta(): void
    {
        foreach (self::KEYS as $key) {
            register_meta('post', $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'auth_callback' => [self::class, 'current_user_may_write'],
            ]);
        }
    }

    public static function current_user_may_write(): bool
    {
        return current_user_can(self::CAPABILITY);
    }

    /**
     * Treat the keys as protected, as an underscore-prefixed key is, so the Custom Fields box
     * neither lists nor accepts them. The key and type are left untyped: core passes them through
     * from is_protected_meta() as given, and a filter that throws on an odd call runs on every meta check.
     *
     * @param  mixed  $meta_key
     * @param  mixed  $meta_type
     */
    public static function protect_meta(bool $protected, $meta_key, $meta_type = ''): bool
    {
        if ($meta_type === 'post' && in_array($meta_key, self::KEYS, true)) {
            return true;
        }

        return $protected;
    }

    /**
     * Short-circuit a write of either key by a user without the capability, whatever path the
     * write takes (the ACF save, the Custom Fields box, XML-RPC): the write returns false and the
     * stored value is kept. Any other key, or a user with the capability, falls through.
     *
     * @param  mixed  $object_id
     * @param  mixed  $meta_key
     */
    public static function refuse_unprivileged_write(mixed $check, $object_id, $meta_key): mixed
    {
        if (in_array($meta_key, self::KEYS, true) && ! self::current_user_may_write()) {
            return false;
        }

        return $check;
    }
}
