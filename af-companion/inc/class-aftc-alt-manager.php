<?php

/**
 * AF Companion - Centralized AI Settings & Alt Text Manager Engine
 *
 * @package           AF_Companion
 * @author            AF Themes
 * @license           GPL-2.0-or-later
 * @link              https://afthemes.com
 * @version           3.2.0
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Main Alt Text Manager Administrative Controller Class
 */
class AF_Alt_Text_Manager
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('wp_ajax_af_alt_quick_update', array($this, 'ajax_quick_update'));
		add_action('wp_ajax_af_alt_generate_ai', array($this, 'ajax_generate_ai_alt'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

		// 1. Gutenberg / Post Editor Featured Image AI Hook
		add_filter('admin_post_thumbnail_html', array($this, 'add_featured_image_ai_button'), 10, 3);

		// 2. Media Library Modal / Attachment Details JavaScript Template Hook
		add_action('admin_print_footer_scripts', array($this, 'render_media_modal_template_script'), 99);
	}

	public function register_menu()
	{
		add_menu_page(
			esc_html__('Alt Text Manager Powered by AI', 'af-companion'),
			esc_html__('AI Alt Text', 'af-companion'),
			'upload_files',
			'af-alt-manager',
			array($this, 'render_admin_page'),
			'dashicons-format-image',
			35
		);
	}

	public function enqueue_assets($hook)
	{
		// Enqueue on Alt Manager page or Post Editor (post.php, post-new.php, upload.php)
		$allowed_hooks = array('af-alt-manager', 'post.php', 'post-new.php', 'upload.php');
		if (! in_array($hook, $allowed_hooks, true) && false === strpos($hook, 'af-alt-manager')) {
			return;
		}

		wp_enqueue_script(
			'af-alt-manager-js',
			AFTC_URL . 'assets/js/alt-manager.js',
			array('jquery', 'wp-util'),
			'3.2.0',
			true
		);

		wp_localize_script(
			'af-alt-manager-js',
			'afAltData',
			array(
				'ajax_url'     => admin_url('admin-ajax.php'),
				'nonce'        => wp_create_nonce('af_alt_nonce'),
				'generating'   => esc_html__('Generating AI Alt Text...', 'af-companion'),
				'success_msg'  => esc_html__('Alt text generated successfully!', 'af-companion'),
			)
		);
	}

	/**
	 * 1. Inject "Context AI" generation button under the Post Editor Featured Image if empty alt text.
	 */
	public function add_featured_image_ai_button($content, $post_id, $thumbnail_id)
	{
		$thumbnail_id = absint($thumbnail_id);
		if (! $thumbnail_id || ! current_user_can('upload_files')) {
			return $content;
		}

		// Check if featured image already has alt text
		$alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
		if (! empty($alt_text)) {
			return $content; // Alt text exists, no need to show generator button
		}

		$ai_button = sprintf(
			'<div class="af-featured-img-ai-wrap" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
				<button type="button" class="button button-secondary button-small af-ai-alt-featured-btn" data-attachment-id="%d" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 5px; color: #0284c7; border-color: #0284c7;">
					<span class="dashicons dashicons-superhero" aria-hidden="true" style="font-size: 15px; width: 15px; height: 15px;"></span>
					%s
				</button>
			</div>',
			$thumbnail_id,
			esc_html__('Generate Context AI Alt Text', 'af-companion')
		);

		return $content . $ai_button;
	}

	/**
	 * 2. Inject AI Alt Text button inside WordPress Media Library Popups / Modals.
	 */
	public function render_media_modal_template_script()
	{
		global $pagenow;
		$allowed_screens = array('upload.php', 'post.php', 'post-new.php', 'site-editor.php');
		if (! in_array($pagenow, $allowed_screens, true) && false === strpos(get_current_screen()->id, 'af')) {
			return;
		}
?>
		<script type="text/html" id="tmpl-af-media-modal-ai-button">
    <div class="setting af-ai-alt-setting" style="display: flex; align-items: flex-start; margin: 12px 0; padding: 0;">
        <label class="name" style="flex: 0 0 30%; padding-top: 4px; color: #646970; font-size: 12px;"></label>
        <div class="field" style="flex: 1; max-width: 70%; box-sizing: border-box;">
            <button type="button" class="button button-secondary af-modal-ai-alt-btn" data-attachment-id="{{ data.id }}">
                <span class="dashicons dashicons-superhero" aria-hidden="true"></span>
                <?php esc_html_e( 'Generate Alt Text', 'af-companion' ); ?>
            </button>
        </div>
    </div>
</script>

<style>
/* Professional Button & Dashicon Alignment Styles */
.wp-core-ui .button.af-modal-ai-alt-btn {
    width: 100% !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    height: 32px !important;
    margin: 0 !important;
    padding: 0 12px !important;
    background: #f6f7f7 !important;
    border-color: #2271b1 !important;
    color: #2271b1 !important;
    font-weight: 500 !important;
    box-shadow: none !important;
    box-sizing: border-box !important;
}

.wp-core-ui .button.af-modal-ai-alt-btn:hover {
    background: #f0f0f1 !important;
    border-color: #135e96 !important;
    color: #135e96 !important;
}

.wp-core-ui .button.af-recheck-post-btn .dashicons,
.wp-core-ui .button#af-generate-all-ai-btn .dashicons,
.wp-core-ui .button.af-modal-ai-alt-btn .dashicons
 {
    position: relative !important;
    top: -1px !important;
    display: inline-block !important;
    font-size: 16px !important;
    width: 16px !important;
    height: 16px !important;
    line-height: 16px !important;
    vertical-align: middle !important;
    margin-top: 0 !important;
}
</style>
		<script>
			jQuery(document).ready(function($) {
				if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
					return;
				}

				// Hook into attachment details modal render to append AI button below Alt Text input
				var originalRender = wp.media.view.Attachment.Details.prototype.render;
				wp.media.view.Attachment.Details.prototype.render = function() {
					originalRender.apply(this, arguments);
					var view = this;
					var attachmentId = view.model.get('id');
					var template = wp.template('af-media-modal-ai-button');

					setTimeout(function() {
						var altInputSetting = view.$el.find('.setting.alt-text');
						if (altInputSetting.length && !view.$el.find('.af-modal-ai-alt-btn').length) {
							altInputSetting.after(template({
								id: attachmentId
							}));
						}
					}, 50);
					return view;
				};

				// AJAX Trigger from Media Modal or Featured Image Box
				$(document).on('click', '.af-modal-ai-alt-btn, .af-ai-alt-featured-btn', function(e) {
					e.preventDefault();
					var btn = $(this);

					if (btn.prop('disabled')) {
						return;
					}

					var attachmentId = btn.data('attachment-id');
					var originalHtml = btn.html();

					btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> ' + afAltData.generating);

					$.ajax({
						url: afAltData.ajax_url,
						type: 'POST',
						data: {
							action: 'af_alt_generate_ai',
							attachment_id: attachmentId,
							nonce: afAltData.nonce
						},
						success: function(response) {
							if (response.success) {
								btn.css({
										'background': '#ecfdf5',
										'color': '#059669',
										'border-color': '#a7f3d0'
									})
									.html('<span class="dashicons dashicons-yes" style="font-size:16px; width:16px; height:16px; vertical-align:middle;"></span> ' + afAltData.success_msg);

								// Target both possible attachment details alt field IDs (two-column modal vs featured image modal)
								var altField = $('#attachment-details-two-column-alt-text, #attachment-details-alt-text');
								if (altField.length) {
									altField.val(response.data.alt_text);

									// Sync with active Backbone model if present
									if (typeof wp.media.frame !== 'undefined' && wp.media.frame.content && wp.media.frame.content.get()) {
										var selection = wp.media.frame.content.get().selection;
										if (selection && selection.length) {
											var model = selection.get(attachmentId);
											if (model) {
												model.set('alt', response.data.alt_text);
											}
										}
									}
									// Trigger change event to lock value into WordPress media state
									altField.trigger('change');
								}

								setTimeout(function() {
									btn.prop('disabled', false).html(originalHtml).removeAttr('style');
								}, 3000);
							} else {
								alert(response.data.message || 'Error generating alt text.');
								btn.prop('disabled', false).html(originalHtml);
							}
						},
						error: function() {
							alert('Server connection error.');
							btn.prop('disabled', false).html(originalHtml);
						}
					});
				});
			});
		</script>
		
	<?php
	}

	private function get_alt_stats()
	{
		global $wpdb;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_mime_type LIKE %s",
				'attachment',
				'image/%'
			)
		);

		if (0 === $total) {
			return array(
				'total'   => 0,
				'missing' => 0,
				'valid'   => 0,
			);
		}

		$missing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(p.ID) 
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} m ON (p.ID = m.post_id AND m.meta_key = %s)
				 WHERE p.post_type = %s 
				   AND p.post_mime_type LIKE %s
				   AND (m.meta_value IS NULL OR TRIM(m.meta_value) = '')",
				'_wp_attachment_image_alt',
				'attachment',
				'image/%'
			)
		);

		$missing = max(0, $missing);
		$valid   = max(0, $total - $missing);

		return array(
			'total'   => $total,
			'missing' => $missing,
			'valid'   => $valid,
		);
	}

	private function get_media_attachments($filter = 'all', $paged = 1, $per_page = 20)
	{
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'paged'          => absint($paged),
			'posts_per_page' => absint($per_page),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ('missing' === $filter) {
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
		} elseif ('valid' === $filter) {
			$args['meta_query'] = array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '!=',
				),
			);
		}

		return new WP_Query($args);
	}

	public function render_admin_page()
	{
		if (! current_user_can('upload_files')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'af-companion'));
		}

		$filter       = isset($_GET['alt_filter']) ? sanitize_key($_GET['alt_filter']) : 'all';
		$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
		$per_page     = 20;

		$stats           = $this->get_alt_stats();
		$query           = $this->get_media_attachments($filter, $current_page, $per_page);
		$is_ai_connected = AF_AI_Helper::is_connector_available();
	?>
		<?php AF_Admin_Header::render('af-alt-manager'); ?>
		<div class="wrap af-status-container">

			<div class="af-status-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<div class="af-status-header-wrap">
					<div class="af-status-branding">
						<h1><?php esc_html_e('AI Alt Text Manager', 'af-companion'); ?></h1>
						<span class="af-badge-status"><?php esc_html_e('Media Accessibility', 'af-companion'); ?></span>
					</div>
					<p class="af-subtitle"><?php esc_html_e('Audit your media library, identify images missing alternative text, and optimize web accessibility instantly.', 'af-companion'); ?></p>
				</div>
				<div>
					<a href="<?php echo esc_url(admin_url('admin.php?page=af-ai-settings')); ?>" class="button button-secondary">
						<?php esc_html_e('AI Settings', 'af-companion'); ?>
					</a>
				</div>
			</div>

			<!-- AI Status Banner -->
			<div class="af-ai-status-banner" style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo $is_ai_connected ? '#10b981' : '#f59e0b'; ?>; border-radius: 8px; padding: 15px 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
				<div style="display: flex; align-items: center; gap: 12px;">
					<span class="dashicons dashicons-superhero" aria-hidden="true" style="font-size: 24px; width: 24px; height: 24px; color: <?php echo $is_ai_connected ? '#10b981' : '#f59e0b'; ?>;"></span>
					<div>
						<div style="font-weight: 700; font-size: 14px; color: #0f172a;">
							<?php echo $is_ai_connected ? esc_html__('AI Connectors Active', 'af-companion') : esc_html__('AI Connector Not Configured', 'af-companion'); ?>
						</div>
						<div style="font-size: 12px; color: #64748b; margin-top: 2px;">
							<?php echo $is_ai_connected ? esc_html__('Using globally configured AI settings & preferences.', 'af-companion') : esc_html__('Connect an AI provider under Settings -> Connectors to unlock generation.', 'af-companion'); ?>
						</div>
					</div>
				</div>
				<div>
					<a href="<?php echo esc_url(AF_AI_Helper::get_connector_setup_url()); ?>" class="button button-secondary">
						<?php esc_html_e('Manage Connectors', 'af-companion'); ?>
					</a>
				</div>
			</div>

			<!-- Metrics Overview Cards -->
			<div class="af-cm-metrics" style="display: flex; gap: 15px; margin-bottom: 20px;">
				<div class="af-cm-card" style="background: #fff; padding: 15px; border: 1px solid #ccd0d7; border-radius: 4px; flex: 1;">
					<span class="af-card-num" style="display: block; font-size: 20px; font-weight: bold;"><?php echo esc_html(number_format_i18n($stats['total'])); ?></span>
					<span class="af-card-label" style="color: #646970;"><?php esc_html_e('Total Images Scanned', 'af-companion'); ?></span>
				</div>
				<div class="af-cm-card card-warning" style="background: #fff; padding: 15px; border: 1px solid #ccd0d7; border-radius: 4px; flex: 1;">
					<span class="af-card-num" style="display: block; font-size: 20px; font-weight: bold; color: #d63638;"><?php echo esc_html(number_format_i18n($stats['missing'])); ?></span>
					<span class="af-card-label" style="color: #646970;"><?php esc_html_e('Missing Alt Text', 'af-companion'); ?></span>
				</div>
				<div class="af-cm-card card-success" style="background: #fff; padding: 15px; border: 1px solid #ccd0d7; border-radius: 4px; flex: 1;">
					<span class="af-card-num" style="display: block; font-size: 20px; font-weight: bold; color: #00a32a;"><?php echo esc_html(number_format_i18n($stats['valid'])); ?></span>
					<span class="af-card-label" style="color: #646970;"><?php esc_html_e('Has Alt Text', 'af-companion'); ?></span>
				</div>
			</div>

			<!-- Toolbar Filter -->
			<div class="af-cm-toolbar" style="margin-bottom: 15px;">
				<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
					<input type="hidden" name="page" value="af-alt-manager" />
					<label for="alt_filter" style="margin-right: 5px;"><?php esc_html_e('Filter Media:', 'af-companion'); ?></label>
					<select name="alt_filter" id="alt_filter">
						<option value="all" <?php selected($filter, 'all'); ?>><?php esc_html_e('All Images', 'af-companion'); ?></option>
						<option value="missing" <?php selected($filter, 'missing'); ?>><?php esc_html_e('Missing Alt Text Only', 'af-companion'); ?></option>
						<option value="valid" <?php selected($filter, 'valid'); ?>><?php esc_html_e('Has Alt Text', 'af-companion'); ?></option>
					</select>
					<button type="submit" class="button"><?php esc_html_e('Filter Audit', 'af-companion'); ?></button>
				</form>
			</div>

			<!-- Media Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" style="width: 70px;"><?php esc_html_e('Image', 'af-companion'); ?></th>
						<th scope="col"><?php esc_html_e('File & Context', 'af-companion'); ?></th>
						<th scope="col" style="width: 42%;"><?php esc_html_e('Alternative Text (Alt)', 'af-companion'); ?></th>
						<th scope="col" style="width: 150px; text-align: right;"><?php esc_html_e('Quick Action', 'af-companion'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($query->have_posts()) : ?>
						<?php
						while ($query->have_posts()) :
							$query->the_post();
							$attachment_id = get_the_ID();
							$alt_text      = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
							$filename      = basename(get_attached_file($attachment_id));

							$parent_id    = wp_get_post_parent_id($attachment_id);
							$parent_title = $parent_id ? get_the_title($parent_id) : '';
							$thumb_url    = wp_get_attachment_image_url($attachment_id, 'thumbnail');
							if (! $thumb_url) {
								$thumb_url = wp_get_attachment_image_url($attachment_id, 'full');
							}
						?>
							<tr>
								<td>
									<?php if ($thumb_url) : ?>
										<img src="<?php echo esc_url($thumb_url); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;" />
									<?php else : ?>
										<span class="dashicons dashicons-format-image" aria-hidden="true" style="font-size: 32px; color: #94a3b8;"></span>
									<?php endif; ?>
								</td>
								<td>
									<strong><a href="<?php echo esc_url(get_edit_post_link($attachment_id)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(get_the_title()); ?></a></strong>
									<div style="color: #64748b; font-size: 11px; margin-top: 2px;"><?php echo esc_html($filename); ?></div>
									<?php if (! empty($parent_title)) : ?>
										<div style="color: #0284c7; font-size: 11px; margin-top: 2px;">
											<span class="dashicons dashicons-media-text" aria-hidden="true" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle;"></span>
											<?php echo esc_html('Parent: ' . $parent_title); ?>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<label for="alt-input-<?php echo esc_attr($attachment_id); ?>" class="screen-reader-text"><?php echo esc_html(sprintf(__('Alternative text for %s', 'af-companion'), get_the_title())); ?></label>
									<input type="text"
										id="alt-input-<?php echo esc_attr($attachment_id); ?>"
										value="<?php echo esc_attr($alt_text); ?>"
										placeholder="<?php esc_attr_e('Enter descriptive alternative text...', 'af-companion'); ?>"
										style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px;" />
								</td>
								<td style="text-align: right; white-space: nowrap;">
									<button type="button"
										class="button button-small af-ai-alt-btn"
										data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
										style="color: #0284c7; border-color: #0284c7; margin-right: 4px;">
										<span class="dashicons dashicons-superhero" aria-hidden="true" style="font-size: 14px; vertical-align: middle; margin-top: 2px;"></span>
										<?php esc_html_e('Context AI', 'af-companion'); ?>
									</button>
									<button type="button"
										class="button button-small af-save-alt-btn"
										data-attachment-id="<?php echo esc_attr($attachment_id); ?>">
										<?php esc_html_e('Save', 'af-companion'); ?>
									</button>
								</td>
							</tr>
						<?php endwhile;
						wp_reset_postdata(); ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php esc_html_e('No images found matching your audit filter.', 'af-companion'); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php
			$total_pages = $query->max_num_pages;
			if ($total_pages > 1) :
			?>
				<nav class="af-cm-pagination" aria-label="<?php esc_attr_e('Media audit pagination', 'af-companion'); ?>" style="margin-top: 15px;">
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg('paged', '%#%'),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $current_page,
						)
					);
					?>
				</nav>
			<?php endif; ?>

		</div>
