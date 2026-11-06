<?php
/**
 * AF Companion - Centralized AI Settings Engine
 *
 * @package           AF_Companion
 * @author            AF Themes
 * @license           GPL-2.0-or-later
 * @link              https://afthemes.com
 * @version           4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Native WordPress AI Client Registry & Centralized Configuration Helper
 */
class AF_AI_Helper {

	const MAX_FILE_SIZE = 5242880;

	/**
	 * Default AI configuration settings.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'ai_provider'        => '',
			'ai_model'           => '',
			'ai_model_selection' => '',
			'alt_prompt'         => 'Analyze this image and write a concise, WCAG-compliant alternative text description tailored specifically to the website context and parent article provided. Maximum 125 characters. Do not include phrases like "image of" or "photo of". Return only the descriptive alt text string.',
		);
	}

	/**
	 * Retrieve saved AI settings with defaults fallback.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( 'af_companion_ai_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_default_settings() );
	}

	/**
	 * Check if any native AI connector is configured via AiClient Registry.
	 *
	 * @return bool
	 */
	public static function is_connector_available() {
		if ( class_exists( '\WordPress\AiClient\AiClient' ) ) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();
				foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {
					if ( $registry->isProviderConfigured( $provider_id ) ) {
						return true;
					}
				}
			} catch ( \Throwable $e ) {
				// Fallback check below.
			}
		}

		$google_key = get_option( 'connectors_ai_google_api_key', '' );
		$openai_key = get_option( 'connectors_ai_openai_api_key', '' );
		$claude_key = get_option( 'connectors_ai_anthropic_api_key', '' );

		return ! empty( $google_key ) || ! empty( $openai_key ) || ! empty( $claude_key );
	}

	/**
	 * Dynamically list all models matching Blockspare's exact registry implementation.
	 *
	 * @return array
	 */
	public static function get_available_models() {
		$options = array( '' => __( 'Select Model (Default)', 'af-companion' ) );

		if ( class_exists( '\WordPress\AiClient\AiClient' ) ) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();

				foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {

					if ( ! $registry->isProviderConfigured( $provider_id ) ) {
						continue;
					}

					$provider_class    = $registry->getProviderClassName( $provider_id );
					$provider_metadata = $provider_class::metadata();
					$provider_label    = $provider_metadata->getName();

					$models = $provider_class::modelMetadataDirectory()->listModelMetadata();

					foreach ( $models as $model ) {
						$supports_text = false;

						foreach ( $model->getSupportedCapabilities() as $capability ) {
							if ( method_exists( $capability, 'isTextGeneration' ) && $capability->isTextGeneration() ) {
								$supports_text = true;
								break;
							}
						}

						if ( ! $supports_text && method_exists( $model, 'supportsTextGeneration' ) ) {
							$supports_text = $model->supportsTextGeneration();
						}

						if ( ! $supports_text ) {
							$supports_text = true;
						}

						$m_id   = $model->getId();
						$m_name = $model->getName();

						$combined_key             = $provider_id . '|' . $m_id;
						$options[ $combined_key ] = sprintf( '%s - %s', $provider_label, $m_name );
					}
				}
			} catch ( \Throwable $e ) {
				// Fallback handled safely.
			}
		}

		return apply_filters( 'af_companion_available_ai_models', $options );
	}

	public static function get_connector_setup_url() {
		return admin_url( 'options-connectors.php' );
	}

	/**
	 * Safe Local Image Path Resolver (capped at 5MB).
	 */
	private static function prepare_image_path( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return new WP_Error( 'invalid_attachment', __( 'Invalid attachment ID provided.', 'af-companion' ) );
		}

		$file_path  = '';
		$meta       = wp_get_attachment_metadata( $attachment_id );
		$upload_dir = wp_upload_dir();

		if ( ! empty( $meta['sizes']['medium']['file'] ) && ! empty( $meta['file'] ) ) {
			$file_path = $upload_dir['basedir'] . '/' . trailingslashit( dirname( $meta['file'] ) ) . $meta['sizes']['medium']['file'];
		} elseif ( ! empty( $meta['sizes']['large']['file'] ) && ! empty( $meta['file'] ) ) {
			$file_path = $upload_dir['basedir'] . '/' . trailingslashit( dirname( $meta['file'] ) ) . $meta['sizes']['large']['file'];
		}

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			$file_path = get_attached_file( $attachment_id );
		}

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Local image file not found on disk.', 'af-companion' ) );
		}

		$real_upload_base = realpath( $upload_dir['basedir'] );
		$real_file_path   = realpath( $file_path );
		if ( ! $real_file_path || ( $real_upload_base && strpos( $real_file_path, $real_upload_base ) !== 0 ) ) {
			return new WP_Error( 'file_path_traversal_denied', __( 'Security violation: Invalid file path reference.', 'af-companion' ) );
		}

		if ( filesize( $file_path ) > self::MAX_FILE_SIZE ) {
			return new WP_Error( 'file_too_large', __( 'Image exceeds maximum 5MB size limit for AI analysis.', 'af-companion' ) );
		}

		return $file_path;
	}

	/**
	 * Dispatch generation request using the native WordPress AI Client SDK.
	 */
	public static function generate( $prompt, $args = array() ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You do not have permission to perform AI generation.', 'af-companion' ) );
		}

		if ( ! self::is_connector_available() ) {
			return new WP_Error(
				'ai_not_configured',
				sprintf(
					/* translators: %s: Connectors setup page URL */
					__( 'AI Connector not configured. Please set up your API keys in <a href="%s" target="_blank">Settings -> Connectors</a>.', 'af-companion' ),
					esc_url( self::get_connector_setup_url() )
				)
			);
		}

		$settings      = self::get_settings();
		$attachment_id = isset( $args['attachment_id'] ) ? absint( $args['attachment_id'] ) : 0;

		if ( ! $attachment_id ) {
			return new WP_Error( 'invalid_attachment', __( 'Missing valid attachment ID.', 'af-companion' ) );
		}

		$file_path = self::prepare_image_path( $attachment_id );
		if ( is_wp_error( $file_path ) ) {
			return $file_path;
		}

		$site_name    = get_bloginfo( 'name' );
		$site_tagline = get_bloginfo( 'description' );
		$parent_title = isset( $args['parent_title'] ) ? sanitize_text_field( $args['parent_title'] ) : '';

		$full_prompt = sanitize_text_field( $prompt ) . " Website Context: '{$site_name}' ({$site_tagline}).";
		if ( ! empty( $parent_title ) ) {
			$full_prompt .= " Parent Article: '{$parent_title}'.";
		}

		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			try {
				$builder = wp_ai_client_prompt( $full_prompt )->with_file( $file_path );

				if ( ! empty( $settings['ai_provider'] ) ) {
					$builder = $builder->using_provider( $settings['ai_provider'] );

					if ( ! empty( $settings['ai_model'] ) ) {
						$builder = $builder->using_model_preference( $settings['ai_model'] );
					}
				}

				$response = $builder->generate_text();

				if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
					return sanitize_text_field( $response );
				}
			} catch ( \Throwable $e ) {
				return new WP_Error( 'ai_sdk_error', $e->getMessage() );
			}
		}

		return new WP_Error( 'ai_client_missing', __( 'The WordPress AI Client API (wp_ai_client_prompt) is not active.', 'af-companion' ) );
	}
}

