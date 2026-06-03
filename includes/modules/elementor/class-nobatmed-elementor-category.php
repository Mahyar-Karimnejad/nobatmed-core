<?php
/**
 * Elementor widget category.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * NobatMed Elementor category.
 */
class NobatMed_Elementor_Category {

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Manager.
	 */
	public static function register( $elements_manager ): void {
		$elements_manager->add_category(
			'nobatmed',
			array(
				'title' => __( 'نوبت‌مد', 'nobatmed-core' ),
				'icon'  => 'fa fa-heartbeat',
			)
		);
	}
}
