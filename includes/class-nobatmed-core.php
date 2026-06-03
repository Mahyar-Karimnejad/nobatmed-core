<?php
/**
 * Main plugin orchestrator.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * NobatMed Core singleton.
 */
final class NobatMed_Core {

	private static ?NobatMed_Core $instance = null;

	private NobatMed_Module_Manager $modules;

	private NobatMed_Admin $admin;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): NobatMed_Core {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->modules = new NobatMed_Module_Manager( $this );
		$this->admin   = new NobatMed_Admin( $this );
	}

	/**
	 * Boot plugin.
	 */
	public function init(): void {
		NobatMed_Activator::maybe_upgrade();
		NobatMed_Classic_Editor::init();

		$this->modules->register_core_modules();

		/**
		 * Fires before modules boot — add-ons register here.
		 *
		 * @param NobatMed_Core $core Core instance.
		 */
		do_action( 'nobatmed_core_init', $this );

		$this->modules->boot_all();

		NobatMed_Orbit_Bridge::init();

		// REST must register on every request (wp-json runs outside is_admin).
		$this->admin->init();

		if ( NOBATMED_LICENSE_ENABLED ) {
			nobatmed_core_boot_license();
		}
	}

	/**
	 * Module manager accessor.
	 */
	public function modules(): NobatMed_Module_Manager {
		return $this->modules;
	}

	/**
	 * Get module by ID (shortcut for add-ons).
	 */
	public function module( string $id ): ?NobatMed_Module_Interface {
		return $this->modules->get( $id );
	}
}

/**
 * Global accessor for add-on plugins.
 */
function nobatmed_core(): NobatMed_Core {
	return NobatMed_Core::instance();
}
