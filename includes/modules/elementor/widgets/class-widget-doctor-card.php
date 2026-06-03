<?php
/**
 * Single doctor card Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Doctor card widget.
 */
class NobatMed_Widget_Doctor_Card extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_doctor_card';
	}

	public function get_title(): string {
		return __( 'کارت پزشک', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
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
	private function doctor_options(): array {
		$options = array( '0' => __( '— انتخاب پزشک —', 'nobatmed-core' ) );

		if ( ! post_type_exists( 'nm_doctor' ) ) {
			return $options;
		}

		$doctors = get_posts(
			array(
				'post_type'      => 'nm_doctor',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $doctors as $doctor ) {
			$options[ (string) $doctor->ID ] = get_the_title( $doctor );
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
			'doctor_id',
			array(
				'label'   => __( 'پزشک', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->doctor_options(),
				'default' => '0',
			)
		);

		$this->add_control(
			'show_booking_btn',
			array(
				'label'        => __( 'دکمه رزرو', 'nobatmed-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$doctor_id = (int) ( $settings['doctor_id'] ?? 0 );

		if ( $doctor_id <= 0 || 'nm_doctor' !== get_post_type( $doctor_id ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'پزشک انتخاب نشده است.', 'nobatmed-core' ) . '</p>';
			return;
		}

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		$specialty = get_post_meta( $doctor_id, '_nm_specialty', true );
		$phone     = get_post_meta( $doctor_id, '_nm_phone', true );
		$thumb     = get_the_post_thumbnail( $doctor_id, 'medium', array( 'class' => 'nm-el-doctor__thumb' ) );

		echo '<article class="nm-el-doctor nm-el-doctor--card">';
		if ( $thumb ) {
			echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<h3 class="nm-el-doctor__title">' . esc_html( get_the_title( $doctor_id ) ) . '</h3>';
		if ( $specialty ) {
			echo '<p class="nm-el-doctor__meta">' . esc_html( (string) $specialty ) . '</p>';
		}
		if ( $phone ) {
			echo '<p class="nm-el-doctor__phone">' . esc_html( (string) $phone ) . '</p>';
		}
		echo '<a class="nm-el-btn nm-el-btn--outline" href="' . esc_url( get_permalink( $doctor_id ) ) . '">' . esc_html__( 'پروفایل', 'nobatmed-core' ) . '</a>';
		if ( 'yes' === ( $settings['show_booking_btn'] ?? 'yes' ) ) {
			echo ' <a class="nm-el-btn" href="' . esc_url( get_permalink( $doctor_id ) ) . '#booking">' . esc_html__( 'رزرو نوبت', 'nobatmed-core' ) . '</a>';
		}
		echo '</article>';
	}
}
