<?php
/**
 * Welcome Notice for AF Companion
 * Highlights: Demo Import, Speed Booster, Growth Tools, and System Status
 */

class AFTC_Welcome_Notice extends AFTC_Notice {

    public function __construct() {
        if ( ! current_user_can( 'publish_posts' ) ) {
            return;
        }

        // Unique dismissal identifiers for the Welcome notice
        $dismiss_url = wp_nonce_url(
            add_query_arg( 'aftc_notice_dismiss', 'welcome_v2', admin_url() ),
            'aftc_upgrade_notice_dismiss_nonce',
            '_aftc_upgrade_notice_dismiss_nonce'
        );

        $temporary_dismiss_url = wp_nonce_url(
            add_query_arg( 'aftc_notice_dismiss_temporary', 'welcome_v2', admin_url() ),
            'aftc_upgrade_notice_dismiss_temporary_nonce',
            '_aftc_upgrade_notice_dismiss_temporary_nonce'
        );

        parent::__construct( 'welcome_v2', 'success', $dismiss_url, $temporary_dismiss_url );

        $this->set_welcome_logic();
    }

    private function set_welcome_logic() {
        // Show immediately if not permanently dismissed or temporarily dismissed in the last 24 hours
        if ( get_user_meta( get_current_user_id(), 'aftc_welcome_v2_notice_dismiss', true )
            || get_user_meta( get_current_user_id(), 'aftc_welcome_v2_notice_dismiss_temporary_start_time', true ) > strtotime( '-1 day' )
        ) {
            add_filter( 'aftc_welcome_v2_notice_dismiss', '__return_true' );
        } else {
            add_filter( 'aftc_welcome_v2_notice_dismiss', '__return_false' );
        }
    }

    public function notice_markup() {
        $current_user = wp_get_current_user();
        ?>
        <div class="notice notice-success aftc-notice is-dismissible" style="border-left-color: #6366f1; padding: 20px;">
            <div class="aftc-notice__content">
                <h2 style="margin-top:0;"><?php printf( esc_html__( 'Welcome to AF Companion, %s!', 'af-companion' ), esc_html( $current_user->display_name ) ); ?></h2>
                <p style="font-size: 15px; max-width: 800px;">
                    <?php esc_html_e( 'Your site management ecosystem is ready. From building with professional starter sites to boosting performance and tracking growth, everything you need is right here.', 'af-companion' ); ?>
                </p>
                
                <div class="aftc-solution-links" style="margin: 15px 0; display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="<?php echo admin_url( 'admin.php?page=af-companion' ); ?>" class="button button-primary">
                        <span class="dashicons dashicons-layout" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></span>
                        <?php esc_html_e( '1. Demo Import', 'af-companion' ); ?>
                    </a>

                    <a href="<?php echo admin_url( 'admin.php?page=af-speed' ); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-performance" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></span>
                        <?php esc_html_e( '2. Speed Booster', 'af-companion' ); ?>
                    </a>

                    <a href="<?php echo admin_url( 'admin.php?page=af-growth' ); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-chart-line" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></span>
                        <?php esc_html_e( '3. Growth Tools', 'af-companion' ); ?>
                    </a>

                    <a href="<?php echo admin_url( 'admin.php?page=af-status' ); ?>" class="button button-secondary" style="border-color: #6366f1; color: #6366f1;">
                        <span class="dashicons dashicons-dashboard" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></span>
                        <?php esc_html_e( '4. System Status', 'af-companion' ); ?>
                    </a>
                </div>

                <div class="aftc-notice-footer" style="margin-top: 15px; font-size: 13px;">
                    <a href="<?php echo esc_url( $this->pricing_url ); ?>" target="_blank" style="text-decoration: none; font-weight: bold; color: #d63638;">
                        <?php esc_html_e( 'Unlock 1,000+ Templates with All Themes Plan', 'af-companion' ); ?>
                    </a>
                    <span style="margin: 0 10px; color: #ccc;">|</span>
                    <a href="<?php echo esc_url( $this->dismiss_url ); ?>" style="text-decoration: none; color: #666;">
                        <?php esc_html_e( 'Dismiss permanently', 'af-companion' ); ?>
                    </a>
                </div>
            </div>
            <a class="aftc-notice-dismiss notice-dismiss" href="<?php echo esc_url( $this->temporary_dismiss_url ); ?>"></a>
        </div>
        <?php
    }
}

// Initialize the welcome notice
new AFTC_Welcome_Notice();