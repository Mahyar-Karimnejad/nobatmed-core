<?php
/**
 * Site bundle import / export (settings, modules, demos — extensible).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import / export handler.
 */
class NobatMed_Import_Export {

	public const FORMAT       = 'nobatmed-export';
	public const FORMAT_VER   = '1.0.0';

	/**
	 * Boot hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function sections(): array {
		$sections = array(
			'modules'           => array(
				'label'       => __( 'ماژول‌های فعال', 'nobatmed-core' ),
				'description' => __( 'وضعیت on/off ماژول‌های Core.', 'nobatmed-core' ),
				'group'       => 'settings',
				'implemented' => true,
				'export'      => true,
				'import'      => true,
			),
			'appearance'        => array(
				'label'       => __( 'ظاهر قالب', 'nobatmed-core' ),
				'description' => __( 'رنگ‌ها، فونت و استایل.', 'nobatmed-core' ),
				'group'       => 'settings',
				'implemented' => true,
				'export'      => true,
				'import'      => true,
			),
			'booking_schedules' => array(
				'label'       => __( 'برنامه‌های نوبت', 'nobatmed-core' ),
				'description' => __( 'جدول برنامه کاری پزشکان.', 'nobatmed-core' ),
				'group'       => 'booking',
				'implemented' => true,
				'export'      => true,
				'import'      => true,
			),
			'profiles'          => array(
				'label'       => __( 'پروفایل‌ها (CPT)', 'nobatmed-core' ),
				'description' => __( 'پزشک، کلینیک، خدمات + متادیتا.', 'nobatmed-core' ),
				'group'       => 'content',
				'implemented' => true,
				'export'      => true,
				'import'      => true,
			),
			'templates'         => array(
				'label'       => __( 'تمپلیت Elementor', 'nobatmed-core' ),
				'description' => __( 'صفحات و sectionهای ذخیره‌شده — فاز بعد.', 'nobatmed-core' ),
				'group'       => 'elementor',
				'implemented' => false,
				'export'      => false,
				'import'      => false,
			),
			'demos'             => array(
				'label'       => __( 'پکیج دمو', 'nobatmed-core' ),
				'description' => __( 'Import یک‌کلیکی دمو پزشک/کلینیک — فاز بعد.', 'nobatmed-core' ),
				'group'       => 'demos',
				'implemented' => false,
				'export'      => false,
				'import'      => false,
			),
			'full_site'         => array(
				'label'       => __( 'بسته کامل سایت', 'nobatmed-core' ),
				'description' => __( 'ترکیب همه بخش‌های آماده — فاز بعد.', 'nobatmed-core' ),
				'group'       => 'demos',
				'implemented' => false,
				'export'      => false,
				'import'      => false,
			),
		);

		return apply_filters( 'nobatmed_import_export_sections', $sections );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function manifest_for_api(): array {
		$list = array();
		foreach ( self::sections() as $id => $section ) {
			$list[] = array_merge(
				array( 'id' => $id ),
				$section
			);
		}
		return $list;
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/import-export/manifest',
			array(
				'methods'             => 'GET',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'callback'            => static fn() => rest_ensure_response(
					array(
						'success' => true,
						'data'    => array(
							'format'   => self::FORMAT,
							'version'  => self::FORMAT_VER,
							'sections' => self::manifest_for_api(),
						),
					)
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/import-export/export',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'callback'            => array( self::class, 'rest_export' ),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/import-export/import',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'callback'            => array( self::class, 'rest_import' ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function rest_export( WP_REST_Request $request ): WP_REST_Response {
		$params   = $request->get_json_params();
		$selected = isset( $params['sections'] ) && is_array( $params['sections'] )
			? array_map( 'sanitize_key', $params['sections'] )
			: array_keys( self::exportable_sections() );

		$bundle = self::build_bundle( $selected );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'بسته export آماده شد.', 'nobatmed-core' ),
				'data'    => $bundle,
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function rest_import( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params['bundle'] ) || ! is_array( $params['bundle'] ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'فایل import نامعتبر است.', 'nobatmed-core' ),
				)
			);
		}

		$bundle   = $params['bundle'];
		$selected = isset( $params['sections'] ) && is_array( $params['sections'] )
			? array_map( 'sanitize_key', $params['sections'] )
			: array_keys( $bundle['sections'] ?? array() );
		$mode     = isset( $params['mode'] ) && 'replace' === $params['mode'] ? 'replace' : 'merge';

		if ( ( $bundle['format'] ?? '' ) !== self::FORMAT ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'فرمت فایل پشتیبانی نمی‌شود.', 'nobatmed-core' ),
				)
			);
		}

		$result = self::apply_bundle( $bundle, $selected, $mode );

		return rest_ensure_response(
			array(
				'success' => ! empty( $result['success'] ),
				'message' => $result['message'],
				'data'    => $result,
			)
		);
	}

	/**
	 * @param string[] $section_ids Section IDs.
	 * @return array<string,mixed>
	 */
	public static function build_bundle( array $section_ids ): array {
		$sections = array();
		foreach ( $section_ids as $id ) {
			if ( ! self::can_export( $id ) ) {
				continue;
			}
			$data = self::export_section( $id );
			if ( null !== $data ) {
				$sections[ $id ] = $data;
			}
		}

		return array(
			'format'       => self::FORMAT,
			'version'      => self::FORMAT_VER,
			'core_version' => NOBATMED_CORE_VERSION,
			'exported_at'  => current_time( 'mysql' ),
			'site_url'     => home_url(),
			'sections'     => $sections,
		);
	}

