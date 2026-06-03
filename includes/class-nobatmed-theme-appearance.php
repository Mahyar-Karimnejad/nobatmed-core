<?php
/**
 * Theme appearance settings (colors, radius) for NobatMed theme.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Appearance settings stored in options and applied as CSS variables.
 */
class NobatMed_Theme_Appearance {

	public const OPTION_KEY = 'nobatmed_theme_appearance';

	/**
	 * Boot hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_frontend_css' ), 20 );
	}

	/**
	 * @return array<string,string|int>
	 */
	public static function defaults(): array {
		$defaults = array(
			'brand'       => '#3b82f6',
			'brand_2'     => '#6366f1',
			'accent'      => '#8b5cf6',
			'text'        => '#0f172a',
			'muted'       => '#64748b',
			'bg'          => '#ebeff6',
			'surface'     => '#ffffff',
			'border'      => '#e2e8f0',
			'radius'      => 14,
			'font_family' => 'Vazir, Tahoma, "Segoe UI", sans-serif',
		);

		return apply_filters( 'nobatmed_theme_appearance_defaults', $defaults );
	}

	/**
	 * @return array<string,string|int>
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
	 * @return array<string,string|int>
	 */
	public static function sanitize( array $input ): array {
		$out    = array();
		$colors = array( 'brand', 'brand_2', 'accent', 'text', 'muted', 'bg', 'surface', 'border' );

		foreach ( $colors as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$color = sanitize_hex_color( (string) $input[ $key ] );
				if ( $color ) {
					$out[ $key ] = $color;
				}
			}
		}

		if ( isset( $input['radius'] ) ) {
			$out['radius'] = max( 4, min( 32, (int) $input['radius'] ) );
		}

		if ( isset( $input['font_family'] ) ) {
			$out['font_family'] = sanitize_text_field( (string) $input['font_family'] );
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $input Settings.
	 */
	public static function save( array $input ): bool {
		$clean = self::sanitize( $input );
		$merged = wp_parse_args( $clean, self::get_settings() );
		return update_option( self::OPTION_KEY, $merged, false );
	}

	public static function reset(): bool {
		return delete_option( self::OPTION_KEY );
	}

	public static function is_theme_active(): bool {
		return 'nobatmed' === get_template();
	}

	/**
	 * CSS custom properties for :root.
	 */
	public static function build_css_variables(): string {
		$s = self::get_settings();

		$brand   = (string) $s['brand'];
		$brand_2 = (string) $s['brand_2'];
		$accent  = (string) $s['accent'];
		$radius  = (int) $s['radius'];

		$shadow = sprintf(
			'0 22px 60px %s, 0 0 0 1px %s',
			self::hex_to_rgba( $brand, 0.16 ),
			self::hex_to_rgba( $brand_2, 0.08 )
		);

		$gradient = sprintf(
			'linear-gradient(140deg, %s 0%%, %s 55%%, %s 100%%)',
			$brand,
			$brand_2,
			$accent
		);

		$css = ':root{';
		$css .= '--cl-brand:' . $brand . ';';
		$css .= '--cl-brand-2:' . $brand_2 . ';';
		$css .= '--cl-text:' . (string) $s['text'] . ';';
		$css .= '--cl-muted:' . (string) $s['muted'] . ';';
		$css .= '--cl-bg:' . (string) $s['bg'] . ';';
		$css .= '--cl-surface:' . (string) $s['surface'] . ';';
		$css .= '--cl-border:' . (string) $s['border'] . ';';
		$css .= '--cl-radius:' . $radius . 'px;';
		$css .= '--cl-shadow:' . $shadow . ';';
		$css .= '--cl-gradient:' . $gradient . ';';
		$css .= '}';

		$font = (string) $s['font_family'];
		if ( '' !== $font ) {
			$css .= 'body{font-family:' . $font . ';}';
		}

		return apply_filters( 'nobatmed_theme_appearance_css', $css, $s );
	}

	public static function enqueue_frontend_css(): void {
		if ( ! self::is_theme_active() ) {
			return;
		}

		$handle = 'nobatmed-base';
		if ( ! wp_style_is( $handle, 'enqueued' ) ) {
			return;
		}

		wp_add_inline_style( $handle, self::build_css_variables() );
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/appearance',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'success' => true,
							'data'    => self::get_for_api(),
						)
					),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'callback'            => array( self::class, 'rest_save' ),
				),
			)
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_for_api(): array {
		return array(
			'settings'    => self::get_settings(),
			'defaults'    => self::defaults(),
			'themeActive' => self::is_theme_active(),
			'themeName'   => wp_get_theme()->get( 'Name' ),
			'presets'     => self::presets(),
		);
	}

	/**
	 * @return array<string,array<string,string|int>>
	 */
	public static function presets(): array {
		return array(
			'blue'   => array(
				'label'   => __( 'آبی (پیش‌فرض)', 'nobatmed-core' ),
				'brand'   => '#3b82f6',
				'brand_2' => '#6366f1',
				'accent'  => '#8b5cf6',
				'bg'      => '#ebeff6',
			),
			'teal'   => array(
				'label'   => __( 'فیروزه‌ای', 'nobatmed-core' ),
				'brand'   => '#0d9488',
				'brand_2' => '#14b8a6',
				'accent'  => '#2dd4bf',
				'bg'      => '#ecfdf5',
			),
			'purple' => array(
				'label'   => __( 'بنفش', 'nobatmed-core' ),
				'brand'   => '#7c3aed',
				'brand_2' => '#8b5cf6',
				'accent'  => '#a78bfa',
				'bg'      => '#f5f3ff',
			),
			'rose'   => array(
				'label'   => __( 'رز', 'nobatmed-core' ),
				'brand'   => '#e11d48',
				'brand_2' => '#f43f5e',
				'accent'  => '#fb7185',
				'bg'      => '#fff1f2',
			),
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function rest_save( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'داده نامعتبر.', 'nobatmed-core' ),
				)
			);
		}

		if ( ! empty( $params['reset'] ) ) {
			self::reset();
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'تنظیمات ظاهر به پیش‌فرض بازگشت.', 'nobatmed-core' ),
					'data'    => self::get_for_api(),
				)
			);
		}

		$settings = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : $params;

		if ( ! self::save( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'ذخیره تنظیمات با خطا مواجه شد.', 'nobatmed-core' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'تنظیمات ظاهر ذخیره شد.', 'nobatmed-core' ),
				'data'    => self::get_for_api(),
			)
		);
	}

	private static function hex_to_rgba( string $hex, float $alpha ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, (string) $alpha );
	}
}
