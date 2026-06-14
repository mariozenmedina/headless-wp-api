<?php
/**
 * Frontend route resolver.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Support;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves frontend paths to public WordPress resources when feasible.
 */
final class RouteResolver {
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
	 * Resolves a path into post, taxonomy, post type archive, or 404 metadata.
	 *
	 * @param string $raw_path Raw path.
	 * @return array<string,mixed>
	 */
	public function resolve( $raw_path ) {
		$path = $this->normalize_path( $raw_path );
		$post = $this->resolve_post_by_path( $path );

		if ( $post ) {
			return $this->post_result( $post );
		}

		$archive = $this->resolve_post_type_archive( $path );

		if ( ! empty( $archive ) ) {
			return $archive;
		}

		$term = $this->resolve_taxonomy_term( $path );

		if ( ! empty( $term ) ) {
			return $term;
		}

		return array(
			'found' => false,
			'kind'  => '404',
			'path'  => $path,
		);
	}

	/**
	 * Resolves a path to a public post.
	 *
	 * @param string $raw_path Raw path.
	 * @return WP_Post|null
	 */
	public function resolve_post_by_path( $raw_path ) {
		$path = $this->normalize_path( $raw_path );

		if ( '/' === $path ) {
			$front_page_id = absint( get_option( 'page_on_front' ) );
			$front_page    = $front_page_id ? get_post( $front_page_id ) : null;

			return $this->is_public_allowed_post( $front_page ) ? $front_page : null;
		}

		$post_id = url_to_postid( home_url( $path ) );

		if ( $post_id ) {
			$post = get_post( $post_id );

			if ( $this->is_public_allowed_post( $post ) ) {
				return $post;
			}
		}

		$slug_path  = trim( $path, '/' );
		$post_types = $this->settings->allowed_post_types();
		$post       = get_page_by_path( $slug_path, OBJECT, $post_types );

		return $this->is_public_allowed_post( $post ) ? $post : null;
	}

	/**
	 * Normalizes a path-like string.
	 *
	 * @param string $raw_path Raw path.
	 * @return string
	 */
	public function normalize_path( $raw_path ) {
		$path = wp_parse_url( (string) $raw_path, PHP_URL_PATH );
		$path = rawurldecode( (string) $path );
		$path = '/' . trim( $path, '/' );

		return '/' === $path ? '/' : untrailingslashit( $path );
	}

	/**
	 * Checks whether a post can be exposed publicly.
	 *
	 * @param mixed $post Post candidate.
	 * @return bool
	 */
	public function is_public_allowed_post( $post ) {
		return $post instanceof WP_Post
			&& 'publish' === $post->post_status
			&& $this->settings->is_post_type_allowed( $post->post_type )
			&& ! post_password_required( $post );
	}

	/**
	 * Builds a post resolver response.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string,mixed>
	 */
	private function post_result( WP_Post $post ) {
		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );

		if ( absint( get_option( 'page_on_front' ) ) === (int) $post->ID && 'page' === $post->post_type ) {
			$path = '/';
		}

		$path = $path ? untrailingslashit( $path ) : '/';

		return array(
			'found'    => true,
			'kind'     => 'post',
			'post_type' => $post->post_type,
			'id'       => (int) $post->ID,
			'slug'     => $post->post_name,
			'path'     => $path,
			'endpoint' => rest_url( 'headless-query/v1/post?id=' . (int) $post->ID ),
		);
	}

	/**
	 * Resolves public post type archives.
	 *
	 * @param string $path Normalized path.
	 * @return array<string,mixed>
	 */
	private function resolve_post_type_archive( $path ) {
		foreach ( $this->settings->allowed_post_types() as $post_type ) {
			$object = get_post_type_object( $post_type );

			if ( ! $object || empty( $object->has_archive ) ) {
				continue;
			}

			$archive_path = wp_parse_url( get_post_type_archive_link( $post_type ), PHP_URL_PATH );
			$archive_path = $archive_path ? untrailingslashit( $archive_path ) : '';

			if ( $archive_path && $archive_path === $path ) {
				return array(
					'found'    => true,
					'kind'     => 'post_type_archive',
					'post_type' => $post_type,
					'path'     => $path,
					'endpoint' => rest_url( 'headless-query/v1/posts?post_type=' . rawurlencode( $post_type ) ),
				);
			}
		}

		return array();
	}

	/**
	 * Resolves public taxonomy term archives.
	 *
	 * @param string $path Normalized path.
	 * @return array<string,mixed>
	 */
	private function resolve_taxonomy_term( $path ) {
		$slug = basename( $path );

		if ( '' === $slug ) {
			return array();
		}

		foreach ( $this->settings->allowed_taxonomies() as $taxonomy ) {
			$term = get_term_by( 'slug', sanitize_title( $slug ), $taxonomy );

			if ( ! $term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$term_path = wp_parse_url( $link, PHP_URL_PATH );
			$term_path = $term_path ? untrailingslashit( $term_path ) : '';

			if ( $term_path !== $path ) {
				continue;
			}

			return array(
				'found'    => true,
				'kind'     => 'taxonomy',
				'taxonomy' => $taxonomy,
				'term_id'  => (int) $term->term_id,
				'slug'     => $term->slug,
				'name'     => $term->name,
				'path'     => $path,
				'endpoint' => rest_url( 'headless-query/v1/posts?taxonomy=' . rawurlencode( $taxonomy ) . '&terms=' . rawurlencode( $term->slug ) ),
			);
		}

		return array();
	}
}