<?php
	}

	public function ajax_quick_update()
	{
		check_ajax_referer('af_alt_nonce', 'nonce');

		$attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
		$alt_text      = isset($_POST['alt_text']) ? sanitize_text_field(wp_unslash($_POST['alt_text'])) : '';

		if (! $attachment_id || ! current_user_can('upload_files', $attachment_id)) {
			wp_send_json_error(array('message' => esc_html__('Permission denied.', 'af-companion')));
		}

		update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

		wp_send_json_success(
			array(
				'message'  => esc_html__('Alt text updated.', 'af-companion'),
				'alt_text' => $alt_text,
			)
		);
	}

	public function ajax_generate_ai_alt()
	{
		check_ajax_referer('af_alt_nonce', 'nonce');

		$attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;

		if (! $attachment_id || ! current_user_can('upload_files', $attachment_id)) {
			wp_send_json_error(array('message' => esc_html__('Permission denied.', 'af-companion')));
		}

		if (! AF_AI_Helper::is_connector_available()) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: Connectors setup page URL */
						__('AI Connector not configured. Please set up your API keys in <a href="%s" target="_blank">Settings -> Connectors</a>.', 'af-companion'),
						esc_url(AF_AI_Helper::get_connector_setup_url())
					),
				)
			);
		}

		$settings     = AF_AI_Helper::get_settings();
		$parent_id    = wp_get_post_parent_id($attachment_id);
		$parent_title = $parent_id ? get_the_title($parent_id) : '';
		$prompt       = ! empty($settings['alt_prompt']) ? $settings['alt_prompt'] : AF_AI_Helper::get_default_settings()['alt_prompt'];

		$result = AF_AI_Helper::generate(
			$prompt,
			array(
				'attachment_id' => $attachment_id,
				'parent_title'  => $parent_title,
			)
		);

		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()));
		}

		$clean_alt = trim($result, " '\"");
		$clean_alt = sanitize_text_field($clean_alt);

		update_post_meta($attachment_id, '_wp_attachment_image_alt', $clean_alt);

		wp_send_json_success(
			array(
				'message'  => esc_html__('Contextual AI alt text generated successfully!', 'af-companion'),
				'alt_text' => $clean_alt,
			)
		);
	}
}

// new AF_Alt_Text_Manager();
