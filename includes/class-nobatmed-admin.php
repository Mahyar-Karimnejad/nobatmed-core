<?php
/**
 * Admin UI bootstrap.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin handler.
 */
class NobatMed_Admin {

	public const PAGE_SLUG = 'nobatmed-core';

	/**
	 * @var NobatMed_Core
	 */
	private NobatMed_Core $core;

	public function __construct( NobatMed_Core $core ) {
		$this->core = $core;
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_menu' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'نوبت‌مد', 'nobatmed-core' ),
			__( 'نوبت‌مد', 'nobatmed-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin' ),
			'dashicons-heart',
			25
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'داشبورد', 'nobatmed-core' ),
			__( 'داشبورد', 'nobatmed-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		add_filter(
			'admin_body_class',
			static function ( string $classes ): string {
				return $classes . ' nobatmed-admin-page';
			}
		);

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'nobatmed-core-admin',
			NOBATMED_CORE_URL . 'assets/css/admin.css',
			array(),
			NOBATMED_CORE_VERSION
		);

		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script(
			'nobatmed-core-admin',
			NOBATMED_CORE_URL . 'assets/js/admin.js',
			array( 'wp-element', 'wp-api-fetch' ),
			NOBATMED_CORE_VERSION,
			true
		);

		wp_add_inline_script(
			'nobatmed-core-admin',
			'window.nobatmedCoreConfig = ' . wp_json_encode(
				array(
					'apiUrl'         => rest_url( 'nobatmed-core/v1' ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'licenseEnabled' => NOBATMED_LICENSE_ENABLED,
					'version'        => NOBATMED_CORE_VERSION,
					'adminUrl'       => admin_url(),
					'siteUrl'        => home_url(),
					'strings'        => array(
						'title'     => __( 'نوبت‌مد', 'nobatmed-core' ),
						'dashboard' => __( 'داشبورد', 'nobatmed-core' ),
						'modules'   => __( 'ماژول‌ها', 'nobatmed-core' ),
						'plugins'   => __( 'پلاگین‌ها', 'nobatmed-core' ),
						'booking'   => __( 'نوبت‌دهی', 'nobatmed-core' ),
						'addons'    => __( 'افزونه‌ها', 'nobatmed-core' ),
						'notices'   => __( 'اعلان‌ها', 'nobatmed-core' ),
					),
				)
			),
			'before'
		);
	}

	public function render_admin(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap nobatmed-core-wrap">
			<div id="nobatmed-core-admin" class="nobatmed-core-admin"></div>
		</div>
		<?php
	}

	public function register_rest_routes(): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/dashboard',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'can_manage' ),
				'callback'            => array( $this, 'rest_dashboard' ),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/modules',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => static fn() => rest_ensure_response(
						array(
							'modules' => NobatMed_Module_Settings::get_for_api(),
							'groups'  => NobatMed_Module_Registry::groups(),
						)
					),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_update_module' ),
				),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/plugins',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'can_manage' ),
				'callback'            => static fn() => rest_ensure_response( array( 'plugins' => NobatMed_Recommended_Plugins::get_for_api() ) ),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/plugins/install',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'can_install_plugins' ),
				'callback'            => array( $this, 'rest_install_plugin' ),
			)
		);

		register_rest_route(
			'nobatmed-core/v1',
			'/plugins/install-required',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'can_install_plugins' ),
				'callback'            => static fn() => rest_ensure_response( NobatMed_Plugin_Installer::install_all_required() ),
			)
		);
	}

	public function rest_dashboard(): WP_REST_Response {
		$modules = NobatMed_Module_Settings::get_for_api();
		$plugins = NobatMed_Recommended_Plugins::get_for_api();

		$enabled_modules = count( array_filter( $modules, static fn( $m ) => $m['enabled'] ) );
		$active_plugins  = count( array_filter( $plugins, static fn( $p ) => 'active' === $p['status'] ) );

		$profiles = array( 'doctors' => 0, 'clinics' => 0, 'services' => 0 );
		if ( post_type_exists( 'nm_doctor' ) ) {
			$profiles = array(
				'doctors'  => (int) wp_count_posts( 'nm_doctor' )->publish,
				'clinics'  => (int) wp_count_posts( 'nm_clinic' )->publish,
				'services' => (int) wp_count_posts( 'nm_service' )->publish,
			);
		}

		global $wpdb;
		$booking = array(
			'schedules'    => 0,
			'appointments' => 0,
			'tablesReady'  => NobatMed_DB::table_exists( NobatMed_DB::TABLE_APPOINTMENTS ),
		);
		if ( $booking['tablesReady'] ) {
			$st = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
			$ap = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );
			$booking['schedules']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$st}" ); // phpcs:ignore
			$booking['appointments'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ap}" ); // phpcs:ignore
		}

		return rest_ensure_response(
			array(
				'status'  => array(
					'version'        => NOBATMED_CORE_VERSION,
					'licenseEnabled' => NOBATMED_LICENSE_ENABLED,
					'woocommerce'    => class_exists( 'WooCommerce' ),
					'elementor'      => did_action( 'elementor/loaded' ) > 0,
					'theme'          => get_template(),
					'themeName'      => wp_get_theme()->get( 'Name' ),
					'siteUrl'        => home_url(),
					'dbVersion'      => NobatMed_DB::get_schema_version(),
					'dbReady'        => NobatMed_DB::table_exists( NobatMed_DB::TABLE_APPOINTMENTS ),
				),
				'stats'   => array(
					'modulesEnabled' => $enabled_modules,
					'modulesTotal'   => count( $modules ),
					'pluginsActive'  => $active_plugins,
					'pluginsTotal'   => count( $plugins ),
				),
				'moduleProgress' => NobatMed_Module_Settings::progress_stats(),
				'orbit'          => array(
					'notices' => NobatMed_Orbit_Bridge::get_notices(),
					'state'   => NobatMed_Orbit_Bridge::get_state(),
				),
				'modules'  => $modules,
				'plugins'  => $plugins,
				'profiles' => $profiles,
				'booking'  => $booking,
				'phase'    => array(
					'label' => __( 'فاز ۱ — MVP', 'nobatmed-core' ),
					'next'  => array(
						__( 'UI کامل نوبت‌دهی + تقویم شمسی', 'nobatmed-core' ),
						__( 'ویجت‌های Elementor', 'nobatmed-core' ),
						__( 'OTP + درگاه پرداخت', 'nobatmed-core' ),
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_update_module( WP_REST_Request $request ): WP_REST_Response {
		$params  = $request->get_json_params();
		$id      = isset( $params['id'] ) ? sanitize_key( (string) $params['id'] ) : '';
		$enabled = ! empty( $params['enabled'] );
		$all     = NobatMed_Module_Registry::all();

		if ( ! isset( $all[ $id ] ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'ماژول نامعتبر است.', 'nobatmed-core' ),
				)
			);
		}

		if ( ! NobatMed_Module_Registry::is_implemented( $id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'این ماژول هنوز توسعه داده نشده و قابل فعال‌سازی نیست.', 'nobatmed-core' ),
				)
			);
		}

		if ( ! NobatMed_Module_Settings::toggle( $id, $enabled ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'تغییر وضعیت ماژول ممکن نیست.', 'nobatmed-core' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'modules' => NobatMed_Module_Settings::get_for_api(),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_install_plugin( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		$id     = isset( $params['id'] ) ? sanitize_key( (string) $params['id'] ) : '';
		$result = NobatMed_Plugin_Installer::install_and_activate( $id );

		if ( is_wp_error( $result ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => $result['message'] ?? __( 'انجام شد.', 'nobatmed-core' ),
				'plugins' => NobatMed_Recommended_Plugins::get_for_api(),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public function can_install_plugins(): bool {
		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
	}
}
