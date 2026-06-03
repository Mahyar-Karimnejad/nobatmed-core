<?php
/**
 * Contract for NobatMed Core modules and add-ons.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Module interface.
 */
interface NobatMed_Module_Interface {

	/**
	 * Unique module slug (e.g. profiles, booking, telemedicine).
	 */
	public function get_id(): string;

	/**
	 * Human-readable name.
	 */
	public function get_name(): string;

	/**
	 * Short description.
	 */
	public function get_description(): string;

	/**
	 * Module version.
	 */
	public function get_version(): string;

	/**
	 * Other module IDs required before boot.
	 *
	 * @return string[]
	 */
	public function get_dependencies(): array;

	/**
	 * Whether module ships with Core (true) or is an add-on (false).
	 */
	public function is_core(): bool;

	/**
	 * Register hooks, CPTs, REST routes, etc.
	 */
	public function boot(): void;

	/**
	 * Runtime status for dashboard.
	 *
	 * @return array{id:string,name:string,version:string,is_core:bool,status:string,message:string}
	 */
	public function get_status(): array;
}
