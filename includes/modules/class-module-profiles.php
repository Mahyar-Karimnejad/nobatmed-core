<?php
/**
 * Doctor, clinic, and service profiles (CPT + meta).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Profiles module.
 */
class NobatMed_Module_Profiles extends NobatMed_Module {

	public const POST_DOCTOR  = 'nm_doctor';
	public const POST_CLINIC  = 'nm_clinic';
	public const POST_SERVICE = 'nm_service';

	public const TAX_SPECIALTY   = 'nm_specialty';
	public const TAX_CLINIC_TYPE = 'nm_clinic_type';

	public function get_id(): string {
		return 'profiles';
	}

	public function get_name(): string {
		return __( 'پروفایل پزشک و کلینیک', 'nobatmed-core' );
	}

	public function get_description(): string {
		return __( 'CPT پزشک، کلینیک، خدمات و متادیتا.', 'nobatmed-core' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_dependencies(): array {
		return array( 'roles' );
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register CPTs.
	 */
	public function register_post_types(): void {
		$cap_map = apply_filters( 'nobatmed_core_capabilities', array() );

		register_post_type(
			self::POST_DOCTOR,
			array(
				'labels'              => array(
					'name'          => __( 'پزشکان', 'nobatmed-core' ),
					'singular_name' => __( 'پزشک', 'nobatmed-core' ),
					'add_new_item'  => __( 'افزودن پزشک', 'nobatmed-core' ),
					'edit_item'     => __( 'ویرایش پزشک', 'nobatmed-core' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'doctor' ),
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-heart',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
				'capability_type'     => array( 'nm_doctor', 'nm_doctors' ),
				'map_meta_cap'        => true,
				'show_in_menu'        => 'nobatmed-core',
			)
		);

		register_post_type(
			self::POST_CLINIC,
			array(
				'labels'              => array(
					'name'          => __( 'کلینیک‌ها', 'nobatmed-core' ),
					'singular_name' => __( 'کلینیک', 'nobatmed-core' ),
					'add_new_item'  => __( 'افزودن کلینیک', 'nobatmed-core' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'clinic' ),
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-building',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'capability_type'     => array( 'nm_clinic', 'nm_clinics' ),
				'map_meta_cap'        => true,
				'show_in_menu'        => 'nobatmed-core',
			)
		);

		register_post_type(
			self::POST_SERVICE,
			array(
				'labels'              => array(
					'name'          => __( 'خدمات', 'nobatmed-core' ),
					'singular_name' => __( 'خدمت', 'nobatmed-core' ),
					'add_new_item'  => __( 'افزودن خدمت', 'nobatmed-core' ),
				),
				'public'              => true,
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'service' ),
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-clipboard',
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'capability_type'     => array( 'nm_service', 'nm_services' ),
				'map_meta_cap'        => true,
				'show_in_menu'        => 'nobatmed-core',
			)
		);

		// Silence unused variable warning in older PHP — cap_map used by filter consumers.
		unset( $cap_map );
	}

	/**
	 * Register taxonomies.
	 */
	public function register_taxonomies(): void {
		register_taxonomy(
			self::TAX_SPECIALTY,
			array( self::POST_DOCTOR ),
			array(
				'labels'       => array(
					'name'          => __( 'تخصص‌ها', 'nobatmed-core' ),
					'singular_name' => __( 'تخصص', 'nobatmed-core' ),
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'specialty' ),
			)
		);

		register_taxonomy(
			self::TAX_CLINIC_TYPE,
			array( self::POST_CLINIC ),
			array(
				'labels'       => array(
					'name'          => __( 'نوع مرکز', 'nobatmed-core' ),
					'singular_name' => __( 'نوع مرکز', 'nobatmed-core' ),
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'clinic-type' ),
			)
		);
	}

	/**
	 * Meta boxes for profile fields.
	 */
	public function register_meta_boxes(): void {
		add_meta_box(
			'nm_doctor_meta',
			__( 'اطلاعات پزشک', 'nobatmed-core' ),
			array( $this, 'render_doctor_meta_box' ),
			self::POST_DOCTOR,
			'normal',
			'high'
		);

		add_meta_box(
			'nm_clinic_meta',
			__( 'اطلاعات کلینیک', 'nobatmed-core' ),
			array( $this, 'render_clinic_meta_box' ),
			self::POST_CLINIC,
			'normal',
			'high'
		);

		add_meta_box(
			'nm_service_meta',
			__( 'اطلاعات خدمت', 'nobatmed-core' ),
			array( $this, 'render_service_meta_box' ),
			self::POST_SERVICE,
			'normal',
			'high'
		);
	}

	/**
	 * Doctor meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_doctor_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'nm_save_profile_meta', 'nm_profile_nonce' );
		$fields = array(
			'medical_number'   => get_post_meta( $post->ID, '_nm_medical_number', true ),
			'phone'            => get_post_meta( $post->ID, '_nm_phone', true ),
			'email'            => get_post_meta( $post->ID, '_nm_email', true ),
			'experience_years' => get_post_meta( $post->ID, '_nm_experience_years', true ),
			'education'          => get_post_meta( $post->ID, '_nm_education', true ),
			'insurance'          => get_post_meta( $post->ID, '_nm_insurance', true ),
			'clinic_ids'         => get_post_meta( $post->ID, '_nm_clinic_ids', true ),
			'faq'                => get_post_meta( $post->ID, '_nm_faq', true ),
		);
		?>
		<table class="form-table">
			<tr>
				<th><label for="nm_medical_number"><?php esc_html_e( 'شماره نظام پزشکی', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_medical_number" name="nm_medical_number" value="<?php echo esc_attr( (string) $fields['medical_number'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_phone"><?php esc_html_e( 'تلفن', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_phone" name="nm_phone" value="<?php echo esc_attr( (string) $fields['phone'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_email"><?php esc_html_e( 'ایمیل', 'nobatmed-core' ); ?></label></th>
				<td><input type="email" class="regular-text" id="nm_email" name="nm_email" value="<?php echo esc_attr( (string) $fields['email'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_experience_years"><?php esc_html_e( 'سابقه (سال)', 'nobatmed-core' ); ?></label></th>
				<td><input type="number" min="0" id="nm_experience_years" name="nm_experience_years" value="<?php echo esc_attr( (string) $fields['experience_years'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_education"><?php esc_html_e( 'تحصیلات (هر خط یک مورد)', 'nobatmed-core' ); ?></label></th>
				<td><textarea class="large-text" rows="3" id="nm_education" name="nm_education"><?php echo esc_textarea( (string) $fields['education'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="nm_insurance"><?php esc_html_e( 'بیمه‌های طرف قرارداد', 'nobatmed-core' ); ?></label></th>
				<td><textarea class="large-text" rows="2" id="nm_insurance" name="nm_insurance"><?php echo esc_textarea( (string) $fields['insurance'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="nm_clinic_ids"><?php esc_html_e( 'شناسه کلینیک‌ها (با کاما)', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_clinic_ids" name="nm_clinic_ids" value="<?php echo esc_attr( (string) $fields['clinic_ids'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_faq"><?php esc_html_e( 'FAQ (JSON)', 'nobatmed-core' ); ?></label></th>
				<td><textarea class="large-text code" rows="4" id="nm_faq" name="nm_faq"><?php echo esc_textarea( (string) $fields['faq'] ); ?></textarea></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Clinic meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_clinic_meta_box( WP_Post $post ): void {
		$address = get_post_meta( $post->ID, '_nm_address', true );
		$phone   = get_post_meta( $post->ID, '_nm_phone', true );
		$lat     = get_post_meta( $post->ID, '_nm_lat', true );
		$lng     = get_post_meta( $post->ID, '_nm_lng', true );
		$hours   = get_post_meta( $post->ID, '_nm_hours', true );
		$gallery = get_post_meta( $post->ID, '_nm_gallery', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="nm_address"><?php esc_html_e( 'آدرس', 'nobatmed-core' ); ?></label></th>
				<td><textarea class="large-text" rows="2" id="nm_address" name="nm_address"><?php echo esc_textarea( (string) $address ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="nm_clinic_phone"><?php esc_html_e( 'تلفن', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_clinic_phone" name="nm_clinic_phone" value="<?php echo esc_attr( (string) $phone ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'موقعیت', 'nobatmed-core' ); ?></th>
				<td>
					<input type="text" placeholder="Lat" name="nm_lat" value="<?php echo esc_attr( (string) $lat ); ?>" style="width:120px">
					<input type="text" placeholder="Lng" name="nm_lng" value="<?php echo esc_attr( (string) $lng ); ?>" style="width:120px">
				</td>
			</tr>
			<tr>
				<th><label for="nm_hours"><?php esc_html_e( 'ساعات کاری', 'nobatmed-core' ); ?></label></th>
				<td><textarea class="large-text" rows="2" id="nm_hours" name="nm_hours"><?php echo esc_textarea( (string) $hours ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="nm_gallery"><?php esc_html_e( 'گالری (ID تصاویر با کاما)', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="large-text" id="nm_gallery" name="nm_gallery" value="<?php echo esc_attr( (string) $gallery ); ?>"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Service meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_service_meta_box( WP_Post $post ): void {
		$price      = get_post_meta( $post->ID, '_nm_price', true );
		$duration   = get_post_meta( $post->ID, '_nm_duration', true );
		$doctor_ids = get_post_meta( $post->ID, '_nm_doctor_ids', true );
		$clinic_ids = get_post_meta( $post->ID, '_nm_clinic_ids', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="nm_price"><?php esc_html_e( 'قیمت (تومان)', 'nobatmed-core' ); ?></label></th>
				<td><input type="number" min="0" id="nm_price" name="nm_price" value="<?php echo esc_attr( (string) $price ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_duration"><?php esc_html_e( 'مدت (دقیقه)', 'nobatmed-core' ); ?></label></th>
				<td><input type="number" min="5" step="5" id="nm_duration" name="nm_duration" value="<?php echo esc_attr( (string) $duration ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_doctor_ids"><?php esc_html_e( 'شناسه پزشکان', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_doctor_ids" name="nm_doctor_ids" value="<?php echo esc_attr( (string) $doctor_ids ); ?>"></td>
			</tr>
			<tr>
				<th><label for="nm_service_clinic_ids"><?php esc_html_e( 'شناسه کلینیک‌ها', 'nobatmed-core' ); ?></label></th>
				<td><input type="text" class="regular-text" id="nm_service_clinic_ids" name="nm_service_clinic_ids" value="<?php echo esc_attr( (string) $clinic_ids ); ?>"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta fields.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['nm_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nm_profile_nonce'] ) ), 'nm_save_profile_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( self::POST_DOCTOR, self::POST_CLINIC, self::POST_SERVICE ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( self::POST_DOCTOR === $post_type ) {
			$this->update_meta( $post_id, '_nm_medical_number', 'nm_medical_number' );
			$this->update_meta( $post_id, '_nm_phone', 'nm_phone' );
			$this->update_meta( $post_id, '_nm_email', 'nm_email' );
			$this->update_meta( $post_id, '_nm_experience_years', 'nm_experience_years' );
			$this->update_meta( $post_id, '_nm_education', 'nm_education' );
			$this->update_meta( $post_id, '_nm_insurance', 'nm_insurance' );
			$this->update_meta( $post_id, '_nm_clinic_ids', 'nm_clinic_ids' );
			$this->update_meta( $post_id, '_nm_faq', 'nm_faq' );
		}

		if ( self::POST_CLINIC === $post_type ) {
			$this->update_meta( $post_id, '_nm_address', 'nm_address' );
			$this->update_meta( $post_id, '_nm_phone', 'nm_clinic_phone' );
			$this->update_meta( $post_id, '_nm_lat', 'nm_lat' );
			$this->update_meta( $post_id, '_nm_lng', 'nm_lng' );
			$this->update_meta( $post_id, '_nm_hours', 'nm_hours' );
			$this->update_meta( $post_id, '_nm_gallery', 'nm_gallery' );
		}

		if ( self::POST_SERVICE === $post_type ) {
			$this->update_meta( $post_id, '_nm_price', 'nm_price' );
			$this->update_meta( $post_id, '_nm_duration', 'nm_duration' );
			$this->update_meta( $post_id, '_nm_doctor_ids', 'nm_doctor_ids' );
			$this->update_meta( $post_id, '_nm_clinic_ids', 'nm_service_clinic_ids' );
		}
	}

	/**
	 * Update single meta from POST.
	 */
	private function update_meta( int $post_id, string $meta_key, string $field ): void {
		if ( ! isset( $_POST[ $field ] ) ) {
			return;
		}
		update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
	}

	/**
	 * REST summary for dashboard.
	 */
	public function register_rest_routes(): void {
		$this->register_rest_route(
			'/profiles/stats',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_stats' ),
				),
			)
		);

		$this->register_rest_route(
			'/profiles/options',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_options' ),
				),
			)
		);
	}

	/**
	 * Profile counts.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_stats(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'doctors'  => wp_count_posts( self::POST_DOCTOR )->publish,
					'clinics'  => wp_count_posts( self::POST_CLINIC )->publish,
					'services' => wp_count_posts( self::POST_SERVICE )->publish,
				),
			)
		);
	}

	/**
	 * Published posts for booking dropdowns.
	 */
	public function rest_options(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'doctors'  => $this->get_post_options( self::POST_DOCTOR ),
					'clinics'  => $this->get_post_options( self::POST_CLINIC ),
					'services' => $this->get_post_options( self::POST_SERVICE ),
				),
			)
		);
	}

	/**
	 * @return array<int,array{id:int,title:string}>
	 */
	private function get_post_options( string $post_type ): array {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$options = array();
		foreach ( $posts as $post ) {
			$options[] = array(
				'id'    => (int) $post->ID,
				'title' => get_the_title( $post ),
			);
		}
		return $options;
	}
}
