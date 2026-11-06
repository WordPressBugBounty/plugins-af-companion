<?php
/**
 * AF Companion - Professional Application Dashboard Header Component
 * 
 * Version: 2.1.0
 * Author: AF Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AF_Admin_Header {

    /**
     * Render the unified application header and navigation across all AF Companion pages.
     * 
     * @param string $active_tab The slug of the currently active tab/page.
     */
    public static function render( $active_tab = 'af-speed' ) {
        // Resolve plugin root URL constant or fallback
        $plugin_url = defined( 'AFTC_PLUGIN_URL' ) ? AFTC_PLUGIN_URL : plugin_dir_url( dirname( __FILE__ ) );
        $logo_url   = trailingslashit( $plugin_url ) . 'assets/img/afthemes-logo.png';

        // Define suite modules
        $tabs = array(
            'af-companion'          => array( 'label' => __( 'Website Templates', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-companion' ) ),
            'af-speed'              => array( 'label' => __( 'Speed Booster', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-speed' ) ),
            'af-link-monitor'       => array( 'label' => __( 'Link Monitor', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-link-monitor' ) ),
            'af-content-monitor'    => array( 'label' => __( 'Content Monitor', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-content-monitor' ) ),
            'af-alt-manager'        => array( 'label' => __( 'Alt Text Manager', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-alt-manager' ) ),
            'af-db-cleaner'         => array( 'label' => __( 'Database Maintenance', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-db-cleaner' ) ),
            'af-growth'             => array( 'label' => __( 'Growth Tools', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-growth' ) ),
            'af-status'             => array( 'label' => __( 'System Status', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-status' ) ),
            'af-recommended-themes' => array( 'label' => __( 'Free Themes', 'af-companion' ), 'url' => admin_url( 'admin.php?page=af-recommended-themes' ) ),
        );
        ?>
        <div class="af-app-header-wrapper">
            <!-- Top Branding & CTA Actions Bar -->
            <div class="af-app-top-bar">
                <div class="af-app-brand">
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'AF Themes Logo', 'af-companion' ); ?>" class="af-app-logo" />
                    <div class="af-app-titles">
                        <h1 class="af-app-name"><?php esc_html_e( 'AF Companion Suite', 'af-companion' ); ?></h1>
                        <span class="af-app-version"><?php echo esc_html( AFTC_VERSION ); ?> &mdash; <?php esc_html_e( 'Enterprise Publishing, Performance, Maintenance & Site Health', 'af-companion' ); ?></span>
                    </div>
                </div>

                <div class="af-app-actions">
                    <a href="https://afthemes.com/supports/" target="_blank" rel="noopener noreferrer" class="button button-secondary af-app-btn-support">
                        <?php esc_html_e( 'Support', 'af-companion' ); ?>
                    </a>
                    <a href="https://afthemes.com/all-themes-plan/" target="_blank" rel="noopener noreferrer" class="button button-primary af-app-btn-pro">
                        <?php esc_html_e( 'Pro Themes & Plugins', 'af-companion' ); ?>
                    </a>
                </div>
            </div>

            <!-- Horizontally Centered Navigation Section Bar -->
            <nav class="af-app-nav-bar">
                <?php foreach ( $tabs as $slug => $tab ) : 
                    $is_active = ( $active_tab === $slug ) ? 'active-tab' : '';
                ?>
                    <a href="<?php echo esc_url( $tab['url'] ); ?>" class="af-app-nav-item <?php echo esc_attr( $is_active ); ?>">
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <style>
            .af-app-header-wrapper { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px; 
                padding: 22px 25px; 
                margin: 20px 20px 25px 0; 
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); 
                /* max-width: 1000px;  */
                box-sizing: border-box; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            
            /* Top Bar Structure */
            .af-app-top-bar { 
                display: flex; 
                align-items: center; 
                justify-content: space-between; 
                flex-wrap: wrap; 
                gap: 15px; 
                padding-bottom: 20px; 
                border-bottom: 1px solid #f1f5f9; 
            }
            .af-app-brand { 
                display: flex; 
                align-items: center; 
                gap: 14px; 
            }
            .af-app-logo { 
                height: 36px; 
                width: auto; 
                object-fit: contain; 
            }
            .af-app-titles { 
                display: flex; 
                flex-direction: column; 
            }
            .af-app-name { 
                font-size: 17px; 
                font-weight: 800; 
                color: #0f172a; 
                margin: 0; 
                line-height: 1.2; 
                letter-spacing: -0.2px;
            }
            .af-app-version { 
                font-size: 11px; 
                color: #64748b; 
                font-weight: 600; 
                margin-top: 3px; 
            }

            /* Professional CTA Buttons */
            .af-app-actions { 
                display: flex; 
                align-items: center; 
                gap: 10px; 
            }
            .af-app-btn-support { 
                border-radius: 6px !important; 
                font-weight: 600 !important; 
                font-size: 13px !important; 
                padding: 6px 16px !important; 
                height: auto !important; 
                line-height: 20px !important; 
                color: #475569 !important;
                border-color: #cbd5e1 !important;
                background: #f8fafc !important;
                box-shadow: none !important;
            }
            .af-app-btn-support:hover {
                background: #f1f5f9 !important;
                color: #0f172a !important;
            }
            .af-app-btn-pro { 
                background: #10b981 !important; 
                border-color: #10b981 !important; 
                border-radius: 6px !important; 
                font-weight: 600 !important; 
                font-size: 13px !important; 
                padding: 6px 18px !important; 
                height: auto !important; 
                line-height: 20px !important; 
                color: #fff !important; 
                box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2) !important;
            }
            .af-app-btn-pro:hover { 
                background: #059669 !important; 
                border-color: #059669 !important;
            }

            /* Horizontally Centered & Balanced Navigation Section Bar */
            .af-app-nav-bar { 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 6px; 
                flex-wrap: wrap; 
                padding-top: 18px; 
            }
            .af-app-nav-item { 
                text-decoration: none; 
                color: #475569; 
                font-size: 13px; 
                font-weight: 600; 
                padding: 7px 13px; 
                border-radius: 6px; 
                background: #f8fafc; 
                border: 1px solid #e2e8f0; 
                transition: all 0.2s ease; 
                white-space: nowrap; 
            }
            .af-app-nav-item:hover { 
                background: #f1f5f9; 
                color: #0f172a; 
                border-color: #cbd5e1; 
            }
            .af-app-nav-item.active-tab { 
                background: #10b981; 
                color: #fff; 
                border-color: #10b981; 
                box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25); 
            }

            /* Responsive Adjustments for Mobile & Tablets */
            @media (max-width: 850px) {
                .af-app-top-bar { 
                    flex-direction: column; 
                    align-items: flex-start; 
                }
                .af-app-actions { 
                    width: 100%; 
                    justify-content: flex-start; 
                }
                .af-app-nav-bar { 
                    justify-content: flex-start; 
                    gap: 5px; 
                }
                .af-app-nav-item { 
                    font-size: 12px; 
                    padding: 6px 10px; 
                }
            }
        </style>
        <?php
    }
}