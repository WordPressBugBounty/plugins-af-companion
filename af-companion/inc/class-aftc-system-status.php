<?php
/**
 * AF System Status - Performance & Environment Diagnostics
 * 
 * Version: 1.3.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AF_System_Status {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ), 23 );
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

    private function get_status_label( $current, $min, $is_bool = false ) {
        // Handle PHP shorthand like '256M' or '1G'
        $current_val = $is_bool ? $current : (float) $current;
        $min_val     = (float) $min;

        $passed = $is_bool ? $current_val : ($current_val >= $min_val);
        
        return $passed 
            ? '<span class="af-status-pass">✔ ' . esc_html__('Optimal', 'af-companion') . '</span>' 
            : '<span class="af-status-fail">✘ ' . esc_html__('Action Required', 'af-companion') . '</span>';
    }

    private function get_cached_db_stats() {
        $stats = get_transient( 'af_system_db_stats' );
        
        if ( false === $stats ) {
            global $wpdb;
            
            // 1. Autoload Check
            $autoload = $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM $wpdb->options WHERE autoload = 'yes'" );
            $autoload_kb = round( (float) $autoload / 1024, 2 );
    
            // 2. Size Calculation - Using table_schema to prevent cross-db contamination
            $tables = $wpdb->get_results( $wpdb->prepare( 
                "SELECT (data_length + index_length) as size, data_free FROM information_schema.tables WHERE table_schema = %s", 
                DB_NAME 
            ), ARRAY_A );
    
            $total_bytes = 0;
            $overhead_bytes = 0;
    
            if ( ! empty( $tables ) && ! is_wp_error( $tables ) ) {
                foreach ( $tables as $table ) {
                    // Use ?? 0 to provide a fallback if the key is missing or null
                    $total_bytes    += (float) ( $table['size'] ?? 0 );
                    $overhead_bytes += (float) ( $table['data_free'] ?? 0 );
                }
            }
    
            // 3. Formatted units for display
            $stats = array(
                'autoload_kb'     => $autoload_kb,
                'total_formatted' => ( $total_bytes < 1048576 ) ? round( $total_bytes / 1024, 2 ) . ' KB' : round( $total_bytes / 1048576, 2 ) . ' MB',
                'overhead_formatted' => ( $overhead_bytes < 1048576 ) ? round( $overhead_bytes / 1024, 2 ) . ' KB' : round( $overhead_bytes / 1048576, 2 ) . ' MB',
                'overhead_raw'    => round( $overhead_bytes / 1048576, 2 ), // For logic check
                'db_version'      => $wpdb->db_version(),
            );
    
            set_transient( 'af_system_db_stats', $stats, 12 * HOUR_IN_SECONDS );
        }
        return $stats;
    }

    public function render_page() {
        $db_stats = $this->get_cached_db_stats();
        
        // Fix: Ensure we use the correct keys from get_cached_db_stats
        $db_stats = wp_parse_args( $db_stats, array(
            'autoload_kb'        => 0,
            'total_formatted'    => '0 MB',
            'overhead_formatted' => '0 MB',
            'overhead_raw'       => 0,
            'db_version'         => 'Unknown'
        ));

        $mem_limit       = ini_get('memory_limit');
        $execution_time  = ini_get('max_execution_time');
        $opcache_enabled = function_exists('opcache_get_status') && @opcache_get_status();
        $object_cache    = wp_using_ext_object_cache();

        ?>
        <div class="wrap af-status-container">
            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'System Status', 'af-companion' ); ?></h1>
                    <span class="af-badge-status"><?php esc_html_e( 'Engine Diagnostics', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Real-time environment report for your WordPress installation.', 'af-companion' ); ?></p>
            </div>

            <div class="af-status-grid">
                
                <!-- PERFORMANCE -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Performance', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong>Object Cache</strong> 
                            <span><?php echo $object_cache ? 'Enabled' : 'Disabled'; ?> <?php echo $this->get_status_label($object_cache, true, true); ?></span>
                        </li>
                        <li>
                            <strong>PHP OPcache</strong> 
                            <span><?php echo $opcache_enabled ? 'Active' : 'Inactive'; ?> <?php echo $this->get_status_label($opcache_enabled, true, true); ?></span>
                        </li>
                        <li>
                            <strong>Memory Limit</strong> 
                            <span><?php echo esc_html( $mem_limit ); ?> <?php echo $this->get_status_label($mem_limit, 256); ?></span>
                        </li>
                        <li>
                            <strong>Max Execution</strong> 
                            <span><?php echo esc_html( $execution_time ); ?>s <?php echo $this->get_status_label($execution_time, 300); ?></span>
                        </li>
                    </ul>
                </div>

                <!-- ENVIRONMENT -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Site Health', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li><strong>WP Version</strong> <span><?php echo esc_html(get_bloginfo('version')); ?></span></li>
                        <li><strong>HTTPS</strong> <span><?php echo is_ssl() ? 'Secure' : 'Insecure'; ?> <?php echo $this->get_status_label(is_ssl(), true, true); ?></span></li>
                        <li><strong>WP-Cron</strong> <span><?php echo (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? 'Manual (Off)' : 'Enabled'; ?></span></li>
                        <li><strong>Debug Mode</strong> <span><?php echo (defined('WP_DEBUG') && WP_DEBUG) ? '<span style="color:#e11d48">Active</span>' : 'Inactive'; ?></span></li>
                    </ul>
                </div>

                <!-- MEDIA -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-camera"></span> <?php esc_html_e( 'Media Handling', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li><strong>Imagick Library</strong> <span><?php echo extension_loaded('imagick') ? 'Installed' : 'Missing'; ?> <?php echo $this->get_status_label(extension_loaded('imagick'), true, true); ?></span></li>
                        <li><strong>GD Library</strong> <span><?php echo extension_loaded('gd') ? 'Installed' : 'Missing'; ?></span></li>
                        <li><strong>Upload Max Size</strong> <span><?php echo esc_html(ini_get('upload_max_filesize')); ?></span></li>
                        <li><strong>Post Max Size</strong> <span><?php echo esc_html(ini_get('post_max_size')); ?></span></li>
                    </ul>
                </div>

                <!-- DATABASE -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Database', 'af-companion' ); ?></h2>
                    <ul class="af-info-list">
                        <li>
                            <strong>Autoloaded Options</strong> 
                            <span><?php echo esc_html($db_stats['autoload_kb']); ?> KB <?php echo $this->get_status_label(($db_stats['autoload_kb'] < 800), true, true); ?></span>
                        </li>
                        <li>
                            <strong>Database Size</strong> 
                            <span><?php echo esc_html($db_stats['total_formatted']); ?></span>
                        </li>
                        <li>
                            <strong>Database Overhead</strong> 
                            <span><?php echo esc_html($db_stats['overhead_formatted']); ?> <?php echo $this->get_status_label(($db_stats['overhead_raw'] < 5), true, true); ?></span>
                        </li>
                        <li><strong>MySQL Version</strong> <span><?php echo esc_html( $db_stats['db_version'] ); ?></span></li>
                    </ul>
                </div>

            </div>
            <div class="af-status-footer">
                <p><?php esc_html_e( 'Data is cached for 12 hours.', 'af-companion' ); ?> <a href="https://afthemes.com/support/" target="_blank"><?php esc_html_e( 'Performance Guide', 'af-companion' ); ?></a></p>
            </div>
        </div>

        <style>
            .af-status-container { max-width: 1100px; margin: 20px auto; font-family: -apple-system, system-ui, sans-serif; }
            .af-status-header { margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; }
            .af-status-branding { display: flex; align-items: center; gap: 12px; }
            .af-status-branding h1 { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; }
            .af-badge-status { background: #6366f1; color: #fff; padding: 4px 12px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
            .af-subtitle { color: #64748b; font-size: 15px; margin-top: 8px; }
            .af-status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; }
            .af-status-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
            .af-card-title { font-size: 14px; font-weight: 700; color: #334155; margin: 0 0 16px 0; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
            .af-card-title .dashicons { color: #6366f1; font-size: 18px; width: 18px; height: 18px; }
            .af-info-list { list-style: none; padding: 0; margin: 0; }
            .af-info-list li { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; }
            .af-info-list li:last-child { border-bottom: none; }
            .af-info-list li strong { color: #64748b; font-weight: 500; }
            .af-info-list li span { color: #1e293b; font-weight: 600; text-align: right; }
            .af-status-pass { background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; font-weight: 700; }
            .af-status-fail { background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; font-weight: 700; }
            .af-status-footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 13px; }
            .af-status-footer a { color: #6366f1; text-decoration: none; font-weight: 600; }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated { display: none !important; }
        </style>
        <?php
    }
}