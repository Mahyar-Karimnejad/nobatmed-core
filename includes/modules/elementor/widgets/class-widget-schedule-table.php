<?php
/**
 * Schedule table Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Working hours table widget.
 */
class NobatMed_Widget_Schedule_Table extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_schedule_table';
	}

	public function get_title(): string {
		return __( 'جدول ساعات', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-table';
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
			'title',
			array(
				'label'   => __( 'عنوان', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'ساعات کاری', 'nobatmed-core' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @return array<int,array{day:string,hours:string}>
	 */
	private function parse_hours( string $raw ): array {
		$rows = array();
		$lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) ) ?: array();

		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = preg_split( '/[:\-|]+/', $line, 2 );
			if ( is_array( $parts ) && count( $parts ) >= 2 ) {
				$rows[] = array(
					'day'   => trim( (string) $parts[0] ),
					'hours' => trim( (string) $parts[1] ),
				);
				continue;
			}

			$rows[] = array(
				'day'   => $line,
				'hours' => '',
			);
		}

		return $rows;
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$clinic_id = (int) ( $settings['clinic_id'] ?? 0 );

		if ( $clinic_id <= 0 || 'nm_clinic' !== get_post_type( $clinic_id ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'کلینیک انتخاب نشده است.', 'nobatmed-core' ) . '</p>';
			return;
		}

		$hours_raw = (string) get_post_meta( $clinic_id, '_nm_hours', true );
		$rows      = $this->parse_hours( $hours_raw );

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		echo '<div class="nm-el-schedule">';
		if ( ! empty( $settings['title'] ) ) {
			echo '<h4 class="nm-el-schedule__title">' . esc_html( (string) $settings['title'] ) . '</h4>';
		}

		if ( empty( $rows ) ) {
			echo '<p class="nm-el-empty">' . esc_html__( 'ساعات کاری ثبت نشده است.', 'nobatmed-core' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="nm-el-schedule__table"><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><th scope="row">' . esc_html( $row['day'] ) . '</th><td>' . esc_html( $row['hours'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
