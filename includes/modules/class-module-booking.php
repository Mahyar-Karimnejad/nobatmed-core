<?php
/**
 * Appointments, schedules, and holidays (custom tables).
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Booking module — foundation for add-ons (SMS, payment, telemedicine).
 */
class NobatMed_Module_Booking extends NobatMed_Module {

	public function get_id(): string {
		return 'booking';
	}

	public function get_name(): string {
		return __( 'نوبت‌دهی', 'nobatmed-core' );
	}

	public function get_description(): string {
		return __( 'برنامه کاری، اسلات‌ها و رزرو نوبت.', 'nobatmed-core' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_dependencies(): array {
		return array( 'profiles' );
	}

	public function boot(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Resolve post title for list rows.
	 */
	private function title_for( int $post_id ): string {
		if ( $post_id <= 0 ) {
			return '—';
		}
		$title = get_the_title( $post_id );
		return '' !== $title ? $title : '#' . $post_id;
	}

	/**
	 * Register booking REST routes.
	 */
	public function register_rest_routes(): void {
		$this->register_rest_route(
			'/booking/stats',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_stats' ),
				),
			)
		);

		$this->register_rest_route(
			'/booking/schedules',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_list_schedules' ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_create_schedule' ),
				),
			)
		);

		$this->register_rest_route(
			'/booking/appointments',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_list_appointments' ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_create_appointment' ),
				),
			)
		);

		$this->register_rest_route(
			'/booking/slots',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_available_slots' ),
					'args'                => array(
						'doctor_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'date'      => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		$this->register_rest_route(
			'/booking/appointments/(?P<id>\d+)/cancel',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'rest_cancel_appointment' ),
				),
			)
		);
	}

	/**
	 * Table readiness + counts.
	 */
	public function rest_stats(): WP_REST_Response {
		$ready = NobatMed_DB::table_exists( NobatMed_DB::TABLE_SCHEDULES )
			&& NobatMed_DB::table_exists( NobatMed_DB::TABLE_APPOINTMENTS );

		global $wpdb;
		$schedules    = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$appointments = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );

		$schedule_count = $ready
			? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$schedules}" ) // phpcs:ignore
			: 0;
		$appointment_count = $ready
			? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$appointments}" ) // phpcs:ignore
			: 0;

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'tablesReady'     => $ready,
					'schedules'       => $schedule_count,
					'appointments'    => $appointment_count,
					'schemaVersion'   => NobatMed_DB::get_schema_version(),
				),
			)
		);
	}

	/**
	 * List schedules.
	 */
	public function rest_list_schedules(): WP_REST_Response {
		global $wpdb;
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 50", ARRAY_A ); // phpcs:ignore

		$list = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$list[] = array_merge(
				$row,
				array(
					'doctor_title' => $this->title_for( (int) $row['doctor_id'] ),
					'clinic_title' => $this->title_for( (int) $row['clinic_id'] ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'schedules' => $list ),
			)
		);
	}

	/**
	 * Create schedule slot.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_create_schedule( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$now   = current_time( 'mysql' );
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );

		$inserted = $wpdb->insert(
			$table,
			array(
				'doctor_id'     => isset( $params['doctor_id'] ) ? (int) $params['doctor_id'] : 0,
				'clinic_id'     => isset( $params['clinic_id'] ) ? (int) $params['clinic_id'] : 0,
				'day_of_week'   => isset( $params['day_of_week'] ) ? (int) $params['day_of_week'] : 0,
				'start_time'    => isset( $params['start_time'] ) ? sanitize_text_field( (string) $params['start_time'] ) : '09:00:00',
				'end_time'      => isset( $params['end_time'] ) ? sanitize_text_field( (string) $params['end_time'] ) : '17:00:00',
				'slot_duration' => isset( $params['slot_duration'] ) ? (int) $params['slot_duration'] : 30,
				'capacity'      => isset( $params['capacity'] ) ? (int) $params['capacity'] : 1,
				'visit_type'    => isset( $params['visit_type'] ) ? sanitize_text_field( (string) $params['visit_type'] ) : 'in_person',
				'status'        => 'active',
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);

		if ( ! $inserted ) {
			return rest_ensure_response( array( 'success' => false, 'message' => __( 'ثبت برنامه انجام نشد.', 'nobatmed-core' ) ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'برنامه کاری ثبت شد.', 'nobatmed-core' ),
				'data'    => array( 'id' => (int) $wpdb->insert_id ),
			)
		);
	}

	/**
	 * List appointments.
	 */
	public function rest_list_appointments(): WP_REST_Response {
		global $wpdb;
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY appointment_date DESC, start_time DESC LIMIT 50", ARRAY_A ); // phpcs:ignore

		$list = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$list[] = array_merge(
				$row,
				array(
					'doctor_title'  => $this->title_for( (int) $row['doctor_id'] ),
					'clinic_title'  => $this->title_for( (int) $row['clinic_id'] ),
					'service_title' => $this->title_for( (int) $row['service_id'] ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'appointments' => $list ),
			)
		);
	}

	/**
	 * Create appointment.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_create_appointment( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$now   = current_time( 'mysql' );
		$table = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );

		$inserted = $wpdb->insert(
			$table,
			array(
				'doctor_id'        => isset( $params['doctor_id'] ) ? (int) $params['doctor_id'] : 0,
				'clinic_id'        => isset( $params['clinic_id'] ) ? (int) $params['clinic_id'] : 0,
				'service_id'       => isset( $params['service_id'] ) ? (int) $params['service_id'] : 0,
				'patient_id'       => isset( $params['patient_id'] ) ? (int) $params['patient_id'] : 0,
				'appointment_date' => isset( $params['appointment_date'] ) ? sanitize_text_field( (string) $params['appointment_date'] ) : gmdate( 'Y-m-d' ),
				'start_time'       => isset( $params['start_time'] ) ? sanitize_text_field( (string) $params['start_time'] ) : '10:00:00',
				'end_time'         => isset( $params['end_time'] ) ? sanitize_text_field( (string) $params['end_time'] ) : '10:30:00',
				'visit_type'       => isset( $params['visit_type'] ) ? sanitize_text_field( (string) $params['visit_type'] ) : 'in_person',
				'status'           => isset( $params['status'] ) ? sanitize_text_field( (string) $params['status'] ) : 'pending',
				'notes'            => isset( $params['notes'] ) ? sanitize_textarea_field( (string) $params['notes'] ) : '',
				'created_at'       => $now,
				'updated_at'       => $now,
			)
		);

		if ( ! $inserted ) {
			return rest_ensure_response( array( 'success' => false, 'message' => __( 'ثبت نوبت انجام نشد.', 'nobatmed-core' ) ) );
		}

		/**
		 * Fires after appointment created — add-ons (SMS, payment) hook here.
		 *
		 * @param int                  $appointment_id New appointment ID.
		 * @param array<string,mixed>  $params         Request params.
		 */
		do_action( 'nobatmed_appointment_created', (int) $wpdb->insert_id, $params );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'نوبت ثبت شد.', 'nobatmed-core' ),
				'data'    => array( 'id' => (int) $wpdb->insert_id ),
			)
		);
	}

	/**
	 * Available time slots for doctor on a date.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_available_slots( WP_REST_Request $request ): WP_REST_Response {
		$doctor_id = (int) $request->get_param( 'doctor_id' );
		$date      = (string) $request->get_param( 'date' );

		if ( $doctor_id <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'پزشک و تاریخ معتبر انتخاب کنید.', 'nobatmed-core' ),
				)
			);
		}

		$slots = $this->compute_slots( $doctor_id, $date );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'date'      => $date,
					'jalali'    => $this->gregorian_to_jalali_label( $date ),
					'doctor_id' => $doctor_id,
					'slots'     => $slots,
				),
			)
		);
	}

	/**
	 * Build slot list from weekly schedules minus booked appointments.
	 *
	 * @return array<int,array{start:string,end:string,label:string}>
	 */
	private function compute_slots( int $doctor_id, string $date ): array {
		global $wpdb;

		$day_of_week = (int) gmdate( 'w', strtotime( $date . ' 12:00:00' ) );
		$table       = NobatMed_DB::table( NobatMed_DB::TABLE_SCHEDULES );
		$schedules   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE doctor_id = %d AND day_of_week = %d AND status = %s",
				$doctor_id,
				$day_of_week,
				'active'
			),
			ARRAY_A
		);

		if ( ! is_array( $schedules ) || empty( $schedules ) ) {
			return array();
		}

		$ap_table = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );
		$booked   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT start_time, end_time FROM {$ap_table} WHERE doctor_id = %d AND appointment_date = %s AND status NOT IN ('cancelled')",
				$doctor_id,
				$date
			),
			ARRAY_A
		);

		$booked_ranges = is_array( $booked ) ? $booked : array();
		$slots         = array();

		foreach ( $schedules as $schedule ) {
			$duration = max( 5, (int) $schedule['slot_duration'] );
			$cursor   = strtotime( $date . ' ' . $schedule['start_time'] );
			$end      = strtotime( $date . ' ' . $schedule['end_time'] );

			while ( $cursor + ( $duration * 60 ) <= $end ) {
				$slot_start = gmdate( 'H:i:s', $cursor );
				$slot_end   = gmdate( 'H:i:s', $cursor + ( $duration * 60 ) );

				if ( ! $this->slot_is_booked( $slot_start, $slot_end, $booked_ranges ) ) {
					$slots[] = array(
						'start' => $slot_start,
						'end'   => $slot_end,
						'label' => substr( $slot_start, 0, 5 ) . ' – ' . substr( $slot_end, 0, 5 ),
					);
				}

				$cursor += $duration * 60;
			}
		}

		return $slots;
	}

	/**
	 * @param array<int,array{start_time:string,end_time:string}> $booked Booked rows.
	 */
	private function slot_is_booked( string $start, string $end, array $booked ): bool {
		$slot_start = strtotime( '1970-01-01 ' . $start );
		$slot_end   = strtotime( '1970-01-01 ' . $end );

		foreach ( $booked as $row ) {
			$b_start = strtotime( '1970-01-01 ' . $row['start_time'] );
			$b_end   = strtotime( '1970-01-01 ' . $row['end_time'] );
			if ( $slot_start < $b_end && $slot_end > $b_start ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Simple Gregorian → Jalali label for admin UI.
	 */
	private function gregorian_to_jalali_label( string $date ): string {
		list( $gy, $gm, $gd ) = array_map( 'intval', explode( '-', $date ) );
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + (int) floor( ( $gy2 + 3 ) / 4 ) - (int) floor( ( $gy2 + 99 ) / 100 ) + (int) floor( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * (int) floor( $days / 12053 ) );
		$days %= 12053;
		$jy   += 4 * (int) floor( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			$jy   += (int) floor( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}
		$jm = ( $days < 186 ) ? 1 + (int) floor( $days / 31 ) : 7 + (int) floor( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days < 186 ) ? ( $days % 31 ) : ( ( $days - 186 ) % 30 ) );

		return sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );
	}

	/**
	 * Cancel an appointment.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_cancel_appointment( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$id = (int) $request->get_param( 'id' );
		if ( $id <= 0 || ! NobatMed_DB::table_exists( NobatMed_DB::TABLE_APPOINTMENTS ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'نوبت یافت نشد.', 'nobatmed-core' ),
				)
			);
		}

		$table = NobatMed_DB::table( NobatMed_DB::TABLE_APPOINTMENTS );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore

		if ( ! is_array( $row ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'نوبت یافت نشد.', 'nobatmed-core' ),
				)
			);
		}

		if ( 'cancelled' === ( $row['status'] ?? '' ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'این نوبت قبلاً لغو شده است.', 'nobatmed-core' ),
				)
			);
		}

		$updated = $wpdb->update(
			$table,
			array(
				'status'     => 'cancelled',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'لغو نوبت با خطا مواجه شد.', 'nobatmed-core' ),
				)
			);
		}

		do_action( 'nobatmed_appointment_cancelled', $id, $row );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'نوبت لغو شد.', 'nobatmed-core' ),
				'data'    => array( 'id' => $id, 'status' => 'cancelled' ),
			)
		);
	}
}
