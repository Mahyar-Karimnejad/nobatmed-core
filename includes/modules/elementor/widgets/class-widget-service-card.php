<?php
/**
 * Single service card Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Service card widget.
 */
class NobatMed_Widget_Service_Card extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_service_card';
	}

	public function get_title(): string {
		return __( 'کارت خدمت', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	/**
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'nobatmed' );
	}

	public function get_style_depends(): array {
		return array( 'nobatmed-elementor-widgets' );
	}

	/**
	 * @return array<string,string>
	 */
	private function service_options(): array {
		$options = array( '0' => __( '— انتخاب خدمت —', 'nobatmed-core' ) );

		if ( ! post_type_exists( 'nm_service' ) ) {
			return $options;
		}

		$services = get_posts(
			array(
				'post_type'      => 'nm_service',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $services as $service ) {
			$options[ (string) $service->ID ] = get_the_title( $service );
		}

		return $options;
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'محتوا', 'nobatmed-core' ),
			)
		);

		$this->add_control(
			'service_id',
			array(
				'label'   => __( 'خدمت', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->service_options(),
				'default' => '0',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$service_id = (int) ( $settings['service_id'] ?? 0 );

		if ( $service_id <= 0 || 'nm_service' !== get_post_type( $service_id ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'خدمت انتخاب نشده است.', 'nobatmed-core' ) . '</p>';
			return;
		}

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		$price    = get_post_meta( $service_id, '_nm_price', true );
		$duration = get_post_meta( $service_id, '_nm_duration', true );
		$excerpt  = has_excerpt( $service_id ) ? get_the_excerpt( $service_id ) : '';

		echo '<article class="nm-el-service-card">';
		echo '<h3 class="nm-el-service-card__title">' . esc_html( get_the_title( $service_id ) ) . '</h3>';
		if ( $excerpt ) {
			echo '<p class="nm-el-service-card__desc">' . esc_html( wp_strip_all_tags( $excerpt ) ) . '</p>';
		}
		echo '<div class="nm-el-service-card__meta">';
		if ( $duration ) {
			echo '<span>' . esc_html( sprintf( __( '%s دقیقه', 'nobatmed-core' ), (string) $duration ) ) . '</span>';
		}
		if ( $price ) {
			echo '<strong>' . esc_html( number_format_i18n( (int) $price ) ) . ' ' . esc_html__( 'تومان', 'nobatmed-core' ) . '</strong>';
		}
		echo '</div>';
		echo '<a class="nm-el-btn nm-el-btn--block" href="' . esc_url( get_permalink( $service_id ) ) . '">' . esc_html__( 'جزئیات خدمت', 'nobatmed-core' ) . '</a>';
		echo '</article>';
	}
}
