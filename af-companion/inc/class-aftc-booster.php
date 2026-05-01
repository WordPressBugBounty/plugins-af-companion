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
        // Load the modules
        require_once AFTC_PATH . 'inc/class-aftc-speed-booster.php';
        require_once AFTC_PATH . 'inc/class-aftc-growth-tools.php';
        require_once AFTC_PATH . 'inc/class-aftc-system-status.php';        

        // Initialize them
        new AF_Speed_Booster();
        new AF_Growth_Tools();
        new AF_System_Status();
    }
}
AF_Companion_Booster::get_instance();