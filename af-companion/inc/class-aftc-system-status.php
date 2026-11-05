<?php
/**
 * AF System Status - Performance & Environment Diagnostics Engine
 * 
 * Version: 2.0.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_System_Status {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ), 23 );
        add_action( 'admin_post_af_convert_myisam_tables', array( $this, 'handle_myisam_conversion' ) );
    }

    public function add_menu() {
        add_submenu_page(
            'af-companion', 
            esc_html__( 'System Status', 'af-companion' ), 
            esc_html__( 'System Status', 'af-companion' ), 
            'manage_options', 
            'af-status', 
            array( $this, 'render_page' ),
            3
        );
    }

    private function get_status_label( $passed, $pass_text = 'Optimal', $fail_text = 'Action Required' ) {
        return $passed 
            ? '<span class="af-status-pass">✔ ' . esc_html( $pass_text ) . '</span>' 
            : '<span class="af-status-fail">✘ ' . esc_html( $fail_text ) . '</span>';
    }

    /**
     * Run Diagnostics Suite (Cached briefly to prevent admin load lag)
     */
    private function run_diagnostics() {
        $diagnostics = get_transient( 'af_system_deep_diagnostics' );

        if ( false === $diagnostics ) {
            global $wpdb;

            // 1. Loopback Request Check
            $loopback_response = wp_remote_get( site_url( 'wp-cron.php' ), array(
                'timeout'   => 5,
                'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            ) );
            $loopup_code = is_wp_error( $loopback_response ) ? 0 : wp_remote_retrieve_response_code( $loopback_response );
            $loopback_ok = ( $loopup_code >= 200 && $loopup_code < 400 );

            // 2. REST API Check
            $rest_response = wp_remote_get( rest_url( 'wp/v2/types' ), array( 'timeout' => 5 ) );
            $rest_code     = is_wp_error( $rest_response ) ? 0 : wp_remote_retrieve_response_code( $rest_response );
            $rest_ok       = ( $rest_code === 200 );

            // 3. PHP Extensions Audit
            $required_extensions = array( 'curl', 'gd', 'mbstring', 'xml', 'zip', 'openssl' );
            $missing_extensions  = array();
            foreach ( $required_extensions as $ext ) {
                if ( ! extension_loaded( $ext ) ) {
                    $missing_extensions[] = $ext;
                }
            }

            // 4. Cron Health & Missed Schedules
            $cron_jobs     = _get_cron_array();
            $missed_cron   = false;
            $current_time  = time();
            if ( ! empty( $cron_jobs ) && is_array( $cron_jobs ) ) {
                foreach ( $cron_jobs as $timestamp => $cronhooks ) {
                    if ( $timestamp < ( $current_time - 900 ) ) { // Overdue by > 15 mins
                        $missed_cron = true;
                        break;
                    }
                }
            }

            // 5. Database Engine & MyISAM Check
            $myisam_tables = $wpdb->get_col( $wpdb->prepare(
                "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND ENGINE = 'MyISAM'",
                DB_NAME
            ) );

            $diagnostics = array(
                'loopback_ok'         => $loopback_ok,
                'loopback_code'       => $loopup_code,
                'rest_ok'             => $rest_ok,
                'rest_code'           => $rest_code,
                'missing_extensions'  => $missing_extensions,
                'missed_cron'         => $missed_cron,
                'myisam_tables'       => is_array( $myisam_tables ) ? $myisam_tables : array(),
            );

            set_transient( 'af_system_deep_diagnostics', $diagnostics, 1 * HOUR_IN_SECONDS );
        }

        return $diagnostics;
    }

    private function get_cached_db_stats() {
        $stats = get_transient( 'af_system_db_stats' );
        
        if ( false === $stats ) {
            global $wpdb;
            
            $autoload    = $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'" );
            $autoload_kb = round( (float) $autoload / 1024, 2 );
    
            $tables = $wpdb->get_results( $wpdb->prepare( 
                "SELECT (data_length + index_length) as size, data_free FROM information_schema.tables WHERE table_schema = %s", 
                DB_NAME 
            ), ARRAY_A );
    
            $total_bytes    = 0;
            $overhead_bytes = 0;
    
            if ( ! empty( $tables ) && ! is_wp_error( $tables ) ) {
                foreach ( $tables as $table ) {
                    $total_bytes    += (float) ( $table['size'] ?? 0 );
                    $overhead_bytes += (float) ( $table['data_free'] ?? 0 );
                }
            }
    
            $stats = array(
                'autoload_kb'        => $autoload_kb,
                'total_formatted'    => ( $total_bytes < 1048576 ) ? round( $total_bytes / 1024, 2 ) . ' KB' : round( $total_bytes / 1048576, 2 ) . ' MB',
                'overhead_formatted' => ( $overhead_bytes < 1048576 ) ? round( $overhead_bytes / 1024, 2 ) . ' KB' : round( $overhead_bytes / 1048576, 2 ) . ' MB',
                'overhead_raw'       => round( $overhead_bytes / 1048576, 2 ),
                'db_version'         => $wpdb->db_version(),
            );
    
            set_transient( 'af_system_db_stats', $stats, 12 * HOUR_IN_SECONDS );
        }
        return $stats;
    }

    public function handle_myisam_conversion() {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'af_convert_myisam_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'af-companion' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'af-companion' ) );
        }

        global $wpdb;
        $myisam_tables = $wpdb->get_col( $wpdb->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND ENGINE = 'MyISAM'",
            DB_NAME
        ) );

        if ( ! empty( $myisam_tables ) ) {
            foreach ( $myisam_tables as $table ) {
                // Sanitize table name since table names cannot be prepared as parameters in SQL
                $safe_table = preg_replace( '/[^a-zA-Z0-9_$]/', '', $table );
                $wpdb->query( "ALTER TABLE `{$safe_table}` ENGINE=InnoDB" );
            }
            delete_transient( 'af_system_deep_diagnostics' );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=af-status&converted=true' ) );
        exit;
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $db_stats    = $this->get_cached_db_stats();
        $diagnostics = $this->run_diagnostics();
        
        $db_stats = wp_parse_args( $db_stats, array(
            'autoload_kb'        => 0,
            'total_formatted'    => '0 MB',
            'overhead_formatted' => '0 MB',
            'overhead_raw'       => 0,
            'db_version'         => 'Unknown'
        ) );

        $mem_limit       = ini_get( 'memory_limit' );
        $execution_time  = ini_get( 'max_execution_time' );
        $opcache_enabled = function_exists( 'opcache_get_status' ) && @opcache_get_status();
        $object_cache    = wp_using_ext_object_cache();
        $upload_max      = ini_get( 'upload_max_filesize' );
        $post_max        = ini_get( 'post_max_size' );
        ?>
        <?php AF_Admin_Header::render( 'af-status' ); ?>
        <div class="wrap af-status-container">
            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'System Status', 'af-companion' ); ?></h1>
                    <span class="af-badge-status"><?php esc_html_e( 'Engine Diagnostics', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Real-time environment report, server security health, and actionable fix guides.', 'af-companion' ); ?></p>
            </div>

            <?php if ( isset( $_GET['converted'] ) && $_GET['converted'] === 'true' ) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom: 20px;"><p><?php esc_html_e( 'Legacy MyISAM tables were successfully converted to InnoDB engine.', 'af-companion' ); ?></p></div>
            <?php endif; ?>

            <div class="af-status-grid">
                
                <!-- PERFORMANCE -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Performance & Core Limits', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong><?php esc_html_e( 'Object Cache', 'af-companion' ); ?></strong> 
                            <span><?php echo $object_cache ? esc_html__( 'Enabled', 'af-companion' ) : esc_html__( 'Disabled', 'af-companion' ); ?> <?php echo $this->get_status_label( $object_cache ); ?></span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'PHP OPcache', 'af-companion' ); ?></strong> 
                            <span><?php echo $opcache_enabled ? esc_html__( 'Active', 'af-companion' ) : esc_html__( 'Inactive', 'af-companion' ); ?> <?php echo $this->get_status_label( $opcache_enabled ); ?></span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Memory Limit', 'af-companion' ); ?></strong> 
                            <span><?php echo esc_html( $mem_limit ); ?> <?php echo $this->get_status_label( wp_convert_hr_to_bytes( $mem_limit ) >= 268435456 ); ?></span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Max Execution', 'af-companion' ); ?></strong> 
                            <span><?php echo esc_html( $execution_time ); ?>s <?php echo $this->get_status_label( (int) $execution_time >= 60 ); ?></span>
                        </li>
                    </ul>
                </div>

                <!-- SITE HEALTH & CONNECTIVITY -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Connectivity & Health', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong><?php esc_html_e( 'Loopback Request', 'af-companion' ); ?></strong>
                            <span>
                                <?php echo $diagnostics['loopback_ok'] ? esc_html__( 'Passed', 'af-companion' ) : sprintf( esc_html__( 'Failed (%d)', 'af-companion' ), $diagnostics['loopback_code'] ); ?>
                                <?php echo $this->get_status_label( $diagnostics['loopback_ok'] ); ?>
                            </span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'REST API Access', 'af-companion' ); ?></strong>
                            <span>
                                <?php echo $diagnostics['rest_ok'] ? esc_html__( 'Available', 'af-companion' ) : sprintf( esc_html__( 'Blocked (%d)', 'af-companion' ), $diagnostics['rest_code'] ); ?>
                                <?php echo $this->get_status_label( $diagnostics['rest_ok'] ); ?>
                            </span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'WP-Cron Status', 'af-companion' ); ?></strong>
                            <span>
                                <?php echo $diagnostics['missed_cron'] ? '<span style="color:#dc2626;">' . esc_html__( 'Delayed Jobs', 'af-companion' ) . '</span>' : esc_html__( 'Healthy', 'af-companion' ); ?>
                                <?php echo $this->get_status_label( ! $diagnostics['missed_cron'] ); ?>
                            </span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'HTTPS Status', 'af-companion' ); ?></strong>
                            <span><?php echo is_ssl() ? esc_html__( 'Secure', 'af-companion' ) : esc_html__( 'Insecure', 'af-companion' ); ?> <?php echo $this->get_status_label( is_ssl() ); ?></span>
                        </li>
                    </ul>
                </div>

                <!-- MEDIA & ENVIRONMENT -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-camera"></span> <?php esc_html_e( 'Environment & Media', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong><?php esc_html_e( 'PHP Extensions', 'af-companion' ); ?></strong>
                            <span>
                                <?php if ( empty( $diagnostics['missing_extensions'] ) ) : ?>
                                    <span class="af-status-pass">✔ <?php esc_html_e( 'All Present', 'af-companion' ); ?></span>
                                <?php else : ?>
                                    <span class="af-status-fail">✘ <?php echo esc_html( implode( ', ', $diagnostics['missing_extensions'] ) ); ?></span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li><strong><?php esc_html_e( 'Imagick / GD', 'af-companion' ); ?></strong> <span><?php echo extension_loaded( 'imagick' ) ? 'Imagick' : ( extension_loaded( 'gd' ) ? 'GD Library' : 'None' ); ?></span></li>
                        <li><strong><?php esc_html_e( 'Upload Max Size', 'af-companion' ); ?></strong> <span><?php echo esc_html( $upload_max ); ?></span></li>
                        <li><strong><?php esc_html_e( 'Post Max Size', 'af-companion' ); ?></strong> <span><?php echo esc_html( $post_max ); ?></span></li>
                    </ul>
                </div>

                <!-- DATABASE -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Database Diagnostics', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong><?php esc_html_e( 'Autoloaded Options', 'af-companion' ); ?></strong> 
                            <span><?php echo esc_html( $db_stats['autoload_kb'] ); ?> KB <?php echo $this->get_status_label( ( $db_stats['autoload_kb'] < 800 ) ); ?></span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Database Overhead', 'af-companion' ); ?></strong> 
                            <span><?php echo esc_html( $db_stats['overhead_formatted'] ); ?> <?php echo $this->get_status_label( ( $db_stats['overhead_raw'] < 5 ) ); ?></span>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Table Engine', 'af-companion' ); ?></strong>
                            <span>
                                <?php if ( empty( $diagnostics['myisam_tables'] ) ) : ?>
                                    <span class="af-status-pass">✔ InnoDB</span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=af_convert_myisam_tables' ), 'af_convert_myisam_nonce' ) ); ?>" class="button button-small button-secondary" onclick="return confirm('<?php esc_attr_e( 'Convert all legacy MyISAM tables to InnoDB?', 'af-companion' ); ?>');">
                                        <?php printf( esc_html__( 'Convert %d MyISAM', 'af-companion' ), count( $diagnostics['myisam_tables'] ) ); ?>
                                    </a>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li><strong><?php esc_html_e( 'MySQL Version', 'af-companion' ); ?></strong> <span><?php echo esc_html( $db_stats['db_version'] ); ?></span></li>
                    </ul>
                </div>

            </div>

            <!-- Actionable Recommendations Guidance Box -->
            <?php if ( ! $diagnostics['loopback_ok'] || ! $diagnostics['rest_ok'] || $diagnostics['missed_cron'] || ! empty( $diagnostics['missing_extensions'] ) || ! empty( $diagnostics['myisam_tables'] ) ) : ?>
                <div style="background:#fff; border:1px solid #cbd5e1; border-left:4px solid #dc2626; border-radius:8px; padding:20px; margin-top:25px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
                    <h3 style="margin:0 0 10px 0; font-size:16px; color:#1d2327; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-warning" style="color:#dc2626;"></span> <?php esc_html_e( 'Actionable Recommendations & Fix Guide', 'af-companion' ); ?>
                    </h3>
                    <ul style="margin:0; padding-left:20px; color:#475569; font-size:14px; line-height:1.6;">
                        <?php if ( ! $diagnostics['loopback_ok'] ) : ?>
                            <li><strong><?php esc_html_e( 'Loopback HTTP Request Failed:', 'af-companion' ); ?></strong> <?php esc_html_e( 'Your server rejected an internal HTTP request (Code: ', 'af-companion' ); echo esc_html( $diagnostics['loopback_code'] ); ?>). <?php esc_html_e( 'Scheduled posts, automated webhooks, and background WP-Cron execution will fail. Check your firewall settings, Cloudflare WAF rules, or server SSL configuration.', 'af-companion' ); ?></li>
                        <?php endif; ?>
                        <?php if ( ! $diagnostics['rest_ok'] ) : ?>
                            <li><strong><?php esc_html_e( 'REST API Blocked:', 'af-companion' ); ?></strong> <?php esc_html_e( 'The WordPress REST API returned a non-200 status code. The Gutenberg Block Editor and frontend widgets in AF Companion require REST access to function properly. Check security plugins (like Wordfence or iThemes) restricting wp-json requests.', 'af-companion' ); ?></li>
                        <?php endif; ?>
                        <?php if ( $diagnostics['missed_cron'] ) : ?>
                            <li><strong><?php esc_html_e( 'Delayed Cron Tasks Detected:', 'af-companion' ); ?></strong> <?php esc_html_e( 'Some scheduled tasks are overdue by over 15 minutes. This usually stems from blocked loopback requests or disabled default WP-Cron. Consider switching to a real server cron job via cPanel.', 'af-companion' ); ?></li>
                        <?php endif; ?>
                        <?php if ( ! empty( $diagnostics['missing_extensions'] ) ) : ?>
                            <li><strong><?php esc_html_e( 'Missing PHP Extensions:', 'af-companion' ); ?></strong> <?php printf( esc_html__( 'Your server lacks required extensions: %s. Contact your host to enable them for full performance.', 'af-companion' ), esc_html( implode( ', ', $diagnostics['missing_extensions'] ) ) ); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="af-status-footer">
                <p><?php esc_html_e( 'Diagnostics are cached hourly.', 'af-companion' ); ?> <a href="https://afthemes.com/support/" target="_blank"><?php esc_html_e( 'Performance Guide', 'af-companion' ); ?></a></p>
            </div>
        </div>

        <style>
            .af-status-container { max-width: 1000px; margin: 20px auto 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; color: #1d2327; }
            .af-status-header { margin-bottom: 30px; border-bottom: 1px solid #dcdcde; padding-bottom: 20px; }
            .af-status-branding { display: flex; align-items: center; gap: 15px; }
            .af-status-branding h1 { font-size: 28px; font-weight: 800; color: #1d2327; margin: 0; }
            .af-badge-status { background: #6366f1; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
            .af-subtitle { color: #646970; font-size: 15px; margin-top: 8px; }
            .af-status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 25px; }
            .af-status-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; padding: 25px; }
            .af-card-title { font-size: 14px; font-weight: 700; color: #334155; margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
            .af-card-title .dashicons { color: #6366f1; font-size: 18px; width: 18px; height: 18px; }
            .af-info-list { list-style: none; padding: 0; margin: 0; }
            .af-info-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; }
            .af-info-list li:last-child { border-bottom: none; }
            .af-info-list li strong { color: #646970; font-weight: 500; }
            .af-info-list li span { color: #1e293b; font-weight: 600; text-align: right; }
            .af-status-pass { background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; font-weight: 700; }
            .af-status-fail { background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; font-weight: 700; }
            .af-status-footer { margin-top: 30px; text-align: center; color: #646970; font-size: 13px; }
            .af-status-footer a { color: #2271b1; text-decoration: none; font-weight: 600; }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated, .update-nag.gt-admin-notice { display: none !important; }
        </style>
        <?php
    }
}