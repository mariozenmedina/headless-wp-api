<?php
/**
 * Admin settings page scaffold.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Admin;

use HeadlessQueryAPI\Support\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin settings in the WordPress admin.
 */
final class SettingsPage {
	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings repository.
	 */
	public function __construct( SettingsRepository $settings ) {
		unset( $settings );
	}

	/**
	 * Adds plugin action links.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public function add_action_links( array $links ) {
		return $links;
	}

	/**
	 * Registers the admin menu.
	 *
	 * Implemented in the settings step of the migration plan.
	 *
	 * @return void
	 */
	public function register_menu() {
	}

	/**
	 * Registers plugin settings.
	 *
	 * Implemented in the settings step of the migration plan.
	 *
	 * @return void
	 */
	public function register_settings() {
	}
}
