<?php
/**
 * License bridge — فقط وقتی NOBATMED_LICENSE_ENABLED = true فعال می‌شود.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

const NOBATMED_CORE_PRODUCT_SLUG  = 'nobatmed-suite';
const NOBATMED_CORE_OPTION_KEY    = 'nobatmed_core_license';
const NOBATMED_CORE_ORBIT_API_URL = 'https://nexaverse.ir/wp-json/nobatmed-orbit/v1';

/**
 * Boot license hooks.
 *
 * @return void
 */
function nobatmed_core_boot_license(): void {
	add_action( 'admin_init', 'nobatmed_core_handle_submit' );
	add_action( 'rest_api_init', 'nobatmed_core_register_license_routes' );
}

/**
 * Get stored license data.
 *
 * @return array<string,mixed>
 */
function nobatmed_core_get_license_state(): array {
	$defaults = array(
		'license_key' => '',
		'status'      => 'inactive',
		'message'     => '',
		'expires_at'  => '',
		'instance'    => '',
		'notices'     => array(),
	);
	$saved    = get_option( NOBATMED_CORE_OPTION_KEY, array() );

	return is_array( $saved ) ? wp_parse_args( $saved, $defaults ) : $defaults;
}

/**
 * Save license state.
 *
 * @param array<string,mixed> $state State.
 * @return void
 */
function nobatmed_core_save_license_state( array $state ): void {
	update_option( NOBATMED_CORE_OPTION_KEY, $state, false );
}

/**
 * Is license valid?
 *
 * @return bool
 */
function nobatmed_core_is_license_valid(): bool {
	if ( ! NOBATMED_LICENSE_ENABLED ) {
		return true;
	}

	$state = nobatmed_core_get_license_state();
	return 'active' === $state['status'];
}

/**
 * Call orbit API.
 *
 * @param string                $route Route.
 * @param array<string, string> $body Body.
 * @return array<string,mixed>
 */
function nobatmed_core_orbit_request( string $route, array $body ): array {
	$url = trailingslashit( NOBATMED_CORE_ORBIT_API_URL ) . ltrim( $route, '/' );
	$res = wp_remote_post(
		$url,
		array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $res ) ) {
		return array( 'success' => false, 'message' => $res->get_error_message() );
	}

	$decoded = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	return is_array( $decoded ) ? $decoded : array( 'success' => false, 'message' => 'پاسخ دریافتی معتبر نیست.' );
}

/**
 * Handle legacy form submit.
 *
 * @return void
 */
function nobatmed_core_handle_submit(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['nobatmed_core_action'] ) || empty( $_POST['_wpnonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'nobatmed_core_license' ) ) {
		return;
	}

	$state  = nobatmed_core_get_license_state();
	$action = sanitize_text_field( wp_unslash( $_POST['nobatmed_core_action'] ) );
	$key    = isset( $_POST['nobatmed_core_license_key'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['nobatmed_core_license_key'] ) ) ) : '';

	if ( '' !== $key ) {
		$state['license_key'] = $key;
	}

	nobatmed_core_process_license_action( $action, $state, $key );
	nobatmed_core_save_license_state( $state );
	wp_safe_redirect( admin_url( 'admin.php?page=nobatmed-core' ) );
	exit;
}

/**
 * Process license action.
 *
 * @param string              $action Action.
 * @param array<string,mixed> $state  State by ref.
 * @param string              $key    Key.
 * @return void
 */
function nobatmed_core_process_license_action( string $action, array &$state, string $key = '' ): void {
	$payload = array(
		'license_key'    => '' !== $key ? $key : (string) $state['license_key'],
		'domain'         => home_url(),
		'product'        => NOBATMED_CORE_PRODUCT_SLUG,
		'instance'       => (string) $state['instance'],
		'license_status' => (string) $state['status'],
	);

	if ( 'activate' === $action ) {
		$result = nobatmed_core_orbit_request( 'activate', $payload );
		if ( ! empty( $result['success'] ) ) {
			$data                = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
			$state['status']     = isset( $data['status'] ) ? (string) $data['status'] : 'active';
			$state['instance']   = isset( $data['instance'] ) ? (string) $data['instance'] : $state['instance'];
			$state['expires_at'] = isset( $data['expires_at'] ) ? (string) $data['expires_at'] : '';
			$state['notices']    = isset( $data['notices'] ) && is_array( $data['notices'] ) ? $data['notices'] : array();
			$state['message']    = isset( $result['message'] ) ? (string) $result['message'] : 'لایسنس با موفقیت فعال شد.';
		} else {
			$state['status']  = 'inactive';
			$state['message'] = isset( $result['message'] ) ? (string) $result['message'] : 'فعال‌سازی ناموفق بود.';
		}
	}

	if ( 'deactivate' === $action ) {
		$result = nobatmed_core_orbit_request( 'deactivate', $payload );
		if ( ! empty( $result['success'] ) ) {
			$state['status']   = 'inactive';
			$state['instance'] = '';
		}
		$state['message'] = isset( $result['message'] ) ? (string) $result['message'] : 'لایسنس غیرفعال شد.';
	}

	if ( 'sync_notices' === $action ) {
		$result = nobatmed_core_orbit_request(
			'notices/poll',
			array(
				'product'        => NOBATMED_CORE_PRODUCT_SLUG,
				'license_status' => (string) $state['status'],
			)
		);
		if ( ! empty( $result['success'] ) && ! empty( $result['data']['notices'] ) && is_array( $result['data']['notices'] ) ) {
			$state['notices'] = $result['data']['notices'];
			$state['message'] = 'نوتیس‌ها همگام‌سازی شد.';
		}
	}
}

/**
 * Register license REST routes.
 *
 * @return void
 */
function nobatmed_core_register_license_routes(): void {
	register_rest_route(
		'nobatmed-core/v1',
		'/state',
		array(
			array(
				'methods'             => 'GET',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'callback'            => static function (): WP_REST_Response {
					return rest_ensure_response(
						array(
							'success' => true,
							'data'    => array(
								'state' => nobatmed_core_get_license_state(),
							),
						)
					);
				},
			),
		)
	);

	register_rest_route(
		'nobatmed-core/v1',
		'/action',
		array(
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'callback'            => 'nobatmed_core_api_action',
			),
		)
	);
}

/**
 * Handle react license actions.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function nobatmed_core_api_action( WP_REST_Request $request ): WP_REST_Response {
	$params = $request->get_json_params();
	$action = isset( $params['action'] ) ? sanitize_text_field( (string) $params['action'] ) : '';
	$key    = isset( $params['license_key'] ) ? strtoupper( sanitize_text_field( (string) $params['license_key'] ) ) : '';
	$state  = nobatmed_core_get_license_state();

	nobatmed_core_process_license_action( $action, $state, $key );
	nobatmed_core_save_license_state( $state );

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'state' => $state,
			),
		)
	);
}
