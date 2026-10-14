<?php
/**
 * Register custom mime-types that WordPress doesn't allow by default
 */

class UAMS_Mimes
{
    public $MIMES = array(
        'ai|eps' => 'application/postscript',
    );

    public function __construct()
    {
        add_filter( 'upload_mimes', array( $this, 'uw_add_custom_upload_mimes' ), 10, 2 );
    }

    public function uw_add_custom_upload_mimes( $existing_mimes, $user = null )
    {
        $existing_mimes = is_array( $existing_mimes ) ? $existing_mimes : array();

        return array_merge( $existing_mimes, $this->MIMES );
    }
}

new UAMS_Mimes();
