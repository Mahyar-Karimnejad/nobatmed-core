<?php
/**
 * Base module class.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract module.
 */
abstract class NobatMed_Module implements NobatMed_Module_Interface {

	/**
	 * Core instance.
	 *
	 * @var NobatMed_Core
	 */
	protected NobatMed_Core $core;

	/**
	 * Constructor.
	 *
	 * @param NobatMed_Core $core Core plugin.
	 */
	public function __construct( NobatMed_Core $core ) {
		$this->core = $core;
	}

	/**
	 * @inheritDoc
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * @inheritDoc
	 */
	public function is_core(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_status(): array {
		return array(
			'id'          => $this->get_id(),
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'is_core'     => $this->is_core(),
			'status'      => 'active',
			'message'     => '',
			'description' => $this->get_description(),
		);
	}

	/**
	 * Register a REST route under nobatmed-core/v1.
	 *
	 * @param string               $route    Route suffix.
	 * @param array<string,mixed>  $args     Route args.
	 */
	protected function register_rest_route( string $route, array $args ): void {
		register_rest_route(
			'nobatmed-core/v1',
			'/' . ltrim( $route, '/' ),
			$args
		);
	}

	/**
	 * Check manage_options capability.
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}
}
