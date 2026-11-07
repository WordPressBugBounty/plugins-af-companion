<?php
/**
 * AF Companion - Enterprise Native List Table Columns & Screen Options Manager
 *
 * @package           AF_Companion
 * @author            AF Themes
 * @license           GPL-2.0-or-later
 * @link              https://afthemes.com
 * @version           2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AF_Post_List_Columns {

	public function __construct() {
		// Strictly restrict hook setup to admin edit.php requests
		add_action( 'admin_init', array( $this, 'init_edit_screen_hooks' ) );
	}

	/**
	 * Restrict loading exclusively to the native post/CPT list table screen (edit.php).
	 */
	public function init_edit_screen_hooks() {
		global $pagenow;

		// Hard early return if not on edit.php screen or during AJAX/REST calls
		if ( 'edit.php' !== $pagenow || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// Security & Capability Check
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( 'af_companion_list_column_post_types', $post_types );

		foreach ( $post_types as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_filter( "manage_{$type}_posts_columns", array( $this, 'add_custom_columns' ) );
			add_action( "manage_{$type}_posts_custom_column", array( $this, 'render_column_content' ), 10, 2 );
		}

		// Enqueue styles strictly on edit.php header
		add_action( 'admin_head-edit.php', array( $this, 'enqueue_column_styles' ) );
	}

	/**
	 * Define custom columns with readable text headers for Screen Options & Table Headers.
	 */
	public function add_custom_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['af_geo_status'] = esc_html__( 'GEO & AI SEO', 'af-companion' );
				$new_columns['af_freshness']  = esc_html__( 'Freshness', 'af-companion' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render compact icon indicators per row with 4-tier color progress support.
	 */
	public function render_column_content( $column_name, $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		switch ( $column_name ) {

			case 'af_geo_status':
				$meta_title = get_post_meta( $post_id, '_af_seo_meta_title', true );
				$keywords   = get_post_meta( $post_id, '_af_ai_keywords', true );
				$info_gain  = get_post_meta( $post_id, '_af_info_gain_summary', true );

				$has_seo = ! empty( $meta_title );
				$has_geo = ! empty( $keywords ) || ! empty( $info_gain );

				if ( $has_seo && $has_geo ) {
					// 100% Complete: Green Check Circle
					printf(
						'<span class="af-icon-status icon-green" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( '100% Fully Optimized (SEO & GEO Graph Active)', 'af-companion' ),
						esc_attr__( '100% Fully Optimized (SEO & GEO Graph Active)', 'af-companion' ),
						esc_html__( '100% Fully Optimized (SEO & GEO Graph Active)', 'af-companion' )
					);
				} elseif ( $has_seo ) {
					// Partial Progress: Blue Info Circle (SEO Set, GEO Missing)
					printf(
						'<span class="af-icon-status icon-blue" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-info" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( 'SEO Meta Configured (GEO Entity Graph Missing)', 'af-companion' ),
						esc_attr__( 'SEO Meta Configured (GEO Entity Graph Missing)', 'af-companion' ),
						esc_html__( 'SEO Meta Configured (GEO Entity Graph Missing)', 'af-companion' )
					);
				} elseif ( $has_geo ) {
					// Partial Progress: Orange Warning (GEO Graph Built, SEO Meta Missing)
					printf(
						'<span class="af-icon-status icon-orange" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-chart-pie" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( 'GEO Entities Active (SEO Meta Title Missing)', 'af-companion' ),
						esc_attr__( 'GEO Entities Active (SEO Meta Title Missing)', 'af-companion' ),
						esc_html__( 'GEO Entities Active (SEO Meta Title Missing)', 'af-companion' )
					);
				} else {
					// Unoptimized: Red Warning Cross
					printf(
						'<span class="af-icon-status icon-red" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( 'Needs Optimization (Missing AI SEO & GEO Metadata)', 'af-companion' ),
						esc_attr__( 'Needs Optimization (Missing AI SEO & GEO Metadata)', 'af-companion' ),
						esc_html__( 'Needs Optimization (Missing AI SEO & GEO Metadata)', 'af-companion' )
					);
				}
				break;

			case 'af_freshness':
				$last_reviewed = get_post_meta( $post_id, '_af_last_reviewed', true );
				$post_modified = get_the_modified_date( 'Y-m-d H:i:s', $post_id );

				$check_date = $last_reviewed ? strtotime( $last_reviewed ) : strtotime( $post_modified );
				$threshold  = strtotime( '-180 days' );
				$is_stale   = $check_date < $threshold;

				if ( $is_stale ) {
					// Stale: Amber Warning Triangle
					printf(
						'<span class="af-icon-status icon-amber" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( 'Needs Review (Older than 180 days)', 'af-companion' ),
						esc_attr__( 'Needs Review (Older than 180 days)', 'af-companion' ),
						esc_html__( 'Needs Review (Older than 180 days)', 'af-companion' )
					);
				} else {
					// Fresh: Green Clock
					printf(
						'<span class="af-icon-status icon-green" title="%s" aria-label="%s" role="img"><span class="dashicons dashicons-clock" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
						esc_attr__( 'Fresh / Recently Reviewed', 'af-companion' ),
						esc_attr__( 'Fresh / Recently Reviewed', 'af-companion' ),
						esc_html__( 'Fresh / Recently Reviewed', 'af-companion' )
					);
				}
				break;
		}
	}

	/**
	 * Enqueue styles strictly in edit.php head section with high-contrast accessibility colors.
	 */
	public function enqueue_column_styles() {
		?>
		<style id="af-companion-progress-columns-css">
			/* Lock column width for balanced text headers and centered icon content */
			.column-af_geo_status {
				width: 110px !important;
				text-align: center !important;
			}
			.column-af_freshness {
				width: 90px !important;
				text-align: center !important;
			}

			th.column-af_geo_status,
			th.column-af_freshness {
				text-align: center !important;
			}
			
			/* Icon Status Wrappers */
			.af-icon-status {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 24px;
				height: 24px;
				border-radius: 50%;
				transition: transform 0.15s ease;
				cursor: help;
			}
			.af-icon-status:hover {
				transform: scale(1.15);
			}
			.af-icon-status .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 1;
			}

			/* High-Contrast WCAG AA Compliant Color Tokens */
			.af-icon-status.icon-green  { background: #dcfce7; color: #15803d; }
			.af-icon-status.icon-blue   { background: #dbeafe; color: #1d4ed8; }
			.af-icon-status.icon-orange { background: #ffedd5; color: #c2410c; }
			.af-icon-status.icon-amber  { background: #fef3c7; color: #b45309; }
			.af-icon-status.icon-red    { background: #ffe4e6; color: #be123c; }
		</style>
		<?php
	}
}

new AF_Post_List_Columns();