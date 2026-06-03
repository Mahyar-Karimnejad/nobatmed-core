<?php
/**
 * Plugin activation — DB schema and defaults.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activator.
 */
class NobatMed_Activator {

	public const DB_VERSION = '1.0.0';

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		self::create_tables();
		NobatMed_DB::set_schema_version( self::DB_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Create or upgrade custom tables.
	 */
	public static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$schedules = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$sql_schedules = "CREATE TABLE {$schedules} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			day_of_week tinyint(1) NOT NULL DEFAULT 0,
			start_time time NOT NULL,
			end_time time NOT NULL,
			slot_duration smallint(5) unsigned NOT NULL DEFAULT 30,
			capacity smallint(5) unsigned NOT NULL DEFAULT 1,
			visit_type varchar(20) NOT NULL DEFAULT 'in_person',
			status varchar(20) NOT NULL DEFAULT 'active',
			meta longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY doctor_id (doctor_id),
			KEY clinic_id (clinic_id),
			KEY day_of_week (day_of_week),
			KEY status (status)
		) {$charset};";

		$appointments = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );
		$sql_appointments = "CREATE TABLE {$appointments} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			service_id bigint(20) unsigned NOT NULL DEFAULT 0,
			patient_id bigint(20) unsigned NOT NULL DEFAULT 0,
			appointment_date date NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			visit_type varchar(20) NOT NULL DEFAULT 'in_person',
			status varchar(20) NOT NULL DEFAULT 'pending',
			notes text NULL,
			meta longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY doctor_id (doctor_id),
			KEY clinic_id (clinic_id),
			KEY patient_id (patient_id),
			KEY appointment_date (appointment_date),
			KEY status (status)
		) {$charset};";

		$holidays = NobatMed_DB::table( NobatMed_DB::TABLE_HOLIDAYS );
		$sql_holidays = "CREATE TABLE {$holidays} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			holiday_date date NOT NULL,
			reason varchar(190) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY doctor_id (doctor_id),
			KEY clinic_id (clinic_id),
			KEY holiday_date (holiday_date)
		) {$charset};";

		dbDelta( $sql_schedules );
		dbDelta( $sql_appointments );
		dbDelta( $sql_holidays );
	}

	/**
	 * Upgrade schema if needed (called on plugins_loaded).
	 */
	public static function maybe_upgrade(): void {
		if ( version_compare( NobatMed_DB::get_schema_version(), self::DB_VERSION, '>=' ) ) {
			return;
		}
		self::create_tables();
		NobatMed_DB::set_schema_version( self::DB_VERSION );
	}
}
