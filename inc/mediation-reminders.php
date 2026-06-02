<?php
/**
 * Mediation Intake — Reminder & Scheduling System
 *
 * Handles automated Agreement and Brief reminder emails via OttoKit webhooks.
 * Cron jobs are scheduled at intake submit time; callbacks check ACF field
 * status before dispatching so signed/received parties are skipped.
 *
 * ACF sub-fields required on each party repeater (plaintiffs, defendants, third_parties):
 *   - mediation_agreement_signed  (True/False, default false)
 *   - discovery_call_completed    (True/False, default false) — tracking only, no automation
 *   - mediation_brief_received    (True/False, default false)
 */

// =============================================================================
// Admin Submenu — Reminders
// =============================================================================

function summit_reminders_register_submenu() {
	add_submenu_page(
		'edit.php?post_type=mediation_intake',
		'Reminders',
		'Reminders',
		'manage_options',
		'summit-intake-reminders',
		'summit_reminders_render_page'
	);
}
add_action( 'admin_menu', 'summit_reminders_register_submenu' );

// =============================================================================
// Settings Registration
// =============================================================================

function summit_reminders_register_settings() {
	register_setting( 'summit_reminders_settings', 'summit_reminders_ottokit_webhook_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	] );

	register_setting( 'summit_reminders_settings', 'summit_agreement_reminder_intervals', [
		'type'              => 'array',
		'sanitize_callback' => 'summit_reminders_sanitize_intervals',
		'default'           => summit_reminders_default_agreement_intervals(),
	] );

	register_setting( 'summit_reminders_settings', 'summit_brief_reminder_intervals', [
		'type'              => 'array',
		'sanitize_callback' => 'summit_reminders_sanitize_intervals',
		'default'           => summit_reminders_default_brief_intervals(),
	] );
}
add_action( 'admin_init', 'summit_reminders_register_settings' );

// =============================================================================
// Defaults
// =============================================================================

function summit_reminders_default_agreement_intervals() {
	return [
		[ 'days' => 90, 'enabled' => true ],
		[ 'days' => 60, 'enabled' => true ],
		[ 'days' => 30, 'enabled' => true ],
		[ 'days' => 15, 'enabled' => true ],
		[ 'days' => 10, 'enabled' => true ],
	];
}

function summit_reminders_default_brief_intervals() {
	return [
		[ 'days' => 20, 'enabled' => true ],
		[ 'days' => 10, 'enabled' => true ],
	];
}

// =============================================================================
// Sanitize Callback
// =============================================================================

function summit_reminders_sanitize_intervals( $raw ) {
	if ( ! is_array( $raw ) ) return [];
	$clean = [];
	foreach ( $raw as $row ) {
		$days    = absint( $row['days'] ?? 0 );
		$enabled = ! empty( $row['enabled'] );
		if ( $days > 0 ) {
			$clean[] = [ 'days' => $days, 'enabled' => $enabled ];
		}
	}
	return $clean;
}

// =============================================================================
// Settings Page
// =============================================================================

function summit_reminders_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$agreement_intervals = get_option( 'summit_agreement_reminder_intervals', summit_reminders_default_agreement_intervals() );
	$brief_intervals     = get_option( 'summit_brief_reminder_intervals', summit_reminders_default_brief_intervals() );
	$webhook_url         = get_option( 'summit_reminders_ottokit_webhook_url', '' );
	?>
	<div class="wrap">
		<h1>Mediation Reminders</h1>
		<p>Configure automated reminder emails sent to counsel via OttoKit. Reminders are scheduled when an intake is submitted and fire based on days before the booking date.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'summit_reminders_settings' ); ?>

			<h2>OttoKit Webhook URL (Reminders)</h2>
			<p>Dedicated webhook URL for reminder emails. If left blank, falls back to the main OttoKit webhook URL in Settings.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="summit_reminders_ottokit_webhook_url">Webhook URL</label></th>
					<td>
						<input type="url" id="summit_reminders_ottokit_webhook_url"
							   name="summit_reminders_ottokit_webhook_url"
							   value="<?php echo esc_attr( $webhook_url ); ?>"
							   class="regular-text" placeholder="https://..." />
					</td>
				</tr>
			</table>

			<hr>

			<h2>Agreement Reminder Intervals</h2>
			<p>Reminders sent to counsel who have not yet returned a signed agreement. The <strong>Agreement Signed</strong> toggle in each intake skips parties that have signed.</p>
			<?php summit_reminders_render_interval_table( 'summit_agreement_reminder_intervals', $agreement_intervals ); ?>

			<hr>

			<h2>Brief Request &amp; Reminder Intervals</h2>
			<p>The 20-day entry is the initial brief request (sent to all counsel). The 10-day entry is a follow-up reminder for counsel who have not yet submitted — checked against the <strong>Brief Received</strong> toggle.</p>
			<?php summit_reminders_render_interval_table( 'summit_brief_reminder_intervals', $brief_intervals ); ?>

			<?php submit_button( 'Save Reminder Settings' ); ?>
		</form>
	</div>
	<?php
}

