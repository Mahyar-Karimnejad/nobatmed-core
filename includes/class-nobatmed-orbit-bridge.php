<?php
/**
 * Orbit Hub bridge — notices, telemetry, addon status (always available).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Client ↔ Orbit Hub integration.
 */
class NobatMed_Orbit_Bridge {

	public const NOTICES_OPTION = 'nobatmed_orbit_notices';
	public const STATE_OPTION   = 'nobatmed_orbit_state';
	public const CRON_HOOK      = 'nobatmed_orbit_daily_sync';

	/**
	 * Boot hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
		add_action( self::CRON_HOOK, array( self::class, 'sync_from_orbit' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', self::CRON_HOOK );
		}
	}

	/**
	 * Register core Orbit REST routes (GET/POST on nobatmed-core).
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/orbit/notices',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'success' => true,
							'data'    => array(
								'notices' => self::get_notices(),
								'state'   => self::get_state(),
							),
						)
					),
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/orbit/sync',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => static function (): WP_REST_Response {
						$result = self::sync_from_orbit();
						return rest_ensure_response( $result );
					},
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/orbit/status',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'success' => true,
							'data'    => self::build_telemetry(),
						)
					),
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/orbit/receive',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( self::class, 'verify_orbit_webhook' ),
					'callback'            => array( self::class, 'receive_notice_webhook' ),
				),
			)
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_notices(): array {
		$stored = get_option( self::NOTICES_OPTION, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_state(): array {
		$defaults = array(
			'last_sync'    => '',
			'last_error'   => '',
			'orbit_url'    => NOBATMED_CORE_ORBIT_API_URL,
		);
		$stored = get_option( self::STATE_OPTION, array() );
		return is_array( $stored ) ? wp_parse_args( $stored, $defaults ) : $defaults;
	}

	/**
	 * Pull notices + send telemetry to Orbit Hub.
	 *
	 * @return array<string,mixed>
	 */
	public static function sync_from_orbit(): array {
		$telemetry      = self::build_telemetry();
		$license_state  = function_exists( 'nobatmed_core_get_license_state' ) ? nobatmed_core_get_license_state() : array( 'status' => 'inactive' );
		$license_status = (string) ( $license_state['status'] ?? 'inactive' );

		$response = wp_remote_post(
			trailingslashit( NOBATMED_CORE_ORBIT_API_URL ) . 'notices/poll',
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'product'        => NOBATMED_CORE_PRODUCT_SLUG,
						'license_status' => $license_status,
						'telemetry'      => $telemetry,
					)
				),
			)
		);

		$state = self::get_state();
		$state['last_sync'] = current_time( 'mysql' );

		if ( is_wp_error( $response ) ) {
			$state['last_error'] = $response->get_error_message();
			update_option( self::STATE_OPTION, $state, false );
			return array(
				'success' => false,
				'message' => $state['last_error'],
				'data'    => array( 'notices' => self::get_notices(), 'state' => $state ),
			);
		}

		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$notices = array();

		if ( is_array( $body ) && ! empty( $body['success'] ) && ! empty( $body['data']['notices'] ) ) {
			$notices = $body['data']['notices'];
			update_option( self::NOTICES_OPTION, $notices, false );
		}

		$state['last_error']  = '';
		$state['telemetry']   = $telemetry;
		update_option( self::STATE_OPTION, $state, false );

		return array(
			'success' => true,
			'message' => __( 'همگام‌سازی Orbit انجام شد.', 'nobatmed-core' ),
			'data'    => array(
				'notices' => $notices ?: self::get_notices(),
				'state'   => $state,
			),
		);
	}

	/**
	 * Site telemetry for Orbit (modules, addons, versions).
	 *
	 * @return array<string,mixed>
	 */
	public static function build_telemetry(): array {
		$modules = NobatMed_Module_Settings::get_for_api();
		$enabled = array_values(
			array_filter(
				$modules,
				static fn( $m ) => ! empty( $m['enabled'] ) && ! empty( $m['implemented'] )
			)
		);

		$pending = array_values(
			array_filter(
				$modules,
				static fn( $m ) => empty( $m['implemented'] )
			)
		);

		return array(
			'domain'          => home_url(),
			'core_version'    => NOBATMED_CORE_VERSION,
			'theme'           => get_template(),
			'theme_name'      => wp_get_theme()->get( 'Name' ),
			'product'         => NOBATMED_CORE_PRODUCT_SLUG,
			'license_status'  => NOBATMED_LICENSE_ENABLED && function_exists( 'nobatmed_core_get_license_state' )
				? ( nobatmed_core_get_license_state()['status'] ?? 'inactive' )
				: 'dev',
			'enabled_modules' => wp_list_pluck( $enabled, 'id' ),
			'pending_modules' => wp_list_pluck( $pending, 'id' ),
			'addons'          => NobatMed_Module_Registry::scan_installed_addons(),
			'db_version'      => NobatMed_DB::get_schema_version(),
		);
	}

	/**
	 * Webhook permission (shared secret — filterable).
	 */
	public static function verify_orbit_webhook( WP_REST_Request $request ): bool {
		$secret = apply_filters( 'nobatmed_orbit_webhook_secret', get_option( 'nobatmed_orbit_webhook_secret', '' ) );
		if ( '' === $secret ) {
			return current_user_can( 'manage_options' );
		}
		$header = $request->get_header( 'x-nobatmed-orbit-secret' );
		return is_string( $header ) && hash_equals( $secret, $header );
	}

	/**
	 * Orbit Hub pushes a notice to this site (future marketing).
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function receive_notice_webhook( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Invalid payload' ) );
		}

		$notice = array(
			'id'       => isset( $params['id'] ) ? (int) $params['id'] : time(),
			'title'    => isset( $params['title'] ) ? sanitize_text_field( (string) $params['title'] ) : '',
			'message'  => isset( $params['message'] ) ? wp_kses_post( (string) $params['message'] ) : '',
			'type'     => isset( $params['type'] ) ? sanitize_text_field( (string) $params['type'] ) : 'info',
			'product'  => isset( $params['product'] ) ? sanitize_text_field( (string) $params['product'] ) : '',
			'received' => current_time( 'mysql' ),
		);

		if ( '' === $notice['title'] || '' === $notice['message'] ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'title and message required' ) );
		}

		$notices   = self::get_notices();
		$notices[] = $notice;
		update_option( self::NOTICES_OPTION, $notices, false );

		return rest_ensure_response( array( 'success' => true, 'message' => 'Notice stored' ) );
	}
}
