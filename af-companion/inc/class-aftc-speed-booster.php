<?php
/**
 * AF Speed Booster - Professional Performance Suite
 * 
 * Version: 2.2.0
 * Author: AF Themes
 * 
 * Highlights:
 * - Restored Card & Grid UI with full logic.
 * - Added Data Sanitization for security.
 * - Added non-blocking script safety.
 * - Full i18n support preserved.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Speed_Booster {

    public function __construct() {
        // Administration
        add_action( 'admin_menu', array( $this, 'add_menu' ), 21 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Frontend Execution
        add_action( 'init', array( $this, 'apply_core_optimizations' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'optimize_assets' ), 999 );
        
        // Advanced Filters
        add_filter( 'style_loader_tag', array( $this, 'optimize_google_fonts' ), 10, 2 );
        add_filter( 'script_loader_tag', array( $this, 'apply_async_js' ), 10, 2 );
        
        // HTML Minification (Safe Buffer)
        add_action( 'template_redirect', array( $this, 'setup_html_minification' ) );

        // Add Resource Hints
        add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );

        // Gutenberg Logic
        add_action( 'init', array( $this, 'handle_gutenberg_logic' ) );
    }

    public function add_menu() {
        add_submenu_page( 
            'af-companion', 
            esc_html__( 'Speed Booster', 'af-companion' ), 
            esc_html__( 'Speed Booster', 'af-companion' ), 
            'manage_options', 
            'af-speed', 
            array( $this, 'render_page' ),
            1
        );
    }

    public function register_settings() {
        // Added sanitization callback for professional data handling
        register_setting( 'af_speed_group', 'af_speed_options', array( $this, 'sanitize_speed_options' ) );

        // --- SECTION: CORE CLEANUP ---
        add_settings_section( 'af_speed_core', esc_html__( 'Core Cleanup', 'af-companion' ), null, 'af-speed' );
        
        $core_fields = array(
            'disable_emojis' => array(
                'label' => __( 'Disable Emojis', 'af-companion' ),
                'desc'  => __( 'Enable to prevent WordPress from automatically detecting and generating emojis in your pages.', 'af-companion' )
            ),
            'disable_embeds' => array(
                'label' => __( 'Disable oEmbeds', 'af-companion' ),
                'desc'  => __( 'Prevents others from embedding your content and stops external embeds from loading JS on your site.', 'af-companion' )
            ),
            'disable_xmlrpc' => array(
                'label' => __( 'Disable XML-RPC', 'af-companion' ),
                'desc'  => __( 'Improves security and speed by disabling a legacy API often targeted by brute-force attacks.', 'af-companion' )
            ),
            'remove_version' => array(
                'label' => __( 'Remove WP Version Meta', 'af-companion' ),
                'desc'  => __( 'Removes the version number from your header and static resources to improve security and caching.', 'af-companion' )
            ),
            'slow_heartbeat' => array(
                'label' => __( 'Optimize Heartbeat', 'af-companion' ),
                'desc'  => __( 'Slows down the WordPress Heartbeat API to reduce server CPU usage while editing.', 'af-companion' )
            ),
        );

        foreach ( $core_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_core', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: ADVANCED HEADER ---
        add_settings_section( 'af_speed_header', esc_html__( 'Advanced Header Cleanup', 'af-companion' ), null, 'af-speed' );
        
        $header_fields = array(
            'remove_rsd_wlw'    => array(
                'label' => __( 'Remove RSD & WLW Links', 'af-companion' ),
                'desc'  => __( 'Removes Really Simple Discovery and Windows Live Writer manifest links from your site header.', 'af-companion' )
            ),
            'disable_pingbacks' => array(
                'label' => __( 'Disable Self-Pingbacks', 'af-companion' ),
                'desc'  => __( 'Prevents WordPress from sending pingbacks to your own site when you link to your own posts.', 'af-companion' )
            ),
            'dns_prefetch'      => array(
                'label' => __( 'Enable DNS Prefetching', 'af-companion' ),
                'desc'  => __( 'Resolves domain names before a user clicks a link, which can reduce latency for external assets.', 'af-companion' )
            ),
        );

        foreach ( $header_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_header', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: FRONTEND & ASSETS ---
        add_settings_section( 'af_speed_frontend', esc_html__( 'Frontend Optimization', 'af-companion' ), null, 'af-speed' );
        
        $frontend_fields = array(
            'minify_html'       => array(
                'label' => __( 'Minify HTML Output', 'af-companion' ),
                'desc'  => __( 'Removes unnecessary characters from your HTML output saving data and improving site speed.', 'af-companion' )
            ),
            'remove_query'      => array(
                'label' => __( 'Remove Query Strings', 'af-companion' ),
                'desc'  => __( 'Removes version query strings (?ver=) from static resources to improve caching.', 'af-companion' )
            ),
            'jquery_migrate'    => array(
                'label' => __( 'Disable jQuery Migrate', 'af-companion' ),
                'desc'  => __( 'Removes the legacy jQuery Migrate script which is unnecessary for modern themes.', 'af-companion' )
            ),
            'async_js'          => array(
                'label' => __( 'Load JS Asynchronously', 'af-companion' ),
                'desc'  => __( 'Adds async parameter to header scripts so they do not block page rendering.', 'af-companion' )
            ),
            'optimize_gfonts'   => array(
                'label' => __( 'Optimize Google Fonts', 'af-companion' ),
                'desc'  => __( 'Adds font-display: swap to Google Fonts for faster text visibility during load.', 'af-companion' )
            ),
            'disable_dashicons' => array(
                'label' => __( 'Remove Dashicons', 'af-companion' ),
                'desc'  => __( 'Prevents Dashicons from loading for non-logged-in visitors to reduce CSS weight.', 'af-companion' )
            ),
        );

        foreach ( $frontend_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_frontend', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: IMAGES ---
        add_settings_section( 'af_speed_images', esc_html__( 'Image Optimization', 'af-companion' ), null, 'af-speed' );
        
        add_settings_field( 'lazy_load', esc_html__( 'Lazy Load Images', 'af-companion' ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_images', array( 
            'id' => 'lazy_load',
            'desc' => __( 'Enables native browser lazy loading for all images to improve initial page load time.', 'af-companion' )
        ) );

        // --- SECTION: BLOCK EDITOR ---
        add_settings_section( 'af_speed_gutenberg', esc_html__( 'Block Editor (Gutenberg)', 'af-companion' ), null, 'af-speed' );
        
        add_settings_field( 'disable_gutenberg', esc_html__( 'Disable Block Editor', 'af-companion' ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_gutenberg', array( 
            'id' => 'disable_gutenberg',
            'desc' => __( 'Reverts to the Classic Editor interface for all posts and pages.', 'af-companion' )
        ) );
        
        add_settings_field( 'remove_block_css', esc_html__( 'Remove Block Library CSS', 'af-companion' ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_gutenberg', array( 
            'id' => 'remove_block_css',
            'desc' => __( 'Removes the default Gutenberg CSS files from the frontend to reduce bloat.', 'af-companion' )
        ) );
    }

    /**
     * Professional Sanitization
     */
    public function sanitize_speed_options( $input ) {
        $new_input = array();
        if ( is_array( $input ) ) {
            foreach ( $input as $key => $val ) {
                $new_input[$key] = ( 1 === (int) $val ) ? 1 : 0;
            }
        }
        return $new_input;
    }

    public function add_resource_hints( $hints, $relation_type ) {
        $options = get_option( 'af_speed_options' );
        if ( empty($options) ) return $hints;

        if ( ! empty( $options['optimize_gfonts'] ) && 'preconnect' === $relation_type ) {
            $hints[] = 'https://fonts.gstatic.com';
        }
        if ( ! empty( $options['dns_prefetch'] ) && 'dns-prefetch' === $relation_type ) {
            $hints[] = 'fonts.googleapis.com';
            $hints[] = 'fonts.gstatic.com';
            $hints[] = 'www.google-analytics.com';
        }
        return $hints;
    }

    public function apply_core_optimizations() {
        $options = get_option( 'af_speed_options' );
        if ( ! $options ) return;

        if ( ! empty( $options['disable_emojis'] ) ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'admin_print_styles', 'print_emoji_styles' );
            add_filter( 'emoji_svg_url', '__return_false' );
        }

        if ( ! empty( $options['disable_embeds'] ) ) {
            remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
            remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        }

        if ( ! empty( $options['disable_xmlrpc'] ) ) {
            add_filter( 'xmlrpc_enabled', '__return_false' );
        }

        if ( ! empty( $options['slow_heartbeat'] ) ) {
            add_filter( 'heartbeat_settings', function( $settings ) {
                $settings['interval'] = 60;
                return $settings;
            });
        }

        if ( ! empty( $options['remove_rsd_wlw'] ) ) {
            remove_action( 'wp_head', 'rsd_link' );
            remove_action( 'wp_head', 'wlwmanifest_link' );
        }

        if ( ! empty( $options['disable_pingbacks'] ) ) {
            add_action( 'pre_ping', function( &$links ) {
                $home = get_option( 'home' );
                foreach ( $links as $l => $link ) {
                    if ( 0 === strpos( $link, $home ) ) {
                        unset( $links[$l] );
                    }
                }
            });
        }
    }

    public function optimize_assets() {
        if ( is_admin() ) return;
        $options = get_option( 'af_speed_options' );

        if ( ! empty( $options['remove_version'] ) ) {
            remove_action( 'wp_head', 'wp_generator' );
        }

        if ( ! empty( $options['jquery_migrate'] ) ) {
            $scripts = wp_scripts();
            if ( isset( $scripts->registered['jquery'] ) ) {
                $scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
            }
        }

        if ( ! is_user_logged_in() && ! empty( $options['disable_dashicons'] ) ) {
            wp_dequeue_style( 'dashicons' );
        }

        if ( ! empty( $options['lazy_load'] ) ) {
            add_filter( 'wp_lazy_loading_enabled', '__return_true' );
        }

        if ( ! empty( $options['remove_query'] ) ) {
            add_filter( 'script_loader_src', array( $this, 'strip_version' ), 15 );
            add_filter( 'style_loader_src', array( $this, 'strip_version' ), 15 );
        }
    }

    public function handle_gutenberg_logic() {
        $options = get_option( 'af_speed_options' );
        if ( ! $options ) return;

        if ( ! empty( $options['disable_gutenberg'] ) ) {
            add_filter( 'use_block_editor_for_post', '__return_false', 10 );
            add_filter( 'use_block_editor_for_post_type', '__return_false', 10 );
        }

        if ( ! empty( $options['remove_block_css'] ) ) {
            add_action( 'wp_enqueue_scripts', function() {
                wp_dequeue_style( 'wp-block-library' );
                wp_dequeue_style( 'wp-block-library-theme' );
                wp_dequeue_style( 'wc-block-style' );
                wp_dequeue_style( 'global-styles' );
                wp_dequeue_style( 'classic-theme-styles' );
            }, 100 );
        }
    }

    public function apply_async_js( $tag, $handle ) {
        $options = get_option( 'af_speed_options' );
        if ( empty( $options['async_js'] ) || is_admin() || is_user_logged_in() ) return $tag;
        $exclude = array( 'jquery-core', 'admin-bar', 'jquery' );
        return ( in_array( $handle, $exclude ) ) ? $tag : str_replace( ' src', ' async src', $tag );
    }

    public function optimize_google_fonts( $tag, $handle ) {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['optimize_gfonts'] ) && strpos( $tag, 'fonts.googleapis.com' ) !== false ) {
            $tag = str_replace( 'families=', 'display=swap&families=', $tag );
        }
        return $tag;
    }

    public function setup_html_minification() {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['minify_html'] ) && ! is_admin() && ! is_user_logged_in() ) {
            ob_start( array( $this, 'minify_output' ) );
        }
    }

    public function minify_output( $buffer ) {
        if ( ! $buffer || strpos( $buffer, '<?xml' ) !== false || strpos( $buffer, '<pre' ) !== false ) return $buffer;
        $search = array( '/\n+/' => ' ', '/\r+/' => ' ', '/\t+/' => ' ', '/\s{2,}/' => ' ', '/<!--.*?-->/s' => '', '/>\s+</' => '><' );
        return trim( preg_replace( array_keys( $search ), array_values( $search ), $buffer ) );
    }

    public function strip_version( $src ) {
        return ( strpos( $src, 'ver=' ) ) ? remove_query_arg( 'ver', $src ) : $src;
    }

    /**
     * UI RENDERING
     */
    public function render_toggle( $args ) {
        $options = get_option( 'af_speed_options' );
        $checked = isset( $options[$args['id']] ) ? checked( 1, $options[$args['id']], false ) : '';
        echo '<label class="af-switch"><input type="checkbox" name="af_speed_options[' . esc_attr( $args['id'] ) . ']" value="1" ' . $checked . '><span class="af-slider"></span></label>';
        if ( isset( $args['desc'] ) ) {
            echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
        }
    }

    public function render_page() {
        ?>
        <div class="wrap af-speed-container">
            <div class="af-speed-header">
                <div class="af-speed-branding">
                    <h1><?php esc_html_e( 'Speed Booster', 'af-companion' ); ?></h1>   
                    <span class="af-badge"><?php esc_html_e( 'PERFORMANCE & OPTIMIZATION', 'af-companion' ); ?></span>                 
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'High-performance engine optimization for WordPress.', 'af-companion' ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'af_speed_group' );
                
                global $wp_settings_sections, $wp_settings_fields;
                $page = 'af-speed';

                if ( isset( $wp_settings_sections[$page] ) ) {
                    foreach ( (array) $wp_settings_sections[$page] as $section ) {
                        echo '<div class="af-speed-card">';
                        if ( $section['title'] ) {
                            echo '<h2 class="af-section-title">' . esc_html( $section['title'] ) . '</h2>';
                        }
                        
                        echo '<div class="af-field-grid">';
                        if ( isset( $wp_settings_fields[$page][$section['id']] ) ) {
                            foreach ( (array) $wp_settings_fields[$page][$section['id']] as $field ) {
                                echo '<div class="af-field-row">';
                                    echo '<div class="af-field-info">';
                                        echo '<label class="af-field-label">' . $field['title'] . '</label>';
                                        if(isset($field['callback'])){
                                             call_user_func($field['callback'], $field['args']);
                                        }
                                    echo '</div>';
                                echo '</div>';
                            }
                        }
                        echo '</div>'; 
                        echo '</div>'; 
                    }
                }
                
                submit_button( esc_html__( 'Save & Apply Optimizations', 'af-companion' ), 'primary large af-submit-btn' );
                ?>
            </form>
        </div>

        <style>
            .af-speed-container { max-width: 1000px; margin: 20px auto 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
            .af-speed-header { margin-bottom: 30px; border-bottom: 1px solid #dcdcde; padding-bottom: 20px; }
            .af-speed-branding { display: flex; align-items: center; gap: 15px; }
            .af-speed-branding h1 { font-size: 28px; font-weight: 800; color: #1d2327; margin: 0; }
            .af-badge { background: #2271b1; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
            .af-subtitle { color: #646970; font-size: 15px; margin-top: 8px; }
            .af-speed-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; transition: transform 0.2s; }
            .af-speed-card:hover { transform: translateY(-2px); }
            .af-section-title { font-size: 18px; font-weight: 700; color: #1d2327; margin: 0 0 25px 0; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1; display: flex; align-items: center; }
            .af-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            @media (max-width: 782px) { .af-field-grid { grid-template-columns: 1fr; } }
            .af-field-row { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #edf2f7; display: flex; flex-direction: column; justify-content: space-between; }
            .af-field-info { display: flex; flex-direction: column; height: 100%; }
            .af-field-label { font-weight: 700; font-size: 14px; color: #1d2327; margin-bottom: 8px; display: block; }
            .af-field-row .description { margin: 10px 0 0 0; color: #64748b; font-size: 13px; line-height: 1.5; font-style: normal; order: 2; }
            .af-switch { position: relative; display: inline-block; width: 48px; height: 26px; order: 1; }
            .af-switch input { opacity: 0; width: 0; height: 0; }
            .af-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
            .af-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            input:checked + .af-slider { background-color: #10b981; }
            input:checked + .af-slider:before { transform: translateX(22px); }
            .af-submit-btn { padding: 12px 30px !important; font-size: 14px !important; font-weight: 600 !important; border-radius: 6px !important; background: #2271b1 !important; border: none !important; box-shadow: 0 4px 6px rgba(34, 113, 177, 0.2) !important; transition: background 0.2s !important; }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated{ display: none!important; }
        </style>
        <?php
    }
}