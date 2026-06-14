<?php
/**
 * Optional frontend redirect scaffold.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI;

use HeadlessQueryAPI\Support\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles optional redirects to an external frontend.
 */
final class FrontendRedirect {
	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings repository.
	 */
	public function __construct( SettingsRepository $settings ) {
		unset( $settings );
	}

	/**
	 * Redirects frontend requests when enabled.
	 *
	 * Implemented in the redirect step of the migration plan.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
	}
}
