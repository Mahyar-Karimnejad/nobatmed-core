<?php
/**
 * Plugin Name:       NobatMed Core
 * Description:       هسته ماژولار نوبت‌مد — پروفایل، نوبت‌دهی و اکوسیستم افزونه‌ها.
 * Version:           0.3.5
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Nexaverse
 * Text Domain:       nobatmed-core
 */

defined( 'ABSPATH' ) || exit;

const NOBATMED_CORE_VERSION = '0.3.5';
const NOBATMED_CORE_FILE    = __FILE__;
const NOBATMED_CORE_PATH    = __DIR__ . '/';

define( 'NOBATMED_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * فعال/غیرفعال کردن سیستم لایسنس.
 * فعلاً false — وقتی سایت nexaverse.ir آماده شد true کنید.
 */
const NOBATMED_LICENSE_ENABLED = false;

require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-db.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-activator.php';
require_once NOBATMED_CORE_PATH . 'includes/interfaces/interface-nobatmed-module.php';
require_once NOBATMED_CORE_PATH . 'includes/abstracts/class-nobatmed-module.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-module-registry.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-module-settings.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-recommended-plugins.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-plugin-installer.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-module-manager.php';
require_once NOBATMED_CORE_PATH . 'includes/modules/class-module-roles.php';
require_once NOBATMED_CORE_PATH . 'includes/modules/class-module-profiles.php';
require_once NOBATMED_CORE_PATH . 'includes/modules/class-module-booking.php';
require_once NOBATMED_CORE_PATH . 'includes/modules/class-module-elementor.php';
require_once NOBATMED_CORE_PATH . 'includes/modules/class-module-otp-sms.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-classic-editor.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-orbit-bridge.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-theme-appearance.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-import-export.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-demo-hub.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-admin.php';
require_once NOBATMED_CORE_PATH . 'includes/class-nobatmed-core.php';
require_once NOBATMED_CORE_PATH . 'includes/license.php';

register_activation_hook( __FILE__, array( 'NobatMed_Activator', 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		nobatmed_core()->init();
	},
	5
);
