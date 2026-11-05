<?php
/**
 * AF Speed Booster - Professional Performance & Control Center Suite
 * 
 * Version: 3.0.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Speed_Booster {

    public function __construct() {
        // Administration
        add_action( 'admin_menu', array( $this, 'add_menu' ), 21 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Frontend Execution & Optimizations
        add_action( 'init', array( $this, 'apply_core_optimizations' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'optimize_assets' ), 999 );
        
        // Advanced Filters & Handlers
        add_filter( 'style_loader_tag', array( $this, 'optimize_google_fonts' ), 10, 2 );
        add_filter( 'script_loader_tag', array( $this, 'apply_async_js' ), 10, 2 );
        add_action( 'template_redirect', array( $this, 'setup_html_minification' ) );
        add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );
        add_action( 'init', array( $this, 'handle_gutenberg_logic' ) );

        // Advanced Dispatcher Controls
        add_filter( 'wp_revisions_to_keep', array( $this, 'throttle_revisions' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'throttle_autosave' ) );
        add_filter( 'the_content', array( $this, 'lazy_load_iframes' ), 20 );
        add_action( 'init', array( $this, 'manage_heartbeat' ), 1 );
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
        register_setting( 'af_speed_group', 'af_speed_options', array( $this, 'sanitize_speed_options' ) );

        // --- SECTION: CORE CLEANUP ---
        add_settings_section( 'af_speed_core', esc_html__( 'Core Cleanup', 'af-companion' ), null, 'af-speed' );
        
        $core_fields = array(
            'disable_emojis' => array(
                'label' => __( 'Disable Emojis', 'af-companion' ),
                'desc'  => __( 'Prevent WordPress from automatically detecting and generating emoji scripts.', 'af-companion' )
            ),
            'disable_embeds' => array(
                'label' => __( 'Disable oEmbeds', 'af-companion' ),
                'desc'  => __( 'Stops external embeds from loading discovery JS on your frontend.', 'af-companion' )
            ),
            'disable_xmlrpc' => array(
                'label' => __( 'Disable XML-RPC', 'af-companion' ),
                'desc'  => __( 'Improves security by blocking legacy XML-RPC API brute-force targets.', 'af-companion' )
            ),
            'remove_version' => array(
                'label' => __( 'Remove WP Version Meta', 'af-companion' ),
                'desc'  => __( 'Removes version tags from headers and resources for enhanced security.', 'af-companion' )
            ),
        );

        foreach ( $core_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_core', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: OVERHEAD & THROTTLING ---
        add_settings_section( 'af_speed_throttling', esc_html__( 'Database & Overhead Dispatcher', 'af-companion' ), null, 'af-speed' );
        
        $throttling_fields = array(
            'limit_revisions' => array(
                'label' => __( 'Limit Post Revisions', 'af-companion' ),
                'desc'  => __( 'Caps historical post revisions to a maximum of 5 entries to save database space.', 'af-companion' )
            ),
            'slow_autosave' => array(
                'label' => __( 'Slow Autosave Interval', 'af-companion' ),
                'desc'  => __( 'Increases editor autosave frequency from 60s to 120s to cut redundant database writes.', 'af-companion' )
            ),
            'control_heartbeat' => array(
                'label' => __( 'Optimize Heartbeat API', 'af-companion' ),
                'desc'  => __( 'Slows down server-polling Heartbeat frequency to 60 seconds across admin.', 'af-companion' )
            ),
        );

        foreach ( $throttling_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_throttling', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: FRONTEND & ASSETS ---
        add_settings_section( 'af_speed_frontend', esc_html__( 'Frontend Asset Optimization', 'af-companion' ), null, 'af-speed' );
        
        $frontend_fields = array(
            'minify_html'       => array(
                'label' => __( 'Minify HTML Output', 'af-companion' ),
                'desc'  => __( 'Strips unnecessary whitespace and comments from the raw HTML output.', 'af-companion' )
            ),
            'remove_query'      => array(
                'label' => __( 'Remove Query Strings', 'af-companion' ),
                'desc'  => __( 'Removes version query parameters (?ver=) from static assets for proxy caching.', 'af-companion' )
            ),
            'jquery_migrate'    => array(
                'label' => __( 'Disable jQuery Migrate', 'af-companion' ),
                'desc'  => __( 'Strips legacy jQuery Migrate script load for faster execution.', 'af-companion' )
            ),
            'async_js'          => array(
                'label' => __( 'Load JS Asynchronously', 'af-companion' ),
                'desc'  => __( 'Injects async attributes into header scripts to prevent render-blocking.', 'af-companion' )
            ),
            'optimize_gfonts'   => array(
                'label' => __( 'Optimize Google Fonts', 'af-companion' ),
                'desc'  => __( 'Appends font-display: swap to Google Font requests for faster text rendering.', 'af-companion' )
            ),
            'disable_dashicons' => array(
                'label' => __( 'Remove Dashicons', 'af-companion' ),
                'desc'  => __( 'Deregisters Dashicons stylesheet for logged-out visitors.', 'af-companion' )
            ),
            'lazy_load_iframes' => array(
                'label' => __( 'Lazy Load Video Embeds', 'af-companion' ),
                'desc'  => __( 'Injects loading="lazy" into YouTube/Vimeo iframe embeds automatically.', 'af-companion' )
            ),
        );

        foreach ( $frontend_fields as $id => $data ) {
            add_settings_field( $id, esc_html( $data['label'] ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_frontend', array( 'id' => $id, 'desc' => $data['desc'] ) );
        }

        // --- SECTION: BLOCK EDITOR ---
        add_settings_section( 'af_speed_gutenberg', esc_html__( 'Block Editor (Gutenberg) Control', 'af-companion' ), null, 'af-speed' );
        
        add_settings_field( 'disable_gutenberg', esc_html__( 'Disable Block Editor', 'af-companion' ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_gutenberg', array( 
            'id'   => 'disable_gutenberg',
            'desc' => __( 'Reverts standard post types to the classic WordPress editor interface.', 'af-companion' )
        ) );
        
        add_settings_field( 'remove_block_css', esc_html__( 'Selective Block CSS Removal', 'af-companion' ), array( $this, 'render_toggle' ), 'af-speed', 'af_speed_gutenberg', array( 
            'id'   => 'remove_block_css',
            'desc' => __( 'Dequeues global Gutenberg block library styles on non-block legacy views.', 'af-companion' )
        ) );
    }

    public function sanitize_speed_options( $input ) {
        $new_input = array();
        if ( is_array( $input ) ) {
            foreach ( $input as $key => $val ) {
                $new_input[ $key ] = ( 1 === (int) $val ) ? 1 : 0;
            }
        }
        return $new_input;
    }

    /**
     * Advanced Dispatcher Logic Implementations
     */
    public function throttle_revisions( $num, $post ) {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['limit_revisions'] ) ) {
            return 5; // Hard cap at 5 revisions
        }
        return $num;
    }

    public function throttle_autosave() {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['slow_autosave'] ) && defined( 'AUTOSAVE_INTERVAL' ) === false ) {
            define( 'AUTOSAVE_INTERVAL', 120 ); // Increase to 120 seconds
        }
    }

    public function manage_heartbeat() {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['control_heartbeat'] ) ) {
            add_filter( 'heartbeat_settings', function( $settings ) {
                $settings['interval'] = 60; // Slow down to 60s
                return $settings;
            } );
        }
    }

    public function lazy_load_iframes( $content ) {
        $options = get_option( 'af_speed_options' );
        if ( ! empty( $options['lazy_load_iframes'] ) && ! is_admin() ) {
            $content = preg_replace( '/<iframe(?!.*loading=)/i', '<iframe loading="lazy"', $content );
        }
        return $content;
    }

    public function add_resource_hints( $hints, $relation_type ) {
        $options = get_option( 'af_speed_options' );
        if ( empty( $options ) ) {
            return $hints;
        }

        if ( ! empty( $options['optimize_gfonts'] ) && 'preconnect' === $relation_type ) {
            $hints[] = 'https://fonts.gstatic.com';
        }
        return $hints;
    }

    public function apply_core_optimizations() {
        $options = get_option( 'af_speed_options' );
        if ( ! $options ) {
            return;
        }

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
    }

    public function optimize_assets() {
        if ( is_admin() ) {
            return;
        }
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

        if ( ! empty( $options['remove_query'] ) ) {
            add_filter( 'script_loader_src', array( $this, 'strip_version' ), 15 );
            add_filter( 'style_loader_src', array( $this, 'strip_version' ), 15 );
        }
    }

    public function handle_gutenberg_logic() {
        $options = get_option( 'af_speed_options' );
        if ( ! $options ) {
            return;
        }

        if ( ! empty( $options['disable_gutenberg'] ) ) {
            add_filter( 'use_block_editor_for_post', '__return_false', 10 );
            add_filter( 'use_block_editor_for_post_type', '__return_false', 10 );
        }

        if ( ! empty( $options['remove_block_css'] ) ) {
            add_action( 'wp_enqueue_scripts', function() {
                wp_dequeue_style( 'wp-block-library' );
                wp_dequeue_style( 'wp-block-library-theme' );
                wp_dequeue_style( 'global-styles' );
            }, 100 );
        }
    }

    public function apply_async_js( $tag, $handle ) {
        $options = get_option( 'af_speed_options' );
        if ( empty( $options['async_js'] ) || is_admin() || is_user_logged_in() ) {
            return $tag;
        }
        $exclude = array( 'jquery-core', 'admin-bar', 'jquery' );
        return ( in_array( $handle, $exclude, true ) ) ? $tag : str_replace( ' src', ' async src', $tag );
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
        if ( ! $buffer || strpos( $buffer, '<?xml' ) !== false || strpos( $buffer, '<pre' ) !== false ) {
            return $buffer;
        }
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
        $checked = isset( $options[ $args['id'] ] ) ? checked( 1, $options[ $args['id'] ], false ) : '';
        echo '<label class="af-switch"><input type="checkbox" name="af_speed_options[' . esc_attr( $args['id'] ) . ']" value="1" ' . $checked . '><span class="af-slider"></span></label>';
        if ( isset( $args['desc'] ) ) {
            echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
        }
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }
        ?>
         <?php AF_Admin_Header::render( 'af-speed' ); ?>
        <div class="wrap af-speed-container">
            <div class="af-speed-header">
                <div class="af-speed-branding">
                    <h1><?php esc_html_e( 'Speed Booster & Dispatcher', 'af-companion' ); ?></h1>   
                    <span class="af-badge"><?php esc_html_e( 'Performance Control Center', 'af-companion' ); ?></span>                 
                </div>
                <p class="af-subtitle"><?php esc_html_e( 'Granular asset management, database write throttling, and frontend rendering optimization.', 'af-companion' ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'af_speed_group' );
                
                global $wp_settings_sections, $wp_settings_fields;
                $page = 'af-speed';

                if ( isset( $wp_settings_sections[ $page ] ) ) {
                    foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
                        echo '<div class="af-speed-card">';
                        if ( $section['title'] ) {
                            echo '<h2 class="af-section-title">' . esc_html( $section['title'] ) . '</h2>';
                        }
                        
                        echo '<div class="af-field-grid">';
                        if ( isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
                            foreach ( (array) $wp_settings_fields[ $page ][ $section['id'] ] as $field ) {
                                echo '<div class="af-field-row">';
                                    echo '<div class="af-field-info">';
                                        echo '<label class="af-field-label">' . esc_html( $field['title'] ) . '</label>';
                                        if ( isset( $field['callback'] ) ) {
                                            call_user_func( $field['callback'], $field['args'] );
                                        }
                                    echo '</div>';
                                echo '</div>';
                            }
                        }
                        echo '</div>'; 
                        echo '</div>'; 
                    }
                }
                
                submit_button( esc_html__( 'Save & Apply Performance Controls', 'af-companion' ), 'primary large af-submit-btn' );
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
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated, .update-nag.gt-admin-notice { display: none !important; }
        </style>
        <?php
    }
}