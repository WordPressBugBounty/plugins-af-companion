<?php
/**
 * AF Content Monitor - Enterprise Editorial, AI SEO & Site Health Dashboard Engine
 *
 * @package           AF_Companion
 * @author            AF Themes
 * @license           GPL-2.0-or-later
 * @link              https://afthemes.com
 * @version           2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AF_Content_Monitor {

	const META_LAST_REVIEWED = '_af_last_reviewed';
	const META_REVIEWED_BY   = '_af_reviewed_by';
	const CACHE_KEY_METRICS  = 'af_cm_dashboard_cached_metrics';

	public function __construct() {
		// Register Admin Menu Page
		add_action( 'admin_menu', array( $this, 'register_content_monitor_tab' ), 20 );

		// Register WordPress Dashboard Widgets
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

		// AJAX Handlers
		add_action( 'wp_ajax_af_cm_get_data', array( $this, 'ajax_get_data' ) );
		add_action( 'wp_ajax_af_mark_content_reviewed', array( $this, 'ajax_mark_reviewed' ) );
		add_action( 'wp_ajax_af_refresh_cm_cache', array( $this, 'ajax_refresh_cache' ) );

		// Clear transient cache on post updates/deletions
		add_action( 'save_post', array( $this, 'clear_metrics_cache' ) );
		add_action( 'delete_post', array( $this, 'clear_metrics_cache' ) );

		// Enqueue Scripts & Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register Content Monitor menu item.
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
	 * Enqueue Assets securely.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'af-content-monitor' ) === false && strpos( $hook, 'af-companion' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'af-content-monitor-js',
			AFTC_URL . '/assets/js/content-monitor.js',
			array( 'jquery' ),
			'2.3.0',
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
	 * Get all public non-system post types.
	 */
	private function get_all_public_post_types() {
		$args       = array( 'public' => true );
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
	 * Render Admin Dashboard Page.
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

		$stats          = $this->get_content_stats( $stale_days, $selected_type );
		$monitored_data = $this->get_monitored_posts_optimized( $selected_type, $stale_days, $current_page, $per_page );
		?>
		<?php AF_Admin_Header::render( 'af-content-monitor' ); ?>
		<div class="wrap af-status-container">
			<div class="af-status-header">
				<div class="af-status-branding">
					<h1><?php esc_html_e( 'Content Monitor', 'af-companion' ); ?></h1>
					<span class="af-badge-status"><?php esc_html_e( 'Enterprise Scaler', 'af-companion' ); ?></span>
				</div>
				<p class="af-subtitle"><?php esc_html_e( 'Track post freshness, monitor last reviewed dates, and prevent stale content across large-scale databases.', 'af-companion' ); ?></p>
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
					<?php if ( ! empty( $monitored_data['posts'] ) ) : ?>
						<?php foreach ( $monitored_data['posts'] as $p ) : 
							$post_id       = (int) $p->ID;
							$last_reviewed = get_post_meta( $post_id, self::META_LAST_REVIEWED, true );
							$check_date    = $last_reviewed ? strtotime( $last_reviewed ) : strtotime( $p->post_modified );
							$threshold     = strtotime( "-{$stale_days} days" );
							$is_stale      = $check_date < $threshold;
							?>
							<tr>
								<td class="column-title">
									<strong>
										<a class="row-title" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
											<?php echo esc_html( $p->post_title ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $p->post_date ) ) ); ?></td>
								<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $p->post_modified ) ) ); ?></td>
								<td>
									<span id="reviewed-date-<?php echo esc_attr( $post_id ); ?>">
										<?php echo $last_reviewed ? esc_html( date_i18n( 'M j, Y', strtotime( $last_reviewed ) ) ) : '<em>' . esc_html__( 'Never', 'af-companion' ) . '</em>'; ?>
									</span>
								</td>
								<td>
									<span class="af-status-pill <?php echo $is_stale ? 'status-stale' : 'status-fresh'; ?>">
										<?php echo $is_stale ? esc_html__( 'Needs Review', 'af-companion' ) : esc_html__( 'Fresh', 'af-companion' ); ?>
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
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No content found matching your filter criteria.', 'af-companion' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php
			$total_pages = ceil( $monitored_data['total_matched'] / $per_page );
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
	 * Transient-Cached Content Statistics Engine (Zero-Cost Execution on Large Sites).
	 */
	private function get_content_stats( $stale_days, $post_type ) {
		$cache_key = sprintf( 'af_cm_stats_%s_%d', $post_type, $stale_days );
		$stats     = get_transient( $cache_key );

		if ( false === $stats ) {
			global $wpdb;

			$total = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
				$post_type
			) );

			if ( $total === 0 ) {
				$stats = array( 'total' => 0, 'needs_review' => 0, 'reviewed' => 0 );
			} else {
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

				$stats = array( 'total' => $total, 'needs_review' => $needs_review, 'reviewed' => $reviewed );
			}

			// Cache metrics for 6 hours
			set_transient( $cache_key, $stats, 6 * HOUR_IN_SECONDS );
		}

		return $stats;
	}

	/**
	 * Scalable Direct SQL Post Fetcher (Bypasses WP_Query Overhead for 100k+ Rows).
	 */
	private function get_monitored_posts_optimized( $post_type, $stale_days, $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$offset = ( $paged - 1 ) * $per_page;

		$posts = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, post_date, post_modified 
			 FROM {$wpdb->posts} 
			 WHERE post_type = %s AND post_status = 'publish' 
			 ORDER BY post_modified DESC 
			 LIMIT %d OFFSET %d",
			$post_type,
			$per_page,
			$offset
		) );

		$total_matched = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
			$post_type
		) );

		return array(
			'posts'         => $posts,
			'total_matched' => $total_matched,
		);
	}

	/**
	 * AJAX Handler to Mark Content Reviewed.
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

		// Clear cached metrics so dashboard reflects updates immediately
		$this->clear_metrics_cache();

		wp_send_json_success( array(
			'formatted_date' => date_i18n( 'M j, Y', strtotime( $current_time ) ),
			'message'        => esc_html__( 'Content marked as reviewed.', 'af-companion' ),
		) );
	}

	/**
	 * AJAX Cache Refresh Handler.
	 */
	public function ajax_refresh_cache() {
		check_ajax_referer( 'af_cm_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
		}

		$this->clear_metrics_cache();

		wp_send_json_success( array(
			'message' => esc_html__( 'Metrics cache successfully cleared.', 'af-companion' ),
		) );
	}

	/**
	 * Clear Transients on Post Status Changes.
	 */
	public function clear_metrics_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_af_cm_%' OR option_name LIKE '_transient_timeout_af_cm_%'" );
	}

	/**
	 * Register WordPress Dashboard Widgets.
	 */
	public function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'af_content_monitor_widget',
			esc_html__( 'AF Content Monitor Overview', 'af-companion' ),
			array( $this, 'render_content_monitor_widget' )
		);

		wp_add_dashboard_widget(
			'af_ai_seo_health_widget',
			esc_html__( 'AF AI SEO & Alt Text Health', 'af-companion' ),
			array( $this, 'render_ai_seo_health_widget' )
		);
	}

	/**
	 * Widget 1: Content Freshness Summary.
	 */
	public function render_content_monitor_widget() {
		$stats = $this->get_content_stats( 180, 'post' );
		?>
		<div class="af-cm-widget-body">
			<p><?php esc_html_e( 'Quick summary of published post freshness (180-day threshold):', 'af-companion' ); ?></p>
			<ul style="margin-bottom:15px; line-height: 1.8;">
				<li><strong><?php esc_html_e( 'Total Posts:', 'af-companion' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></li>
				<li><strong style="color:#dc2626;"><?php esc_html_e( 'Needs Review:', 'af-companion' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['needs_review'] ) ); ?></li>
				<li><strong style="color:#059669;"><?php esc_html_e( 'Fresh / Reviewed:', 'af-companion' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['reviewed'] ) ); ?></li>
			</ul>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=af-content-monitor' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Open Content Monitor', 'af-companion' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Widget 2: AI SEO, GEO & Alt Text Health (Cached).
	 */
	public function render_ai_seo_health_widget() {
		$cache_key    = 'af_ai_seo_health_metrics';
		$health_stats = get_transient( $cache_key );

		if ( false === $health_stats ) {
			global $wpdb;

			// Count images lacking alt text safely
			$missing_alt = (int) $wpdb->get_var( "
				SELECT COUNT(p.ID) FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} m ON (p.ID = m.post_id AND m.meta_key = '_wp_attachment_image_alt')
				WHERE p.post_type = 'attachment' 
				  AND p.post_mime_type LIKE 'image/%' 
				  AND (m.meta_value IS NULL OR m.meta_value = '')
			" );

			// Count published posts lacking AI SEO titles
			$missing_seo = (int) $wpdb->get_var( "
				SELECT COUNT(p.ID) FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} m ON (p.ID = m.post_id AND m.meta_key = '_af_seo_meta_title')
				WHERE p.post_type IN ('post', 'page') 
				  AND p.post_status = 'publish' 
				  AND (m.meta_value IS NULL OR m.meta_value = '')
			" );

			$health_stats = array(
				'missing_alt' => $missing_alt,
				'missing_seo' => $missing_seo,
			);

			// Cache for 6 hours
			set_transient( $cache_key, $health_stats, 6 * HOUR_IN_SECONDS );
		}
		?>
		<div class="af-cm-widget-body">
			<p><?php esc_html_e( 'Overview of AI optimization and accessibility health:', 'af-companion' ); ?></p>
			<ul style="margin-bottom:15px; line-height: 1.8;">
				<li><strong style="color:#d97706;"><?php esc_html_e( 'Images Lacking Alt Text:', 'af-companion' ); ?></strong> <?php echo esc_html( number_format_i18n( $health_stats['missing_alt'] ) ); ?></li>
				<li><strong style="color:#6366f1;"><?php esc_html_e( 'Published Posts Missing AI SEO Meta:', 'af-companion' ); ?></strong> <?php echo esc_html( number_format_i18n( $health_stats['missing_seo'] ) ); ?></li>
			</ul>
			<p style="font-size: 12px; color: #64748b; margin-bottom: 15px;"><?php esc_html_e( 'Tip: Use the Alt Text Manager and post editor AI generators to optimize these metrics for GEO and search discovery.', 'af-companion' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=af-alt-manager' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Open Alt Text Manager', 'af-companion' ); ?>
			</a>
		</div>
		<?php
	}
}

// new AF_Content_Monitor();