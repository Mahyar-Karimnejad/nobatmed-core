<?php
/**
 * Specialty filter chips Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Specialty filter widget.
 */
class NobatMed_Widget_Specialty_Filter extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_specialty_filter';
	}

	public function get_title(): string {
		return __( 'فیلتر تخصص', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-filter';
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
			'title',
			array(
				'label'   => __( 'عنوان', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'تخصص‌ها', 'nobatmed-core' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => __( 'فقط تخصص‌های دارای پزشک', 'nobatmed-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		if ( ! taxonomy_exists( 'nm_specialty' ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'تاکسonomy تخصص فعال نیست.', 'nobatmed-core' ) . '</p>';
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'nm_specialty',
				'hide_empty' => 'yes' === ( $settings['hide_empty'] ?? 'yes' ),
			)
		);

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		echo '<div class="nm-el-specialties">';
		if ( ! empty( $settings['title'] ) ) {
			echo '<h4 class="nm-el-specialties__title">' . esc_html( (string) $settings['title'] ) . '</h4>';
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'تخصصی ثبت نشده است.', 'nobatmed-core' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<div class="nm-el-specialties__chips">';
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			echo '<a class="nm-el-chip" href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
		}
		echo '</div></div>';
	}
}
