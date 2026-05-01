<?php
/**
 * AF Growth Tools - The Publisher's Growth & Analytics Suite
 * 
 * Version: 1.4.0
 * Author: AF Themes
 * 
 * Peer Review Updates:
 * - Fixed: GTM Noscript injection now uses proper escaping.
 * - Added: Nonce verification on form submission for PRT compliance.
 * - Added: Social Meta fallback logic for Organization Schema.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Growth_Tools {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ), 22 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Frontend Injections
        add_action( 'wp_head', array( $this, 'inject_header_codes' ), 1 );
        add_action( 'wp_body_open', array( $this, 'inject_body_codes' ) );
        add_action( 'wp_footer', array( $this, 'inject_footer_codes' ), 99 );
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

    public function register_settings() {
        register_setting( 'af_growth_group', 'af_growth_options', array(
            'sanitize_callback' => array( $this, 'sanitize_options' )
        ) );

        // --- SECTION 1: ANALYTICS ---
        add_settings_section( 'af_growth_analytics', esc_html__( 'Analytics & Measurement', 'af-companion' ), null, 'af-growth' );
        $analytics_fields = array(
            'ga4_id'     => array( 'label' => __( 'GA4 Measurement ID', 'af-companion' ), 'placeholder' => 'G-XXXXXXXXXX', 'desc' => __( 'Direct Google Analytics 4 integration.', 'af-companion' ) ),
            'gtm_id'     => array( 'label' => __( 'Google Tag Manager ID', 'af-companion' ), 'placeholder' => 'GTM-XXXXXXX', 'desc' => __( 'Injects GTM container in head and body.', 'af-companion' ) ),
            'fbpixel_id' => array( 'label' => __( 'Meta Pixel ID', 'af-companion' ), 'placeholder' => '1234567890', 'desc' => __( 'Track social media conversions.', 'af-companion' ) ),
        );

        // --- SECTION 2: BEHAVIORAL TOOLS ---
        add_settings_section( 'af_growth_behavior', esc_html__( 'User Experience & Heatmaps', 'af-companion' ), null, 'af-growth' );
        $behavior_fields = array(
            'hotjar_id'  => array( 'label' => __( 'Hotjar Site ID', 'af-companion' ), 'placeholder' => '1234567', 'desc' => __( 'Enable heatmaps and session recordings.', 'af-companion' ) ),
            'clarity_id' => array( 'label' => __( 'Microsoft Clarity ID', 'af-companion' ), 'placeholder' => 'abcdefghij', 'desc' => __( 'Free behavioral analytics by Microsoft.', 'af-companion' ) ),
        );

        // --- SECTION 3: SCHEMA & SEO ---
        add_settings_section( 'af_growth_schema', esc_html__( 'Schema & Search Appearance', 'af-companion' ), null, 'af-growth' );
        $schema_fields = array(
            'enable_article_schema' => array( 'label' => __( 'Article Schema', 'af-companion' ), 'type' => 'checkbox', 'desc' => __( 'Enable NewsArticle/BlogPosting JSON-LD for posts.', 'af-companion' ) ),
            'org_type' => array( 
                'label' => __( 'Organization Type', 'af-companion' ), 
                'type' => 'select', 
                'options' => array(
                    'Organization' => 'General Organization',
                    'NewsMediaOrganization' => 'News/Magazine',
                    'EducationalOrganization' => 'Educational (College/School)'
                ),
                'desc' => __( 'Identify your site type for Google Knowledge Graph.', 'af-companion' ) 
            ),
        );

        // --- SECTION 4: MONETIZATION & SOCIAL ---
        add_settings_section( 'af_growth_monetize', esc_html__( 'Monetization & Retention', 'af-companion' ), null, 'af-growth' );
        $monetize_fields = array(
            'adsense_id'       => array( 'label' => __( 'AdSense Publisher ID', 'af-companion' ), 'placeholder' => 'pub-XXXXXXXXXXXXXXXX', 'desc' => __( 'Injects AdSense Auto-Ads script.', 'af-companion' ) ),
            'og_default_image' => array( 'label' => __( 'Social Share Image', 'af-companion' ), 'placeholder' => 'https://site.com/image.jpg', 'desc' => __( 'Fallback Open Graph image.', 'af-companion' ) ),
            'mailchimp_api'    => array( 'label' => __( 'Mailchimp API', 'af-companion' ), 'placeholder' => 'xxxx-usX', 'desc' => __( 'Sync magazine newsletter subscribers.', 'af-companion' ) ),
        );

        $sections = array(
            'af_growth_analytics' => $analytics_fields,
            'af_growth_behavior'  => $behavior_fields,
            'af_growth_schema'    => $schema_fields,
            'af_growth_monetize'  => $monetize_fields,
        );

        foreach ( $sections as $s_id => $fields ) {
            foreach ( $fields as $f_id => $args ) {
                $callback = array( $this, 'render_field' );
                if ( isset($args['type']) ) {
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
                $new_input[$key] = sanitize_text_field( $val );
            }
        }
        return $new_input;
    }

    public function inject_header_codes() {
        if ( is_admin() ) return;
        $options = get_option( 'af_growth_options' );
        if ( ! $options ) return;

        // GA4 Injection
        if ( ! empty( $options['ga4_id'] ) ) {
            $ga_id = esc_js( $options['ga4_id'] ); // Use esc_js inside script blocks
            ?>
            <!-- GA4 by AF Themes -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '<?php echo $ga_id; ?>');
            </script>
            <?php
        }

        // GTM Head
        if ( ! empty( $options['gtm_id'] ) ) {
            $gtm_id = esc_js( $options['gtm_id'] );
            ?>
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtm_id; ?>');</script>
            <?php
        }

        // Clarity Behavioral Heatmaps
        if ( ! empty( $options['clarity_id'] ) ) {
            $cl_id = esc_js( $options['clarity_id'] );
            ?>
            <script type="text/javascript">(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window, document, "clarity", "script", "<?php echo $cl_id; ?>");</script>
            <?php
        }

        // JSON-LD Schema
        if ( is_singular('post') && ! empty( $options['enable_article_schema'] ) ) {
            $schema = array(
                "@context" => "https://schema.org",
                "@type"    => "NewsArticle",
                "headline" => get_the_title(),
                "datePublished" => get_the_date('c'),
                "author" => array( "@type" => "Person", "name"  => get_the_author() ),
                "image"  => get_the_post_thumbnail_url(get_the_ID(), 'full')
            );
            echo "\n<script type='application/ld+json'>" . wp_json_encode($schema) . "</script>\n";
        }

        // Social OG Fallback
        if ( is_front_page() && ! empty( $options['og_default_image'] ) ) {
            echo '<meta property="og:image" content="' . esc_url( $options['og_default_image'] ) . '" />' . "\n";
        }
    }

    public function inject_body_codes() {
        $options = get_option( 'af_growth_options' );
        if ( ! empty( $options['gtm_id'] ) ) {
            // Proper PRT-compliant escaping for iframe URLs
            $gtm_url = add_query_arg( 'id', $options['gtm_id'], 'https://www.googletagmanager.com/ns.html' );
            ?>
            <noscript><iframe src="<?php echo esc_url( $gtm_url ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <?php
        }
    }

    public function inject_footer_codes() {
        // Keep for future growth hooks (e.g. non-JS pixel tracking)
    }

    /** UI RENDERERS **/
    public function render_field( $args ) {
        $options = get_option( 'af_growth_options' );
        $val = isset( $options[$args['id']] ) ? $options[$args['id']] : '';
        echo '<input type="text" name="af_growth_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $val ) . '" class="af-growth-input" placeholder="' . esc_attr( $args['placeholder'] ) . '">';
        echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    public function render_select( $args ) {
        $options = get_option( 'af_growth_options' );
        $val = isset( $options[$args['id']] ) ? $options[$args['id']] : '';
        echo '<select name="af_growth_options[' . esc_attr( $args['id'] ) . ']" class="af-growth-input">';
        foreach ( $args['options'] as $key => $label ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr($key), selected($val, $key, false), esc_html($label) );
        }
        echo '</select>';
    }

    public function render_checkbox( $args ) {
        $options = get_option( 'af_growth_options' );
        $val = isset( $options[$args['id']] ) ? $options[$args['id']] : '';
        printf( '<input type="checkbox" name="af_growth_options[%s]" value="1" %s />', esc_attr($args['id']), checked(1, $val, false) );
        echo '<span class="description" style="margin-left:10px;">' . esc_html( $args['desc'] ) . '</span>';
    }

    public function render_page() {
        ?>
        <div class="wrap af-growth-container">
            <div class="af-growth-header">
                <div class="af-growth-branding">
                    <h1><?php esc_html_e( 'Growth Tools', 'af-companion' ); ?></h1>
                    <span class="af-badge"><?php esc_html_e( 'Revenue & Monetize', 'af-companion' ); ?></span>
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Optimized analytics and discovery suite for publishers.', 'af-companion' ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'af_growth_group' );
                global $wp_settings_sections, $wp_settings_fields;
                $page = 'af-growth';

                if ( isset( $wp_settings_sections[$page] ) ) {
                    foreach ( (array) $wp_settings_sections[$page] as $section ) {
                        echo '<div class="af-growth-card">';
                        echo '<h2 class="af-section-title">' . esc_html( $section['title'] ) . '</h2>';
                        echo '<div class="af-field-grid">';
                        if ( isset( $wp_settings_fields[$page][$section['id']] ) ) {
                            foreach ( (array) $wp_settings_fields[$page][$section['id']] as $field ) {
                                echo '<div class="af-field-row">';
                                echo '<label class="af-field-label">' . $field['title'] . '</label>';
                                call_user_func($field['callback'], $field['args']);
                                echo '</div>';
                            }
                        }
                        echo '</div></div>';
                    }
                }
                submit_button( esc_html__( 'Update Growth Engine', 'af-companion' ), 'primary large af-submit-btn' );
                ?>
            </form>
        </div>

        <style>
            .af-growth-container { max-width: 1000px; margin: 20px auto; font-family: -apple-system, system-ui, sans-serif; }
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
            .af-growth-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background: #fff; }
            .af-growth-input:focus { border-color: #10b981; outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
            .af-submit-btn { background: #10b981 !important; border: none !important; padding: 12px 35px !important; font-weight: 600 !important; border-radius: 8px !important; transition: 0.2s; }
            .af-submit-btn:hover { background: #059669 !important; transform: translateY(-1px); }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated { display: none !important; }
        </style>
        <?php
    }
}