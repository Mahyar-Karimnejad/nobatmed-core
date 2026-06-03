<?php
/**
 * Booking form Elementor widget (frontend stub — REST in phase 1).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Booking form widget.
 */
class NobatMed_Widget_Booking_Form extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_booking_form';
	}

	public function get_title(): string {
		return __( 'فرم نوبت‌دهی', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
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
				'default' => __( 'رزرو نوبت', 'nobatmed-core' ),
			)
		);

		$this->add_control(
			'doctor_id',
			array(
				'label'   => __( 'پزشک (اختیاری)', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 0,
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$doctor_id = (int) ( $settings['doctor_id'] ?? 0 );

		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		$doctors = post_type_exists( 'nm_doctor' )
			? get_posts(
				array(
					'post_type'      => 'nm_doctor',
					'post_status'    => 'publish',
					'posts_per_page' => 100,
				)
			)
			: array();
		?>
		<div class="nm-el-booking" data-nobatmed-booking>
			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<h3 class="nm-el-booking__title"><?php echo esc_html( (string) $settings['title'] ); ?></h3>
			<?php endif; ?>
			<form class="nm-el-booking__form" onsubmit="return false;">
				<label class="nm-el-booking__field">
					<span><?php esc_html_e( 'پزشک', 'nobatmed-core' ); ?></span>
					<select name="doctor_id">
						<option value=""><?php esc_html_e( 'انتخاب پزشک', 'nobatmed-core' ); ?></option>
						<?php foreach ( $doctors as $doctor ) : ?>
							<option value="<?php echo esc_attr( (string) $doctor->ID ); ?>" <?php selected( $doctor_id, $doctor->ID ); ?>>
								<?php echo esc_html( get_the_title( $doctor ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="nm-el-booking__field">
					<span><?php esc_html_e( 'تاریخ', 'nobatmed-core' ); ?></span>
					<input type="date" name="appointment_date" />
				</label>
				<label class="nm-el-booking__field">
					<span><?php esc_html_e( 'نوع ویزیت', 'nobatmed-core' ); ?></span>
					<select name="visit_type">
						<option value="in_person"><?php esc_html_e( 'حضوری', 'nobatmed-core' ); ?></option>
						<option value="online"><?php esc_html_e( 'آنلاین', 'nobatmed-core' ); ?></option>
					</select>
				</label>
				<button type="button" class="nm-el-btn nm-el-btn--block" disabled>
					<?php esc_html_e( 'ثبت نوبت — UI فرانت به‌زودی', 'nobatmed-core' ); ?>
				</button>
				<p class="nm-el-booking__note"><?php esc_html_e( 'فعلاً از پنل ادمین نوبت‌مد یا پس از تکمیل JS فرانت استفاده کنید.', 'nobatmed-core' ); ?></p>
			</form>
		</div>
		<?php
	}
}