function summit_reminders_render_interval_table( $option_name, $intervals ) {
	?>
	<table class="widefat striped" style="max-width:420px;margin-bottom:1rem;">
		<thead>
			<tr>
				<th style="width:70px;text-align:center;">Enabled</th>
				<th>Days Before Booking</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $intervals as $i => $rule ) : ?>
			<tr>
				<td style="text-align:center;">
					<input type="checkbox"
						   name="<?php echo esc_attr( $option_name ); ?>[<?php echo $i; ?>][enabled]"
						   value="1"
						   <?php checked( ! empty( $rule['enabled'] ) ); ?> />
				</td>
				<td>
					<input type="number" min="1" max="365"
						   name="<?php echo esc_attr( $option_name ); ?>[<?php echo $i; ?>][days]"
						   value="<?php echo absint( $rule['days'] ); ?>"
						   style="width:80px;" /> days
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

// =============================================================================
// Cron Scheduling Helpers
// =============================================================================

/**
 * Schedule Agreement reminder crons for a given intake.
 *
 * @param int    $post_id          Mediation intake post ID.
 * @param string $booking_date_raw MySQL datetime string (e.g. "2026-08-15 10:00:00").
 */
function summit_reminders_schedule_agreement( $post_id, $booking_date_raw ) {
	$intervals = get_option( 'summit_agreement_reminder_intervals', summit_reminders_default_agreement_intervals() );
	foreach ( $intervals as $rule ) {
		if ( empty( $rule['enabled'] ) ) continue;
		$ts   = strtotime( $booking_date_raw ) - ( absint( $rule['days'] ) * DAY_IN_SECONDS );
		$args = [ $post_id ];
		if ( $ts > time() && ! wp_next_scheduled( 'summit_agreement_reminder_check', $args ) ) {
			wp_schedule_single_event( $ts, 'summit_agreement_reminder_check', $args );
		}
	}
}

/**
 * Schedule the 20-day Brief request cron (if date is still in the future).
 *
 * @param int    $post_id
 * @param string $booking_date_raw
 */
function summit_reminders_schedule_brief_request( $post_id, $booking_date_raw ) {
	$ts   = strtotime( $booking_date_raw ) - ( 20 * DAY_IN_SECONDS );
	$args = [ $post_id ];
	if ( $ts > time() && ! wp_next_scheduled( 'summit_brief_request_send', $args ) ) {
		wp_schedule_single_event( $ts, 'summit_brief_request_send', $args );
	}
}

/**
 * Schedule the 10-day Brief reminder cron (if date is still in the future).
 *
 * @param int    $post_id
 * @param string $booking_date_raw
 */
function summit_reminders_schedule_brief_reminder( $post_id, $booking_date_raw ) {
	$ts   = strtotime( $booking_date_raw ) - ( 10 * DAY_IN_SECONDS );
	$args = [ $post_id ];
	if ( $ts > time() && ! wp_next_scheduled( 'summit_brief_reminder_check', $args ) ) {
		wp_schedule_single_event( $ts, 'summit_brief_reminder_check', $args );
	}
}

// =============================================================================
// Cron Callbacks
// =============================================================================

/**
 * Fires at each configured Agreement reminder interval.
 * Skips parties whose Agreement Signed toggle is true.
 */
function summit_agreement_reminder_check( $post_id ) {
	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);
	foreach ( $parties as $party ) {
		if ( ! empty( $party['mediation_agreement_signed'] ) ) continue;
		if ( empty( $party['counsel_email'] ) ) continue;
		summit_reminders_dispatch( $post_id, 'agreement_reminder', $party['counsel_email'], $party['counsel_name'] ?? '' );
	}
}
add_action( 'summit_agreement_reminder_check', 'summit_agreement_reminder_check' );

