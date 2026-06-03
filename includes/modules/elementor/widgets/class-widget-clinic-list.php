<?php
/**
 * Clinic list Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clinic list widget.
 */
class NobatMed_Widget_Clinic_List extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_clinic_list';
	}

	public function get_title(): string {
		return __( 'لیست کلینیک‌ها', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
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
				'default' => 6,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'ستون', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '2',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$limit    = max( 1, (int) ( $settings['posts_per_page'] ?? 6 ) );
		$columns  = max( 1, min( 3, (int) ( $settings['columns'] ?? 2 ) ) );

		if ( ! post_type_exists( 'nm_clinic' ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'ماژول پروفایل فعال نیست.', 'nobatmed-core' ) . '</p>';
			return;
		}

		$clinics = get_posts(
			array(
				'post_type'      => 'nm_clinic',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		echo '<div class="nm-el-clinics nm-el-cols-' . esc_attr( (string) $columns ) . '">';

		if ( empty( $clinics ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'کلینیکی ثبت نشده است.', 'nobatmed-core' ) . '</p>';
		}

		foreach ( $clinics as $clinic ) {
			$address = get_post_meta( $clinic->ID, '_nm_address', true );
			$phone   = get_post_meta( $clinic->ID, '_nm_phone', true );
			$thumb   = get_the_post_thumbnail( $clinic->ID, 'medium', array( 'class' => 'nm-el-clinic__thumb' ) );

			echo '<article class="nm-el-clinic">';
			if ( $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3 class="nm-el-clinic__title">' . esc_html( get_the_title( $clinic ) ) . '</h3>';
			if ( $address ) {
				echo '<p class="nm-el-clinic__meta">' . esc_html( (string) $address ) . '</p>';
			}
			if ( $phone ) {
				echo '<p class="nm-el-clinic__phone">' . esc_html( (string) $phone ) . '</p>';
			}
			echo '<a class="nm-el-btn" href="' . esc_url( get_permalink( $clinic ) ) . '">' . esc_html__( 'مشاهده کلینیک', 'nobatmed-core' ) . '</a>';
			echo '</article>';
		}

		echo '</div>';
	}
}
