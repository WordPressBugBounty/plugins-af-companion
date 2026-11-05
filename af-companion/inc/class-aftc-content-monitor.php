<?php
/**
 * AF Content Monitor - Editorial & Content Maintenance Engine
 * 
 * Version: 2.1.1
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Content_Monitor {

    const META_LAST_REVIEWED = '_af_last_reviewed';
    const META_REVIEWED_BY   = '_af_reviewed_by';

    public function __construct() {
        // Register Content Monitor as a Tab/Section inside the main AF Companion page
        add_action( 'admin_menu', array( $this, 'register_content_monitor_tab' ), 20 );

        // WordPress Dashboard Widget
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

        // AJAX Handlers for Instant Actions & Filtering
        add_action( 'wp_ajax_af_cm_get_data', array( $this, 'ajax_get_data' ) );
        add_action( 'wp_ajax_af_mark_content_reviewed', array( $this, 'ajax_mark_reviewed' ) );

        // Enqueue Styles & Scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Hook into admin menu or register sub-action hook for main AF Companion page
     */
    public function register_content_monitor_tab() {
        add_menu_page(
            esc_html__( 'Content Monitor', 'af-companion' ),
            esc_html__( 'Content Monitor', 'af-companion' ),
            'edit_posts',
            'af-content-monitor',
            array( $this, 'render_admin_page' ),
            'dashicons-visibility',
            34
        );
    }

    /**
     * Enqueue Assets securely on the Content Monitor screen
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'af-content-monitor' ) === false && strpos( $hook, 'af-companion' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'af-content-monitor-js',
            AFTC_URL . '/assets/js/content-monitor.js',
            array( 'jquery' ),
            '2.1.1',
            true
        );

        wp_localize_script(
            'af-content-monitor-js',
            'afCMData',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'af_cm_nonce' ),
            )
        );
    }

    /**
     * Automatically discover all public post types (WooCommerce, Posts, Pages, CPTs)
     */
    private function get_all_public_post_types() {
        $args = array( 'public' => true );
        $post_types = get_post_types( $args, 'objects' );

        $excluded_system_types = array(
            'attachment', 'nav_menu_item', 'wp_block', 'wp_template',
            'wp_template_part', 'wp_navigation', 'revision', 'custom_css', 'oembed_cache',
        );

        $filtered_types = array();
        foreach ( $post_types as $slug => $type_obj ) {
            if ( in_array( $slug, $excluded_system_types, true ) ) {
                continue;
            }
            if ( strpos( $slug, 'elespare_' ) === 0 || strpos( $slug, 'elementor_' ) === 0 ) {
                continue;
            }
            $filtered_types[ $slug ] = $type_obj;
        }

        return apply_filters( 'af_cm_supported_post_types', $filtered_types );
    }

    /**
     * Render the Content Monitor Admin Dashboard View
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $active_post_types = get_option( 'af_cm_active_post_types', class_exists( 'WooCommerce' ) ? array( 'post', 'page', 'product' ) : array( 'post', 'page' ) );
        
        $stale_days    = isset( $_GET['stale_days'] ) ? absint( $_GET['stale_days'] ) : 180;
        $selected_type = isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $active_post_types, true ) 
                         ? sanitize_key( $_GET['post_type'] ) 
                         : reset( $active_post_types );

        $current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page      = 20;

        $stats = $this->get_content_stats( $stale_days, $selected_type );
        $query = $this->get_monitored_posts( $selected_type, $current_page, $per_page );
        ?>
        <?php AF_Admin_Header::render( 'af-content-monitor' ); ?>
        <div class="wrap af-status-container">
            <div class="af-status-header">
                <div class="af-status-branding">
                    <h1><?php esc_html_e( 'Content Monitor', 'af-companion' ); ?></h1>
                    <span class="af-badge-status"><?php esc_html_e( 'Editorial Accountability', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Track post freshness, monitor last reviewed dates, and prevent stale content across your site.', 'af-companion' ); ?></p>
            </div>

            <div class="af-cm-metrics">
                <div class="af-cm-card">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Total Published Content', 'af-companion' ); ?></span>
                </div>
                <div class="af-cm-card card-warning">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['needs_review'] ) ); ?></span>
                    <span class="af-card-label"><?php echo sprintf( esc_html__( 'Needs Review (> %d Days)', 'af-companion' ), $stale_days ); ?></span>
                </div>
                <div class="af-cm-card card-success">
                    <span class="af-card-num"><?php echo esc_html( number_format_i18n( $stats['reviewed'] ) ); ?></span>
                    <span class="af-card-label"><?php esc_html_e( 'Fresh / Reviewed', 'af-companion' ); ?></span>
                </div>
            </div>

            <div class="af-cm-toolbar">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                    <input type="hidden" name="page" value="af-content-monitor" />
                    
                    <label for="post_type"><?php esc_html_e( 'Content Type:', 'af-companion' ); ?></label>
                    <select name="post_type" id="post_type">
                        <?php foreach ( $active_post_types as $type_slug ) {
                            $type_obj = get_post_type_object( $type_slug );
                            if ( ! $type_obj ) continue;
                            echo '<option value="' . esc_attr( $type_slug ) . '" ' . selected( $selected_type, $type_slug, false ) . '>' . esc_html( $type_obj->labels->name ) . '</option>';
                        } ?>
                    </select>

                    <label for="stale_days"><?php esc_html_e( 'Stale Interval:', 'af-companion' ); ?></label>
                    <select name="stale_days" id="stale_days">
                        <option value="30" <?php selected( $stale_days, 30 ); ?>><?php esc_html_e( '30 Days', 'af-companion' ); ?></option>
                        <option value="90" <?php selected( $stale_days, 90 ); ?>><?php esc_html_e( '90 Days', 'af-companion' ); ?></option>
                        <option value="180" <?php selected( $stale_days, 180 ); ?>><?php esc_html_e( '180 Days', 'af-companion' ); ?></option>
                        <option value="365" <?php selected( $stale_days, 365 ); ?>><?php esc_html_e( '365 Days', 'af-companion' ); ?></option>
                    </select>

                    <button type="submit" class="button"><?php esc_html_e( 'Filter Results', 'af-companion' ); ?></button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped af-cm-table">
                <thead>
                    <tr>
                        <th class="column-title"><?php esc_html_e( 'Title', 'af-companion' ); ?></th>
                        <th><?php esc_html_e( 'Published', 'af-companion' ); ?></th>
                        <th><?php esc_html_e( 'Last Modified', 'af-companion' ); ?></th>
                        <th><?php esc_html_e( 'Last Reviewed', 'af-companion' ); ?></th>
                        <th><?php esc_html_e( 'Review Status', 'af-companion' ); ?></th>
                        <th class="column-actions"><?php esc_html_e( 'Quick Action', 'af-companion' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $query->have_posts() ) : ?>
                        <?php while ( $query->have_posts() ) : $query->the_post(); 
                            $post_id       = get_the_ID();
                            $last_reviewed = get_post_meta( $post_id, self::META_LAST_REVIEWED, true );
                            $status_info   = $this->calculate_review_status( $post_id, $stale_days );
                            ?>
                            <tr>
                                <td class="column-title">
                                    <strong>
                                        <a class="row-title" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></td>
                                <td><?php echo esc_html( get_the_modified_date( 'M j, Y' ) ); ?></td>
                                <td>
                                    <span id="reviewed-date-<?php echo esc_attr( $post_id ); ?>">
                                        <?php echo $last_reviewed ? esc_html( date_i18n( 'M j, Y', strtotime( $last_reviewed ) ) ) : '<em>' . esc_html__( 'Never', 'af-companion' ) . '</em>'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="af-status-pill <?php echo esc_attr( $status_info['class'] ); ?>">
                                        <?php echo esc_html( $status_info['label'] ); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="button button-small af-mark-reviewed-btn" 
                                            data-post-id="<?php echo esc_attr( $post_id ); ?>">
                                        <?php esc_html_e( 'Mark Reviewed', 'af-companion' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e( 'No content found matching your filter criteria.', 'af-companion' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

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
     * Optimized SQL Statistics Aggregator
     */
    private function get_content_stats( $stale_days, $post_type ) {
        global $wpdb;

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
            $post_type
        ) );

        if ( $total === 0 ) {
            return array( 'total' => 0, 'needs_review' => 0, 'reviewed' => 0 );
        }

        $threshold_date = date( 'Y-m-d H:i:s', strtotime( "-{$stale_days} days" ) );

        $needs_review = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} AS p
             LEFT JOIN {$wpdb->postmeta} AS m ON (p.ID = m.post_id AND m.meta_key = %s)
             WHERE p.post_type = %s 
               AND p.post_status = 'publish' 
               AND (
                   (m.meta_value IS NULL AND p.post_modified < %s) 
                   OR 
                   (m.meta_value IS NOT NULL AND m.meta_value < %s)
               )",
            self::META_LAST_REVIEWED,
            $post_type,
            $threshold_date,
            $threshold_date
        ) );

        $needs_review = max( 0, $needs_review );
        $reviewed     = max( 0, $total - $needs_review );

        return array( 'total' => $total, 'needs_review' => $needs_review, 'reviewed' => $reviewed );
    }

    /**
     * Query Monitored Posts Safely
     */
    private function get_monitored_posts( $post_type, $paged = 1, $per_page = 20 ) {
        $active_post_types = get_option( 'af_cm_active_post_types', array( 'post', 'page' ) );
        if ( ! in_array( $post_type, $active_post_types, true ) ) {
            $post_type = reset( $active_post_types );
        }

        return new WP_Query( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'paged'          => $paged,
            'posts_per_page' => $per_page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );
    }

    /**
     * Calculate Review Status
     */
    private function calculate_review_status( $post_id, $stale_days ) {
        $last_reviewed = get_post_meta( $post_id, self::META_LAST_REVIEWED, true );
        $check_date    = $last_reviewed ? strtotime( $last_reviewed ) : strtotime( get_the_modified_date( 'Y-m-d H:i:s', $post_id ) );
        $threshold     = strtotime( "-{$stale_days} days" );

        if ( $check_date < $threshold ) {
            return array( 'label' => esc_html__( 'Needs Review', 'af-companion' ), 'class' => 'status-stale' );
        }
        return array( 'label' => esc_html__( 'Fresh', 'af-companion' ), 'class' => 'status-fresh' );
    }

    /**
     * AJAX Handler for Inline Mark Reviewed Action
     */
    public function ajax_mark_reviewed() {
        check_ajax_referer( 'af_cm_nonce', 'nonce' );
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
        }

        $current_time    = current_time( 'mysql' );
        $current_user_id = get_current_user_id();

        update_post_meta( $post_id, self::META_LAST_REVIEWED, $current_time );
        update_post_meta( $post_id, self::META_REVIEWED_BY, $current_user_id );

        wp_send_json_success( array(
            'formatted_date' => date_i18n( 'M j, Y', strtotime( $current_time ) ),
            'message'        => esc_html__( 'Content marked as reviewed.', 'af-companion' ),
        ) );
    }

    /**
     * Dashboard Widget Registration
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'af_content_monitor_widget',
            esc_html__( 'AF Content Monitor Overview', 'af-companion' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
        $stats = $this->get_content_stats( 180, 'post' );
        ?>
        <div class="af-cm-widget-body">
            <p><?php esc_html_e( 'Quick summary of published post freshness (180-day threshold):', 'af-companion' ); ?></p>
            <ul style="margin-bottom:15px;">
                <li><strong><?php esc_html_e( 'Total Posts:', 'af-companion' ); ?></strong> <?php echo esc_html( $stats['total'] ); ?></li>
                <li><strong style="color:#dc2626;"><?php esc_html_e( 'Needs Review:', 'af-companion' ); ?></strong> <?php echo esc_html( $stats['needs_review'] ); ?></li>
                <li><strong style="color:#059669;"><?php esc_html_e( 'Fresh / Reviewed:', 'af-companion' ); ?></strong> <?php echo esc_html( $stats['reviewed'] ); ?></li>
            </ul>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=af-content-monitor' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Manage Content Monitor', 'af-companion' ); ?>
            </a>
        </div>
        <?php
    }
}