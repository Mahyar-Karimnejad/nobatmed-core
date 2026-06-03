<?php
/**
 * Persist module on/off state.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Module settings.
 */
class NobatMed_Module_Settings {

	private const OPTION_KEY = 'nobatmed_enabled_modules';

	/**
	 * @return string[]
	 */
	public static function get_enabled(): array {
		$stored = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $stored ) ) {
			return self::default_enabled();
		}
		return array_values( array_filter( array_map( 'strval', $stored ) ) );
	}

	public static function is_enabled( string $module_id ): bool {
		if ( ! NobatMed_Module_Registry::is_implemented( $module_id ) ) {
			return false;
		}
		if ( NobatMed_Module_Registry::is_locked( $module_id ) ) {
			return true;
		}
		return in_array( $module_id, self::get_enabled(), true );
	}

	/**
	 * @param string[] $module_ids Module IDs.
	 */
	public static function set_enabled( array $module_ids ): bool {
		$valid = array_keys( NobatMed_Module_Registry::all() );
		$clean = array();

		foreach ( array_intersect( array_map( 'strval', $module_ids ), $valid ) as $id ) {
			if ( NobatMed_Module_Registry::is_implemented( $id ) && NobatMed_Module_Registry::can_toggle( $id ) ) {
				$clean[] = $id;
			}
		}

		foreach ( NobatMed_Module_Registry::all() as $id => $module ) {
			if ( ! empty( $module['locked'] ) && ! empty( $module['implemented'] ) ) {
				$clean[] = $id;
			}
		}

		$clean = array_values( array_unique( $clean ) );

		return update_option( self::OPTION_KEY, $clean, false );
	}

	public static function toggle( string $module_id, bool $enabled ): bool {
		$modules = NobatMed_Module_Registry::all();
		if ( ! isset( $modules[ $module_id ] ) ) {
			return false;
		}
		if ( ! empty( $modules[ $module_id ]['locked'] ) ) {
			return true;
		}
		if ( ! NobatMed_Module_Registry::is_implemented( $module_id ) ) {
			return false;
		}

		$list = self::get_enabled();
		if ( $enabled ) {
			if ( ! in_array( $module_id, $list, true ) ) {
				$list[] = $module_id;
			}
		} else {
			$list = array_values( array_diff( $list, array( $module_id ) ) );
		}

		return self::set_enabled( $list );
	}

	/**
	 * @return array{done:int,progress:int,pending:int,enabled:int,total:int}
	 */
	public static function progress_stats(): array {
		$all     = NobatMed_Module_Registry::all();
		$enabled = self::get_enabled();
		$done    = 0;
		$progress = 0;
		$pending = 0;

		foreach ( $all as $id => $module ) {
			if ( ! empty( $module['implemented'] ) ) {
				if ( 'done' === ( $module['devStatus'] ?? '' ) ) {
					++$done;
				} else {
					++$progress;
				}
			} else {
				++$pending;
			}
		}

		return array(
			'done'     => $done,
			'progress' => $progress,
			'pending'  => $pending,
			'enabled'  => count( array_intersect( $enabled, array_keys( $all ) ) ),
			'total'    => count( $all ),
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_for_api(): array {
		$enabled  = self::get_enabled();
		$registry = NobatMed_Module_Registry::all();
		$groups   = NobatMed_Module_Registry::groups();
		$list     = array();

		foreach ( $registry as $id => $module ) {
			$requires       = $module['requires'] ?? '';
			$deps_ok        = true;
			$implemented    = ! empty( $module['implemented'] );
			$dev_status     = (string) ( $module['devStatus'] ?? 'pending' );

			if ( $requires && function_exists( 'is_plugin_active' ) && ! is_plugin_active( $requires ) ) {
				$deps_ok = false;
			}

			$is_enabled = $implemented && (
				! empty( $module['locked'] ) || in_array( $id, $enabled, true )
			);

			if ( $implemented && ! empty( $module['default'] ) && ! empty( $module['locked'] ) ) {
				$is_enabled = true;
			}

			$list[] = array(
				'id'           => $id,
				'name'         => $module['name'],
				'description'  => $module['description'],
				'group'        => $module['group'],
				'groupLabel'   => $groups[ $module['group'] ] ?? $module['group'],
				'icon'         => $module['icon'],
				'enabled'      => $is_enabled,
				'locked'       => ! empty( $module['locked'] ),
				'implemented'  => $implemented,
				'devStatus'    => $dev_status,
				'canToggle'    => NobatMed_Module_Registry::can_toggle( $id ) && $deps_ok,
				'comingSoon'   => ! $implemented,
				'phase'        => (int) ( $module['phase'] ?? 1 ),
				'type'         => $module['type'] ?? 'core',
				'orbitProduct' => $module['orbitProduct'] ?? '',
				'available'    => $deps_ok,
			);
		}

		return $list;
	}

	/**
	 * @return string[]
	 */
	private static function default_enabled(): array {
		$ids = array();
		foreach ( NobatMed_Module_Registry::all() as $id => $module ) {
			if ( empty( $module['implemented'] ) ) {
				continue;
			}
			if ( ! empty( $module['default'] ) || ! empty( $module['locked'] ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
}
