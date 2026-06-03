<?php
/**
 * Theme appearance settings (colors, radius, fonts) for NobatMed theme.
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
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_font_assets' ), 15 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_frontend_css' ), 20 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_font_assets' ), 15 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_css' ), 25 );
		add_action( 'elementor/frontend/after_enqueue_styles', array( self::class, 'enqueue_elementor_font_css' ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$defaults = array(
			'brand'               => '#3b82f6',
			'brand_2'             => '#6366f1',
			'accent'              => '#8b5cf6',
			'text'                => '#0f172a',
			'muted'               => '#64748b',
			'bg'                  => '#ebeff6',
			'surface'             => '#ffffff',
			'border'              => '#e2e8f0',
			'radius'              => 14,
			'font_mode'           => 'default',
			'font_preset'         => 'vazir',
			'font_family_name'    => 'Vazir',
			'font_regular_id'     => 0,
			'font_bold_id'        => 0,
			'font_external_url'   => '',
			'font_apply_frontend' => true,
			'font_apply_admin'    => true,
			'font_apply_elementor'=> true,
		);

		return apply_filters( 'nobatmed_theme_appearance_defaults', $defaults );
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

		if ( isset( $input['font_mode'] ) ) {
			$modes = array( 'default', 'preset', 'upload', 'external' );
			$mode  = sanitize_key( (string) $input['font_mode'] );
			if ( in_array( $mode, $modes, true ) ) {
				$out['font_mode'] = $mode;
			}
		}

		if ( isset( $input['font_preset'] ) ) {
			$preset = sanitize_key( (string) $input['font_preset'] );
			if ( isset( self::font_presets()[ $preset ] ) ) {
				$out['font_preset'] = $preset;
			}
		}

		if ( isset( $input['font_family_name'] ) ) {
			$name = sanitize_text_field( (string) $input['font_family_name'] );
			$name = preg_replace( '/[^a-zA-Z0-9\x{0600}-\x{06FF}\-_ ]/u', '', $name );
			if ( '' !== $name ) {
				$out['font_family_name'] = $name;
			}
		}

		foreach ( array( 'font_regular_id', 'font_bold_id' ) as $id_key ) {
			if ( isset( $input[ $id_key ] ) ) {
				$out[ $id_key ] = max( 0, (int) $input[ $id_key ] );
			}
		}

		if ( isset( $input['font_external_url'] ) ) {
			$out['font_external_url'] = esc_url_raw( (string) $input['font_external_url'] );
		}

		foreach ( array( 'font_apply_frontend', 'font_apply_admin', 'font_apply_elementor' ) as $bool_key ) {
			if ( array_key_exists( $bool_key, $input ) ) {
				$out[ $bool_key ] = ! empty( $input[ $bool_key ] );
			}
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $input Settings.
	 */
	public static function save( array $input ): bool {
		$clean  = self::sanitize( $input );
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
	 * @return array<string,array<string,string>>
	 */
	public static function font_presets(): array {
		return array(
			'vazir'    => array(
				'label'   => __( 'Vazir (پیش‌فرض)', 'nobatmed-core' ),
				'family'  => 'Vazir, Tahoma, "Segoe UI", sans-serif',
				'css_url' => 'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css',
			),
			'samim'    => array(
				'label'   => __( 'Samim', 'nobatmed-core' ),
				'family'  => 'Samim, Tahoma, sans-serif',
				'css_url' => 'https://cdn.jsdelivr.net/gh/rastikerdar/samim-font@v4.0.2/dist/font-face.css',
			),
			'shabnam'  => array(
				'label'   => __( 'Shabnam', 'nobatmed-core' ),
				'family'  => 'Shabnam, Tahoma, sans-serif',
				'css_url' => 'https://cdn.jsdelivr.net/gh/rastikerdar/shabnam-font@v5.0.1/dist/font-face.css',
			),
			'iransans' => array(
				'label'   => __( 'IRANSans', 'nobatmed-core' ),
				'family'  => 'IRANSans, Tahoma, sans-serif',
				'css_url' => 'https://cdn.jsdelivr.net/gh/rastikerdar/iran-sans-fonts@v1.0.0/dist/font-face.css',
			),
		);
	}

	/**
	 * Font stack for theme, admin, Elementor widgets.
	 */
	public static function get_font_family_stack(): string {
		$s    = self::get_settings();
		$mode = (string) ( $s['font_mode'] ?? 'default' );

		if ( 'default' === $mode ) {
			return (string) self::font_presets()['vazir']['family'];
		}

		if ( 'preset' === $mode ) {
			$key = (string) ( $s['font_preset'] ?? 'vazir' );
			return (string) ( self::font_presets()[ $key ]['family'] ?? self::font_presets()['vazir']['family'] );
		}

		if ( 'upload' === $mode ) {
			$name = trim( (string) ( $s['font_family_name'] ?? 'CustomFont' ) );
			return $name . ', Tahoma, "Segoe UI", sans-serif';
		}

		if ( 'external' === $mode ) {
			$name = trim( (string) ( $s['font_family_name'] ?? 'CustomFont' ) );
			return $name . ', Tahoma, sans-serif';
		}

		return (string) self::font_presets()['vazir']['family'];
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_font_files_for_api(): array {
		$s = self::get_settings();
		return array(
			'regular' => self::attachment_payload( (int) ( $s['font_regular_id'] ?? 0 ) ),
			'bold'    => self::attachment_payload( (int) ( $s['font_bold_id'] ?? 0 ) ),
		);
	}

	/**
	 * @return array{id:int,url:string,filename:string}|null
	 */
	private static function attachment_payload( int $attachment_id ): ?array {
		if ( $attachment_id <= 0 ) {
			return null;
		}
		$url = wp_get_attachment_url( $attachment_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return null;
		}
		return array(
			'id'       => $attachment_id,
			'url'      => $url,
			'filename' => basename( $url ),
		);
	}

	/**
	 * @font-face rules for uploaded fonts.
	 */
	public static function build_font_face_css(): string {
		$s    = self::get_settings();
		$mode = (string) ( $s['font_mode'] ?? 'default' );

		if ( 'upload' !== $mode ) {
			return '';
		}

		$name = trim( (string) ( $s['font_family_name'] ?? 'CustomFont' ) );
		if ( '' === $name ) {
			return '';
		}

		$css = '';
		$regular = self::attachment_payload( (int) ( $s['font_regular_id'] ?? 0 ) );
		$bold    = self::attachment_payload( (int) ( $s['font_bold_id'] ?? 0 ) );

		if ( $regular ) {
			$css .= self::font_face_rule( $name, 400, 'normal', $regular['url'] );
		}
		if ( $bold ) {
			$css .= self::font_face_rule( $name, 700, 'normal', $bold['url'] );
		}

		return apply_filters( 'nobatmed_theme_appearance_font_face_css', $css, $s );
	}

	private static function font_face_rule( string $family, int $weight, string $style, string $url ): string {
		$format = self::detect_font_format( $url );
		return sprintf(
			"@font-face{font-family:'%s';src:url('%s') format('%s');font-weight:%d;font-style:%s;font-display:swap;}",
			esc_attr( $family ),
			esc_url( $url ),
			esc_attr( $format ),
			$weight,
			esc_attr( $style )
		);
	}

	private static function detect_font_format( string $url ): string {
		$ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
		return match ( $ext ) {
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'otf'   => 'opentype',
			'ttf'   => 'truetype',
			default => 'woff2',
		};
	}

	/**
	 * CSS custom properties + font-family.
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

		$font_stack = self::get_font_family_stack();

		$css  = self::build_font_face_css();
		$css .= ':root{';
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
		$css .= '--cl-font-family:' . $font_stack . ';';
		$css .= '--nm-font-family:' . $font_stack . ';';
		$css .= '}';

		if ( ! empty( $s['font_apply_frontend'] ) ) {
			$css .= 'body{font-family:var(--cl-font-family);}';
		}

		return apply_filters( 'nobatmed_theme_appearance_css', $css, $s );
	}

	public static function build_admin_css(): string {
		$s = self::get_settings();
		if ( empty( $s['font_apply_admin'] ) ) {
			return '';
		}

		$css  = self::build_font_face_css();
		$css .= ':root{--nm-font-family:' . self::get_font_family_stack() . ';}';
		$css .= '.nobatmed-core-wrap,.nobatmed-core-wrap .nm-shell{font-family:var(--nm-font-family);}';

		return apply_filters( 'nobatmed_theme_appearance_admin_css', $css, $s );
	}

	public static function enqueue_font_assets(): void {
		if ( ! self::is_theme_active() || empty( self::get_settings()['font_apply_frontend'] ) ) {
			return;
		}

		self::enqueue_font_stylesheet( 'nobatmed-appearance-font' );
	}

	public static function enqueue_admin_font_assets( string $hook ): void {
		if ( 'toplevel_page_' . NobatMed_Admin::PAGE_SLUG !== $hook ) {
			return;
		}
		if ( empty( self::get_settings()['font_apply_admin'] ) ) {
			return;
		}
		self::enqueue_font_stylesheet( 'nobatmed-appearance-font-admin' );
	}

	private static function enqueue_font_stylesheet( string $handle ): void {
		$s    = self::get_settings();
		$mode = (string) ( $s['font_mode'] ?? 'default' );

		if ( in_array( $mode, array( 'default', 'preset' ), true ) ) {
			$key     = 'default' === $mode ? 'vazir' : (string) ( $s['font_preset'] ?? 'vazir' );
			$presets = self::font_presets();
			if ( isset( $presets[ $key ]['css_url'] ) ) {
				wp_enqueue_style( $handle, $presets[ $key ]['css_url'], array(), null );
			}
			return;
		}

		if ( 'external' === $mode && ! empty( $s['font_external_url'] ) ) {
			wp_enqueue_style( $handle, esc_url( (string) $s['font_external_url'] ), array(), null );
		}
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

	public static function enqueue_admin_css( string $hook ): void {
		if ( 'toplevel_page_' . NobatMed_Admin::PAGE_SLUG !== $hook ) {
			return;
		}

		$css = self::build_admin_css();
		if ( '' === $css || ! wp_style_is( 'nobatmed-core-admin', 'enqueued' ) ) {
			return;
		}

		wp_add_inline_style( 'nobatmed-core-admin', $css );
	}

	public static function enqueue_elementor_font_css(): void {
		$s = self::get_settings();
		if ( empty( $s['font_apply_elementor'] ) ) {
			return;
		}

		$css  = self::build_font_face_css();
		$css .= ':root{--cl-font-family:' . self::get_font_family_stack() . ';}';
		$css .= '.elementor-widget-container{font-family:var(--cl-font-family);}';

		$css = apply_filters( 'nobatmed_theme_appearance_elementor_css', $css, $s );

		if ( '' !== $css && wp_style_is( 'elementor-frontend', 'enqueued' ) ) {
			wp_add_inline_style( 'elementor-frontend', $css );
		}
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
			'fontPresets' => self::font_presets(),
			'fontFiles'   => self::get_font_files_for_api(),
			'fontStack'   => self::get_font_family_stack(),
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

/**
 * Font stack for theme / widgets / third-party code.
 */
function nobatmed_get_appearance_font_family(): string {
	return NobatMed_Theme_Appearance::get_font_family_stack();
}
