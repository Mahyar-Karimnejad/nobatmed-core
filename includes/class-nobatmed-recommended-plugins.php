<?php
/**
 * Recommended plugins for NobatMed setup.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recommended plugins list.
 */
class NobatMed_Recommended_Plugins {

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function all(): array {
		$plugins = array(
			array(
				'id'          => 'woocommerce',
				'name'        => 'WooCommerce',
				'description' => __( 'فروشگاه و پرداخت — پایه اکوسیستم نوبت‌مد.', 'nobatmed-core' ),
				'slug'        => 'woocommerce',
				'file'        => 'woocommerce/woocommerce.php',
				'required'    => true,
			),
			array(
				'id'          => 'elementor',
				'name'        => 'Elementor',
				'description' => __( 'صفحه‌ساز — ویجت‌های پزشکی و قالب.', 'nobatmed-core' ),
				'slug'        => 'elementor',
				'file'        => 'elementor/elementor.php',
				'required'    => true,
			),
			array(
				'id'          => 'rank-math',
				'name'        => 'Rank Math SEO',
				'description' => __( 'سئو Schema پزشک و کلینیک — پیشنهادی.', 'nobatmed-core' ),
				'slug'        => 'seo-by-rank-math',
				'file'        => 'seo-by-rank-math/rank-math.php',
				'required'    => false,
			),
			array(
				'id'          => 'litespeed-cache',
				'name'        => 'LiteSpeed Cache',
				'description' => __( 'کش و بهینه‌سازی سرعت.', 'nobatmed-core' ),
				'slug'        => 'litespeed-cache',
				'file'        => 'litespeed-cache/litespeed-cache.php',
				'required'    => false,
			),
		);

		return apply_filters( 'nobatmed_core_recommended_plugins', $plugins );
	}

	public static function get_by_id( string $plugin_id ): ?array {
		foreach ( self::all() as $plugin ) {
			if ( $plugin['id'] === $plugin_id ) {
				return $plugin;
			}
		}
		return null;
	}

	public static function get_plugin_state( string $plugin_file ): string {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			return 'missing';
		}
		if ( is_plugin_active( $plugin_file ) ) {
			return 'active';
		}
		return 'inactive';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_for_api(): array {
		$can_install  = current_user_can( 'install_plugins' );
		$can_activate = current_user_can( 'activate_plugins' );
		$list         = array();

		foreach ( self::all() as $plugin ) {
			$state  = self::get_plugin_state( $plugin['file'] );
			$action = self::get_action_meta( $state, $can_install, $can_activate );

			$list[] = array(
				'id'          => $plugin['id'],
				'name'        => $plugin['name'],
				'description' => $plugin['description'],
				'required'    => ! empty( $plugin['required'] ),
				'status'      => $state,
				'action'      => $action['type'],
				'actionLabel' => $action['label'],
			);
		}

		return $list;
	}

	/**
	 * @return array{type:string,label:string}
	 */
	private static function get_action_meta( string $state, bool $can_install, bool $can_activate ): array {
		if ( 'active' === $state ) {
			return array( 'type' => 'none', 'label' => __( 'فعال', 'nobatmed-core' ) );
		}
		if ( 'inactive' === $state ) {
			return array(
				'type'  => $can_activate ? 'activate' : 'none',
				'label' => __( 'فعال‌سازی', 'nobatmed-core' ),
			);
		}
		return array(
			'type'  => $can_install ? 'install' : 'none',
			'label' => __( 'نصب و فعال‌سازی', 'nobatmed-core' ),
		);
	}
}
