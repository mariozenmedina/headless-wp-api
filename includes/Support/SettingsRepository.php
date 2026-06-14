<?php
/**
 * Settings access and defaults.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides normalized plugin settings.
 */
final class SettingsRepository {
	public const OPTION_NAME = 'headless_query_api_settings';

	/**
	 * Returns default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'                        => true,
			'allowed_post_types'             => array_values( array_intersect( array( 'post', 'page' ), self::public_post_type_names() ) ),
			'allowed_taxonomies'             => array_values( array_intersect( array( 'category', 'post_tag' ), self::public_taxonomy_names() ) ),
			'default_posts_per_page'         => 10,
			'max_posts_per_page'             => 50,
			'default_fields'                 => array( 'id', 'type', 'slug', 'path', 'title', 'excerpt', 'date', 'modified' ),
			'include_featured_image_default' => false,
			'include_terms_default'          => false,
			'default_acf_behavior'           => 'none',
			'allowed_acf_fields'             => array(),
			'resolver_enabled'               => true,
			'frontend_redirect_enabled'      => false,
			'frontend_url'                   => '',
			'redirect_status'                => 302,
		);
	}

	/**
	 * Gets all normalized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		$options = get_option( self::OPTION_NAME, false );

		if ( false === $options || ! is_array( $options ) ) {
			return self::defaults();
		}

		return wp_parse_args( $options, self::defaults() );
	}

	/**
	 * Gets one setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$settings = $this->all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Checks whether the public API is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) $this->get( 'enabled', true );
	}

	/**
	 * Checks whether the resolver endpoint is enabled.
	 *
	 * @return bool
	 */
	public function is_resolver_enabled() {
		return (bool) $this->get( 'resolver_enabled', true );
	}

	/**
	 * Gets the default posts per page.
	 *
	 * @return int
	 */
	public function default_posts_per_page() {
		return max( 1, absint( $this->get( 'default_posts_per_page', 10 ) ) );
	}

	/**
	 * Gets the maximum posts per page.
	 *
	 * @return int
	 */
	public function max_posts_per_page() {
		return max( 1, absint( $this->get( 'max_posts_per_page', 50 ) ) );
	}

	/**
	 * Gets allowed public post types.
	 *
	 * @return string[]
	 */
	public function allowed_post_types() {
		$post_types = self::sanitize_list( (array) $this->get( 'allowed_post_types', array() ) );

		return array_values(
			array_filter(
				$post_types,
				static function ( $post_type ) {
					$object = get_post_type_object( $post_type );

					return $object && ! empty( $object->public ) && 'attachment' !== $post_type;
				}
			)
		);
	}

	/**
	 * Checks whether a post type may be queried publicly.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function is_post_type_allowed( $post_type ) {
		return in_array( $post_type, $this->allowed_post_types(), true );
	}

	/**
	 * Gets allowed public taxonomies.
	 *
	 * @return string[]
	 */
	public function allowed_taxonomies() {
		$taxonomies = self::sanitize_list( (array) $this->get( 'allowed_taxonomies', array() ) );

		return array_values(
			array_filter(
				$taxonomies,
				static function ( $taxonomy ) {
					$object = get_taxonomy( $taxonomy );

					return $object && ! empty( $object->public );
				}
			)
		);
	}

	/**
	 * Checks whether a taxonomy may be queried publicly.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public function is_taxonomy_allowed( $taxonomy ) {
		return in_array( $taxonomy, $this->allowed_taxonomies(), true );
	}

	/**
	 * Gets default response fields.
	 *
	 * @param string $context Response context.
	 * @return string[]
	 */
	public function default_fields( $context = 'collection' ) {
		$fields = self::sanitize_list( (array) $this->get( 'default_fields', array() ) );

		if ( 'single' === $context && ! in_array( 'content', $fields, true ) ) {
			$fields[] = 'content';
		}

		return array_values( array_unique( $fields ) );
	}

	/**
	 * Gets whitelisted ACF field names.
	 *
	 * @return string[]
	 */
	public function allowed_acf_fields() {
		return self::sanitize_list( (array) $this->get( 'allowed_acf_fields', array() ) );
	}

	/**
	 * Gets public post types available in WordPress.
	 *
	 * @return string[]
	 */
	public static function public_post_type_names() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Gets public taxonomies available in WordPress.
	 *
	 * @return string[]
	 */
	public static function public_taxonomy_names() {
		return array_values( get_taxonomies( array( 'public' => true ), 'names' ) );
	}

	/**
	 * Normalizes comma-separated or array input into safe names.
	 *
	 * @param mixed $value List input.
	 * @return string[]
	 */
	public static function sanitize_list( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$items = array();

		foreach ( $value as $item ) {
			$item = trim( (string) $item );
			$item = preg_replace( '/[^A-Za-z0-9_\-]/', '', $item );

			if ( '' !== $item ) {
				$items[] = $item;
			}
		}

		return array_values( array_unique( $items ) );
	}
}
