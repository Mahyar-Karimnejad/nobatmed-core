<?php
/**
 * Single clinic card Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clinic card widget.
 */
class NobatMed_Widget_Clinic_Card extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_clinic_card';
	}

	public function get_title(): string {
		return __( 'کارت کلینیک', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-map-pin';
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
	private function clinic_options(): array {
		$options = array( '0' => __( '— انتخاب کلینیک —', 'nobatmed-core' ) );

		if ( ! post_type_exists( 'nm_clinic' ) ) {
			return $options;
		}

		$clinics = get_posts(
			array(
				'post_type'      => 'nm_clinic',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $clinics as $clinic ) {
			$options[ (string) $clinic->ID ] = get_the_title( $clinic );
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
			'clinic_id',
			array(
				'label'   => __( 'کلینیک', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->clinic_options(),
				'default' => '0',
			)
		);

		$this->add_control(
			'show_hours',
			array(
				'label'        => __( 'نمایش ساعات', 'nobatmed-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$clinic_id = (int) ( $settings['clinic_id'] ?? 0 );

		if ( $clinic_id <= 0 || 'nm_clinic' !== get_post_type( $clinic_id ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'کلینیک انتخاب نشده است.', 'nobatmed-core' ) . '</p>';
			return;
		}

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		$address = get_post_meta( $clinic_id, '_nm_address', true );
		$phone   = get_post_meta( $clinic_id, '_nm_phone', true );
		$hours   = get_post_meta( $clinic_id, '_nm_hours', true );
		$thumb   = get_the_post_thumbnail( $clinic_id, 'medium', array( 'class' => 'nm-el-clinic__thumb' ) );

		echo '<article class="nm-el-clinic nm-el-clinic--card">';
		if ( $thumb ) {
			echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<h3 class="nm-el-clinic__title">' . esc_html( get_the_title( $clinic_id ) ) . '</h3>';
		if ( $address ) {
			echo '<p class="nm-el-clinic__meta">' . esc_html( (string) $address ) . '</p>';
		}
		if ( $phone ) {
			echo '<p class="nm-el-clinic__phone">' . esc_html( (string) $phone ) . '</p>';
		}
		if ( 'yes' === ( $settings['show_hours'] ?? 'yes' ) && $hours ) {
			echo '<pre class="nm-el-clinic__hours">' . esc_html( (string) $hours ) . '</pre>';
		}
		echo '<a class="nm-el-btn" href="' . esc_url( get_permalink( $clinic_id ) ) . '">' . esc_html__( 'مشاهده کلینیک', 'nobatmed-core' ) . '</a>';
		echo '</article>';
	}
}
