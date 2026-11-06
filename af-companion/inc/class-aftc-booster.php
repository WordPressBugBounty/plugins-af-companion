<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Companion_Booster {
    protected static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Require all module definition files so menus, hooks, and AJAX listeners are registered
        require_once AFTC_PATH . 'inc/class-aftc-admin-header.php';
        require_once AFTC_PATH . 'inc/class-aftc-speed-booster.php';
        require_once AFTC_PATH . 'inc/class-aftc-growth-tools.php';
        require_once AFTC_PATH . 'inc/class-aftc-system-status.php';        
        require_once AFTC_PATH . 'inc/class-aftc-content-monitor.php';        
        require_once AFTC_PATH . 'inc/class-aftc-link-monitor.php';        
        require_once AFTC_PATH . 'inc/class-aftc-alt-manager.php';        
        require_once AFTC_PATH . 'inc/class-aftc-db-cleaner.php';        
        require_once AFTC_PATH . 'inc/class-aftc-recommended-themes.php';        
        require_once AFTC_PATH . 'inc/ai/class-aftc-ai-helper.php';        

        // Initialize them safely
        new AF_Admin_Header();
        new AF_Speed_Booster();
        new AF_Growth_Tools();
        new AF_System_Status();
        new AF_Content_Monitor();
        new AF_Alt_Text_Manager();
        new AF_Link_Monitor();
        new AF_Database_Cleaner();
        new AF_Recommended_Themes();
        // new AF_AI_Settings_Dashboard();
    }
}

AF_Companion_Booster::get_instance();