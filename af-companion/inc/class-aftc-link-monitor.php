<?php
/**
 * AF Link Monitor - Broken Link & Health Scanner Engine
 * 
 * Version: 1.0.1
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Link_Monitor {

    public function __construct() {
        // Register top-level admin menu
        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        // AJAX Handlers for Scanning and Re-checking Links
        add_action( 'wp_ajax_af_link_run_scan', array( $this, 'ajax_run_scan' ) );
        add_action( 'wp_ajax_af_link_recheck_single', array( $this, 'ajax_recheck_single' ) );

        // Enqueue Assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Create Custom Table on activation/load if missing
        add_action( 'init', array( $this, 'maybe_create_table' ) );
    }

    /**
     * Register Top-Level Menu Page
     */
    public function register_menu() {
        add_menu_page(
            esc_html__( 'Link Monitor', 'af-companion' ),
            esc_html__( 'Link Monitor', 'af-companion' ),
            'manage_options',
            'af-link-monitor',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-links',
            36
        );
    }

    /**
     * Enqueue Assets
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'af-link-monitor' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'af-link-monitor-js',
            AFTC_URL . '/assets/js/link-monitor.js',
            array( 'jquery' ),
            '1.0.1',
            true
        );

        wp_localize_script(
            'af-link-monitor-js',
            'afLinkData',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'af_link_nonce' ),
            )
        );
    }

    /**
     * Ensure Custom Database Table Exists
     */
    public function maybe_create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'af_link_monitor';

        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id BIGINT(20) UNSIGNED NOT NULL,
                url TEXT NOT NULL,
                url_hash CHAR(32) NOT NULL,
                anchor_text TEXT NULL,
                http_code INT(4) DEFAULT 0,
                status VARCHAR(20) DEFAULT 'unverified',
                last_checked DATETIME DEFAULT NULL,
                error_message VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY post_id (post_id),
                KEY url_hash (url_hash),
                KEY status (status)
            ) {$charset_collate};";

            dbDelta( $sql );
        }
    }

    /**
     * Get Link Statistics Summary
     */
    private function get_link_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'af_link_monitor';

        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
            return array( 'total' => 0, 'working' => 0, 'broken' => 0, 'unverified' => 0 );
        }

        $total      = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name}" );
        $working    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name} WHERE status = 'working'" );
        $broken     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name} WHERE status = 'broken'" );
        $unverified = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name} WHERE status = 'unverified'" );

        return array(
            'total'      => $total,
            'working'    => $working,
            'broken'     => $broken,
            'unverified' => $unverified,
        );
    }

    /**
     * Render Main Admin Dashboard
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'af_link_monitor';
        $stats      = $this->get_link_stats();

        $status_filter = isset( $_GET['link_status'] ) ? sanitize_key( $_GET['link_status'] ) : 'all';
        $current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page      = 20;
        $offset        = ( $current_page - 1 ) * $per_page;

        $where = '1=1';
        if ( $status_filter !== 'all' ) {
            $where = $wpdb->prepare( 'status = %s', $status_filter );
        }

        $links = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );

        $total_items = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name} WHERE {$where}" );
        $total_pages = ceil( $total_items / $per_page );
        ?>
        <?php AF_Admin_Header::render( 'af-link-monitor' ); ?>
        <div class="wrap af-status-container">
            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'Link Monitor', 'af-companion' ); ?></h1>
                    <span class="af-badge-status"><?php esc_html_e( 'Site Health', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Scan published posts and pages for internal and external links, detect HTTP errors, and fix broken destinations.', 'af-companion' ); ?></p>
            </div>

            <!-- Metrics Cards -->
            <div class="af-cm-metrics">
                <div class="af-cm-card">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Total Links Scanned', 'af-companion' ); ?></span>
                </div>
                <div class="af-cm-card card-warning">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['broken'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Broken Links (404/Err)', 'af-companion' ); ?></span>
                </div>
                <div class="af-cm-card card-success">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['working'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Working Links', 'af-companion' ); ?></span>
                </div>
            </div>

            <!-- Toolbar & Scan Trigger -->
            <div class="af-cm-toolbar">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                    <input type="hidden" name="page" value="af-link-monitor" />
                    <label for="link_status"><?php esc_html_e( 'Filter Status:', 'af-companion' ); ?></label>
                    <select name="link_status" id="link_status">
                        <option value="all" <?php selected( $status_filter, 'all' ); ?>><?php esc_html_e( 'All Links', 'af-companion' ); ?></option>
                        <option value="working" <?php selected( $status_filter, 'working' ); ?>><?php esc_html_e( 'Working', 'af-companion' ); ?></option>
                        <option value="broken" <?php selected( $status_filter, 'broken' ); ?>><?php esc_html_e( 'Broken', 'af-companion' ); ?></option>
                        <option value="unverified" <?php selected( $status_filter, 'unverified' ); ?>><?php esc_html_e( 'Unverified', 'af-companion' ); ?></option>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'af-companion' ); ?></button>
                </form>

                <button type="button" id="af-run-scan-btn" class="button button-primary">
                    <?php esc_html_e( 'Run Content Link Scan Now', 'af-companion' ); ?>
                </button>
            </div>

            <div id="af-scan-progress" style="display:none; background:#e0f2fe; border:1px solid #bae6fd; padding:10px 15px; border-radius:6px; margin-bottom:16px; color:#0369a1; font-weight:600;">
                <?php esc_html_e( 'Scanning content and verifying HTTP links... Please do not close this window.', 'af-companion' ); ?>
            </div>

            <!-- Links Table -->
            <table class="wp-list-table widefat fixed striped af-cm-table">
                <thead>
                    <tr>
                        <th style="width: 35%;"><?php esc_html_e( 'Target URL', 'af-companion' ); ?></th>
                        <th style="width: 20%;"><?php esc_html_e( 'Source Post', 'af-companion' ); ?></th>
                        <th style="width: 15%;"><?php esc_html_e( 'HTTP Status', 'af-companion' ); ?></th>
                        <th style="width: 15%;"><?php esc_html_e( 'Link State', 'af-companion' ); ?></th>
                        <th style="width: 15%; text-align: right;"><?php esc_html_e( 'Actions', 'af-companion' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $links ) ) : ?>
                        <?php foreach ( $links as $link ) : 
                            $badge_class = 'status-fresh';
                            if ( $link->status === 'broken' ) {
                                $badge_class = 'status-stale';
                            } elseif ( $link->status === 'unverified' ) {
                                $badge_class = '';
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $link->url ); ?>" target="_blank" style="word-break: break-all;">
                                        <?php echo esc_html( $link->url ); ?>
                                    </a>
                                    <div style="color:#64748b; font-size:11px; margin-top:2px;">
                                        <?php esc_html_e( 'Anchor:', 'af-companion' ); ?> <em><?php echo esc_html( $link->anchor_text ? $link->anchor_text : __( 'N/A', 'af-companion' ) ); ?></em>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_post_link( $link->post_id ) ); ?>" target="_blank">
                                        <?php echo esc_html( get_the_title( $link->post_id ) ); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $link->http_code ? $link->http_code : '---' ); ?></strong>
                                    <?php if ( $link->error_message ) : ?>
                                        <div style="color:#dc2626; font-size:10px;"><?php echo esc_html( $link->error_message ); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="af-status-pill <?php echo esc_attr( $badge_class ); ?>">
                                        <?php echo esc_html( ucfirst( $link->status ) ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="button button-small af-recheck-link-btn" data-link-id="<?php echo esc_attr( $link->id ); ?>">
                                        <?php esc_html_e( 'Recheck', 'af-companion' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No links found. Click "Run Content Link Scan Now" to index your content.', 'af-companion' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="af-cm-pagination">
                    <?php
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ) );
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Parse Published Posts, Extract Links, and Verify HTTP Status
     */
    public function ajax_run_scan() {
        check_ajax_referer( 'af_link_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'af_link_monitor';

        // Fetch recent published posts/pages
        $posts = get_posts( array(
            'post_type'      => array( 'post', 'page' ),
            'post_status'    => 'publish',
            'posts_per_page' => 3, // Batched for performance on local/slow environments
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $scanned_count = 0;

        foreach ( $posts as $post ) {
            $content = $post->post_content;
            
            // Regex to extract href attributes from HTML anchor tags
            preg_match_all( '/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', $content, $matches, PREG_SET_ORDER );

            foreach ( $matches as $match ) {
                $url         = esc_url_raw( $match[1] );
                $anchor_text = wp_strip_all_tags( $match[2] );
                
                if ( empty( $url ) || strpos( $url, '#' ) === 0 || strpos( $url, 'mailto:' ) === 0 ) {
                    continue;
                }

                $url_hash = md5( $url );

                // Verify HTTP Status
                $check  = $this->test_url( $url );
                $status = $check['status'];
                $code   = $check['code'];
                $error  = $check['error'];

                // Insert or Update database record
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE post_id = %d AND url_hash = %s", $post->ID, $url_hash ) );

                if ( $exists ) {
                    $wpdb->update(
                        $table_name,
                        array(
                            'http_code'     => $code,
                            'status'        => $status,
                            'last_checked'  => current_time( 'mysql' ),
                            'error_message' => $error,
                        ),
                        array( 'id' => $exists ),
                        array( '%d', '%s', '%s', '%s' ),
                        array( '%d' )
                    );
                } else {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'post_id'       => $post->ID,
                            'url'           => $url,
                            'url_hash'      => $url_hash,
                            'anchor_text'   => $anchor_text,
                            'http_code'     => $code,
                            'status'        => $status,
                            'last_checked'  => current_time( 'mysql' ),
                            'error_message' => $error,
                        ),
                        array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
                    );
                }
                $scanned_count++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( esc_html__( 'Scan completed successfully. Indexed and checked %d links.', 'af-companion' ), $scanned_count ),
        ) );
    }

    /**
     * AJAX: Recheck a Single Link Record
     */
    public function ajax_recheck_single() {
        check_ajax_referer( 'af_link_nonce', 'nonce' );

        $link_id = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
        if ( ! $link_id || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'af_link_monitor';
        $link       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $link_id ) );

        if ( ! $link ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Link not found.', 'af-companion' ) ) );
        }

        $check = $this->test_url( $link->url );
        $wpdb->update(
            $table_name,
            array(
                'http_code'     => $check['code'],
                'status'        => $check['status'],
                'last_checked'  => current_time( 'mysql' ),
                'error_message' => $check['error'],
            ),
            array( 'id' => $link_id ),
            array( '%d', '%s', '%s', '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array(
            'code'   => $check['code'],
            'status' => ucfirst( $check['status'] ),
        ) );
    }

    /**
     * Helper: Test URL Connection Status
     */
    private function test_url( $url ) {
        if ( ! wp_http_validate_url( $url ) ) {
            return array( 'code' => 0, 'status' => 'broken', 'error' => esc_html__( 'Invalid URL structure', 'af-companion' ) );
        }

        $args = array(
            'timeout'     => 4,
            'redirection' => 3,
            'user-agent'  => 'AF-Companion-LinkChecker/1.0',
            'sslverify'   => false,
        );

        $response = wp_remote_head( $url, $args );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 405 ) {
            $response = wp_remote_get( $url, $args );
        }

        if ( is_wp_error( $response ) ) {
            return array(
                'code'   => 0,
                'status' => 'broken',
                'error'  => $response->get_error_message(),
            );
        }

        $code   = (int) wp_remote_retrieve_response_code( $response );
        $status = 'working';

        if ( $code >= 400 && $code < 500 && ! in_array( $code, array( 403, 429 ), true ) ) {
            $status = 'broken';
        } elseif ( $code >= 500 ) {
            $status = 'broken';
        } elseif ( in_array( $code, array( 403, 429 ), true ) ) {
            $status = 'unverified'; // Firewall/Bot protection block, not necessarily dead link
        }

        return array(
            'code'   => $code,
            'status' => $status,
            'error'  => '',
        );
    }
}