<?php
/**
 * Adjusts settings for TinyMCE
 */

class UAMS_TinyMCE
{
    private array $remove = [
        'theme_advanced_buttons2' => [ 'justifyfull' ],
    ];

    public function __construct()
    {
        add_filter( 'tiny_mce_before_init', [ $this, 'buttons' ] );
    }

    public function buttons( $settings )
    {
        if ( ! is_array( $settings ) ) {
            return $settings;
        }

        foreach ( $this->remove as $buttongroup => $buttonlist ) {
            if ( array_key_exists( $buttongroup, $settings ) && ! empty( $settings[ $buttongroup ] ) ) {
                $buttons                 = explode( ',', (string) $settings[ $buttongroup ] );
                $newBtns                 = array_diff( $buttons, $buttonlist );
                $settings[ $buttongroup ] = implode( ',', $newBtns );
            }
        }
        
        return $settings;
    }
}

new UAMS_TinyMCE();
