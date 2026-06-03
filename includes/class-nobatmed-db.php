<?php
/**
 * Database helpers.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * DB utility class.
 */
class NobatMed_DB {

	public const TABLE_SCHEDULES    = 'nobatmed_schedules';
	public const TABLE_APPOINTMENTS = 'nobatmed_appointments';
	public const TABLE_HOLIDAYS     = 'nobatmed_holidays';

	/**
	 * Full table name with prefix.
	 */
	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . $name;
	}

	/**
	 * Current schema version stored in options.
	 */
	public static function get_schema_version(): string {
		return (string) get_option( 'nobatmed_db_version', '0' );
	}

	/**
	 * Update schema version.
	 */
	public static function set_schema_version( string $version ): void {
		update_option( 'nobatmed_db_version', $version, false );
	}

	/**
	 * Check if custom table exists.
	 */
	public static function table_exists( string $name ): bool {
		global $wpdb;
		$table = self::table( $name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}
}
