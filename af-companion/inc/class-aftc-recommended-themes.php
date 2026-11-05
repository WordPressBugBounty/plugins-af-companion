<?php
/**
 * AF Recommended Themes - Showcase & 1-Click Installer Directory
 * 
 * Version: 1.0.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Recommended_Themes {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ), 24 );
    }

    public function add_menu() {
        // Dynamic badge count (e.g., total recommended themes available)
        $badge_themes = 'Free';

        add_submenu_page(
            'af-companion',
            esc_html__( 'Recommended Themes', 'af-companion' ),
            sprintf(
                __('Themes %s', 'af-companion'),
                '<span class="update-plugins count-' . esc_attr( $badge_themes ) . '"><span class="plugin-count">' . esc_html( $badge_themes ) . '</span></span>'
            ),
            'manage_options',
            'af-recommended-themes',
            array( $this, 'render_recommended_themes' ),
            5
        );
    }

    /**
     * Render the Recommended Themes showcase dashboard.
     */
    public function render_recommended_themes() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'af-companion' ) );
        }

        $admin_theme_install_url = admin_url( 'theme-install.php?search=' );
        
        // Assumes plugin root URL constant is defined or resolved from file path.
        // Change AFTC_PLUGIN_URL to your plugin's actual URL constant if defined elsewhere.
        $plugin_url = defined( 'AFTC_PLUGIN_URL' ) ? AFTC_PLUGIN_URL : plugin_dir_url( dirname( __FILE__ ) );
        $static_img = trailingslashit( $plugin_url ) . 'assets/img/themes/';

        $themes = array(
            array(
                'slug'         => 'covernews',
                'filename'     => 'CoverNews',
                'title'        => __('CoverNews', 'af-companion'),
                'description'  => __('A content-rich news and magazine theme. Fully integrated with starter sites, Elementor kits, and block patterns for seamless demo import.', 'af-companion'),
                'badge'        => __('News Portals & Editorial', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/covernews/',
            ),
            array(
                'slug'         => 'chromenews',
                'filename'     => 'ChromeNews',
                'title'        => __('ChromeNews', 'af-companion'),
                'description'  => __('Designed for modern online newspapers with widgetized homepages and customizer controls. Easily import pre-built site demos.', 'af-companion'),
                'badge'        => __('Newspaper & Digital Media', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/chromenews/',
            ),
            array(
                'slug'         => 'morenews',
                'filename'     => 'MoreNews',
                'title'        => __('MoreNews', 'af-companion'),
                'description'  => __('Versatile magazine theme featuring light/dark modes and WooCommerce support. Combine with site templates for instant launches.', 'af-companion'),
                'badge'        => __('Modern Magazines', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/morenews/',
            ),
            array(
                'slug'         => 'darknews',
                'filename'     => 'DarkNews',
                'title'        => __('DarkNews', 'af-companion'),
                'description'  => __('A high-impact dark editorial theme. Import dark-mode starter kits and post grids powered by Elementor and Gutenberg patterns.', 'af-companion'),
                'badge'        => __('Dark-Style News', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/darknews/',
            ),
            array(
                'slug'         => 'newsphere',
                'filename'     => 'Newsphere',
                'title'        => __('Newsphere', 'af-companion'),
                'description'  => __('Fast, performance-tuned news theme. Features 1-click starter site imports, full customizer styling, and WooCommerce compatibility.', 'af-companion'),
                'badge'        => __('Fast News & Demos', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/newsphere/',
            ),
            array(
                'slug'         => 'elegant-magazine',
                'filename'     => 'Elegant-Magazine',
                'title'        => __('Elegant Magazine', 'af-companion'),
                'description'  => __('Typography-focused magazine theme for editorial bloggers. Import complete template kits and widgetized section layouts.', 'af-companion'),
                'badge'        => __('Editorial Blogs', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/elegant-magazine/',
            ),
            array(
                'slug'         => 'enternews',
                'filename'     => 'EnterNews',
                'title'        => __('EnterNews', 'af-companion'),
                'description'  => __('Clean newspaper theme for digital publishers. Build engaging homepages quickly by importing pre-designed starter demos.', 'af-companion'),
                'badge'        => __('Digital Newspaper', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/enternews/',
            ),
            array(
                'slug'         => 'newsever',
                'filename'     => 'Newsever',
                'title'        => __('Newsever', 'af-companion'),
                'description'  => __('Global news theme with full RTL support and custom color controls. Ready for 1-click import using demo migration tools.', 'af-companion'),
                'badge'        => __('Global News & RTL', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/newsever/',
            ),
            array(
                'slug'         => 'newsium',
                'filename'     => 'Newsium',
                'title'        => __('Newsium', 'af-companion'),
                'description'  => __('Content-dense publishing theme for news portals and review sites. Extends effortlessly with BlockSpare blocks and Elementor kits.', 'af-companion'),
                'badge'        => __('News & Review Portals', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/newsium/',
            ),
            array(
                'slug'         => 'newspare',
                'filename'     => 'NewSpare',
                'title'        => __('NewSpare', 'af-companion'),
                'description'  => __('Native Full Site Editing (FSE) block theme for Gutenberg. Build headers, footers, and archive layouts using news and blog patterns.', 'af-companion'),
                'badge'        => __('Gutenberg & FSE', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/newspare/',
            ),
            array(
                'slug'         => 'reviewnews',
                'filename'     => 'ReviewNews',
                'title'        => __('ReviewNews', 'af-companion'),
                'description'  => __('Engineered for product reviews and affiliate news. Import complete review website demos instantly using site importers.', 'af-companion'),
                'badge'        => __('Affiliate & Reviews', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/reviewnews/',
            ),
            array(
                'slug'         => 'broadnews',
                'filename'     => 'BroadNews',
                'title'        => __('BroadNews', 'af-companion'),
                'description'  => __('Wide-container editorial theme built for high-density publishing. Compatible with custom widgets, layouts, and Elementor kits.', 'af-companion'),
                'badge'        => __('High-Density Editorial', 'af-companion'),
                'download_url' => 'https://afthemes.com/products/broadnews/',
            ),
        );
        ?>
        <?php AF_Admin_Header::render( 'af-recommended-themes' ); ?>
        <div class="wrap af-companion-wrap">
            <div class="themes-panel news-panel">
                <div class="panel-header" style="margin-bottom: 25px; border-bottom: 1px solid #dcdcde; padding-bottom: 20px;">
                    <h2 style="font-size: 26px; font-weight: 800; color: #1d2327; margin: 0 0 8px 0;"><?php esc_html_e( 'Recommended Free WordPress Themes for News, Magazines & Blogs', 'af-companion' ); ?></h2>
                    <p class="panel-subtitle" style="color: #646970; font-size: 15px; margin: 0;">
                        <?php esc_html_e( 'Power your news, magazine, and blog sites with free WordPress themes engineered by AF Themes.', 'af-companion' ); ?>
                    </p>
                </div>

                <div class="themes-item bs-themes-grid">
                    <?php foreach ( $themes as $theme ) :
                        $install_url = esc_url( $admin_theme_install_url . $theme['slug'] );
                        $preview_url = esc_url( $static_img . $theme['filename'] . '.jpg' );
                    ?>
                        <div class="bs-card theme-card">
                            <div class="bs-content">
                                <div class="bs-preview">
                                    <div class="bs-image-placeholder">
                                        <img src="<?php echo $preview_url; ?>" alt="<?php echo esc_attr( $theme['title'] ); ?>" loading="lazy" />
                                    </div>
                                </div>

                                <div class="bs-info">
                                    <?php if ( ! empty( $theme['badge'] ) ) : ?>
                                        <span class="bs-tag"><?php echo esc_html( $theme['badge'] ); ?></span>
                                    <?php endif; ?>
                                    <h3 class="bs-title"><?php echo esc_html( $theme['title'] ); ?></h3>
                                    <p class="bs-description"><?php echo esc_html( $theme['description'] ); ?></p>

                                    <div class="bs-card-actions">
                                        <a href="<?php echo $install_url; ?>" class="button button-primary bs-btn-install">
                                            <?php esc_html_e( 'Install Now', 'af-companion' ); ?>
                                        </a>
                                        <a href="<?php echo esc_url( $theme['download_url'] ); ?>" class="button button-secondary bs-btn-download" target="_blank" rel="noopener noreferrer">
                                            <?php esc_html_e( 'View Details', 'af-companion' ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <style>
            .af-companion-wrap { margin: 20px 20px 0 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
            .bs-themes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; }
            .bs-card.theme-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
            .bs-card.theme-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
            .bs-content { display: flex; flex-direction: column; height: 100%; }
            .bs-preview { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; overflow: hidden; height: 210px; }
            .bs-image-placeholder img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
            .bs-card.theme-card:hover .bs-image-placeholder img { transform: scale(1.03); }
            .bs-info { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
            .bs-tag { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; width: fit-content; }
            .bs-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; }
            .bs-description { color: #64748b; font-size: 13px; line-height: 1.5; margin: 0 0 20px 0; flex-grow: 1; }
            .bs-card-actions { display: flex; gap: 10px; margin-top: auto; }
            .bs-card-actions .button { border-radius: 6px !important; font-weight: 600 !important; padding: 6px 14px !important; height: auto !important; line-height: 20px !important; }
            .bs-btn-install { background: #2271b1 !important; border-color: #2271b1 !important; }
            .notice, .aftc-notice, div.fs-notice.promotion, div.fs-notice.success, div.fs-notice.updated, .update-nag.gt-admin-notice { display: none !important; }
        </style>
        <?php
    }
}