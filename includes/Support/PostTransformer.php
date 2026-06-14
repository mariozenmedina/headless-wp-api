<?php
/**
 * Normalized post response transformer.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Support;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts WordPress posts into frontend-friendly response arrays.
 */
final class PostTransformer {
	/**
	 * Supported response fields.
	 */
	public const ALLOWED_FIELDS = array(
		'id',
		'type',
		'slug',
		'path',
		'link',
		'title',
		'excerpt',
		'content',
		'date',
		'modified',
		'featured_image',
		'terms',
		'acf',
	);

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * ACF projector.
	 *
	 * @var AcfProjector
	 */
	private $acf_projector;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings      Settings repository.
	 * @param AcfProjector       $acf_projector ACF projector.
	 */
	public function __construct( SettingsRepository $settings, AcfProjector $acf_projector ) {
		$this->settings      = $settings;
		$this->acf_projector = $acf_projector;
	}

	/**
	 * Normalizes a post object.
	 *
	 * @param WP_Post              $post    Post object.
	 * @param array<string,mixed>  $options Response options.
	 * @return array<string,mixed>
	 */
	public function transform( WP_Post $post, array $options ) {
		$fields = array_fill_keys( $this->normalize_fields( $options ), true );
		$data   = array();

		if ( isset( $fields['id'] ) ) {
			$data['id'] = (int) $post->ID;
		}

		if ( isset( $fields['type'] ) ) {
			$data['type'] = $post->post_type;
		}

		if ( isset( $fields['slug'] ) ) {
			$data['slug'] = $post->post_name;
		}

		if ( isset( $fields['path'] ) ) {
			$data['path'] = $this->get_post_path( $post );
		}

		if ( isset( $fields['link'] ) ) {
			$data['link'] = get_permalink( $post );
		}

		if ( isset( $fields['title'] ) ) {
			$data['title'] = html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}

		if ( isset( $fields['excerpt'] ) ) {
			$data['excerpt'] = apply_filters( 'the_excerpt', get_the_excerpt( $post ) );
		}

		if ( isset( $fields['content'] ) ) {
			$data['content'] = apply_filters( 'the_content', $post->post_content );
		}

		if ( isset( $fields['date'] ) ) {
			$data['date'] = get_post_time( 'c', false, $post );
		}

		if ( isset( $fields['modified'] ) ) {
			$data['modified'] = get_post_modified_time( 'c', false, $post );
		}

		if ( isset( $fields['featured_image'] ) ) {
			$data['featured_image'] = $this->get_featured_image( $post );
		}

		if ( isset( $fields['terms'] ) ) {
			$data['terms'] = $this->get_terms( $post );
		}

		if ( isset( $fields['acf'] ) ) {
			$data['acf'] = $this->acf_projector->project( (int) $post->ID, $options['acf_projection'] );
		}

		return $data;
	}

	/**
	 * Gets the relative frontend path for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public function get_post_path( WP_Post $post ) {
		$front_page_id = absint( get_option( 'page_on_front' ) );

		if ( $front_page_id === (int) $post->ID && 'page' === $post->post_type ) {
			return '/';
		}

		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );

		return $path ? trailingslashit( $path ) : '/';
	}

	/**
	 * Normalizes requested response fields.
	 *
	 * @param array<string,mixed> $options Response options.
	 * @return string[]
	 */
	private function normalize_fields( array $options ) {
		$fields = isset( $options['fields'] ) && is_array( $options['fields'] ) ? $options['fields'] : array();
		$fields = array_values( array_intersect( $fields, self::ALLOWED_FIELDS ) );

		if ( ! empty( $options['include_featured_image'] ) && ! in_array( 'featured_image', $fields, true ) ) {
			$fields[] = 'featured_image';
		}

		if ( ! empty( $options['include_terms'] ) && ! in_array( 'terms', $fields, true ) ) {
			$fields[] = 'terms';
		}

		if ( ! empty( $options['acf_projection'] ) && $this->acf_projector->has_projection( $options['acf_projection'] ) && ! in_array( 'acf', $fields, true ) ) {
			$fields[] = 'acf';
		}

		return empty( $fields ) ? $this->settings->default_fields() : $fields;
	}

	/**
	 * Builds a normalized featured image object.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string,mixed>|null
	 */
	private function get_featured_image( WP_Post $post ) {
		$attachment_id = get_post_thumbnail_id( $post );

		if ( ! $attachment_id ) {
			return null;
		}

		$full = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $full ) {
			return null;
		}

		return array(
			'id'     => (int) $attachment_id,
			'url'    => $full[0],
			'alt'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'width'  => (int) $full[1],
			'height' => (int) $full[2],
			'mime'   => get_post_mime_type( $attachment_id ),
			'sizes'  => $this->get_image_sizes( $attachment_id ),
		);
	}

	/**
	 * Gets normalized image sizes for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_image_sizes( $attachment_id ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
			return array();
		}

		$sizes = array();

		foreach ( array_keys( $metadata['sizes'] ) as $size_name ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size_name );

			if ( ! $image ) {
				continue;
			}

			$sizes[ $size_name ] = array(
				'url'    => $image[0],
				'width'  => (int) $image[1],
				'height' => (int) $image[2],
			);
		}

		return $sizes;
	}

	/**
	 * Gets public taxonomy terms for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	private function get_terms( WP_Post $post ) {
		$allowed_taxonomies = $this->settings->allowed_taxonomies();
		$post_taxonomies    = get_object_taxonomies( $post->post_type, 'names' );
		$taxonomies         = array_values( array_intersect( $allowed_taxonomies, $post_taxonomies ) );
		$data               = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				$data[ $taxonomy ] = array();
				continue;
			}

			$data[ $taxonomy ] = array_map(
				static function ( $term ) {
					return array(
						'id'   => (int) $term->term_id,
						'slug' => $term->slug,
						'name' => $term->name,
					);
				},
				array_values( $terms )
			);
		}

		return $data;
	}
}
