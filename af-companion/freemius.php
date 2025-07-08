<?php
if ( ! function_exists( 'afcompanion_fs' ) ) {
    // Create a helper function for easy SDK access.
    function afcompanion_fs() {
        global $afcompanion_fs;

        if ( ! isset( $afcompanion_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $afcompanion_fs = fs_dynamic_init( array(
                'id'                  => '7637',
                'slug'                => 'all_themes_plan',
                'premium_slug'        => 'all_themes_plan',
                'type'                => 'bundle',
                'public_key'          => 'pk_5f4c56aa7d0bac4236a8e650bb520',
                'is_premium'          => false,
                'is_premium_only'     => false,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'menu'                => array(
                    'slug'           => 'af-companion',
                    'first-path'     => 'admin.php?page=af-companion',
                    'support'        => false,
                ),
            ) );
        }

        return $afcompanion_fs;
    }

    // Init Freemius.
    afcompanion_fs();
    // Signal that SDK was initiated.
    do_action( 'afcompanion_fs_loaded' );
}