	/**
	 * @param array<string,mixed> $bundle   Bundle.
	 * @param string[]            $selected Section IDs.
	 * @param string              $mode     merge|replace.
	 * @return array<string,mixed>
	 */
	public static function apply_bundle( array $bundle, array $selected, string $mode ): array {
		$imported = array();
		$errors   = array();
		$payload  = isset( $bundle['sections'] ) && is_array( $bundle['sections'] ) ? $bundle['sections'] : array();

		foreach ( $selected as $id ) {
			if ( ! isset( $payload[ $id ] ) || ! self::can_import( $id ) ) {
				continue;
			}
			$ok = self::import_section( $id, $payload[ $id ], $mode );
			if ( is_wp_error( $ok ) ) {
				$errors[] = $ok->get_error_message();
			} else {
				$imported[] = $id;
			}
		}

		if ( empty( $imported ) && ! empty( $errors ) ) {
			return array(
				'success'  => false,
				'message'  => implode( ' ', $errors ),
				'imported' => $imported,
				'errors'   => $errors,
			);
		}

		return array(
			'success'  => true,
			'message'  => sprintf(
				/* translators: %d: number of imported sections */
				__( '%d بخش import شد.', 'nobatmed-core' ),
				count( $imported )
			),
			'imported' => $imported,
			'errors'   => $errors,
		);
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private static function exportable_sections(): array {
		return array_filter(
			self::sections(),
			static fn( $s ) => ! empty( $s['implemented'] ) && ! empty( $s['export'] )
		);
	}

	private static function can_export( string $id ): bool {
		$sections = self::sections();
		return isset( $sections[ $id ] ) && ! empty( $sections[ $id ]['implemented'] ) && ! empty( $sections[ $id ]['export'] );
	}

	private static function can_import( string $id ): bool {
		$sections = self::sections();
		return isset( $sections[ $id ] ) && ! empty( $sections[ $id ]['implemented'] ) && ! empty( $sections[ $id ]['import'] );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function export_section( string $id ): ?array {
		switch ( $id ) {
			case 'modules':
				return array(
					'enabled' => NobatMed_Module_Settings::get_enabled(),
				);
			case 'appearance':
				return NobatMed_Theme_Appearance::get_settings();
			case 'booking_schedules':
				return self::export_booking_schedules();
			case 'profiles':
				return self::export_profiles();
		}

		$custom = apply_filters( 'nobatmed_import_export_export_' . $id, null );
		return is_array( $custom ) ? $custom : null;
	}

	/**
	 * @param array<string,mixed> $data Exported data.
	 * @return true|WP_Error
	 */
	private static function import_section( string $id, array $data, string $mode ) {
		switch ( $id ) {
			case 'modules':
				if ( empty( $data['enabled'] ) || ! is_array( $data['enabled'] ) ) {
					return new WP_Error( 'invalid', __( 'داده ماژول نامعتبر.', 'nobatmed-core' ) );
				}
				NobatMed_Module_Settings::set_enabled( $data['enabled'] );
				return true;
			case 'appearance':
				NobatMed_Theme_Appearance::save( $data );
				return true;
			case 'booking_schedules':
				return self::import_booking_schedules( $data, $mode );
			case 'profiles':
				return self::import_profiles( $data, $mode );
		}

		$result = apply_filters( 'nobatmed_import_export_import_' . $id, null, $data, $mode );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( true === $result ) {
			return true;
		}
		return new WP_Error( 'unsupported', __( 'بخش import پشتیبانی نمی‌شود.', 'nobatmed-core' ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function export_booking_schedules(): array {
		if ( ! NobatMed_DB::table_exists( NobatMed_DB::TABLE_SCHEDULES ) ) {
			return array( 'rows' => array() );
		}

		global $wpdb;
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore

		return array(
			'rows' => is_array( $rows ) ? $rows : array(),
		);
	}

	/**
	 * @param array<string,mixed> $data Data.
	 * @return true|WP_Error
	 */
	private static function import_booking_schedules( array $data, string $mode ) {
		if ( ! NobatMed_DB::table_exists( NobatMed_DB::TABLE_SCHEDULES ) ) {
			return new WP_Error( 'no_table', __( 'جدول نوبت‌دهی وجود ندارد.', 'nobatmed-core' ) );
		}

		global $wpdb;
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$rows  = isset( $data['rows'] ) && is_array( $data['rows'] ) ? $data['rows'] : array();

		if ( 'replace' === $mode ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore
		}

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			unset( $row['id'] );
			$wpdb->insert( $table, $row ); // phpcs:ignore
		}

		return true;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function export_profiles(): array {
		$types = array( 'nm_doctor', 'nm_clinic', 'nm_service' );
		$out   = array();

		foreach ( $types as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			$posts = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => array( 'publish', 'draft' ),
					'posts_per_page' => -1,
				)
			);
			$out[ $type ] = array();
			foreach ( $posts as $post ) {
				$out[ $type ][] = array(
					'post_title'   => $post->post_title,
					'post_name'    => $post->post_name,
					'post_content' => $post->post_content,
					'post_status'  => $post->post_status,
					'meta'         => get_post_meta( $post->ID ),
				);
			}
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $data Data.
	 * @return true|WP_Error
	 */
	private static function import_profiles( array $data, string $mode ) {
		foreach ( $data as $post_type => $items ) {
			if ( ! post_type_exists( (string) $post_type ) || ! is_array( $items ) ) {
				continue;
			}

			if ( 'replace' === $mode ) {
				$existing = get_posts(
					array(
						'post_type'      => $post_type,
						'post_status'    => 'any',
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				);
				foreach ( $existing as $post_id ) {
					wp_delete_post( (int) $post_id, true );
				}
			}

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || empty( $item['post_title'] ) ) {
					continue;
				}

				$slug = isset( $item['post_name'] ) ? sanitize_title( (string) $item['post_name'] ) : '';
				$existing_id = $slug ? self::find_post_by_slug( (string) $post_type, $slug ) : 0;

				$postarr = array(
					'post_type'    => $post_type,
					'post_title'   => sanitize_text_field( (string) $item['post_title'] ),
					'post_name'    => $slug,
					'post_content' => isset( $item['post_content'] ) ? wp_kses_post( (string) $item['post_content'] ) : '',
					'post_status'  => isset( $item['post_status'] ) ? sanitize_key( (string) $item['post_status'] ) : 'publish',
				);

				if ( $existing_id > 0 ) {
					$postarr['ID'] = $existing_id;
					$post_id       = wp_update_post( $postarr, true );
				} else {
					$post_id = wp_insert_post( $postarr, true );
				}

				if ( is_wp_error( $post_id ) ) {
					continue;
				}

				if ( ! empty( $item['meta'] ) && is_array( $item['meta'] ) ) {
					foreach ( $item['meta'] as $meta_key => $meta_values ) {
						if ( ! is_string( $meta_key ) || str_starts_with( $meta_key, '_' ) ) {
							continue;
						}
						delete_post_meta( $post_id, $meta_key );
						$vals = is_array( $meta_values ) ? $meta_values : array( $meta_values );
						foreach ( $vals as $val ) {
							add_post_meta( $post_id, $meta_key, maybe_unserialize( $val ) );
						}
					}
				}
			}
		}

		return true;
	}

	private static function find_post_by_slug( string $post_type, string $slug ): int {
		$post = get_page_by_path( $slug, OBJECT, $post_type );
		return $post instanceof WP_Post ? (int) $post->ID : 0;
	}
}