/**
 * Dedicated AI Settings Dashboard Controller Class
 */
class AF_AI_Settings_Dashboard {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings_init' ) );
	}

	public function register_settings_menu() {
		$badge_status = 'Models';

		$hook = add_submenu_page(
			'af-companion',
			esc_html__( 'AI Settings', 'af-companion' ),
			sprintf(
				/* translators: %s: Badge markup */
				__( 'AI Settings %s', 'af-companion' ),
				'<span class="update-plugins count-' . esc_attr( $badge_status ) . '"><span class="plugin-count">' . esc_html( $badge_status ) . '</span></span>'
			),
			'manage_options',
			'af-ai-settings',
			array( $this, 'render_settings_page' ),
			1
		);

		if ( $hook ) {
			add_action( 'admin_print_styles-' . $hook, array( $this, 'enqueue_dashboard_styles' ) );
		}
	}

	public function enqueue_dashboard_styles() {
		echo '<style>
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
		</style>';
	}

	public function register_settings_init() {
		register_setting(
			'af_companion_ai_settings_group',
			'af_companion_ai_settings',
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'af_companion_ai_main_section',
			'', // Handled customly inside card layout
			array( $this, 'section_callback' ),
			'af-ai-settings-page'
		);

		add_settings_field(
			'ai_model_selection',
			esc_html__( 'Preferred AI Model', 'af-companion' ),
			array( $this, 'field_model_callback' ),
			'af-ai-settings-page',
			'af_companion_ai_main_section'
		);

		add_settings_field(
			'alt_prompt',
			esc_html__( 'Default Alt Text Prompt', 'af-companion' ),
			array( $this, 'field_prompt_callback' ),
			'af-ai-settings-page',
			'af_companion_ai_main_section'
		);
	}

	public function section_callback() {
		// Section description handled within the card container header
	}

	public function field_model_callback() {
		$settings = AF_AI_Helper::get_settings();
		$current  = ! empty( $settings['ai_model_selection'] ) ? $settings['ai_model_selection'] : ( ! empty( $settings['ai_provider'] ) && ! empty( $settings['ai_model'] ) ? $settings['ai_provider'] . '|' . $settings['ai_model'] : '' );
		$options  = AF_AI_Helper::get_available_models();

		?>
		<label for="ai_model_selection_field" class="screen-reader-text"><?php esc_html_e( 'Preferred AI Model', 'af-companion' ); ?></label>
		<select id="ai_model_selection_field" name="af_companion_ai_settings[ai_model_selection]" class="regular-text" style="width: 100%; max-width: 400px; padding: 6px 10px; border-radius: 4px; border: 1px solid #cbd5e1;">
			<?php
			foreach ( $options as $val => $label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $val ),
					selected( $current, $val, false ),
					esc_html( $label )
				);
			}
			?>
		</select>
		<p class="description" style="margin-top: 6px;"><?php esc_html_e( 'Select the preferred model for AI text and image generation tasks.', 'af-companion' ); ?></p>
		<?php
	}

	public function field_prompt_callback() {
		$settings = AF_AI_Helper::get_settings();
		$prompt   = isset( $settings['alt_prompt'] ) ? $settings['alt_prompt'] : '';
		?>
		<label for="alt_prompt_field" class="screen-reader-text"><?php esc_html_e( 'Default Alt Text Prompt', 'af-companion' ); ?></label>
		<textarea id="alt_prompt_field" name="af_companion_ai_settings[alt_prompt]" rows="4" class="large-text" style="width: 100%; max-width: 600px; padding: 8px 12px; border-radius: 4px; border: 1px solid #cbd5e1;"><?php echo esc_textarea( $prompt ); ?></textarea>
		<p class="description" style="margin-top: 6px;"><?php esc_html_e( 'Customize instructions sent to the AI when generating alt text.', 'af-companion' ); ?></p>
		<?php
	}

	public function sanitize_settings( $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error(
				'af_companion_ai_settings',
				'unauthorized',
				__( 'You do not have sufficient permissions to update settings.', 'af-companion' ),
				'error'
			);
			return AF_AI_Helper::get_settings();
		}

		$output = AF_AI_Helper::get_settings();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$allowed_models = AF_AI_Helper::get_available_models();
		$selected_model = isset( $input['ai_model_selection'] ) ? sanitize_text_field( wp_unslash( $input['ai_model_selection'] ) ) : '';

		if ( ! empty( $selected_model ) && array_key_exists( $selected_model, $allowed_models ) && false !== strpos( $selected_model, '|' ) ) {
			$parts                        = explode( '|', $selected_model, 2 );
			$output['ai_model_selection'] = $selected_model;
			$output['ai_provider']        = sanitize_key( $parts[0] );
			$output['ai_model']           = sanitize_text_field( $parts[1] );
		} else {
			$output['ai_model_selection'] = '';
			$output['ai_provider']        = '';
			$output['ai_model']           = '';
		}

		if ( isset( $input['alt_prompt'] ) ) {
			$output['alt_prompt'] = sanitize_textarea_field( wp_unslash( $input['alt_prompt'] ) );
		}

		return $output;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
		}
		?>
		<div class="wrap af-status-container">
			<div class="af-status-header">
				<div class="af-status-branding">
					<h1><?php esc_html_e( 'AI Settings', 'af-companion' ); ?></h1>
					<span class="af-badge-status"><?php esc_html_e( 'Configuration', 'af-companion' ); ?></span>
				</div>
				<p class="af-subtitle"><?php esc_html_e( 'Configure your default AI provider, model preferences, and prompt parameters used across plugin features.', 'af-companion' ); ?></p>
			</div>

			<?php settings_errors( 'af_companion_ai_settings' ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'af_companion_ai_settings_group' ); ?>
				
				<div class="af-status-grid">
					<div class="af-status-card" style="grid-column: 1 / -1;">
						<h2 class="af-card-title">
							<span class="dashicons dashicons-superhero" aria-hidden="true"></span>
							<?php esc_html_e( 'General AI Engine Configuration', 'af-companion' ); ?>
						</h2>
						
						<?php do_settings_sections( 'af-ai-settings-page' ); ?>

						<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
							<?php submit_button( esc_html__( 'Save AI Configuration', 'af-companion' ), 'primary', 'submit', false ); ?>
						</div>
					</div>
				</div>
			</form>

			<div class="af-status-footer">
				<p><?php printf( esc_html__( 'Powered by AF Themes &bull; Need help setting up API keys? Visit <a href="%s" target="_blank">Connectors Settings</a>', 'af-companion' ), esc_url( AF_AI_Helper::get_connector_setup_url() ) ); ?></p>
			</div>
		</div>
		<?php
	}
}

new AF_AI_Settings_Dashboard();