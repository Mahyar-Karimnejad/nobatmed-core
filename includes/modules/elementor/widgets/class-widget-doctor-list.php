<?php
/**
 * Doctor list Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Doctor list widget.
 */
class NobatMed_Widget_Doctor_List extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_doctor_list';
	}

	public function get_title(): string {
		return __( 'لیست پزشکان', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-person';
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
				'default' => '3',
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
		$columns  = max( 1, min( 3, (int) ( $settings['columns'] ?? 3 ) ) );

		if ( ! post_type_exists( 'nm_doctor' ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'ماژول پروفایل فعال نیست.', 'nobatmed-core' ) . '</p>';
			return;
		}

		$doctors = get_posts(
			array(
				'post_type'      => 'nm_doctor',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		echo '<div class="nm-el-doctors nm-el-cols-' . esc_attr( (string) $columns ) . '">';

		if ( empty( $doctors ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'پزشکی ثبت نشده است.', 'nobatmed-core' ) . '</p>';
		}

		foreach ( $doctors as $doctor ) {
			$specialty = get_post_meta( $doctor->ID, '_nm_specialty', true );
			$thumb     = get_the_post_thumbnail( $doctor->ID, 'medium', array( 'class' => 'nm-el-doctor__thumb' ) );
			echo '<article class="nm-el-doctor">';
			if ( $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3 class="nm-el-doctor__title">' . esc_html( get_the_title( $doctor ) ) . '</h3>';
			if ( $specialty ) {
				echo '<p class="nm-el-doctor__meta">' . esc_html( (string) $specialty ) . '</p>';
			}
			echo '<a class="nm-el-btn" href="' . esc_url( get_permalink( $doctor ) ) . '">' . esc_html__( 'مشاهده پروفایل', 'nobatmed-core' ) . '</a>';
			echo '</article>';
		}

		echo '</div>';
	}
}
