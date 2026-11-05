<?php
/**
 * AF Alt Text Manager - Media Accessibility & Audit Engine
 * 
 * Version: 1.0.1
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Alt_Text_Manager {

    public function __construct() {
        // Register top-level admin menu (bulletproof routing)
        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        // AJAX Handlers for Inline Alt Text Updates
        add_action( 'wp_ajax_af_alt_quick_update', array( $this, 'ajax_quick_update' ) );

        // Enqueue Assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register Top-Level Menu Page
     */
    public function register_menu() {
        add_menu_page(
            esc_html__( 'Alt Text Manager', 'af-companion' ),
            esc_html__( 'Alt Text Manager', 'af-companion' ),
            'upload_files',
            'af-alt-manager',
            array( $this, 'render_admin_page' ),
            'dashicons-format-image',
            35
        );
    }

    /**
     * Enqueue Styles & Scripts
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'af-alt-manager' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'af-alt-manager-js',
            AFTC_URL . '/assets/js/alt-manager.js',
            array( 'jquery' ),
            '1.0.1',
            true
        );

        wp_localize_script(
            'af-alt-manager-js',
            'afAltData',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'af_alt_nonce' ),
            )
        );
    }

    /**
     * Fast SQL Media Statistics Aggregation
     */
    private function get_alt_stats() {
        global $wpdb;

        $total = (int) $wpdb->get_var( "
            SELECT COUNT(ID) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
        " );

        if ( $total === 0 ) {
            return array( 'total' => 0, 'missing' => 0, 'valid' => 0 );
        }

        $missing = (int) $wpdb->get_var( "
            SELECT COUNT(p.ID) 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} m ON (p.ID = m.post_id AND m.meta_key = '_wp_attachment_image_alt')
            WHERE p.post_type = 'attachment' 
              AND p.post_mime_type LIKE 'image/%'
              AND (m.meta_value IS NULL OR TRIM(m.meta_value) = '')
        " );

        $missing = max( 0, $missing );
        $valid   = max( 0, $total - $missing ); // Fixed calculation glitch

        return array(
            'total'   => $total,
            'missing' => $missing,
            'valid'   => $valid,
        );
    }

    /**
     * Query Media Attachments Safely with Filters
     */
    private function get_media_attachments( $filter = 'all', $paged = 1, $per_page = 20 ) {
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'paged'          => $paged,
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $filter === 'missing' ) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => '',
                    'compare' => '=',
                ),
            );
        } elseif ( $filter === 'valid' ) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => '',
                    'compare' => '!=',
                ),
            );
        }

        return new WP_Query( $args );
    }

    /**
     * Main Administration View & Report Dashboard
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $filter       = isset( $_GET['alt_filter'] ) ? sanitize_key( $_GET['alt_filter'] ) : 'all';
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page     = 20;

        $stats = $this->get_alt_stats();
        $query = $this->get_media_attachments( $filter, $current_page, $per_page );
        ?>
        <?php AF_Admin_Header::render( 'af-alt-manager' ); ?>
        <div class="wrap af-status-container">

            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'Alt Text Manager', 'af-companion' ); ?></h1>
                    <span class="af-badge-status"><?php esc_html_e( 'Media Accessibility', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Audit your media library, identify images missing alternative text, and optimize web accessibility instantly.', 'af-companion' ); ?></p>
            </div>

            <!-- Metrics Overview Cards -->
            <div class="af-cm-metrics">
                <div class="af-cm-card">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Total Images Scanned', 'af-companion' ); ?></span>
                </div>
                <div class="af-cm-card card-warning">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['missing'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Missing Alt Text', 'af-companion' ); ?></span>
                </div>
                <div class="af-cm-card card-success">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['valid'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Has Alt Text', 'af-companion' ); ?></span>
                </div>
            </div>

            <!-- Toolbar Filter -->
            <div class="af-cm-toolbar">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                    <input type="hidden" name="page" value="af-alt-manager" />

                    <label for="alt_filter"><?php esc_html_e( 'Filter Media:', 'af-companion' ); ?></label>
                    <select name="alt_filter" id="alt_filter">
                        <option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All Images', 'af-companion' ); ?></option>
                        <option value="missing" <?php selected( $filter, 'missing' ); ?>><?php esc_html_e( 'Missing Alt Text Only', 'af-companion' ); ?></option>
                        <option value="valid" <?php selected( $filter, 'valid' ); ?>><?php esc_html_e( 'Has Alt Text', 'af-companion' ); ?></option>
                    </select>

                    <button type="submit" class="button"><?php esc_html_e( 'Filter Audit', 'af-companion' ); ?></button>
                </form>
            </div>

            <!-- Media Table -->
            <table class="wp-list-table widefat fixed striped af-cm-table">
                <thead>
                    <tr>
                        <th class="column-thumbnail" style="width: 70px;"><?php esc_html_e( 'Image', 'af-companion' ); ?></th>
                        <th class="column-title"><?php esc_html_e( 'File & Filename', 'af-companion' ); ?></th>
                        <th class="column-alt" style="width: 45%;"><?php esc_html_e( 'Alternative Text (Alt)', 'af-companion' ); ?></th>
                        <th class="column-actions" style="width: 140px; text-align: right;"><?php esc_html_e( 'Quick Action', 'af-companion' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $query->have_posts() ) : ?>
                        <?php while ( $query->have_posts() ) : $query->the_post(); 
                            $attachment_id = get_the_ID();
                            $alt_text      = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                            $filename      = basename( get_attached_file( $attachment_id ) );
                            
                            // Try thumbnail first, fallback to full image URL if thumbnail file is missing on disk
                            $thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
                            if ( ! $thumb_url ) {
                                $thumb_url = wp_get_attachment_image_url( $attachment_id, 'full' );
                            }
                            $has_alt = ! empty( trim( $alt_text ) );
                            ?>
                            <tr>
                                <td class="column-thumbnail">
                                    <?php if ( $thumb_url ) : ?>
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;" />
                                    <?php else : ?>
                                        <span class="dashicons dashicons-format-image" style="font-size: 32px; color: #94a3b8;"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-title">
                                    <strong><a href="<?php echo esc_url( get_edit_post_link( $attachment_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title() ); ?></a></strong>
                                    <div style="color: #64748b; font-size: 11px; margin-top: 2px;"><?php echo esc_html( $filename ); ?></div>
                                </td>
                                <td class="column-alt">
                                    <input type="text" 
                                           id="alt-input-<?php echo esc_attr( $attachment_id ); ?>" 
                                           value="<?php echo esc_attr( $alt_text ); ?>" 
                                           placeholder="<?php esc_attr_e( 'Enter descriptive alternative text...', 'af-companion' ); ?>" 
                                           style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px;" />
                                </td>
                                <td class="column-actions" style="text-align: right;">
                                    <button type="button" 
                                            class="button button-small af-save-alt-btn" 
                                            data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>">
                                        <?php esc_html_e( 'Save Alt', 'af-companion' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'No images found matching your audit filter.', 'af-companion' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php
            $total_pages = $query->max_num_pages;
            if ( $total_pages > 1 ) :
                ?>
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
     * AJAX Handler for Inline Alt Text Saving
     */
    public function ajax_quick_update() {
        check_ajax_referer( 'af_alt_nonce', 'nonce' );

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        $alt_text      = isset( $_POST['alt_text'] ) ? sanitize_text_field( $_POST['alt_text'] ) : '';

        if ( ! $attachment_id || ! current_user_can( 'upload_files', $attachment_id ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

        wp_send_json_success( array(
            'message'  => esc_html__( 'Alt text updated.', 'af-companion' ),
            'alt_text' => $alt_text,
        ) );
    }
}