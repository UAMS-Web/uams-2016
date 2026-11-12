<?php

/**
 * Adjusts settings for TinyMCE
 */
class UAMS_TinyMCE
{
    /**
     * @var array<string, list<string>>
     */
    // Coverage attributes no line to a property default, so its mutants are reported uncovered, not
    // surviving. 'removes the justify full button from the second row' fails if the group or the button is removed.
    // @pest-mutate-ignore: RemoveArrayItem
    private array $remove = [
        'theme_advanced_buttons2' => ['justifyfull'],
    ];

    public function __construct()
    {
        add_filter('tiny_mce_before_init', [$this, 'buttons']);
    }

    /**
     * Remove the configured buttons from the TinyMCE toolbars.
     *
     * @param  mixed  $settings  The TinyMCE settings; a non-array value passes through unchanged.
     * @return mixed
     */
    public function buttons($settings)
    {
        if (! is_array($settings)) {
            return $settings;
        }

        foreach ($this->remove as $buttongroup => $buttonlist) {
            if (array_key_exists($buttongroup, $settings) && ! empty($settings[$buttongroup])) {
                // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
                // @phpstan-ignore cast.string
                $buttons = explode(',', (string) $settings[$buttongroup]);
                $newBtns = array_diff($buttons, $buttonlist);
                $settings[$buttongroup] = implode(',', $newBtns);
            }
        }

        return $settings;
    }
}

new UAMS_TinyMCE;
