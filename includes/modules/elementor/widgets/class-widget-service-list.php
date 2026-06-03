<?php
/**
 * Service list Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Service list widget.
 */
class NobatMed_Widget_Service_List extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_service_list';
	}

	public function get_title(): string {
		return __( 'لیست خدمات', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
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

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'محتوا', 'nobatmed-core' ),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'تعداد', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 8,
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'نمایش قیمت', 'nobatmed-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$limit    = max( 1, (int) ( $settings['posts_per_page'] ?? 8 ) );

		if ( ! post_type_exists( 'nm_service' ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'ماژول پروفایل فعال نیست.', 'nobatmed-core' ) . '</p>';
			return;
		}

		$services = get_posts(
			array(
				'post_type'      => 'nm_service',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		echo '<ul class="nm-el-services">';

		if ( empty( $services ) ) {
			echo '<li class="nm-el-empty">' . esc_html__( 'خدمتی ثبت نشده است.', 'nobatmed-core' ) . '</li>';
		}

		foreach ( $services as $service ) {
			$price    = get_post_meta( $service->ID, '_nm_price', true );
			$duration = get_post_meta( $service->ID, '_nm_duration', true );

			echo '<li class="nm-el-service">';
			echo '<div class="nm-el-service__body">';
			echo '<strong class="nm-el-service__title">' . esc_html( get_the_title( $service ) ) . '</strong>';
			if ( $duration ) {
				echo '<span class="nm-el-service__meta">' . esc_html( sprintf( __( '%s دقیقه', 'nobatmed-core' ), (string) $duration ) ) . '</span>';
			}
			echo '</div>';
			if ( 'yes' === ( $settings['show_price'] ?? 'yes' ) && $price ) {
				echo '<span class="nm-el-service__price">' . esc_html( number_format_i18n( (int) $price ) ) . ' ' . esc_html__( 'تومان', 'nobatmed-core' ) . '</span>';
			}
			echo '</li>';
		}

		echo '</ul>';
	}
}
