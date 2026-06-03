<?php
/**
 * Demo hub — fetch and install demos from nexaverse (future).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo catalog and install stub.
 */
class NobatMed_Demo_Hub {

	public const CATALOG_OPTION = 'nobatmed_demo_catalog_cache';

	/**
	 * Default remote URL (filterable before launch).
	 */
	public static function catalog_url(): string {
		return apply_filters(
			'nobatmed_demo_catalog_url',
			'https://nexaverse.ir/wp-json/nobatmed-demos/v1/catalog'
		);
	}

	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/demos/catalog',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'success' => true,
							'data'    => self::get_catalog(),
						)
					),
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/demos/install',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => array( self::class, 'rest_install' ),
				),
			)
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_catalog(): array {
		$placeholder = array(
			'remoteReady' => false,
			'catalogUrl'  => self::catalog_url(),
			'message'     => __( 'کاتالوگ دمو به‌زودی از nexaverse.ir در دسترس خواهد بود.', 'nobatmed-core' ),
			'demos'       => array(
				array(
					'id'          => 'doctor-single',
					'name'        => __( 'پزشک تکی', 'nobatmed-core' ),
					'description' => __( 'سایت تک‌پزشک با نوبت‌دهی — Import از Demo Hub', 'nobatmed-core' ),
					'status'      => 'coming_soon',
					'preview'     => '',
				),
				array(
					'id'          => 'clinic-multi',
					'name'        => __( 'کلینیک چندتخصصی', 'nobatmed-core' ),
					'description' => __( 'چند پزشک + کلینیک + WooCommerce', 'nobatmed-core' ),
					'status'      => 'coming_soon',
					'preview'     => '',
				),
			),
		);

		return apply_filters( 'nobatmed_demo_catalog', $placeholder );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function rest_install( WP_REST_Request $request ): WP_REST_Response {
		$params  = $request->get_json_params();
		$demo_id = isset( $params['id'] ) ? sanitize_key( (string) $params['id'] ) : '';

		if ( '' === $demo_id ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'دمو انتخاب نشده.', 'nobatmed-core' ),
				)
			);
		}

		/**
		 * Future: download bundle from catalog URL and run NobatMed_Import_Export.
		 *
		 * @param string $demo_id Demo slug.
		 */
		$result = apply_filters( 'nobatmed_demo_install', null, $demo_id );

		if ( is_array( $result ) && ! empty( $result['success'] ) ) {
			return rest_ensure_response( $result );
		}

		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'نصب دمو هنوز فعال نشده — پس از راه‌اندازی Demo Hub از nexaverse.ir.', 'nobatmed-core' ),
			)
		);
	}
}
