<?php
/**
 * REST API controller scaffold.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\REST;

use HeadlessQueryAPI\Query\QueryMapper;
use HeadlessQueryAPI\Support\AcfProjector;
use HeadlessQueryAPI\Support\PostTransformer;
use HeadlessQueryAPI\Support\RouteResolver;
use HeadlessQueryAPI\Support\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the versioned public REST API.
 */
final class Controller {
	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'headless-query/v1';

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings         Settings repository.
	 * @param QueryMapper        $query_mapper     Query mapper.
	 * @param PostTransformer    $post_transformer Post transformer.
	 * @param RouteResolver      $route_resolver   Route resolver.
	 * @param AcfProjector       $acf_projector    ACF projector.
	 */
	public function __construct(
		SettingsRepository $settings,
		QueryMapper $query_mapper,
		PostTransformer $post_transformer,
		RouteResolver $route_resolver,
		AcfProjector $acf_projector
	) {
		unset( $settings, $query_mapper, $post_transformer, $route_resolver, $acf_projector );
	}

	/**
	 * Registers REST routes.
	 *
	 * Route registration is implemented in step 2 of the migration plan.
	 *
	 * @return void
	 */
	public function register_routes() {
	}
}
