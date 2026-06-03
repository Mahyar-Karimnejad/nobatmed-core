<?php
/**
 * Custom roles and capabilities.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Roles module.
 */
class NobatMed_Module_Roles extends NobatMed_Module {

	public function get_id(): string {
		return 'roles';
	}

	public function get_name(): string {
		return __( 'نقش‌ها و دسترسی', 'nobatmed-core' );
	}

	public function get_description(): string {
		return __( 'نقش‌های پزشک، بیمار و منشی.', 'nobatmed-core' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'register_roles' ), 5 );
		add_action( 'init', array( $this, 'ensure_admin_caps' ), 6 );
		add_filter( 'nobatmed_core_capabilities', array( $this, 'get_capability_map' ) );
	}

	/**
	 * Register custom roles on init.
	 */
	public function register_roles(): void {
		if ( get_option( 'nobatmed_roles_installed' ) ) {
			return;
		}

		add_role(
			'nobatmed_doctor',
			__( 'پزشک', 'nobatmed-core' ),
			array(
				'read'                   => true,
				'upload_files'           => true,
				'edit_nm_doctors'        => true,
				'edit_published_nm_doctors' => true,
				'read_nm_doctor'         => true,
			)
		);

		add_role(
			'nobatmed_patient',
			__( 'بیمار', 'nobatmed-core' ),
			array(
				'read' => true,
			)
		);

		add_role(
			'nobatmed_receptionist',
			__( 'منشی', 'nobatmed-core' ),
			array(
				'read'                      => true,
				'edit_nm_appointments'      => true,
				'read_nm_appointments'        => true,
				'edit_nm_doctors'           => true,
				'read_nm_doctor'            => true,
			)
		);

		$this->add_admin_caps();

		update_option( 'nobatmed_roles_installed', 1, false );
	}

	/**
	 * Ensure administrator has all NobatMed caps.
	 */
	public function ensure_admin_caps(): void {
		$this->add_admin_caps();
	}

	/**
	 * Grant CPT caps to administrator.
	 */
	private function add_admin_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		$caps = array_keys( $this->get_capability_map() );
		foreach ( $caps as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Capability map used by Profiles module.
	 *
	 * @return array<string,string>
	 */
	public function get_capability_map(): array {
		return array(
			'edit_nm_doctor'              => 'edit_post',
			'read_nm_doctor'              => 'read_post',
			'delete_nm_doctor'            => 'delete_post',
			'edit_nm_doctors'             => 'edit_posts',
			'edit_others_nm_doctors'      => 'edit_others_posts',
			'publish_nm_doctors'          => 'publish_posts',
			'read_private_nm_doctors'     => 'read_private_posts',
			'delete_nm_doctors'           => 'delete_posts',
			'delete_private_nm_doctors'   => 'delete_private_posts',
			'delete_published_nm_doctors' => 'delete_published_posts',
			'delete_others_nm_doctors'    => 'delete_others_posts',
			'edit_private_nm_doctors'     => 'edit_private_posts',
			'edit_published_nm_doctors'   => 'edit_published_posts',
			'edit_nm_clinic'              => 'edit_post',
			'read_nm_clinic'              => 'read_post',
			'delete_nm_clinic'            => 'delete_post',
			'edit_nm_clinics'             => 'edit_posts',
			'edit_others_nm_clinics'      => 'edit_others_posts',
			'publish_nm_clinics'          => 'publish_posts',
			'read_private_nm_clinics'     => 'read_private_posts',
			'delete_nm_clinics'           => 'delete_posts',
			'edit_nm_service'             => 'edit_post',
			'read_nm_service'             => 'read_post',
			'delete_nm_service'           => 'delete_post',
			'edit_nm_services'            => 'edit_posts',
			'publish_nm_services'         => 'publish_posts',
			'delete_nm_services'          => 'delete_posts',
			'edit_nm_appointments'        => 'edit_posts',
			'read_nm_appointments'        => 'read',
		);
	}
}
