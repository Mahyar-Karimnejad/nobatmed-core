<?php
/**
 * Elementor widgets module (base widgets in Core).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration.
 */
class NobatMed_Module_Elementor extends NobatMed_Module {

	public function get_id(): string {
		return 'elementor-widgets';
	}

	public function get_name(): string {
		return __( 'ویجت‌های Elementor (پایه)', 'nobatmed-core' );
	}

	public function get_description(): string {
		return __( 'ویجت‌های رایگان نوبت‌مد در Elementor.', 'nobatmed-core' );
	}

	public function get_version(): string {
		return '0.1.0';
	}

	public function get_dependencies(): array {
		return array( 'profiles' );
	}

	public function boot(): void {
		if ( did_action( 'elementor/loaded' ) ) {
			$this->init_elementor();
			return;
		}
		add_action( 'elementor/loaded', array( $this, 'init_elementor' ) );
	}

	public function init_elementor(): void {
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/class-nobatmed-elementor-category.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-doctor-list.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-doctor-search.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-booking-form.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-clinic-list.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-service-list.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-doctor-card.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-clinic-card.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-specialty-filter.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-schedule-table.php';
		require_once NOBATMED_CORE_PATH . 'includes/modules/elementor/widgets/class-widget-service-card.php';

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_widget_assets' ) );
	}

	public function register_category( $elements_manager ): void {
		NobatMed_Elementor_Category::register( $elements_manager );
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widgets( $widgets_manager ): void {
		$widgets_manager->register( new NobatMed_Widget_Doctor_List() );
		$widgets_manager->register( new NobatMed_Widget_Doctor_Search() );
		$widgets_manager->register( new NobatMed_Widget_Booking_Form() );
		$widgets_manager->register( new NobatMed_Widget_Clinic_List() );
		$widgets_manager->register( new NobatMed_Widget_Service_List() );
		$widgets_manager->register( new NobatMed_Widget_Doctor_Card() );
		$widgets_manager->register( new NobatMed_Widget_Clinic_Card() );
		$widgets_manager->register( new NobatMed_Widget_Specialty_Filter() );
		$widgets_manager->register( new NobatMed_Widget_Schedule_Table() );
		$widgets_manager->register( new NobatMed_Widget_Service_Card() );
	}

	public function enqueue_widget_assets(): void {
		if ( ! wp_style_is( 'nobatmed-elementor-widgets', 'registered' ) ) {
			wp_register_style(
				'nobatmed-elementor-widgets',
				NOBATMED_CORE_URL . 'assets/css/elementor-widgets.css',
				array(),
				NOBATMED_CORE_VERSION
			);
		}
	}
}
