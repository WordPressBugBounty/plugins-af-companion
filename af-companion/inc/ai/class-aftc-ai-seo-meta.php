<?php
/**
 * AF Companion - Complete AI SEO, AIO, GEO & Schema Engine
 *
 * @package           AF_Companion
 * @author            AF Themes
 * @license           GPL-2.0-or-later
 * @link              https://afthemes.com
 * @version           4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AF_AI_SEO_Meta {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
		add_action( 'wp_ajax_af_generate_ai_seo_meta', array( $this, 'ajax_generate_seo_meta' ) );

		// Frontend Document Head Injections
		add_filter( 'pre_get_document_title', array( $this, 'render_frontend_title' ), 15 );
		add_action( 'wp_head', array( $this, 'render_frontend_metadata' ), 5 );
	}

	/**
	 * Register Meta Box on all public post types.
	 */
	public function register_meta_box() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( 'af_companion_seo_meta_post_types', $post_types );

		foreach ( $post_types as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box(
				'af_ai_seo_meta_box',
				esc_html__( 'AF Advanced AI SEO, AIO & GEO Assistant', 'af-companion' ),
				array( $this, 'render_meta_box' ),
				$type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render Post Editor Meta Box Interface
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'af_seo_meta_nonce_action', 'af_seo_meta_nonce' );

		// Core SEO & Social Fields
		$meta_title   = get_post_meta( $post->ID, '_af_seo_meta_title', true );
		$meta_desc    = get_post_meta( $post->ID, '_af_seo_meta_description', true );
		$social_title = get_post_meta( $post->ID, '_af_social_title', true );
		$social_desc  = get_post_meta( $post->ID, '_af_social_desc', true );
		$social_image = get_post_meta( $post->ID, '_af_social_image', true );

		// GEO / AIO Extended Fields
		$ai_keywords   = get_post_meta( $post->ID, '_af_ai_keywords', true );
		$info_gain     = get_post_meta( $post->ID, '_af_info_gain_summary', true );
		$faq_pairs     = get_post_meta( $post->ID, '_af_faq_schema_data', true );
		$readability   = get_post_meta( $post->ID, '_af_readability_score', true );
		$schema_type   = get_post_meta( $post->ID, '_af_schema_type', true );
		if ( empty( $schema_type ) ) {
			$schema_type = 'Article';
		}

		// Feature Toggles
		$enable_seo    = get_post_meta( $post->ID, '_af_enable_seo', true );
		$enable_social = get_post_meta( $post->ID, '_af_enable_social', true );
		$enable_schema = get_post_meta( $post->ID, '_af_enable_schema', true );
		$enable_geo    = get_post_meta( $post->ID, '_af_enable_geo', true );

		if ( '' === $enable_seo ) { $enable_seo = '1'; }
		if ( '' === $enable_social ) { $enable_social = '1'; }
		if ( '' === $enable_schema ) { $enable_schema = '1'; }
		if ( '' === $enable_geo ) { $enable_geo = '1'; }

		$is_ai_connected = AF_AI_Helper::is_connector_available();
		?>
		<div class="af-seo-meta-container" style="padding: 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			
			<!-- Feature Toggles -->
			<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
				<span style="font-weight: 600; font-size: 13px; color: #334155;"><?php esc_html_e( 'Module Feature Toggles:', 'af-companion' ); ?></span>
				<div style="display: flex; gap: 15px; flex-wrap: wrap;">
					<label style="font-size: 12px; cursor: pointer;"><input type="checkbox" name="_af_enable_seo" value="1" <?php checked( $enable_seo, '1' ); ?>> <?php esc_html_e( 'SEO Meta', 'af-companion' ); ?></label>
					<label style="font-size: 12px; cursor: pointer;"><input type="checkbox" name="_af_enable_social" value="1" <?php checked( $enable_social, '1' ); ?>> <?php esc_html_e( 'Social & OpenGraph', 'af-companion' ); ?></label>
					<label style="font-size: 12px; cursor: pointer;"><input type="checkbox" name="_af_enable_schema" value="1" <?php checked( $enable_schema, '1' ); ?>> <?php esc_html_e( 'Schema JSON-LD', 'af-companion' ); ?></label>
					<label style="font-size: 12px; cursor: pointer;"><input type="checkbox" name="_af_enable_geo" value="1" <?php checked( $enable_geo, '1' ); ?>> <?php esc_html_e( 'AIO / GEO Optimization', 'af-companion' ); ?></label>
				</div>
			</div>

			<!-- Action Controls -->
			<div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
				<button type="button" class="button button-primary button-large" id="af-generate-all-ai-btn" data-post-id="<?php echo esc_attr( $post->ID ); ?>" <?php disabled( ! $is_ai_connected ); ?>>
					<span class="dashicons dashicons-superhero" aria-hidden="true" style="vertical-align: middle; margin-top: 2px; color: #fff;"></span>
					<?php esc_html_e( 'Generate Complete AI SEO, GEO & Schema Metrics', 'af-companion' ); ?>
				</button>
				<span class="spinner" id="af-seo-spinner" style="float: none; margin: 0;"></span>
				<?php if ( ! $is_ai_connected ) : ?>
					<span style="font-size: 11px; color: #d63638;">
						<?php printf( esc_html__( 'Connect AI in <a href="%s" target="_blank">Settings</a>', 'af-companion' ), esc_url( AF_AI_Helper::get_connector_setup_url() ) ); ?>
					</span>
				<?php endif; ?>
			</div>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
				
				<!-- Column 1: Traditional SEO & Social -->
				<div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px;">
					<h3 style="margin-top: 0; font-size: 14px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
						<?php esc_html_e( '1. Search & Social Metadata', 'af-companion' ); ?>
					</h3>
					
					<p style="margin-bottom: 12px;">
						<label for="af_seo_meta_title" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'SEO Title', 'af-companion' ); ?></label>
						<input type="text" id="af_seo_meta_title" name="af_seo_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" />
					</p>

					<p style="margin-bottom: 12px;">
						<label for="af_seo_meta_description" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'SEO Description', 'af-companion' ); ?></label>
						<textarea id="af_seo_meta_description" name="af_seo_meta_description" class="widefat" rows="3" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;"><?php echo esc_textarea( $meta_desc ); ?></textarea>
					</p>

					<p style="margin-bottom: 12px;">
						<label for="af_social_title" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Social Card Title', 'af-companion' ); ?></label>
						<input type="text" id="af_social_title" name="af_social_title" value="<?php echo esc_attr( $social_title ); ?>" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" />
					</p>

					<p style="margin-bottom: 12px;">
						<label for="af_social_desc" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Social Description', 'af-companion' ); ?></label>
						<textarea id="af_social_desc" name="af_social_desc" class="widefat" rows="2" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;"><?php echo esc_textarea( $social_desc ); ?></textarea>
					</p>

					<p style="margin-bottom: 0;">
						<label for="af_social_image" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Custom Social Fallback Image URL', 'af-companion' ); ?></label>
						<input type="url" id="af_social_image" name="af_social_image" value="<?php echo esc_attr( $social_image ); ?>" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" placeholder="https://example.com/default-share-banner.jpg" />
						<span style="font-size: 10px; color: #64748b;"><?php esc_html_e( 'Used if post has no featured image.', 'af-companion' ); ?></span>
					</p>
				</div>

				<!-- Column 2: GEO & AIO Calculations -->
				<div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px;">
					<h3 style="margin-top: 0; font-size: 14px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
						<?php esc_html_e( '2. GEO & AIO Engine Calculations', 'af-companion' ); ?>
					</h3>

					<p style="margin-bottom: 12px;">
						<label for="af_ai_keywords" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( '1. Entity Density & Semantic Relationships', 'af-companion' ); ?></label>
						<input type="text" id="af_ai_keywords" name="af_ai_keywords" value="<?php echo esc_attr( $ai_keywords ); ?>" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" placeholder="Comma-separated entity nodes..." />
						<span style="font-size: 10px; color: #64748b;"><?php esc_html_e( 'Fed directly to LLMs via ai-entity-graph & keywords tags.', 'af-companion' ); ?></span>
					</p>

					<p style="margin-bottom: 12px;">
						<label for="af_info_gain_summary" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( '2. Information Gain & Uniqueness (Key Takeaways)', 'af-companion' ); ?></label>
						<textarea id="af_info_gain_summary" name="af_info_gain_summary" class="widefat" rows="3" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" placeholder="Core facts & unique findings..."><?php echo esc_textarea( $info_gain ); ?></textarea>
						<span style="font-size: 10px; color: #64748b;"><?php esc_html_e( 'Highlights unique findings so AI search engines skip generic intro fluff.', 'af-companion' ); ?></span>
					</p>

					<p style="margin-bottom: 12px;">
						<label for="af_readability_score" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( '4. Readability & Chunking Score', 'af-companion' ); ?></label>
						<input type="text" id="af_readability_score" name="af_readability_score" value="<?php echo esc_attr( $readability ); ?>" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;" readonly />
					</p>

					<p style="margin-bottom: 0;">
						<label for="af_schema_type" style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( '3. Schema Type (Structured JSON-LD)', 'af-companion' ); ?></label>
						<select id="af_schema_type" name="af_schema_type" class="widefat" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
							<option value="Article" <?php selected( $schema_type, 'Article' ); ?>><?php esc_html_e( 'Article / BlogPosting', 'af-companion' ); ?></option>
							<option value="TechArticle" <?php selected( $schema_type, 'TechArticle' ); ?>><?php esc_html_e( 'TechArticle', 'af-companion' ); ?></option>
							<option value="NewsArticle" <?php selected( $schema_type, 'NewsArticle' ); ?>><?php esc_html_e( 'NewsArticle', 'af-companion' ); ?></option>
							<option value="FAQPage" <?php selected( $schema_type, 'FAQPage' ); ?>><?php esc_html_e( 'FAQPage', 'af-companion' ); ?></option>
						</select>
					</p>
				</div>

			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			$('#af-generate-all-ai-btn').on('click', function(e){
				e.preventDefault();
				var $btn = $(this);
				var postId = $btn.data('post-id');
				var $spinner = $('#af-seo-spinner');

				$btn.prop('disabled', true);
				$spinner.addClass('is-active');

				$.post(ajaxurl, {
					action: 'af_generate_ai_seo_meta',
					post_id: postId,
					nonce: $('#af_seo_meta_nonce').val()
				}, function(response){
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');

					if(response.success && response.data) {
						if(response.data.title) $('#af_seo_meta_title').val(response.data.title);
						if(response.data.description) $('#af_seo_meta_description').val(response.data.description);
						if(response.data.social_title) $('#af_social_title').val(response.data.social_title);
						if(response.data.social_desc) $('#af_social_desc').val(response.data.social_desc);
						if(response.data.keywords) $('#af_ai_keywords').val(response.data.keywords);
						if(response.data.info_gain) $('#af_info_gain_summary').val(response.data.info_gain);
						if(response.data.readability) $('#af_readability_score').val(response.data.readability);
					} else {
						alert(response.data.message || '<?php esc_html_e( 'Generation failed.', 'af-companion' ); ?>');
					}
				}).fail(function(){
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					alert('<?php esc_html_e( 'Server communication error.', 'af-companion' ); ?>');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Save Meta Box Inputs
	 */
	public function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['af_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['af_seo_meta_nonce'] ), 'af_seo_meta_nonce_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Feature Toggles
		update_post_meta( $post_id, '_af_enable_seo', isset( $_POST['_af_enable_seo'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_af_enable_social', isset( $_POST['_af_enable_social'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_af_enable_schema', isset( $_POST['_af_enable_schema'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_af_enable_geo', isset( $_POST['_af_enable_geo'] ) ? '1' : '0' );

		// Input Sanitization
		if ( isset( $_POST['af_seo_meta_title'] ) ) {
			update_post_meta( $post_id, '_af_seo_meta_title', sanitize_text_field( wp_unslash( $_POST['af_seo_meta_title'] ) ) );
		}
		if ( isset( $_POST['af_seo_meta_description'] ) ) {
			update_post_meta( $post_id, '_af_seo_meta_description', sanitize_textarea_field( wp_unslash( $_POST['af_seo_meta_description'] ) ) );
		}
		if ( isset( $_POST['af_social_title'] ) ) {
			update_post_meta( $post_id, '_af_social_title', sanitize_text_field( wp_unslash( $_POST['af_social_title'] ) ) );
		}
		if ( isset( $_POST['af_social_desc'] ) ) {
			update_post_meta( $post_id, '_af_social_desc', sanitize_textarea_field( wp_unslash( $_POST['af_social_desc'] ) ) );
		}
		if ( isset( $_POST['af_social_image'] ) ) {
			update_post_meta( $post_id, '_af_social_image', esc_url_raw( wp_unslash( $_POST['af_social_image'] ) ) );
		}
		if ( isset( $_POST['af_ai_keywords'] ) ) {
			update_post_meta( $post_id, '_af_ai_keywords', sanitize_text_field( wp_unslash( $_POST['af_ai_keywords'] ) ) );
		}
		if ( isset( $_POST['af_info_gain_summary'] ) ) {
			update_post_meta( $post_id, '_af_info_gain_summary', sanitize_textarea_field( wp_unslash( $_POST['af_info_gain_summary'] ) ) );
		}
		if ( isset( $_POST['af_schema_type'] ) ) {
			update_post_meta( $post_id, '_af_schema_type', sanitize_text_field( wp_unslash( $_POST['af_schema_type'] ) ) );
		}
	}

	/**
	 * AJAX Handler for Unified GEO & AIO Analysis
	 */
	public function ajax_generate_seo_meta() {
		check_ajax_referer( 'af_seo_meta_nonce_action', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'af-companion' ) ) );
		}

		if ( ! AF_AI_Helper::is_connector_available() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'AI Connector not configured.', 'af-companion' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Post not found.', 'af-companion' ) ) );
		}

		$raw_content   = $post->post_content;
		$clean_content = wp_strip_all_tags( strip_shortcodes( $raw_content ) );
		$clean_content = wp_trim_words( $clean_content, 450, '' );

		if ( empty( $clean_content ) ) {
			$clean_content = get_the_title( $post_id );
		}

		// Calculate Readability & Structural Chunking Score locally
		$word_count    = str_word_count( $clean_content );
		$heading_count = preg_match_all( '/<h[2-4]/i', $raw_content );
		$list_count    = preg_match_all( '/<ul|<ol/i', $raw_content );
		$chunk_score   = sprintf( 'Word Count: %d | Headings: %d | Lists: %d (Structure: %s)', $word_count, $heading_count, $list_count, ( $heading_count >= 2 ? 'Optimal' : 'Needs Subheadings' ) );

		$site_name = get_bloginfo( 'name' );
		$prompt    = sprintf(
			'Act as an expert in Generative Engine Optimization (GEO) and AI Answer Engine Indexing for "%s". Analyze this article ("%s"). Return ONLY a raw JSON object with these exact keys:
			"title": SEO title under 60 chars.
			"description": Meta description between 145-155 chars.
			"social_title": Catchy social headline.
			"social_desc": Social summary.
			"keywords": Comma-separated list of 6-8 core entity nodes & concepts for LLM graph indexing.
			"info_gain": A 2-sentence bulleted summary extracting the most unique facts, stats, or takeaways from this piece.
			"faqs": An array of objects with "q" (question) and "a" (answer) for the top 2 questions answered in this article.
			Do not use markdown blocks. Content: %s',
			$site_name,
			get_the_title( $post_id ),
			$clean_content
		);

		$response = AF_AI_Helper::generate_text( $prompt );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$clean_json = trim( preg_replace( '/^```json\s*|\s*```$/i', '', $response ) );
		$data       = json_decode( $clean_json, true );

		if ( ! is_array( $data ) || empty( $data['title'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to parse AI output into valid GEO metrics.', 'af-companion' ) ) );
		}

		// Sanitize Data
		$seo_title    = sanitize_text_field( $data['title'] );
		$seo_desc     = sanitize_textarea_field( $data['description'] );
		$social_title = sanitize_text_field( isset( $data['social_title'] ) ? $data['social_title'] : $seo_title );
		$social_desc  = sanitize_textarea_field( isset( $data['social_desc'] ) ? $data['social_desc'] : $seo_desc );
		$keywords     = sanitize_text_field( isset( $data['keywords'] ) ? $data['keywords'] : '' );
		$info_gain    = sanitize_textarea_field( isset( $data['info_gain'] ) ? $data['info_gain'] : '' );
		$faqs         = isset( $data['faqs'] ) && is_array( $data['faqs'] ) ? $data['faqs'] : array();

		// Save Post Meta
		update_post_meta( $post_id, '_af_seo_meta_title', $seo_title );
		update_post_meta( $post_id, '_af_seo_meta_description', $seo_desc );
		update_post_meta( $post_id, '_af_social_title', $social_title );
		update_post_meta( $post_id, '_af_social_desc', $social_desc );
		update_post_meta( $post_id, '_af_ai_keywords', $keywords );
		update_post_meta( $post_id, '_af_info_gain_summary', $info_gain );
		update_post_meta( $post_id, '_af_readability_score', $chunk_score );
		update_post_meta( $post_id, '_af_faq_schema_data', $faqs );

		wp_send_json_success(
			array(
				'title'        => $seo_title,
				'description'  => $seo_desc,
				'social_title' => $social_title,
				'social_desc'  => $social_desc,
				'keywords'     => $keywords,
				'info_gain'    => $info_gain,
				'readability'  => $chunk_score,
			)
		);
	}

	/**
	 * Frontend Document Title Filter
	 */
	public function render_frontend_title( $title ) {
		if ( is_singular() ) {
			$post_id    = get_queried_object_id();
			$enable_seo = get_post_meta( $post_id, '_af_enable_seo', true );

			if ( '1' === $enable_seo ) {
				$custom_title = get_post_meta( $post_id, '_af_seo_meta_title', true );
				if ( ! empty( $custom_title ) ) {
					return esc_html( $custom_title );
				}
			}
		}
		return $title;
	}

	/**
	 * Frontend Injections: Meta Tags, GEO Graph Tags & JSON-LD Schema
	 */
	public function render_frontend_metadata() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		// 1. Traditional SEO Meta & GEO Entity Graph Tags
		$enable_seo = get_post_meta( $post_id, '_af_enable_seo', true );
		if ( '1' === $enable_seo ) {
			$custom_desc = get_post_meta( $post_id, '_af_seo_meta_description', true );
			if ( ! empty( $custom_desc ) ) {
				printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $custom_desc ) );
			}

			// GEO Entity Tags
			$keywords = get_post_meta( $post_id, '_af_ai_keywords', true );
			if ( ! empty( $keywords ) ) {
				printf( '<meta name="keywords" content="%s" />' . "\n", esc_attr( $keywords ) );
				printf( '<meta name="ai-entity-graph" content="%s" />' . "\n", esc_attr( $keywords ) );
			}

			// GEO Information Gain Tag
			$info_gain = get_post_meta( $post_id, '_af_info_gain_summary', true );
			if ( ! empty( $info_gain ) ) {
				printf( '<meta name="ai-summary-takeaways" content="%s" />' . "\n", esc_attr( $info_gain ) );
			}
		}

		// 2. Social OpenGraph & Twitter Cards (With Custom Fallback Image Support)
		$enable_social = get_post_meta( $post_id, '_af_enable_social', true );
		if ( '1' === $enable_social ) {
			$s_title = get_post_meta( $post_id, '_af_social_title', true );
			if ( empty( $s_title ) ) {
				$s_title = get_the_title( $post_id );
			}

			$s_desc = get_post_meta( $post_id, '_af_social_desc', true );
			$s_url  = get_permalink( $post_id );
			
			// Resolution Hierarchy: Featured Image -> Custom Fallback Image -> Site Icon
			$s_img = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( empty( $s_img ) ) {
				$s_img = get_post_meta( $post_id, '_af_social_image', true );
			}
			if ( empty( $s_img ) ) {
				$s_img = get_site_icon_url( 512 );
			}

			echo '<meta property="og:type" content="article" />' . "\n";
			printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $s_title ) );
			if ( ! empty( $s_desc ) ) {
				printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $s_desc ) );
			}
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $s_url ) );
			if ( ! empty( $s_img ) ) {
				printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $s_img ) );
			}

			echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
			printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $s_title ) );
			if ( ! empty( $s_desc ) ) {
				printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $s_desc ) );
			}
			if ( ! empty( $s_img ) ) {
				printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $s_img ) );
			}
		}

		// 3. Information Accessibility: JSON-LD Schema (Article + Dynamic FAQPage)
		$enable_schema = get_post_meta( $post_id, '_af_enable_schema', true );
		if ( '1' === $enable_schema ) {
			$schema_type = get_post_meta( $post_id, '_af_schema_type', true );
			if ( empty( $schema_type ) ) {
				$schema_type = 'Article';
			}

			$post_obj    = get_post( $post_id );
			$author_name = get_the_author_meta( 'display_name', $post_obj->post_author );
			$publisher   = get_bloginfo( 'name' );
			$logo_url    = get_site_icon_url();

			$schema_nodes = array(
				array(
					'@context'         => 'https://schema.org',
					'@type'            => esc_attr( $schema_type ),
					'headline'         => get_the_title( $post_id ),
					'description'      => get_post_meta( $post_id, '_af_seo_meta_description', true ),
					'datePublished'    => get_the_date( 'c', $post_id ),
					'dateModified'     => get_the_modified_date( 'c', $post_id ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => $author_name,
					),
					'publisher'        => array(
						'@type' => 'Organization',
						'name'  => $publisher,
						'logo'  => array(
							'@type' => 'URL',
							'url'   => $logo_url,
						),
					),
					'mainEntityOfPage' => array(
						'@type' => 'WebPage',
						'@id'   => get_permalink( $post_id ),
					),
				),
			);

			// Append FAQPage Schema node if AI extracted FAQs
			$faqs = get_post_meta( $post_id, '_af_faq_schema_data', true );
			if ( is_array( $faqs ) && ! empty( $faqs ) ) {
				$faq_entities = array();
				foreach ( $faqs as $pair ) {
					if ( ! empty( $pair['q'] ) && ! empty( $pair['a'] ) ) {
						$faq_entities[] = array(
							'@type'          => 'Question',
							'name'           => esc_html( $pair['q'] ),
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => esc_html( $pair['a'] ),
							),
						);
					}
				}

				if ( ! empty( $faq_entities ) ) {
					$schema_nodes[] = array(
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $faq_entities,
					);
				}
			}

			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode( $schema_nodes, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
			);
		}
	}
}

new AF_AI_SEO_Meta();