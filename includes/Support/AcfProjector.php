<?php
/**
 * Optional ACF field projection.
 *
 * @package HeadlessQueryAPI
 */

namespace HeadlessQueryAPI\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads selected ACF fields without requiring ACF to be installed.
 */
final class AcfProjector {
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
	 * Creates an ACF projection from a REST parameter.
	 *
	 * @param mixed $raw Raw request value.
	 * @return array<string,mixed>
	 */
	public function projection_from_request( $raw ) {
		if ( null === $raw || '' === $raw ) {
			return $this->default_projection();
		}

		if ( is_bool( $raw ) ) {
			return $raw ? array( 'mode' => 'all', 'fields' => array() ) : array( 'mode' => 'none', 'fields' => array() );
		}

		$raw = strtolower( trim( (string) $raw ) );

		if ( in_array( $raw, array( 'false', '0', 'no', 'none' ), true ) ) {
			return array( 'mode' => 'none', 'fields' => array() );
		}

		if ( 'all' === $raw || 'true' === $raw || '1' === $raw ) {
			return array( 'mode' => 'all', 'fields' => array() );
		}

		$fields = SettingsRepository::sanitize_list( $raw );

		return array(
			'mode'   => empty( $fields ) ? 'none' : 'selected',
			'fields' => $this->apply_whitelist( $fields ),
		);
	}

	/**
	 * Projects ACF data for one post.
	 *
	 * @param int                  $post_id    Post ID.
	 * @param array<string,mixed>  $projection Projection definition.
	 * @return array<string,mixed>
	 */
	public function project( $post_id, array $projection ) {
		if ( 'none' === $projection['mode'] || ! function_exists( 'get_field' ) ) {
			return array();
		}

		if ( 'all' === $projection['mode'] ) {
			return $this->project_all_fields( $post_id );
		}

		$data = array();

		foreach ( (array) $projection['fields'] as $field_name ) {
			$data[ $field_name ] = get_field( $field_name, $post_id );
		}

		return $data;
	}

	/**
	 * Checks whether the projection includes any ACF data.
	 *
	 * @param array<string,mixed> $projection Projection definition.
	 * @return bool
	 */
	public function has_projection( array $projection ) {
		return isset( $projection['mode'] ) && 'none' !== $projection['mode'];
	}

	/**
	 * Builds the configured default projection.
	 *
	 * @return array<string,mixed>
	 */
	private function default_projection() {
		$behavior = (string) $this->settings->get( 'default_acf_behavior', 'none' );

		if ( 'all' === $behavior ) {
			return array( 'mode' => 'all', 'fields' => array() );
		}

		if ( 'selected' === $behavior ) {
			$fields = $this->settings->allowed_acf_fields();

			return array(
				'mode'   => empty( $fields ) ? 'none' : 'selected',
				'fields' => $fields,
			);
		}

		return array( 'mode' => 'none', 'fields' => array() );
	}

	/**
	 * Reads every available ACF field and applies the optional whitelist.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	private function project_all_fields( $post_id ) {
		if ( ! function_exists( 'get_fields' ) ) {
			return array();
		}

		$fields = get_fields( $post_id );

		if ( ! is_array( $fields ) ) {
			return array();
		}

		$allowed = $this->settings->allowed_acf_fields();

		if ( empty( $allowed ) ) {
			return $fields;
		}

		return array_intersect_key( $fields, array_flip( $allowed ) );
	}

	/**
	 * Applies an admin-configured ACF field whitelist.
	 *
	 * @param string[] $fields Requested field names.
	 * @return string[]
	 */
	private function apply_whitelist( array $fields ) {
		$allowed = $this->settings->allowed_acf_fields();

		if ( empty( $allowed ) ) {
			return $fields;
		}

		return array_values( array_intersect( $fields, $allowed ) );
	}
}
