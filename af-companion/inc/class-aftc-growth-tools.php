<?php
/**
 * AF Growth Tools - The Publisher's Growth & SEO Engine
 * 
 * Version: 2.1.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Growth_Tools {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ), 22 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Frontend Injections
        add_action( 'wp_head', array( $this, 'inject_header_codes' ), 1 );
        add_action( 'wp_body_open', array( $this, 'inject_body_codes' ) );
        add_filter( 'the_content', array( $this, 'inject_rss_footer' ), 99 );
    }

    public function add_menu() {
        add_submenu_page(
            'af-companion', 
            esc_html__( 'Growth Tools', 'af-companion' ), 
            esc_html__( 'Growth Tools', 'af-companion' ), 
            'manage_options', 
            'af-growth', 
            array( $this, 'render_page' ),
            2
        );
    }

    /**
     * Detect conflicting primary SEO plugins
     */
    private function detect_conflicting_seo_plugin() {
        $active_plugins = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
        }

        foreach ( $active_plugins as $plugin ) {
            if ( strpos( $plugin, 'wordpress-seo' ) !== false ) {
                return 'Yoast SEO';
            }
            if ( strpos( $plugin, 'seo-by-rank-math' ) !== false ) {
                return 'Rank Math SEO';
            }
            if ( strpos( $plugin, 'all-in-one-seo-pack' ) !== false ) {
                return 'All in One SEO (AIOSEO)';
            }
            if ( strpos( $plugin, 'seopress' ) !== false ) {
                return 'SEOPress';
            }
        }
        return false;
    }

    public function register_settings() {
        register_setting( 'af_growth_group', 'af_growth_options', array(
            'sanitize_callback' => array( $this, 'sanitize_options' )
        ) );

        // --- SECTION 1: ANALYTICS & MEASUREMENT ---
        add_settings_section( 'af_growth_analytics', esc_html__( 'Analytics & Measurement', 'af-companion' ), null, 'af-growth' );
        $analytics_fields = array(
            'ga4_id'     => array( 'label' => __( 'GA4 Measurement ID', 'af-companion' ), 'placeholder' => 'G-XXXXXXXXXX', 'desc' => __( 'Direct Google Analytics 4 tracking integration.', 'af-companion' ) ),
            'gtm_id'     => array( 'label' => __( 'Google Tag Manager ID', 'af-companion' ), 'placeholder' => 'GTM-XXXXXXX', 'desc' => __( 'Injects GTM container in head and body.', 'af-companion' ) ),
            'fbpixel_id' => array( 'label' => __( 'Meta Pixel ID', 'af-companion' ), 'placeholder' => '1234567890', 'desc' => __( 'Track social media conversions & events.', 'af-companion' ) ),
            'clarity_id' => array( 'label' => __( 'Microsoft Clarity ID', 'af-companion' ), 'placeholder' => 'abcdefghij', 'desc' => __( 'Free heatmaps and session recordings.', 'af-companion' ) ),
        );

        // --- SECTION 2: WEBMASTER VERIFICATION ---
        add_settings_section( 'af_growth_verification', esc_html__( 'Webmaster Verification Vault', 'af-companion' ), null, 'af-growth' );
        $verification_fields = array(
            'google_verify'    => array( 'label' => __( 'Google Search Console', 'af-companion' ), 'placeholder' => 'google-site-verification code', 'desc' => __( 'Enter meta content value for Google verification.', 'af-companion' ) ),
            'bing_verify'      => array( 'label' => __( 'Bing Webmaster Tools', 'af-companion' ), 'placeholder' => 'msvalidate.01 code', 'desc' => __( 'Enter Bing site verification code.', 'af-companion' ) ),
            'yandex_verify'    => array( 'label' => __( 'Yandex Webmaster', 'af-companion' ), 'placeholder' => 'yandex-verification code', 'desc' => __( 'Enter Yandex verification code.', 'af-companion' ) ),
            'pinterest_verify' => array( 'label' => __( 'Pinterest Verify', 'af-companion' ), 'placeholder' => 'p:domain_verify code', 'desc' => __( 'Enter Pinterest confirmation tag.', 'af-companion' ) ),
        );

        // --- SECTION 3: SCHEMA & PUBLISHER SEO ---
        add_settings_section( 'af_growth_schema', esc_html__( 'Schema & Publisher SEO Controls', 'af-companion' ), null, 'af-growth' );
        $schema_fields = array(
            'enable_article_schema' => array( 'label' => __( 'Article Schema Markup', 'af-companion' ), 'type' => 'checkbox', 'desc' => __( 'Inject NewsArticle/BlogPosting JSON-LD (Auto-disabled if Yoast/RankMath is active).', 'af-companion' ) ),
            'org_type' => array( 
                'label'   => __( 'Organization Type', 'af-companion' ), 
                'type'    => 'select', 
                'options' => array(
                    'Organization'            => 'General Organization',
                    'NewsMediaOrganization'   => 'News/Magazine Publisher',
                    'EducationalOrganization' => 'Educational Institution'
                ),
                'desc'    => __( 'Organization type for Knowledge Graph graphs.', 'af-companion' ) 
            ),
            'og_default_image' => array( 'label' => __( 'Fallback Open Graph Image', 'af-companion' ), 'placeholder' => 'https://site.com/image.jpg', 'desc' => __( 'Default social share image when no featured image exists.', 'af-companion' ) ),
            'news_keywords'    => array( 'label' => __( 'Default News Keywords', 'af-companion' ), 'placeholder' => 'news, politics, breaking', 'desc' => __( 'Default meta news_keywords for articles.', 'af-companion' ) ),
            'original_source'  => array( 'label' => __( 'Copyright Attribution URL', 'af-companion' ), 'placeholder' => 'https://yoursite.com', 'desc' => __( 'Source attribution link injected into RSS feeds to deter scrapers.', 'af-companion' ) ),
        );

        // --- SECTION 4: MONETIZATION & RETENTION ---
        add_settings_section( 'af_growth_monetize', esc_html__( 'Monetization & Retention', 'af-companion' ), null, 'af-growth' );
        $monetize_fields = array(
            'adsense_id'    => array( 'label' => __( 'AdSense Publisher ID', 'af-companion' ), 'placeholder' => 'pub-XXXXXXXXXXXXXXXX', 'desc' => __( 'Injects Google AdSense Auto-Ads header snippet.', 'af-companion' ) ),
            'mailchimp_api' => array( 'label' => __( 'Mailchimp Newsletter API', 'af-companion' ), 'placeholder' => 'xxxx-usX', 'desc' => __( 'Sync reader subscription lists securely.', 'af-companion' ) ),
        );

        $sections = array(
            'af_growth_analytics'    => $analytics_fields,
            'af_growth_verification' => $verification_fields,
            'af_growth_schema'       => $schema_fields,
            'af_growth_monetize'     => $monetize_fields,
        );

        foreach ( $sections as $s_id => $fields ) {
            foreach ( $fields as $f_id => $args ) {
                $callback = array( $this, 'render_field' );
                if ( isset( $args['type'] ) ) {
                    if ( $args['type'] === 'select' )   $callback = array( $this, 'render_select' );
                    if ( $args['type'] === 'checkbox' ) $callback = array( $this, 'render_checkbox' );
                }
                add_settings_field( $f_id, esc_html( $args['label'] ), $callback, 'af-growth', $s_id, array_merge( array( 'id' => $f_id ), $args ) );
            }
        }
    }

    public function sanitize_options( $input ) {
        $new_input = array();
        if ( is_array( $input ) ) {
            foreach ( $input as $key => $val ) {
                if ( $key === 'original_source' || $key === 'og_default_image' ) {
                    $new_input[ $key ] = esc_url_raw( $val );
                } else {
                    $new_input[ $key ] = sanitize_text_field( $val );
                }
            }
        }
        return $new_input;
    }

    public function inject_header_codes() {
        if ( is_admin() ) {
            return;
        }
        $options = get_option( 'af_growth_options' );
        if ( ! $options ) {
            return;
        }

        // 1. Webmaster Verification Metas
        if ( ! empty( $options['google_verify'] ) ) {
            echo '<meta name="google-site-verification" content="' . esc_attr( $options['google_verify'] ) . '" />' . "\n";
        }
        if ( ! empty( $options['bing_verify'] ) ) {
            echo '<meta name="msvalidate.01" content="' . esc_attr( $options['bing_verify'] ) . '" />' . "\n";
        }
        if ( ! empty( $options['yandex_verify'] ) ) {
            echo '<meta name="yandex-verification" content="' . esc_attr( $options['yandex_verify'] ) . '" />' . "\n";
        }
        if ( ! empty( $options['pinterest_verify'] ) ) {
            echo '<meta name="p:domain_verify" content="' . esc_attr( $options['pinterest_verify'] ) . '" />' . "\n";
        }

        // 2. GA4 Tracking
        if ( ! empty( $options['ga4_id'] ) ) {
            $ga_id = esc_js( $options['ga4_id'] );
            ?>
            <!-- GA4 Tracking by AF Companion -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '<?php echo $ga_id; ?>');
            </script>
            <?php
        }

        // 3. GTM Head
        if ( ! empty( $options['gtm_id'] ) ) {
            $gtm_id = esc_js( $options['gtm_id'] );
            ?>
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtm_id; ?>');</script>
            <?php
        }

        // 4. Microsoft Clarity
        if ( ! empty( $options['clarity_id'] ) ) {
            $cl_id = esc_js( $options['clarity_id'] );
            ?>
            <script type="text/javascript">(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window, document, "clarity", "script", "<?php echo $cl_id; ?>");</script>
            <?php
        }

        // 5. Schema De-confliction Check (Disable if primary SEO plugin detected)
        $conflicting_seo = $this->detect_conflicting_seo_plugin();
        if ( ! $conflicting_seo && is_singular( 'post' ) && ! empty( $options['enable_article_schema'] ) ) {
            $schema = array(
                "@context"      => "https://schema.org",
                "@type"         => "NewsArticle",
                "headline"      => get_the_title(),
                "datePublished" => get_the_date( 'c' ),
                "author"        => array( "@type" => "Person", "name" => get_the_author() ),
                "image"         => get_the_post_thumbnail_url( get_the_ID(), 'full' ) ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : ( $options['og_default_image'] ?? '' )
            );
            echo "\n<script type='application/ld+json'>" . wp_json_encode( $schema ) . "</script>\n";
        }

        // 6. Open Graph Social Fallbacks
        $og_image = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : ( $options['og_default_image'] ?? '' );
        if ( ! empty( $og_image ) ) {
            echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
        }
    }

    public function inject_body_codes() {
        $options = get_option( 'af_growth_options' );
        if ( ! empty( $options['gtm_id'] ) ) {
            $gtm_url = add_query_arg( 'id', $options['gtm_id'], 'https://www.googletagmanager.com/ns.html' );
            ?>
            <noscript><iframe src="<?php echo esc_url( $gtm_url ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <?php
        }
    }

    public function inject_rss_footer( $content ) {
        if ( is_feed() ) {
            $options = get_option( 'af_growth_options' );
            if ( ! empty( $options['original_source'] ) ) {
                $source_link = esc_url( $options['original_source'] );
                $content    .= '<p><small>' . sprintf( esc_html__( 'Originally published at %s', 'af-companion' ), '<a href="' . $source_link . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>' ) . '</small></p>';
            }
        }
        return $content;
    }

    /** UI RENDERERS **/
    public function render_field( $args ) {
        $options = get_option( 'af_growth_options' );
        $val     = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        echo '<input type="text" name="af_growth_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $val ) . '" class="af-growth-input" placeholder="' . esc_attr( $args['placeholder'] ) . '">';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    public function render_select( $args ) {
        $options = get_option( 'af_growth_options' );
        $val     = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        echo '<select name="af_growth_options[' . esc_attr( $args['id'] ) . ']" class="af-growth-input">';
        foreach ( $args['options'] as $key => $label ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $val, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    public function render_checkbox( $args ) {
        $options = get_option( 'af_growth_options' );
        $val     = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        printf( '<input type="checkbox" name="af_growth_options[%s]" value="1" %s />', esc_attr( $args['id'] ), checked( 1, $val, false ) );
        echo '<span class="description" style="margin:0;">' . esc_html( $args['desc'] ) . '</span>';
        echo '</div>';
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $conflicting_seo = $this->detect_conflicting_seo_plugin();
        $options         = get_option( 'af_growth_options', array() );
        $current_og_img  = isset( $options['og_default_image'] ) ? esc_url( $options['og_default_image'] ) : '';
        $site_domain     = parse_url( home_url(), PHP_URL_HOST );
        ?>
        <?php AF_Admin_Header::render( 'af-growth' ); ?>
        <div class="wrap af-growth-container">
            <div class="af-growth-header">
                <div class="af-growth-branding">
                    <h1><?php esc_html_e( 'Growth Tools & Publisher Suite', 'af-companion' ); ?></h1>
                    <span class="af-badge"><?php esc_html_e( 'SEO & Analytics', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Configure webmaster verification, social sharing metadata, analytics, and schema de-confliction.', 'af-companion' ); ?></p>
            </div>

            <?php if ( $conflicting_seo ) : ?>
                <div class="notice notice-warning" style="margin: 0 0 20px 0; padding: 12px; border-left-color: #f59e0b;">
                    <p style="margin:0; font-weight: 600;">
                        <?php printf( esc_html__( '%s detected. AF Companion schema controls are automatically disabled to prevent duplicate JSON-LD tags.', 'af-companion' ), esc_html( $conflicting_seo ) ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'af_growth_group' );
                global $wp_settings_sections, $wp_settings_fields;
                $page = 'af-growth';

                if ( isset( $wp_settings_sections[ $page ] ) ) {
                    foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
                        echo '<div class="af-growth-card">';
                        echo '<h2 class="af-section-title">' . esc_html( $section['title'] ) . '</h2>';
                        echo '<div class="af-field-grid">';
                        
                        if ( isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
                            foreach ( (array) $wp_settings_fields[ $page ][ $section['id'] ] as $field ) {
                                echo '<div class="af-field-row">';
                                echo '<label class="af-field-label">' . esc_html( $field['title'] ) . '</label>';
                                call_user_func( $field['callback'], $field['args'] );
                                echo '</div>';

                                // INTERCEPT: Render Live Social Share Preview right beneath the Fallback Open Graph Image field
                                if ( $field['id'] === 'og_default_image' ) {
                                    echo '<div class="af-field-row" style="grid-column: 1 / -1; background: #fff; border: 1px solid #e2e8f0;">';
                                    echo '<label class="af-field-label" style="color: #1e293b;">' . esc_html__( 'Live Social Share Preview', 'af-companion' ) . '</label>';
                                    ?>
                                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; max-width: 420px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-top: 10px;">
                                        <div id="af-og-preview-img-container" style="height: 180px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 600; font-size: 13px; overflow: hidden;">
                                            <?php if ( $current_og_img ) : ?>
                                                <img src="<?php echo esc_url( $current_og_img ); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Preview" />
                                            <?php else : ?>
                                                <span id="af-live-og-placeholder"><?php esc_html_e( 'No Image Set (Using Placeholder)', 'af-companion' ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="padding: 15px;">
                                            <div style="color: #64748b; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;"><?php echo esc_html( $site_domain ); ?></div>
                                            <div style="font-weight: 700; color: #1e293b; font-size: 15px; line-height: 1.4; margin-bottom: 6px;"><?php esc_html_e( 'Sample Article Headline Preview Title', 'af-companion' ); ?></div>
                                            <div style="color: #475569; font-size: 13px; line-height: 1.4;"><?php esc_html_e( 'How your content metadata appears when shared across social channels.', 'af-companion' ); ?></div>
                                        </div>
                                    </div>
                                    <?php
                                    echo '</div>';
                                }
                            }
                        }
                        echo '</div></div>';
                    }
                }
                submit_button( esc_html__( 'Update Growth & SEO Engine', 'af-companion' ), 'primary large af-submit-btn' );
                ?>
            </form>
        </div>

        <!-- Live Binding Script for OG Image Input -->
        <script>
        jQuery(document).ready(function($) {
            $('input[name="af_growth_options[og_default_image]"]').on('input change', function() {
                var imgUrl = $(this).val().trim();
                var $container = $('#af-og-preview-img-container');
                
                if (imgUrl) {
                    $container.html('<img src="' + imgUrl + '" style="width: 100%; height: 100%; object-fit: cover;" alt="Preview" onerror="this.onerror=null;this.parentElement.innerHTML=\'<span>Invalid Image URL</span>\';" />');
                } else {
                    $container.html('<span id="af-live-og-placeholder">No Image Set (Using Placeholder)</span>');
                }
            });
        });
        </script>

        <style>
            .af-growth-container { max-width: 1000px; margin: 20px auto 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; color: #1d2327; }
            .af-growth-header { margin-bottom: 30px; border-bottom: 1px solid #dcdcde; padding-bottom: 20px; }
            .af-growth-branding { display: flex; align-items: center; gap: 15px; }
            .af-growth-branding h1 { font-size: 28px; font-weight: 800; color: #1d2327; margin: 0; }
            .af-badge { background: #10b981; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
            .af-subtitle { color: #646970; font-size: 15px; margin-top: 8px; }
            .af-growth-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; }
            .af-section-title { font-size: 18px; font-weight: 700; color: #1d2327; margin: 0 0 25px 0; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1; }
            .af-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            @media (max-width: 782px) { .af-field-grid { grid-template-columns: 1fr; } }
            .af-field-row { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #edf2f7; }
            .af-field-label { font-weight: 700; font-size: 12px; color: #64748b; margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
            .af-growth-input { width: 100%; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; box-sizing: border-box; }
            select.af-growth-input { height: 38px; }
            .af-growth-input:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
            .af-submit-btn { background: #10b981 !important; border: none !important; padding: 12px 35px !important; font-weight: 600 !important; border-radius: 6px !important; transition: 0.2s; }
            .af-submit-btn:hover { background: #059669 !important; transform: translateY(-1px); }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated, .update-nag.gt-admin-notice { display: none !important; }
        </style>
        <?php
    }
}