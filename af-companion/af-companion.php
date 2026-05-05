<?php
/*
    Plugin Name: AF Companion
    Plugin URI: https://wordpress.org/plugins/af-companion/
    Description: The all-in-one performance and growth suite for WordPress. Instantly deploy expert-designed starter sites, optimize site speed with the Speed Booster engine, and scale your audience with integrated growth and diagnostic tools.
    Version: 2.1.0
    Author: AF themes
    Author URI: https://www.afthemes.com
    License: GPL3
    License URI: https://www.gnu.org/licenses/gpl.html
    Text Domain: af-companion
    */

// Block direct access to the main plugin file.
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Display admin error message if PHP version is older than 5.3.2.
 * Otherwise execute the main plugin class.
 */
if (version_compare(phpversion(), '5.3.2', '<')) {

	/**
	 * Display an admin error notice when PHP is older the version 5.3.2.
	 * Hook it to the 'admin_notices' action.
	 */
	function AFTC_old_php_admin_error_notice()
	{
		$message = sprintf(esc_html__('The %2$sAF Companion%3$s plugin requires %2$sPHP 5.3.2+%3$s to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 5.3.2.%4$s Your current version of PHP: %2$s%1$s%3$s', 'af-companion'), phpversion(), '<strong>', '</strong>', '<br>');

		printf('<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post($message));
	}
	add_action('admin_notices', 'AFTC_old_php_admin_error_notice');
} else {

	// Current version of the plugin.
	define('AFTC_VERSION', '1.2.12');

	// Path/URL to root of this plugin, with trailing slash.
	define('AFTC_PATH', plugin_dir_path(__FILE__));
	define('AFTC_URL', plugin_dir_url(__FILE__));

	// Require main plugin file.
	require AFTC_PATH . 'inc/class-aftc-main.php';

/**
   * Freemius.
   */
  require_once(AFTC_PATH . '/freemius.php');
	// Instantiate the main plugin class *Singleton*.
	$AF_Companion = AF_Companion::getInstance();
}

/**
 * Add Quick-Access Solution Links to the Plugin Dashboard
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'af_companion_action_links' );

function af_companion_action_links( $links ) {
    // 1. Link to the Build/Import section
    $build_link = '<a href="' . admin_url( 'admin.php?page=af-companion' ) . '">' . esc_html__( 'Demo Import', 'af-companion' ) . '</a>';
    
    // 2. Link to the Speed Booster tab (assumes you handle these via tab parameters)
    $speed_link = '<a href="' . admin_url( 'admin.php?page=af-speed' ) . '">' . esc_html__( 'Speed Booster', 'af-companion' ) . '</a>';
    
    // 3. Link to Growth Tools
    $growth_link = '<a href="' . admin_url( 'admin.php?page=af-growth' ) . '">' . esc_html__( 'Growth Tools', 'af-companion' ) . '</a>';
    
    // 4. Link to System Status (the class we built earlier)
    $status_link = '<a href="' . admin_url( 'admin.php?page=af-status' ) . '" style="font-weight: bold; color: #6366f1;">' . esc_html__( 'System Status', 'af-status' ) . '</a>';

    // Add them to the start of the links array
    $new_links = array(
        'build'  => $build_link,
        'speed'  => $speed_link,
        'growth' => $growth_link,
        'status' => $status_link,
    );

    return array_merge( $new_links, $links );
}