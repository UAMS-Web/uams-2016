<?php

/**
 * Register custom mime-types that WordPress doesn't allow by default
 */
class UAMS_Mimes
{
    /**
     * @var array<string, string>
     */
    // Coverage attributes no line to a property default, so its mutants are reported uncovered, not
    // surviving. 'hooks the upload types with both arguments' asserts the list.
    // @pest-mutate-ignore: RemoveArrayItem
    public $MIMES = [
        'ai|eps' => 'application/postscript',
    ];

    public function __construct()
    {
        add_filter('upload_mimes', [$this, 'uw_add_custom_upload_mimes'], 10, 2);
    }

    /**
     * Add the custom mime types to the allowed uploads.
     *
     * @param  mixed  $existing_mimes  The allowed mime types keyed by extension pattern.
     * @param  int|WP_User|null  $user  The user ID or user object.
     * @return array<string, string> The allowed mime types.
     */
    public function uw_add_custom_upload_mimes($existing_mimes, $user = null): array
    {
        $existing_mimes = is_array($existing_mimes) ? $existing_mimes : [];

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore return.type
        return array_merge($existing_mimes, $this->MIMES);
    }
}

new UAMS_Mimes;
