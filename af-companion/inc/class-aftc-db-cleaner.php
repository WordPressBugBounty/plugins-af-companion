<?php
/**
 * AF Database Cleaner - Enterprise Safe Database Optimization & Storage Maintenance
 * 
 * Version: 1.0.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Database_Cleaner {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 24 );

        // AJAX Processing Handlers (Preview & Execution)
        add_action( 'wp_ajax_af_db_cleaner_preview', array( $this, 'ajax_preview_cleanup' ) );
        add_action( 'wp_ajax_af_db_cleaner_execute', array( $this, 'ajax_execute_cleanup' ) );

        // Enqueue Assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu() {
        add_submenu_page(
            'af-companion',
            esc_html__( 'Database Maintenance', 'af-companion' ),
            esc_html__( 'Database Cleaner', 'af-companion' ),
            'manage_options',
            'af-db-cleaner',
            array( $this, 'render_admin_page' ),
            4
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'af-db-cleaner' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'af-db-cleaner-js',
            AFTC_URL . '/assets/js/db-cleaner.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'af-db-cleaner-js',
            'afDBData',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'af_db_cleaner_nonce' ),
                'confirm'  => esc_html__( 'Are you sure you want to perform this database cleanup? Make sure you have a site backup.', 'af-companion' ),
            )
        );
    }

    /**
     * Dry-Run Scanner (Calculate exact row counts without modifying tables)
     */
    private function scan_database_overhead() {
        global $wpdb;

        // 1. Post Revisions (Keeping latest 5 per post)
        $revisions_query = "
            SELECT COUNT(r.ID) 
            FROM {$wpdb->posts} r
            INNER JOIN {$wpdb->posts} p ON r.post_parent = p.ID
            WHERE r.post_type = 'revision'
              AND r.ID NOT IN (
                  SELECT ID FROM (
                      SELECT ID, post_parent,
                             ROW_NUMBER() OVER (PARTITION BY post_parent ORDER BY ID DESC) as rn
                      FROM {$wpdb->posts}
                      WHERE post_type = 'revision'
                  ) ranked WHERE rn <= 5
              )";
        $revisions = (int) $wpdb->get_var( $revisions_query );

        // 2. Expired Transients
        $time = time();
        $expired_transients = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(option_id) FROM {$wpdb->options} 
             WHERE option_name LIKE %s AND option_value < %d",
            '_transient_timeout_%',
            $time
        ) );

        // 3. Spam & Trashed Comments
        $bad_comments = (int) $wpdb->get_var(
            "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved IN ('spam', 'trash')"
        );

        // 4. Trashed Posts
        $trashed_posts = (int) $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );

        // 5. Orphaned Post Meta
        $orphaned_postmeta = (int) $wpdb->get_var(
            "SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} pm 
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE p.ID IS NULL"
        );

        return array(
            'revisions'          => max( 0, $revisions ),
            'expired_transients' => max( 0, $expired_transients ),
            'bad_comments'       => max( 0, $bad_comments ),
            'trashed_posts'      => max( 0, $trashed_posts ),
            'orphaned_postmeta'  => max( 0, $orphaned_postmeta ),
        );
    }

    /**
     * AJAX Dry-Run Preview
     */
    public function ajax_preview_cleanup() {
        check_ajax_referer( 'af_db_cleaner_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        $stats = $this->scan_database_overhead();
        wp_send_json_success( array( 'stats' => $stats ) );
    }

    /**
     * AJAX Batched Execution (Safe Low-Query Deletion Engine)
     */
    public function ajax_execute_cleanup() {
        check_ajax_referer( 'af_db_cleaner_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        $target = isset( $_POST['target'] ) ? sanitize_key( $_POST['target'] ) : '';
        global $wpdb;
        $deleted_count = 0;
        $batch_limit   = 500; // Low per-page query limit to prevent lockup

        switch ( $target ) {
            case 'revisions':
                $ids = $wpdb->get_col( "
                    SELECT r.ID FROM {$wpdb->posts} r
                    INNER JOIN {$wpdb->posts} p ON r.post_parent = p.ID
                    WHERE r.post_type = 'revision'
                      AND r.ID NOT IN (
                          SELECT ID FROM (
                              SELECT ID, post_parent,
                                     ROW_NUMBER() OVER (PARTITION BY post_parent ORDER BY ID DESC) as rn
                              FROM {$wpdb->posts}
                              WHERE post_type = 'revision'
                          ) ranked WHERE rn <= 5
                      )
                    LIMIT {$batch_limit}" );

                foreach ( $ids as $rev_id ) {
                    wp_delete_post_revision( absint( $rev_id ) );
                    $deleted_count++;
                }
                break;

            case 'transients':
                $time = time();
                $expired_keys = $wpdb->get_col( $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                     WHERE option_name LIKE %s AND option_value < %d 
                     LIMIT %d",
                    '_transient_timeout_%',
                    $time,
                    $batch_limit
                ) );

                foreach ( $expired_keys as $timeout_key ) {
                    $transient_name = str_replace( '_transient_timeout_', '', $timeout_key );
                    delete_transient( $transient_name );
                    $deleted_count++;
                }
                break;

            case 'bad_comments':
                $comment_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT comment_ID FROM {$wpdb->comments} 
                     WHERE comment_approved IN ('spam', 'trash') 
                     LIMIT %d",
                    $batch_limit
                ) );

                foreach ( $comment_ids as $c_id ) {
                    wp_delete_comment( absint( $c_id ), true );
                    $deleted_count++;
                }
                break;

            case 'trashed_posts':
                $post_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash' LIMIT %d",
                    $batch_limit
                ) );

                foreach ( $post_ids as $p_id ) {
                    wp_delete_post( absint( $p_id ), true );
                    $deleted_count++;
                }
                break;

            case 'orphaned_postmeta':
                $deleted_count = (int) $wpdb->query( $wpdb->prepare(
                    "DELETE pm FROM {$wpdb->postmeta} pm 
                     LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                     WHERE p.ID IS NULL LIMIT %d",
                    $batch_limit
                ) );
                break;

            default:
                wp_send_json_error( array( 'message' => esc_html__( 'Invalid cleanup target selected.', 'af-companion' ) ) );
        }

        wp_send_json_success( array(
            'target'        => $target,
            'deleted_count' => $deleted_count,
            'message'       => sprintf( esc_html__( 'Successfully cleaned %d records.', 'af-companion' ), $deleted_count ),
        ) );
    }

    /**
     * Render Administration UI
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $stats = $this->scan_database_overhead();
        ?>
        <?php AF_Admin_Header::render( 'af-db-cleaner' ); ?>
        <div class="wrap af-status-container">
            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'Database Maintenance', 'af-companion' ); ?></h1>
                    <span class="af-badge-status" style="background:#0284c7;"><?php esc_html_e( 'Safe Optimization', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Dry-run preview, bulk clean post revisions, purge expired transients, and remove orphaned meta without server timeouts.', 'af-companion' ); ?></p>
            </div>

            <!-- Maintenance Actions Grid -->
            <div class="af-status-grid">

                <!-- POST REVISIONS -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Old Revisions', 'af-companion' ); ?></h2>
                    <p style="color:#64748b; font-size:13px; margin-bottom:15px;"><?php esc_html_e( 'Cleans historical draft revisions while automatically retaining the 5 most recent entries per post.', 'af-companion' ); ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:22px; font-weight:800; color:#0f172a;" id="stat-revisions"><?php echo esc_html( number_format_i18n( $stats['revisions'] ) ); ?></span>
                        <button type="button" class="button button-primary af-clean-btn" data-target="revisions"><?php esc_html_e( 'Clean Revisions', 'af-companion' ); ?></button>
                    </div>
                </div>

                <!-- EXPIRED TRANSIENTS -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Expired Transients', 'af-companion' ); ?></h2>
                    <p style="color:#64748b; font-size:13px; margin-bottom:15px;"><?php esc_html_e( 'Removes temporary cached options that have already expired from the wp_options table.', 'af-companion' ); ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:22px; font-weight:800; color:#0f172a;" id="stat-transients"><?php echo esc_html( number_format_i18n( $stats['expired_transients'] ) ); ?></span>
                        <button type="button" class="button button-primary af-clean-btn" data-target="transients"><?php esc_html_e( 'Purge Transients', 'af-companion' ); ?></button>
                    </div>
                </div>

                <!-- SPAM & TRASH COMMENTS -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-admin-comments"></span> <?php esc_html_e( 'Spam & Trash Comments', 'af-companion' ); ?></h2>
                    <p style="color:#64748b; font-size:13px; margin-bottom:15px;"><?php esc_html_e( 'Permanently purges comments currently marked as spam or residing in the comment trash.', 'af-companion' ); ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:22px; font-weight:800; color:#0f172a;" id="stat-bad_comments"><?php echo esc_html( number_format_i18n( $stats['bad_comments'] ) ); ?></span>
                        <button type="button" class="button button-primary af-clean-btn" data-target="bad_comments"><?php esc_html_e( 'Empty Comment Trash', 'af-companion' ); ?></button>
                    </div>
                </div>

                <!-- ORPHANED META -->
                <div class="af-status-card">
                    <h2 class="af-card-title"><span class="dashicons dashicons-database-remove"></span> <?php esc_html_e( 'Orphaned Postmeta', 'af-companion' ); ?></h2>
                    <p style="color:#64748b; font-size:13px; margin-bottom:15px;"><?php esc_html_e( 'Removes post metadata entries left behind from posts or products that no longer exist.', 'af-companion' ); ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:22px; font-weight:800; color:#0f172a;" id="stat-orphaned_postmeta"><?php echo esc_html( number_format_i18n( $stats['orphaned_postmeta'] ) ); ?></span>
                        <button type="button" class="button button-primary af-clean-btn" data-target="orphaned_postmeta"><?php esc_html_e( 'Clean Orphaned Meta', 'af-companion' ); ?></button>
                    </div>
                </div>

            </div>

            <div style="margin-top:25px; display: flex; align-items: center; justify-content: flex-end; gap: 15px;">
                <span id="af-scan-notice" style="font-size: 13px; color: #10b981; font-weight: 600; display: none; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px;"></span> 
                    <?php esc_html_e( 'Database re-scanned successfully!', 'af-companion' ); ?>
                </span>
                <button type="button" id="af-db-refresh-scan" class="button button-secondary">
                    <?php esc_html_e( 'Re-Scan Database Overhead', 'af-companion' ); ?>
                </button>
            </div>
        </div>

        <style>
            .af-status-container { max-width: 1000px; margin: 20px auto 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1d2327; }
            .af-status-header { margin-bottom: 30px; border-bottom: 1px solid #dcdcde; padding-bottom: 20px; }
            .af-status-branding { display: flex; align-items: center; gap: 15px; }
            .af-status-branding h1 { font-size: 28px; font-weight: 800; color: #1d2327; margin: 0; }
            .af-badge-status { color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
            .af-subtitle { color: #646970; font-size: 15px; margin-top: 8px; }
            .af-status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 25px; }
            .af-status-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; padding: 25px; }
            .af-card-title { font-size: 16px; font-weight: 700; color: #1d2327; margin: 0 0 12px 0; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
            .af-card-title .dashicons { color: #0284c7; font-size: 20px; width: 20px; height: 20px; }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated, .update-nag.gt-admin-notice { display: none !important; }
        </style>
        <?php
    }
}