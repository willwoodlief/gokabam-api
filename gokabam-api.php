<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 *
 * @wordpress-plugin
 * Plugin Name:       GoKabam Api Developer
 * Plugin URI:        mailto:willwoodlief@gmail.com
 * Description:       Api Specification
 * Version:           1.0.0
 * Author:            Will Woodlief
 * Author URI:        willwoodlief@gmail.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gokabam_api
 * Domain Path:       /languages
 * Requires at least: 4.6
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


# constants
require_once 'constants.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-activator.php
 */
/**
 * @throws Exception
 */
function activate_gokabam_api() {
	/** @noinspection PhpIncludeInspection */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	\gokabam_api\Activator::activate();
}



/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator.php
 */
function deactivate_gokabam_api() {
	/** @noinspection PhpIncludeInspection */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	\gokabam_api\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gokabam_api' );
register_deactivation_hook( __FILE__, 'deactivate_gokabam_api' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
/** @noinspection PhpIncludeInspection */
require plugin_dir_path( __FILE__ ) . 'includes/class-start.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gokabam_api() {

	$plugin = new \gokabam_api\Start();
	$plugin->run();

}
run_gokabam_api();
