<?php
/**
 * Plugin Name: Headless Query API
 * Plugin URI: https://github.com/example/headless-query-api
 * Description: A lightweight headless query layer for WordPress and ACF projects with safe WP_Query-inspired requests, normalized JSON responses, and frontend-friendly routing utilities.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Mário Medina
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: headless-query-api
 *
 * @package HeadlessQueryAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HEADLESS_QUERY_API_VERSION', '0.1.0' );
define( 'HEADLESS_QUERY_API_FILE', __FILE__ );
define( 'HEADLESS_QUERY_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'HEADLESS_QUERY_API_URL', plugin_dir_url( __FILE__ ) );

$headless_query_api_files = array(
	'includes/Support/SettingsRepository.php',
	'includes/Support/AcfProjector.php',
	'includes/Support/RouteResolver.php',
	'includes/Support/PostTransformer.php',
	'includes/Query/QueryMapper.php',
	'includes/REST/Controller.php',
	'includes/Admin/SettingsPage.php',
	'includes/FrontendRedirect.php',
	'includes/Plugin.php',
);

foreach ( $headless_query_api_files as $headless_query_api_file ) {
	require_once HEADLESS_QUERY_API_PATH . $headless_query_api_file;
}

register_activation_hook( __FILE__, array( '\HeadlessQueryAPI\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\HeadlessQueryAPI\Plugin::instance()->boot();
	}
);
