<?php
/**
 * OTP SMS module — Iranian SMS providers (phase 1 foundation).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * OTP SMS integration.
 */
class NobatMed_Module_Otp_Sms extends NobatMed_Module {

	public const OPTION_KEY = 'nobatmed_otp_settings';

	public function get_id(): string {
		return 'otp-sms';
	}

	public function get_name(): string {
		return __( 'OTP پیامکی', 'nobatmed-core' );
	}

	public function get_description(): string {
		return __( 'ارسال و تأیید OTP با IPPanel، کاوه‌نگار، Melipayamak و…', 'nobatmed-core' );
	}

	public function get_version(): string {
		return '0.1.0';
	}

	public function boot(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'        => false,
			'provider'       => 'kavenegar',
			'api_key'        => '',
			'api_secret'     => '',
			'sender_line'    => '',
			'code_length'    => 5,
			'code_ttl'       => 300,
			'resend_cooldown'=> 60,
			'dev_mode'       => true,
			'template'       => __( 'کد ورود نوبت‌مد: {code}', 'nobatmed-core' ),
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function providers(): array {
		return array(
			'kavenegar'   => 'Kavenegar',
			'melipayamak' => 'Melipayamak',
			'ippanel'     => 'IPPanel',
			'smsir'       => 'SMS.ir',
			'farazsms'    => 'Faraz SMS',
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_settings(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			return self::defaults();
		}
		return wp_parse_args( self::sanitize( $stored ), self::defaults() );
	}

	/**
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$out = array();

		if ( array_key_exists( 'enabled', $input ) ) {
			$out['enabled'] = ! empty( $input['enabled'] );
		}

		if ( isset( $input['provider'] ) ) {
			$provider = sanitize_key( (string) $input['provider'] );
			if ( isset( self::providers()[ $provider ] ) ) {
				$out['provider'] = $provider;
			}
		}

		foreach ( array( 'api_key', 'api_secret', 'sender_line', 'template' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( (string) $input[ $key ] );
			}
		}

		if ( isset( $input['code_length'] ) ) {
			$out['code_length'] = max( 4, min( 8, (int) $input['code_length'] ) );
		}

		if ( isset( $input['code_ttl'] ) ) {
			$out['code_ttl'] = max( 60, min( 900, (int) $input['code_ttl'] ) );
		}

		if ( isset( $input['resend_cooldown'] ) ) {
			$out['resend_cooldown'] = max( 30, min( 300, (int) $input['resend_cooldown'] ) );
		}

		if ( array_key_exists( 'dev_mode', $input ) ) {
			$out['dev_mode'] = ! empty( $input['dev_mode'] );
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $input Settings.
	 */
	public static function save_settings( array $input ): bool {
		$merged = wp_parse_args( self::sanitize( $input ), self::get_settings() );
		return update_option( self::OPTION_KEY, $merged, false );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_for_api(): array {
		$settings = self::get_settings();
		return array(
			'settings'  => $settings,
			'defaults'  => self::defaults(),
			'providers' => self::providers(),
			'ready'     => self::is_ready( $settings ),
		);
	}

	/**
	 * @param array<string,mixed> $settings Settings.
	 */
	public static function is_ready( array $settings ): bool {
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}
		if ( ! empty( $settings['dev_mode'] ) ) {
			return true;
		}
		return '' !== trim( (string) ( $settings['api_key'] ?? '' ) );
	}

	public function register_rest_routes(): void {
		$this->register_rest_route(
			'/otp/settings',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'success' => true,
							'data'    => self::get_for_api(),
						)
					),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_save_settings' ),
				),
			)
		);

		$this->register_rest_route(
			'/otp/send',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => array( $this, 'rest_send' ),
				),
			)
		);

		$this->register_rest_route(
			'/otp/verify',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => array( $this, 'rest_verify' ),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_save_settings( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'داده نامعتبر.', 'nobatmed-core' ),
				)
			);
		}

		$settings = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : $params;

		if ( ! self::save_settings( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'ذخیره تنظیمات OTP با خطا مواجه شد.', 'nobatmed-core' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'تنظیمات OTP ذخیره شد.', 'nobatmed-core' ),
				'data'    => self::get_for_api(),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_send( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		$phone  = self::normalize_phone( is_array( $params ) ? (string) ( $params['phone'] ?? '' ) : '' );

		if ( '' === $phone ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'شماره موبایل نامعتبر است.', 'nobatmed-core' ),
				)
			);
		}

		$settings = self::get_settings();
		if ( ! self::is_ready( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'ماژول OTP فعال یا پیکربندی نشده است.', 'nobatmed-core' ),
				)
			);
		}

		$cooldown_key = 'nobatmed_otp_cd_' . md5( $phone );
		if ( get_transient( $cooldown_key ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'لطفاً چند ثانیه صبر کنید و دوباره تلاش کنید.', 'nobatmed-core' ),
				)
			);
		}

		$length = (int) ( $settings['code_length'] ?? 5 );
		$code   = self::generate_code( $length );
		$ttl    = (int) ( $settings['code_ttl'] ?? 300 );

		set_transient( self::code_key( $phone ), $code, $ttl );
		set_transient( $cooldown_key, 1, (int) ( $settings['resend_cooldown'] ?? 60 ) );

		$message = str_replace( '{code}', $code, (string) ( $settings['template'] ?? '' ) );
		$sent    = self::dispatch_sms( $phone, $message, $settings );

		$response = array(
			'success' => $sent['success'],
			'message' => $sent['message'],
		);

		if ( ! empty( $settings['dev_mode'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$response['devCode'] = $code;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_verify( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		$phone  = self::normalize_phone( is_array( $params ) ? (string) ( $params['phone'] ?? '' ) : '' );
		$code   = is_array( $params ) ? sanitize_text_field( (string) ( $params['code'] ?? '' ) ) : '';

		if ( '' === $phone || '' === $code ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'شماره یا کد نامعتبر است.', 'nobatmed-core' ),
				)
			);
		}

		$stored = get_transient( self::code_key( $phone ) );
		if ( ! is_string( $stored ) || '' === $stored ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'کد منقضی شده — دوباره درخواست دهید.', 'nobatmed-core' ),
				)
			);
		}

		if ( ! hash_equals( $stored, $code ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'کد وارد شده نادرست است.', 'nobatmed-core' ),
				)
			);
		}

		delete_transient( self::code_key( $phone ) );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'شماره تأیید شد.', 'nobatmed-core' ),
			)
		);
	}

	private static function code_key( string $phone ): string {
		return 'nobatmed_otp_' . md5( $phone );
	}

	private static function generate_code( int $length ): string {
		$max = (int) str_repeat( '9', max( 4, min( 8, $length ) ) );
		$num = wp_rand( 0, $max );
		return str_pad( (string) $num, $length, '0', STR_PAD_LEFT );
	}

	public static function normalize_phone( string $phone ): string {
		$digits = preg_replace( '/\D+/', '', $phone ) ?? '';
		if ( str_starts_with( $digits, '98' ) && strlen( $digits ) >= 12 ) {
			$digits = '0' . substr( $digits, 2 );
		}
		if ( str_starts_with( $digits, '9' ) && 10 === strlen( $digits ) ) {
			$digits = '0' . $digits;
		}
		if ( ! preg_match( '/^09\d{9}$/', $digits ) ) {
			return '';
		}
		return $digits;
	}

	/**
	 * @param array<string,mixed> $settings Settings.
	 * @return array{success:bool,message:string}
	 */
	private static function dispatch_sms( string $phone, string $message, array $settings ): array {
		if ( ! empty( $settings['dev_mode'] ) ) {
			return array(
				'success' => true,
				'message' => __( 'کد در حالت توسعه تولید شد (ارسال واقعی SMS بعداً).', 'nobatmed-core' ),
			);
		}

		/**
		 * Send OTP SMS via configured provider.
		 *
		 * @param bool   $sent     Default false.
		 * @param string $phone    Mobile number.
		 * @param string $message  SMS body.
		 * @param array  $settings Module settings.
		 */
		$result = apply_filters( 'nobatmed_otp_send_sms', null, $phone, $message, $settings );

		if ( is_array( $result ) && ! empty( $result['success'] ) ) {
			return array(
				'success' => true,
				'message' => (string) ( $result['message'] ?? __( 'پیامک ارسال شد.', 'nobatmed-core' ) ),
			);
		}

		return array(
			'success' => false,
			'message' => __( 'ارسال SMS هنوز به provider وصل نشده — dev_mode را فعال کنید.', 'nobatmed-core' ),
		);
	}
}
