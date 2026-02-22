<?php
/**
 * Plugin Name: Personal Auto Distribution Engine
 * Description: Personal automated content distribution with AI integration and diagnostics dashboard.
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Author: Personal Use
 * Text Domain: personal-auto-engine
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PADE_VERSION', '1.0.0' );
define( 'PADE_PLUGIN_FILE', __FILE__ );
define( 'PADE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PADE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PADE_PLUGIN_DIR . 'includes/class-database.php';
require_once PADE_PLUGIN_DIR . 'includes/class-logger.php';
require_once PADE_PLUGIN_DIR . 'includes/class-settings.php';
require_once PADE_PLUGIN_DIR . 'includes/class-ai-engine.php';
require_once PADE_PLUGIN_DIR . 'includes/class-router.php';
require_once PADE_PLUGIN_DIR . 'includes/class-queue.php';
require_once PADE_PLUGIN_DIR . 'includes/class-scheduler.php';

require_once PADE_PLUGIN_DIR . 'platforms/class-telegram.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-pinterest.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-facebook.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-twitter.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-linkedin.php';

require_once PADE_PLUGIN_DIR . 'admin/page-settings.php';
require_once PADE_PLUGIN_DIR . 'admin/page-control-center.php';
require_once PADE_PLUGIN_DIR . 'admin/page-queue.php';

/**
 * Bootstrap plugin.
 */
function pade_bootstrap(): void {
	PADE_Database::init();
	PADE_Settings::init();
	PADE_Queue::init();
	PADE_Scheduler::init();
	PADE_Admin_Settings_Page::init();
	PADE_Admin_Control_Center_Page::init();
	PADE_Admin_Queue_Page::init();
}

register_activation_hook( __FILE__, array( 'PADE_Database', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PADE_Scheduler', 'deactivate' ) );
add_action( 'plugins_loaded', 'pade_bootstrap' );
