<?php
/**
 * Feature module registry — tracks dev status and Orbit product slugs.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Module registry.
 */
class NobatMed_Module_Registry {

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function all(): array {
		$modules = array(
			// --- Core (فاز ۱ — پیاده‌سازی‌شده) ---
			'roles'             => self::entry(
				'roles',
				__( 'نقش‌ها و دسترسی', 'nobatmed-core' ),
				__( 'پزشک، بیمار، منشی و capabilityها.', 'nobatmed-core' ),
				'core',
				'groups',
				1,
				array(
					'implemented' => true,
					'devStatus'   => 'done',
					'locked'      => true,
					'default'     => true,
					'type'        => 'core',
				)
			),
			'profiles'          => self::entry(
				'profiles',
				__( 'پروفایل پزشک و کلینیک', 'nobatmed-core' ),
				__( 'CPT پزشک، کلینیک، خدمات و متادیتا.', 'nobatmed-core' ),
				'core',
				'heart',
				1,
				array(
					'implemented' => true,
					'devStatus'   => 'done',
					'locked'      => true,
					'default'     => true,
					'type'        => 'core',
				)
			),
			'booking'           => self::entry(
				'booking',
				__( 'نوبت‌دهی', 'nobatmed-core' ),
				__( 'برنامه کاری، اسلات، رزرو — UI در حال تکمیل.', 'nobatmed-core' ),
				'core',
				'calendar-alt',
				1,
				array(
					'implemented' => true,
					'devStatus'   => 'progress',
					'default'     => true,
					'type'        => 'core',
				)
			),
			'orbit-bridge'      => self::entry(
				'orbit-bridge',
				__( 'اتصال Orbit Hub', 'nobatmed-core' ),
				__( 'دریافت نوتیس مارکتینگ و گزارش وضعیت ماژول/add-on.', 'nobatmed-core' ),
				'core',
				'cloud',
				1,
				array(
					'implemented' => true,
					'devStatus'   => 'progress',
					'default'     => true,
					'locked'      => true,
					'type'        => 'core',
				)
			),

			// --- فاز ۱ — در صف توسعه ---
			'elementor-widgets'   => self::entry(
				'elementor-widgets',
				__( 'ویجت‌های Elementor (پایه)', 'nobatmed-core' ),
				__( '۱۰ ویجت رایگان در Core — فرم نوبت، لیست پزشک، جستجو.', 'nobatmed-core' ),
				'elementor',
				'layout',
				1,
				array(
					'requires'        => 'elementor/elementor.php',
					'orbitProduct'    => 'nobatmed-elementor',
					'addonPluginFile' => '',
				)
			),
			'elementor-widget-pack' => self::entry(
				'elementor-widget-pack',
				__( 'Elementor Widget Pack', 'nobatmed-core' ),
				__( 'پک Premium — ۳۲+ ویجت اضافی با خرید addon جداگانه.', 'nobatmed-core' ),
				'addons',
				'star-filled',
				1,
				array(
					'type'            => 'addon',
					'requires'        => 'elementor/elementor.php',
					'orbitProduct'    => 'nobatmed-elementor-pack',
					'addonPluginFile' => 'nobatmed-elementor-pack/nobatmed-elementor-pack.php',
				)
			),
			'otp-sms'           => self::entry(
				'otp-sms',
				__( 'OTP پیامکی', 'nobatmed-core' ),
				__( 'IPPanel، کاوه‌نگار، Melipayamak و…', 'nobatmed-core' ),
				'integrations',
				'email',
				1,
				array( 'orbitProduct' => 'nobatmed-otp' )
			),
			'payment'           => self::entry(
				'payment',
				__( 'درگاه پرداخت', 'nobatmed-core' ),
				__( 'زرین‌پال / آیدی‌پی — بیعانه نوبت.', 'nobatmed-core' ),
				'integrations',
				'money-alt',
				1,
				array(
					'requires'     => 'woocommerce/woocommerce.php',
					'orbitProduct' => 'nobatmed-payment',
				)
			),
			'doctor-register'   => self::entry(
				'doctor-register',
				__( 'ثبت‌نام پزشک + تأیید', 'nobatmed-core' ),
				__( 'فرم ثبت‌نام و تأیید/رد توسط مدیر.', 'nobatmed-core' ),
				'core',
				'id-alt',
				1,
				array( 'orbitProduct' => 'nobatmed-doctor-register' )
			),
			'demo-import'       => self::entry(
				'demo-import',
				__( 'Import دمو', 'nobatmed-core' ),
				__( '۲ دمو یک‌کلیکی (پزشک تکی + کلینیک).', 'nobatmed-core' ),
				'core',
				'download',
				1,
				array( 'orbitProduct' => 'nobatmed-demo' )
			),
			'schema-seo'        => self::entry(
				'schema-seo',
				__( 'Schema SEO', 'nobatmed-core' ),
				__( 'Physician, MedicalClinic, FAQPage.', 'nobatmed-core' ),
				'integrations',
				'search',
				2,
				array( 'orbitProduct' => 'nobatmed-schema' )
			),

			// --- Add-on فاز ۲ ---
			'telemedicine'      => self::entry(
				'telemedicine',
				__( 'تله‌مدیسین', 'nobatmed-core' ),
				__( 'چت، ویدیو، مشاوره آنلاین.', 'nobatmed-core' ),
				'addons',
				'video-alt3',
				2,
				array(
					'type'            => 'addon',
					'orbitProduct'    => 'nobatmed-telemedicine',
					'addonPluginFile' => 'nobatmed-telemedicine/nobatmed-telemedicine.php',
				)
			),
			'multi-clinic'      => self::entry(
				'multi-clinic',
				__( 'Multi-Clinic', 'nobatmed-core' ),
				__( 'چند مطب و چند شعبه.', 'nobatmed-core' ),
				'addons',
				'building',
				2,
				array(
					'type'            => 'addon',
					'orbitProduct'    => 'nobatmed-multi-clinic',
					'addonPluginFile' => 'nobatmed-multi-clinic/nobatmed-multi-clinic.php',
				)
			),
			'wallet'            => self::entry(
				'wallet',
				__( 'کیف پول', 'nobatmed-core' ),
				__( 'شارژ و برداشت WooCommerce.', 'nobatmed-core' ),
				'addons',
				'wallet',
				2,
				array(
					'type'         => 'addon',
					'orbitProduct' => 'nobatmed-wallet',
					'requires'     => 'woocommerce/woocommerce.php',
				)
			),
			'subscription'      => self::entry(
				'subscription',
				__( 'پلن اشتراک پزشک', 'nobatmed-core' ),
				__( 'اشتراک ماهانه متخصص.', 'nobatmed-core' ),
				'addons',
				'tickets-alt',
				2,
				array( 'type' => 'addon', 'orbitProduct' => 'nobatmed-subscription' )
			),
			'reports'           => self::entry(
				'reports',
				__( 'گزارش‌گیری', 'nobatmed-core' ),
				__( 'درآمد، لغو، پرشدگی.', 'nobatmed-core' ),
				'addons',
				'chart-bar',
				2,
				array( 'type' => 'addon', 'orbitProduct' => 'nobatmed-reports' )
			),

			// --- Add-on فاز ۳ ---
			'lms-therapy'       => self::entry(
				'lms-therapy',
				__( 'LMS تراپی', 'nobatmed-core' ),
				__( 'دوره، جلسه گروهی، گواهینامه.', 'nobatmed-core' ),
				'addons',
				'welcome-learn-more',
				3,
				array(
					'type'            => 'addon',
					'orbitProduct'    => 'nobatmed-lms',
					'addonPluginFile' => 'nobatmed-lms-therapy/nobatmed-lms-therapy.php',
				)
			),
			'emr-lite'          => self::entry(
				'emr-lite',
				__( 'EMR Lite', 'nobatmed-core' ),
				__( 'پرونده بیمار، timeline، آپلود آزمایش.', 'nobatmed-core' ),
				'addons',
				'media-document',
				3,
				array( 'type' => 'addon', 'orbitProduct' => 'nobatmed-emr' )
			),
			'crm'               => self::entry(
				'crm',
				__( 'CRM بیمار', 'nobatmed-core' ),
				__( 'برچسب، کمپین SMS، یادآوری.', 'nobatmed-core' ),
				'addons',
				'businessman',
				3,
				array( 'type' => 'addon', 'orbitProduct' => 'nobatmed-crm' )
			),
		);

		return apply_filters( 'nobatmed_core_module_registry', $modules );
	}

