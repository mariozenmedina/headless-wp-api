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
use WP_Error;
use WP_REST_Request;

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
	public const REST_NAMESPACE = 'headless-query/v1';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Query mapper.
	 *
	 * @var QueryMapper
	 */
	private $query_mapper;

	/**
	 * Post transformer.
	 *
	 * @var PostTransformer
	 */
	private $post_transformer;

	/**
	 * Route resolver.
	 *
	 * @var RouteResolver
	 */
	private $route_resolver;

	/**
	 * ACF projector.
	 *
	 * @var AcfProjector
	 */
	private $acf_projector;

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
		$this->settings         = $settings;
		$this->query_mapper     = $query_mapper;
		$this->post_transformer = $post_transformer;
		$this->route_resolver   = $route_resolver;
		$this->acf_projector    = $acf_projector;
	}

	/**
	 * Registers REST routes.
	 *
	 * Route registration is implemented in step 2 of the migration plan.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/posts',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => array( $this, 'can_read_public' ),
				'args'                => $this->collection_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/post',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_post' ),
				'permission_callback' => array( $this, 'can_read_public' ),
				'args'                => $this->single_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/resolve',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'resolve' ),
				'permission_callback' => array( $this, 'can_resolve_public' ),
				'args'                => $this->resolve_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/site',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_site' ),
				'permission_callback' => array( $this, 'can_read_public' ),
				'args'                => $this->site_args(),
			)
		);
	}

	/**
	 * Checks whether public API requests are enabled.
	 *
	 * @return true|WP_Error
	 */
	public function can_read_public() {
		if ( $this->settings->is_enabled() ) {
			return true;
		}

		return new WP_Error(
			'headless_query_api_disabled',
			__( 'The Headless Query API is disabled.', 'headless-query-api' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Checks whether public resolver requests are enabled.
	 *
	 * @return true|WP_Error
	 */
	public function can_resolve_public() {
		$permission = $this->can_read_public();

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		if ( $this->settings->is_resolver_enabled() ) {
			return true;
		}

		return new WP_Error(
			'headless_query_resolver_disabled',
			__( 'The Headless Query API route resolver is disabled.', 'headless-query-api' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Handles the collection endpoint.
	 *
	 * Implemented in step 3 of the migration plan.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_Error
	 */
	public function get_posts( WP_REST_Request $request ) {
		unset( $request );

		return $this->not_implemented();
	}

	/**
	 * Handles the single post endpoint.
	 *
	 * Implemented in step 4 of the migration plan.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_Error
	 */
	public function get_post( WP_REST_Request $request ) {
		unset( $request );

		return $this->not_implemented();
	}

	/**
	 * Handles the route resolver endpoint.
	 *
	 * Implemented in step 5 of the migration plan.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_Error
	 */
	public function resolve( WP_REST_Request $request ) {
		unset( $request );

		return $this->not_implemented();
	}

	/**
	 * Handles the site metadata endpoint.
	 *
	 * Implemented in step 6 of the migration plan.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_Error
	 */
	public function get_site( WP_REST_Request $request ) {
		unset( $request );

		return $this->not_implemented();
	}

	/**
	 * Gets collection endpoint arguments.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function collection_args() {
		return array_merge(
			array(
				'post_type'              => $this->csv_arg( __( 'Allowed post type slug or comma-separated list.', 'headless-query-api' ) ),
				'page'                   => $this->integer_arg( __( 'One-based result page.', 'headless-query-api' ), 1 ),
				'paged'                  => $this->integer_arg( __( 'Alias for page.', 'headless-query-api' ), 1 ),
				'posts_per_page'         => $this->integer_arg( __( 'Number of posts per page, capped by settings.', 'headless-query-api' ), 1 ),
				'per_page'               => $this->integer_arg( __( 'Alias for posts_per_page.', 'headless-query-api' ), 1 ),
				'order'                  => $this->enum_arg( __( 'Sort direction.', 'headless-query-api' ), array( 'ASC', 'DESC', 'asc', 'desc' ) ),
				'orderby'                => $this->enum_arg( __( 'Sort field.', 'headless-query-api' ), array( 'date', 'modified', 'title', 'menu_order', 'slug', 'id', 'include' ) ),
				'search'                 => $this->text_arg( __( 'Search term.', 'headless-query-api' ) ),
				'slug'                   => $this->text_arg( __( 'Post slug filter.', 'headless-query-api' ) ),
				'parent'                 => $this->integer_arg( __( 'Parent post ID.', 'headless-query-api' ), 0 ),
				'author'                 => $this->integer_arg( __( 'Author user ID.', 'headless-query-api' ), 1 ),
				'taxonomy'               => $this->key_arg( __( 'Allowed taxonomy slug.', 'headless-query-api' ) ),
				'terms'                  => $this->csv_arg( __( 'Comma-separated term IDs or slugs.', 'headless-query-api' ) ),
				'include'                => $this->csv_arg( __( 'Comma-separated post IDs to include.', 'headless-query-api' ) ),
				'exclude'                => $this->csv_arg( __( 'Comma-separated post IDs to exclude.', 'headless-query-api' ) ),
				'fields'                 => $this->csv_arg( __( 'Comma-separated response fields.', 'headless-query-api' ) ),
				'acf'                    => $this->text_arg( __( 'ACF projection: false, all, or comma-separated field names.', 'headless-query-api' ) ),
				'include_terms'          => $this->boolean_arg( __( 'Include public taxonomy terms.', 'headless-query-api' ) ),
				'include_featured_image' => $this->boolean_arg( __( 'Include normalized featured image data.', 'headless-query-api' ) ),
			),
			$this->language_args()
		);
	}

	/**
	 * Gets single endpoint arguments.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function single_args() {
		return array_merge(
			array(
				'id'                     => $this->integer_arg( __( 'Post ID.', 'headless-query-api' ), 1 ),
				'post_type'              => $this->csv_arg( __( 'Allowed post type slug or comma-separated list.', 'headless-query-api' ) ),
				'slug'                   => $this->text_arg( __( 'Post slug.', 'headless-query-api' ) ),
				'path'                   => $this->path_arg( __( 'Frontend path to resolve to content.', 'headless-query-api' ) ),
				'fields'                 => $this->csv_arg( __( 'Comma-separated response fields.', 'headless-query-api' ) ),
				'acf'                    => $this->text_arg( __( 'ACF projection: false, all, or comma-separated field names.', 'headless-query-api' ) ),
				'include_terms'          => $this->boolean_arg( __( 'Include public taxonomy terms.', 'headless-query-api' ) ),
				'include_featured_image' => $this->boolean_arg( __( 'Include normalized featured image data.', 'headless-query-api' ) ),
			),
			$this->language_args()
		);
	}

	/**
	 * Gets resolver endpoint arguments.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function resolve_args() {
		return array_merge(
			array(
				'path' => $this->path_arg( __( 'Frontend path to resolve.', 'headless-query-api' ), true ),
			),
			$this->language_args()
		);
	}

	/**
	 * Gets site endpoint arguments.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function site_args() {
		return array_merge(
			array(
				'include_post_types' => $this->boolean_arg( __( 'Include enabled public post types.', 'headless-query-api' ) ),
				'include_taxonomies' => $this->boolean_arg( __( 'Include enabled public taxonomies.', 'headless-query-api' ) ),
			),
			$this->language_args()
		);
	}

	/**
	 * Gets common language arguments for multilingual plugins such as Polylang.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function language_args() {
		return array(
			'lang' => array(
				'description'       => __( 'Optional language slug for multilingual plugins such as Polylang. Use all to request all languages when supported.', 'headless-query-api' ),
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_language' ),
				'validate_callback' => array( $this, 'validate_language' ),
			),
		);
	}

	/**
	 * Sanitizes a language slug.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_language( $value, $request = null, $param = '' ) {
		unset( $request, $param );

		return sanitize_key( (string) $value );
	}

	/**
	 * Validates a language slug.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public function validate_language( $value, $request = null, $param = '' ) {
		unset( $request, $param );

		$value = sanitize_key( (string) $value );

		return '' === $value || 'all' === $value || (bool) preg_match( '/^[a-z0-9_-]{2,20}$/', $value );
	}

	/**
	 * Returns a not implemented response for staged endpoint work.
	 *
	 * @return WP_Error
	 */
	private function not_implemented() {
		return new WP_Error(
			'headless_query_not_implemented',
			__( 'This endpoint is registered and will be implemented in a later migration step.', 'headless-query-api' ),
			array( 'status' => 501 )
		);
	}

	/**
	 * Builds a text argument definition.
	 *
	 * @param string $description Argument description.
	 * @return array<string,mixed>
	 */
	private function text_arg( $description ) {
		return array(
			'description'       => $description,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
	}

	/**
	 * Builds a path argument definition.
	 *
	 * @param string $description Argument description.
	 * @param bool   $required    Whether the argument is required.
	 * @return array<string,mixed>
	 */
	private function path_arg( $description, $required = false ) {
		return array(
			'description'       => $description,
			'type'              => 'string',
			'required'          => $required,
			'sanitize_callback' => 'sanitize_text_field',
		);
	}

	/**
	 * Builds a key argument definition.
	 *
	 * @param string $description Argument description.
	 * @return array<string,mixed>
	 */
	private function key_arg( $description ) {
		return array(
			'description'       => $description,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
		);
	}

	/**
	 * Builds a comma-separated value argument definition.
	 *
	 * @param string $description Argument description.
	 * @return array<string,mixed>
	 */
	private function csv_arg( $description ) {
		return array(
			'description'       => $description,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
	}

	/**
	 * Builds an integer argument definition.
	 *
	 * @param string $description Argument description.
	 * @param int    $minimum     Minimum value.
	 * @return array<string,mixed>
	 */
	private function integer_arg( $description, $minimum ) {
		return array(
			'description'       => $description,
			'type'              => 'integer',
			'minimum'           => $minimum,
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * Builds a boolean argument definition.
	 *
	 * @param string $description Argument description.
	 * @return array<string,mixed>
	 */
	private function boolean_arg( $description ) {
		return array(
			'description'       => $description,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
		);
	}

	/**
	 * Builds an enum argument definition.
	 *
	 * @param string   $description Argument description.
	 * @param string[] $allowed     Allowed values.
	 * @return array<string,mixed>
	 */
	private function enum_arg( $description, array $allowed ) {
		return array(
			'description'       => $description,
			'type'              => 'string',
			'enum'              => $allowed,
			'sanitize_callback' => 'sanitize_text_field',
		);
	}
}