/**
 * Fires 20 days before booking. Sends brief request to all counsel.
 */
function summit_brief_request_send( $post_id ) {
	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);
	foreach ( $parties as $party ) {
		if ( empty( $party['counsel_email'] ) ) continue;
		summit_reminders_dispatch( $post_id, 'brief_request', $party['counsel_email'], $party['counsel_name'] ?? '' );
	}
}
add_action( 'summit_brief_request_send', 'summit_brief_request_send' );

/**
 * Fires 10 days before booking. Skips parties whose Brief Received toggle is true.
 */
function summit_brief_reminder_check( $post_id ) {
	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);
	foreach ( $parties as $party ) {
		if ( ! empty( $party['mediation_brief_received'] ) ) continue;
		if ( empty( $party['counsel_email'] ) ) continue;
		summit_reminders_dispatch( $post_id, 'brief_reminder', $party['counsel_email'], $party['counsel_name'] ?? '' );
	}
}
add_action( 'summit_brief_reminder_check', 'summit_brief_reminder_check' );

// =============================================================================
// Dispatch Helper
// =============================================================================

/**
 * Fire a non-blocking webhook to OttoKit for a single counsel.
 *
 * @param int    $post_id       Mediation intake post ID.
 * @param string $type          Webhook type: agreement_request | agreement_reminder | brief_request | brief_reminder.
 * @param string $counsel_email Recipient email.
 * @param string $counsel_name  Recipient name (for email greeting).
 */
function summit_reminders_dispatch( $post_id, $type, $counsel_email, $counsel_name ) {
	// Dedicated reminders URL takes priority; falls back to main webhook URL.
	$webhook_url = get_option( 'summit_reminders_ottokit_webhook_url', '' )
		?: get_option( 'summit_intake_ottokit_webhook_url', '' );
	if ( ! $webhook_url ) return;

	$booking_date_raw = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	$booking_date_fmt = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$case_name        = preg_replace( '/^Intake\s*[—\-]+\s*/u', '', get_the_title( $post_id ) );

	$team_member_id = get_field( 'team_member', $post_id );
	$mediator_name  = $team_member_id ? get_the_title( $team_member_id ) : '';

	$days_until = null;
	if ( $booking_date_raw ) {
		$days_until = (int) ceil( ( strtotime( $booking_date_raw ) - time() ) / DAY_IN_SECONDS );
	}

	$upload_url_map = get_post_meta( $post_id, '_summit_counsel_upload_urls', true ) ?: [];
	$upload_url     = $upload_url_map[ $counsel_email ] ?? '';

	wp_remote_post( $webhook_url, [
		'headers'  => [ 'Content-Type' => 'application/json' ],
		'body'     => wp_json_encode( [
			'type'               => $type,
			'intake_id'          => $post_id,
			'case_name'          => $case_name,
			'booking_date'       => $booking_date_fmt,
			'booking_date_raw'   => $booking_date_raw,
			'mediator_name'      => $mediator_name,
			'zoom_join_url'      => get_post_meta( $post_id, '_summit_intake_zoom_url', true ) ?: '',
			'counsel_email'      => $counsel_email,
			'counsel_name'       => $counsel_name,
			'days_until_booking' => $days_until,
			'expedited'          => (bool) get_post_meta( $post_id, '_summit_intake_expedited', true ),
			'upload_url'         => $upload_url,
		] ),
		'timeout'  => 10,
		'blocking' => false,
	] );
}

// =============================================================================
// Expedited Badge — Admin List
// =============================================================================

/**
 * Add an Expedited column to the Mediation Intakes list table.
 */
function summit_reminders_add_expedited_column( $columns ) {
	$new = [];
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['expedited'] = 'Expedited';
		}
	}
	return $new;
}
add_filter( 'manage_mediation_intake_posts_columns', 'summit_reminders_add_expedited_column' );

/**
 * Render the Expedited column value.
 */
function summit_reminders_render_expedited_column( $column, $post_id ) {
	if ( $column !== 'expedited' ) return;
	if ( get_post_meta( $post_id, '_summit_intake_expedited', true ) ) {
		echo '<span style="color:#b32d2e;font-weight:600;" title="Booking was within 30 days of intake submission">&#9873; Expedited</span>';
	} else {
		echo '<span style="color:#999;">—</span>';
	}
}
add_action( 'manage_mediation_intake_posts_custom_column', 'summit_reminders_render_expedited_column', 10, 2 );
