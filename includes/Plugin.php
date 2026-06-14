<?php
/**
 * Plugin bootstrap.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI;

use HeadlessQueryAPI\Admin\SettingsPage;
use HeadlessQueryAPI\Query\QueryMapper;
use HeadlessQueryAPI\REST\Controller;
use HeadlessQueryAPI\Support\AcfProjector;
use HeadlessQueryAPI\Support\PostTransformer;
use HeadlessQueryAPI\Support\RouteResolver;
use HeadlessQueryAPI\Support\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services and WordPress hooks.
 */
final class Plugin {
	/**
	 * Shared plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * REST controller.
	 *
	 * @var Controller
	 */
	private $rest_controller;

	/**
	 * Admin settings page.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Optional frontend redirect handler.
	 *
	 * @var FrontendRedirect
	 */
	private $frontend_redirect;

	/**
	 * Gets the shared plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Stores initial defaults on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( SettingsRepository::OPTION_NAME, false ) ) {
			add_option( SettingsRepository::OPTION_NAME, SettingsRepository::defaults() );
		}
	}

	/**
	 * Builds plugin services.
	 */
	private function __construct() {
		$this->settings = new SettingsRepository();

		$acf_projector       = new AcfProjector( $this->settings );
		$route_resolver      = new RouteResolver( $this->settings );
		$post_transformer    = new PostTransformer( $this->settings, $acf_projector );
		$query_mapper        = new QueryMapper( $this->settings );
		$this->rest_controller = new Controller(
			$this->settings,
			$query_mapper,
			$post_transformer,
			$route_resolver,
			$acf_projector
		);

		$this->settings_page     = new SettingsPage( $this->settings );
		$this->frontend_redirect = new FrontendRedirect( $this->settings );
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );
		add_action( 'admin_menu', array( $this->settings_page, 'register_menu' ) );
		add_action( 'admin_init', array( $this->settings_page, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HEADLESS_QUERY_API_FILE ), array( $this->settings_page, 'add_action_links' ) );
		add_action( 'template_redirect', array( $this->frontend_redirect, 'maybe_redirect' ), 1 );
	}
}
