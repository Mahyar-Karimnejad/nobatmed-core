<?php
/**
 * Module registry and boot loader.
 *
 * Add-ons register via:
 *   add_action( 'nobatmed_core_init', function( NobatMed_Core $core ) {
 *       $core->modules()->register( new My_Addon_Module( $core ) );
 *   });
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Module manager.
 */
class NobatMed_Module_Manager {

	/**
	 * @var NobatMed_Core
	 */
	private NobatMed_Core $core;

	/**
	 * @var array<string,NobatMed_Module_Interface>
	 */
	private array $registered = array();

	/**
	 * @var array<string,NobatMed_Module_Interface>
	 */
	private array $booted = array();

	/**
	 * @param NobatMed_Core $core Core instance.
	 */
	public function __construct( NobatMed_Core $core ) {
		$this->core = $core;
	}

	/**
	 * Register a module (Core or add-on).
	 */
	public function register( NobatMed_Module_Interface $module ): void {
		$id = $module->get_id();
		if ( isset( $this->registered[ $id ] ) ) {
			return;
		}
		$this->registered[ $id ] = $module;
	}

	/**
	 * Register built-in core modules, then allow extensions.
	 */
	public function register_core_modules(): void {
		$this->register( new NobatMed_Module_Roles( $this->core ) );
		$this->register( new NobatMed_Module_Profiles( $this->core ) );
		$this->register( new NobatMed_Module_Booking( $this->core ) );
		$this->register( new NobatMed_Module_Elementor( $this->core ) );
		$this->register( new NobatMed_Module_Otp_Sms( $this->core ) );

		/**
		 * Filter module instances before boot.
		 *
		 * @param array<string,NobatMed_Module_Interface> $registered Registered modules.
		 * @param NobatMed_Core                           $core       Core instance.
		 */
		$filtered = apply_filters( 'nobatmed_core_modules', $this->registered, $this->core );
		if ( is_array( $filtered ) ) {
			foreach ( $filtered as $id => $module ) {
				if ( $module instanceof NobatMed_Module_Interface ) {
					$this->registered[ $module->get_id() ] = $module;
				}
			}
		}
	}

	/**
	 * Boot all modules respecting dependency order.
	 */
	public function boot_all(): void {
		$pending = $this->registered;

		while ( ! empty( $pending ) ) {
			$progress = false;

			foreach ( $pending as $id => $module ) {
				if ( ! $this->dependencies_met( $module ) ) {
					continue;
				}

				if ( ! $this->should_boot_module( $module ) ) {
					unset( $pending[ $id ] );
					$progress = true;
					continue;
				}

				$module->boot();
				$this->booted[ $id ] = $module;
				unset( $pending[ $id ] );
				$progress = true;
			}

			if ( ! $progress ) {
				// Unresolved dependencies — boot remaining to avoid total failure.
				foreach ( $pending as $id => $module ) {
					$this->booted[ $id ] = $module;
					$module->boot();
				}
				break;
			}
		}

		/**
		 * Fires after all modules are booted.
		 *
		 * @param NobatMed_Core $core Core instance.
		 */
		do_action( 'nobatmed_core_modules_loaded', $this->core );
	}

	/**
	 * Get booted module by ID.
	 */
	public function get( string $id ): ?NobatMed_Module_Interface {
		return $this->booted[ $id ] ?? $this->registered[ $id ] ?? null;
	}

	/**
	 * @return array<string,NobatMed_Module_Interface>
	 */
	public function all(): array {
		return $this->registered;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_statuses(): array {
		$list = array();
		foreach ( $this->registered as $module ) {
			$list[] = $module->get_status();
		}
		return $list;
	}

	/**
	 * Check module dependencies.
	 */
	private function dependencies_met( NobatMed_Module_Interface $module ): bool {
		foreach ( $module->get_dependencies() as $dep ) {
			if ( ! isset( $this->booted[ $dep ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check registry toggle (locked modules always boot).
	 */
	private function should_boot_module( NobatMed_Module_Interface $module ): bool {
		$id = $module->get_id();
		if ( NobatMed_Module_Registry::is_locked( $id ) ) {
			return true;
		}
		return NobatMed_Module_Settings::is_enabled( $id );
	}
}
