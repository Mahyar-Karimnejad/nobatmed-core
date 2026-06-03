<?php
/**
 * Doctor search Elementor widget.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Search form widget.
 */
class NobatMed_Widget_Doctor_Search extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'nobatmed_doctor_search';
	}

	public function get_title(): string {
		return __( 'جستجوی پزشک', 'nobatmed-core' );
	}

	public function get_icon(): string {
		return 'eicon-search';
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
			'placeholder',
			array(
				'label'   => __( 'Placeholder', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'نام پزشک یا تخصص…', 'nobatmed-core' ),
			)
		);

		$this->add_control(
			'button_label',
			array(
				'label'   => __( 'متن دکمه', 'nobatmed-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'جستجو', 'nobatmed-core' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		wp_enqueue_style( 'nobatmed-elementor-widgets' );

		$action = home_url( '/' );
		?>
		<form class="nm-el-search" method="get" action="<?php echo esc_url( $action ); ?>">
			<input type="hidden" name="post_type" value="nm_doctor" />
			<input
				type="search"
				name="s"
				class="nm-el-search__input"
				placeholder="<?php echo esc_attr( (string) ( $settings['placeholder'] ?? '' ) ); ?>"
				value="<?php echo esc_attr( get_search_query() ); ?>"
			/>
			<button type="submit" class="nm-el-btn"><?php echo esc_html( (string) ( $settings['button_label'] ?? __( 'جستجو', 'nobatmed-core' ) ) ); ?></button>
		</form>
		<?php
	}
}
