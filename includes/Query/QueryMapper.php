<?php
/**
 * Safe WP_Query request mapper.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Query;

use HeadlessQueryAPI\Support\SettingsRepository;
use WP_Error;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts public REST parameters into constrained WP_Query arguments.
 */
final class QueryMapper {
	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings repository.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Maps collection query parameters.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>|WP_Error
	 */
	public function map_collection_request( WP_REST_Request $request ) {
		$post_types = $this->parse_post_types( $request->get_param( 'post_type' ) );

		if ( is_wp_error( $post_types ) ) {
			return $post_types;
		}

		$page           = max( 1, absint( $request->get_param( 'page' ) ?: $request->get_param( 'paged' ) ?: 1 ) );
		$default_limit  = $this->settings->default_posts_per_page();
		$maximum_limit  = $this->settings->max_posts_per_page();
		$requested_size = absint( $request->get_param( 'posts_per_page' ) ?: $request->get_param( 'per_page' ) ?: $default_limit );
		$per_page       = min( max( 1, $requested_size ), $maximum_limit );
		$order          = strtoupper( sanitize_text_field( (string) ( $request->get_param( 'order' ) ?: 'DESC' ) ) );
		$order          = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
		$orderby        = $this->map_orderby( $request->get_param( 'orderby' ) );

		$args = array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'paged'               => $page,
			'posts_per_page'      => $per_page,
			'order'               => $order,
			'orderby'             => $orderby,
			'has_password'        => false,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		$this->apply_scalar_filters( $request, $args );

		$taxonomy_filter = $this->build_taxonomy_filter( $request );
		if ( is_wp_error( $taxonomy_filter ) ) {
			return $taxonomy_filter;
		}

		if ( ! empty( $taxonomy_filter ) ) {
			$args['tax_query'] = array( $taxonomy_filter );
		}

		return $args;
	}

	/**
	 * Parses and validates requested post types.
	 *
	 * @param mixed $raw Raw post type request value.
	 * @return string[]|WP_Error
	 */
	public function parse_post_types( $raw = null ) {
		$allowed = $this->settings->allowed_post_types();

		if ( empty( $allowed ) ) {
			return new WP_Error(
				'headless_query_no_post_types',
				__( 'No public post types are enabled for the Headless Query API.', 'headless-query-api' ),
				array( 'status' => 400 )
			);
		}

		$requested = SettingsRepository::sanitize_list( null === $raw || '' === $raw ? $allowed : $raw );
		$post_types = array_values( array_intersect( $requested, $allowed ) );

		if ( empty( $post_types ) ) {
			return new WP_Error(
				'headless_query_post_type_not_allowed',
				__( 'The requested post type is not allowed.', 'headless-query-api' ),
				array( 'status' => 400 )
			);
		}

		return $post_types;
	}

	/**
	 * Applies safe scalar filters to WP_Query arguments.
	 *
	 * @param WP_REST_Request    $request REST request.
	 * @param array<string,mixed> $args    Query arguments.
	 * @return void
	 */
	private function apply_scalar_filters( WP_REST_Request $request, array &$args ) {
		$search = $request->get_param( 'search' );

		if ( null !== $search && '' !== $search ) {
			$args['s'] = sanitize_text_field( (string) $search );
		}

		$slug = $request->get_param( 'slug' );

		if ( null !== $slug && '' !== $slug ) {
			$args['name'] = sanitize_title( (string) $slug );
		}

		$parent = $request->get_param( 'parent' );

		if ( null !== $parent && '' !== $parent ) {
			$args['post_parent'] = absint( $parent );
		}

		$author = $request->get_param( 'author' );

		if ( null !== $author && '' !== $author ) {
			$args['author'] = absint( $author );
		}

		$include = $this->parse_id_list( $request->get_param( 'include' ) );

		if ( ! empty( $include ) ) {
			$args['post__in'] = $include;
		}

		$exclude = $this->parse_id_list( $request->get_param( 'exclude' ) );

		if ( ! empty( $exclude ) ) {
			$args['post__not_in'] = $exclude;
		}
	}

	/**
	 * Builds a constrained taxonomy filter.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>|WP_Error
	 */
	private function build_taxonomy_filter( WP_REST_Request $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$terms    = $request->get_param( 'terms' );

		if ( null === $taxonomy || '' === $taxonomy ) {
			return array();
		}

		$taxonomy = sanitize_key( (string) $taxonomy );

		if ( ! $this->settings->is_taxonomy_allowed( $taxonomy ) ) {
			return new WP_Error(
				'headless_query_taxonomy_not_allowed',
				__( 'The requested taxonomy is not allowed.', 'headless-query-api' ),
				array( 'status' => 400 )
			);
		}

		$terms = SettingsRepository::sanitize_list( $terms );

		if ( empty( $terms ) ) {
			return new WP_Error(
				'headless_query_terms_required',
				__( 'The terms parameter is required when taxonomy is provided.', 'headless-query-api' ),
				array( 'status' => 400 )
			);
		}

		$all_numeric = count( $terms ) === count( array_filter( $terms, 'is_numeric' ) );

		return array(
			'taxonomy' => $taxonomy,
			'field'    => $all_numeric ? 'term_id' : 'slug',
			'terms'    => $all_numeric ? array_map( 'absint', $terms ) : array_map( 'sanitize_title', $terms ),
		);
	}

	/**
	 * Maps public orderby values to WP_Query values.
	 *
	 * @param mixed $raw Raw orderby value.
	 * @return string
	 */
	private function map_orderby( $raw ) {
		$raw = sanitize_key( (string) ( $raw ?: 'date' ) );

		$map = array(
			'date'       => 'date',
			'modified'   => 'modified',
			'title'      => 'title',
			'menu_order' => 'menu_order',
			'slug'       => 'name',
			'id'         => 'ID',
			'include'    => 'post__in',
		);

		return isset( $map[ $raw ] ) ? $map[ $raw ] : 'date';
	}

	/**
	 * Parses a bounded list of post IDs.
	 *
	 * @param mixed $raw Raw ID list.
	 * @return int[]
	 */
	private function parse_id_list( $raw ) {
		$ids = SettingsRepository::sanitize_list( $raw );
		$ids = array_slice( array_map( 'absint', $ids ), 0, 100 );

		return array_values( array_filter( $ids ) );
	}
}