	/**
	 * Build module entry with defaults for pending modules.
	 *
	 * @param array<string,mixed> $extra Extra fields.
	 * @return array<string,mixed>
	 */
	private static function entry(
		string $id,
		string $name,
		string $description,
		string $group,
		string $icon,
		int $phase,
		array $extra = array()
	): array {
		$defaults = array(
			'id'               => $id,
			'name'             => $name,
			'description'      => $description,
			'group'            => $group,
			'icon'             => $icon,
			'phase'            => $phase,
			'implemented'      => false,
			'devStatus'        => 'pending',
			'locked'           => false,
			'default'          => false,
			'coming_soon'      => true,
			'type'             => 'core',
			'orbitProduct'     => 'nobatmed-' . $id,
			'addonPluginFile'  => '',
			'requires'         => '',
		);

		return array_merge( $defaults, $extra );
	}

	/**
	 * @return array<string,string>
	 */
	public static function groups(): array {
		return array(
			'core'         => __( 'هسته', 'nobatmed-core' ),
			'elementor'    => __( 'Elementor', 'nobatmed-core' ),
			'integrations' => __( 'یکپارچگی', 'nobatmed-core' ),
			'addons'       => __( 'افزونه‌ها (Add-on)', 'nobatmed-core' ),
		);
	}

	public static function is_locked( string $module_id ): bool {
		$all = self::all();
		return ! empty( $all[ $module_id ]['locked'] );
	}

	public static function is_implemented( string $module_id ): bool {
		$all = self::all();
		return ! empty( $all[ $module_id ]['implemented'] );
	}

	public static function can_toggle( string $module_id ): bool {
		$all = self::all();
		if ( ! isset( $all[ $module_id ] ) ) {
			return false;
		}
		$m = $all[ $module_id ];
		if ( ! empty( $m['locked'] ) ) {
			return false;
		}
		return ! empty( $m['implemented'] );
	}

	/**
	 * Detect installed NobatMed add-on plugins.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function scan_installed_addons(): array {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$list = array();
		foreach ( self::all() as $module ) {
			if ( 'addon' !== ( $module['type'] ?? 'core' ) ) {
				continue;
			}
			$file = (string) ( $module['addonPluginFile'] ?? '' );
			if ( '' === $file ) {
				continue;
			}
			$list[] = array(
				'id'       => $module['id'],
				'product'  => $module['orbitProduct'] ?? '',
				'file'     => $file,
				'installed'=> file_exists( WP_PLUGIN_DIR . '/' . $file ),
				'active'   => is_plugin_active( $file ),
			);
		}
		return $list;
	}
}
