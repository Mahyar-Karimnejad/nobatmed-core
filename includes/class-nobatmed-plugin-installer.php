<?php
/**
 * Install plugins from WordPress.org repository.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin installer.
 */
class NobatMed_Plugin_Installer {

	private static function load_dependencies(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public static function install_and_activate( string $plugin_id ) {
		$plugin = NobatMed_Recommended_Plugins::get_by_id( $plugin_id );
		if ( null === $plugin ) {
			return new WP_Error( 'invalid_plugin', __( 'پلاگین نامعتبر است.', 'nobatmed-core' ), array( 'status' => 400 ) );
		}

		self::load_dependencies();
		$file = $plugin['file'];

		if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
			return self::activate_plugin_file( $file );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'forbidden', __( 'شما اجازه نصب پلاگین را ندارید.', 'nobatmed-core' ), array( 'status' => 403 ) );
		}

		$slug = $plugin['slug'] ?? '';
		if ( '' === $slug ) {
			return new WP_Error( 'no_slug', __( 'اسلاگ مخزن تعریف نشده.', 'nobatmed-core' ), array( 'status' => 400 ) );
		}

		$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
		if ( is_wp_error( $api ) ) {
			return new WP_Error( 'api_error', $api->get_error_message(), array( 'status' => 502 ) );
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( false === $result ) {
			return new WP_Error( 'install_failed', __( 'نصب پلاگین ناموفق بود.', 'nobatmed-core' ), array( 'status' => 500 ) );
		}

		return self::activate_plugin_file( self::resolve_plugin_file( $slug, $file ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function install_all_required(): array {
		$results = array();
		$failed  = 0;

		foreach ( NobatMed_Recommended_Plugins::all() as $plugin ) {
			if ( empty( $plugin['required'] ) ) {
				continue;
			}
			$id     = $plugin['id'];
			$status = NobatMed_Recommended_Plugins::get_plugin_state( $plugin['file'] );

			if ( 'active' === $status ) {
				$results[ $id ] = array( 'success' => true, 'message' => __( 'قبلاً فعال است.', 'nobatmed-core' ) );
				continue;
			}

			$install = self::install_and_activate( $id );
			if ( is_wp_error( $install ) ) {
				$results[ $id ] = array( 'success' => false, 'message' => $install->get_error_message() );
				++$failed;
			} else {
				$results[ $id ] = array( 'success' => true, 'message' => __( 'نصب و فعال‌سازی انجام شد.', 'nobatmed-core' ) );
			}
		}

		return array(
			'success' => 0 === $failed,
			'summary' => array( 'failed' => $failed ),
			'results' => $results,
			'plugins' => NobatMed_Recommended_Plugins::get_for_api(),
		);
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	private static function activate_plugin_file( string $plugin_file ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'forbidden', __( 'اجازه فعال‌سازی ندارید.', 'nobatmed-core' ), array( 'status' => 403 ) );
		}
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			return new WP_Error( 'not_installed', __( 'فایل پلاگین یافت نشد.', 'nobatmed-core' ), array( 'status' => 500 ) );
		}
		if ( is_plugin_active( $plugin_file ) ) {
			return array( 'success' => true, 'message' => __( 'پلاگین از قبل فعال است.', 'nobatmed-core' ) );
		}

		$result = activate_plugin( $plugin_file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true, 'message' => __( 'پلاگین با موفقیت فعال شد.', 'nobatmed-core' ) );
	}

	private static function resolve_plugin_file( string $slug, string $expected_file ): string {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $expected_file ) ) {
			return $expected_file;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $file => $data ) {
			if ( str_starts_with( $file, $slug . '/' ) ) {
				return $file;
			}
		}
		return $expected_file;
	}
}
