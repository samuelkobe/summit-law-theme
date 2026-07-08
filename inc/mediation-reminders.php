<?php
/**
 * Mediation Intake — Reminder & Scheduling System
 *
 * Handles automated reminder emails via wp_mail(). Cron jobs are scheduled at
 * intake submit time; callbacks check ACF field status before sending so
 * signed/received parties are skipped.
 *
 * Email types: agreement_reminder | brief_request | brief_reminder | session_reminder
 *
 * ACF sub-fields required on each party repeater (plaintiffs, defendants, third_parties):
 *   - mediation_agreement_signed  (True/False, default false)
 *   - discovery_call_completed    (True/False, default false) — tracking only, no automation
 *   - mediation_brief_received    (True/False, default false)
 */

// =============================================================================
// Settings Registration
// =============================================================================

function summit_reminders_register_settings() {
	// Interval schedules.
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

	// Session reminder days-before settings (two reminders).
	register_setting( 'summit_reminders_settings', 'summit_session_reminder_days', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 2,
	] ); // kept for backward compat — new intakes use the two settings below
	register_setting( 'summit_reminders_settings', 'summit_session_reminder_1_days', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 7,
	] );
	register_setting( 'summit_reminders_settings', 'summit_session_reminder_2_days', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 1,
	] );

	// Session internal notification (team + mediator).
	register_setting( 'summit_reminders_settings', 'summit_session_internal_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Upcoming Session — {case_name} — {booking_date}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_session_internal_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "This is an internal reminder that a mediation session is upcoming.\n\nCase: {case_name}\nDate: {booking_date}\nMediator: {mediator_name}\nZoom: {zoom_url}\n\nSession reminder emails have been sent to all parties.",
	] );

	// Discovery call initiation (team email only).
	register_setting( 'summit_reminders_settings', 'summit_discovery_call_days', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 45,
	] );
	register_setting( 'summit_reminders_settings', 'summit_discovery_call_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Discovery Calls Due — {case_name}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_discovery_call_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "This is a reminder to initiate discovery calls for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nPlease reach out to all parties to arrange discovery calls.",
	] );

	// Post-reminder team notification.
	register_setting( 'summit_reminders_settings', 'summit_team_notification_offset_days', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 1,
	] );
	register_setting( 'summit_reminders_settings', 'summit_team_notification_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Reminder Follow-up — {case_name} — {reminder_type}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_team_notification_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "{party_count} {reminder_type} email(s) were sent yesterday for {case_name} (session: {booking_date}).\n\nPlease check whether additional documents or responses have been submitted.",
	] );

	// Email subjects.
	register_setting( 'summit_reminders_settings', 'summit_agreement_reminder_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Reminder: Mediation Agreement Required — {case_name}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_brief_request_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Mediation Brief Request — {case_name}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_brief_reminder_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Reminder: Mediation Brief Due — {case_name}',
	] );
	register_setting( 'summit_reminders_settings', 'summit_session_reminder_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Upcoming Mediation — {case_name} — {booking_date}',
	] );

	// Email bodies.
	register_setting( 'summit_reminders_settings', 'summit_agreement_reminder_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nThis is a reminder that we have not yet received your signed Mediation Agreement for <b>{case_name}</b>, scheduled for <b>{booking_date}</b>.\n\nYour session is <b>{days_until_booking} days away</b>. Please sign and return the agreement as soon as possible using your secure upload link:\n\n{upload_url}\n\nIf you have misplaced the original agreement, you can download the most recent version directly from your upload page at the link above.\n\nIf you have already submitted your agreement, please disregard this message. If you have any questions or concerns, contact us at <a href=\"mailto:info@summitlaw.ca\">info@summitlaw.ca</a> or (613) 749-4700.\n\nSincerely,\n<b>Summit Law LLP — Mediation Services</b>",
	] );
	register_setting( 'summit_reminders_settings', 'summit_brief_request_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nPlease prepare and submit your Mediation Brief for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nYour brief is due at least 10 days before the mediation date. Please upload it using your secure link:\n{upload_url}\n\nThank you,\nSummit Law LLP",
	] );
	register_setting( 'summit_reminders_settings', 'summit_brief_reminder_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nThis is a reminder that we have not yet received your Mediation Brief for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nYour mediation is in {days_until_booking} days. Please upload your brief as soon as possible:\n{upload_url}\n\nThank you,\nSummit Law LLP",
	] );
	register_setting( 'summit_reminders_settings', 'summit_session_reminder_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nThis is a reminder that your mediation session for {case_name} is coming up.\n\nDate: {booking_date}\nMediator: {mediator_name}\nZoom: {zoom_url}\n\nCalendar links are included below.\n\nThank you,\nSummit Law LLP",
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
// Interval Table Helper (reused in tabbed settings page)
// =============================================================================

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
 * Schedule the Brief request and reminder crons.
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

function summit_reminders_schedule_brief_reminder( $post_id, $booking_date_raw ) {
	$ts   = strtotime( $booking_date_raw ) - ( 10 * DAY_IN_SECONDS );
	$args = [ $post_id ];
	if ( $ts > time() && ! wp_next_scheduled( 'summit_brief_reminder_check', $args ) ) {
		wp_schedule_single_event( $ts, 'summit_brief_reminder_check', $args );
	}
}

/**
 * Schedule two session reminder crons (configurable days before booking).
 *
 * @param int    $post_id
 * @param string $booking_date_raw
 */
function summit_reminders_schedule_session( $post_id, $booking_date_raw ) {
	$booking_ts = strtotime( $booking_date_raw );
	foreach ( [ 1 => 7, 2 => 1 ] as $num => $default ) {
		$days = absint( get_option( "summit_session_reminder_{$num}_days", $default ) );
		$ts   = $booking_ts - ( $days * DAY_IN_SECONDS );
		$hook = "summit_session_reminder_{$num}_send";
		$args = [ $post_id ];
		if ( $ts > time() && ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_single_event( $ts, $hook, $args );
		}
	}
}

/**
 * Schedule the Discovery Call initiation email to the team.
 *
 * @param int    $post_id
 * @param string $booking_date_raw
 */
function summit_reminders_schedule_discovery_call( $post_id, $booking_date_raw ) {
	$days = absint( get_option( 'summit_discovery_call_days', 45 ) );
	$ts   = strtotime( $booking_date_raw ) - ( $days * DAY_IN_SECONDS );
	$args = [ $post_id ];
	if ( $ts > time() && ! wp_next_scheduled( 'summit_discovery_call_send', $args ) ) {
		wp_schedule_single_event( $ts, 'summit_discovery_call_send', $args );
	}
}

/**
 * Schedule a post-reminder team notification (fires $offset days after reminder batch).
 *
 * @param int    $post_id
 * @param string $type    Reminder type slug (agreement_reminder|brief_request|brief_reminder).
 * @param int    $count   Number of party emails sent in this batch.
 */
function summit_reminders_schedule_team_notification( $post_id, $type, $count ) {
	$offset = absint( get_option( 'summit_team_notification_offset_days', 1 ) );
	$ts     = time() + ( $offset * DAY_IN_SECONDS );
	wp_schedule_single_event( $ts, 'summit_team_reminder_notification', [ $post_id, $type, $count ] );
}

/**
 * Unschedule all pending cron events for a given intake post.
 * Safe to call even if no events are queued — wp_clear_scheduled_hook() is a no-op then.
 *
 * @param int $post_id Mediation intake post ID.
 */
function summit_reminders_unschedule_all( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) return;

	// Single-arg hooks — all use [ $post_id ] as the args array.
	$single_arg_hooks = [
		'summit_agreement_reminder_check',
		'summit_brief_request_send',
		'summit_brief_reminder_check',
		'summit_session_reminder_1_send',
		'summit_session_reminder_2_send',
		'summit_session_reminder_send',  // legacy hook — kept for any already-queued events
		'summit_discovery_call_send',
	];
	foreach ( $single_arg_hooks as $hook ) {
		wp_clear_scheduled_hook( $hook, [ $post_id ] );
	}

	// summit_team_reminder_notification uses [ $post_id, $type, $count ] — args vary,
	// so iterate the cron array to find and remove all instances for this post.
	$crons = _get_cron_array();
	if ( ! is_array( $crons ) ) return;

	foreach ( $crons as $timestamp => $hooks_at_time ) {
		if ( ! isset( $hooks_at_time['summit_team_reminder_notification'] ) ) continue;
		foreach ( $hooks_at_time['summit_team_reminder_notification'] as $event ) {
			$args = $event['args'] ?? [];
			if ( ! empty( $args[0] ) && (int) $args[0] === $post_id ) {
				wp_unschedule_event( $timestamp, 'summit_team_reminder_notification', $args );
			}
		}
	}
}

// =============================================================================
// wp_mail() Send Helper
// =============================================================================

/**
 * Send a reminder email to a single recipient via wp_mail().
 *
 * @param int    $post_id Mediation intake post ID.
 * @param string $type    Email type: agreement_reminder | brief_request | brief_reminder | session_reminder.
 * @param string $email   Recipient email address.
 * @param string $name    Recipient display name.
 */
function summit_reminders_send_email( $post_id, $type, $email, $name, $cc_email = '' ) {
	$subject_tpl = get_option( 'summit_' . $type . '_subject', '' );
	$body_tpl    = get_option( 'summit_' . $type . '_body', '' );

	if ( ! $subject_tpl || ! $body_tpl || ! $email ) return;

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$booking_raw  = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';
	$url_map      = get_post_meta( $post_id, '_summit_counsel_upload_urls', true ) ?: [];
	$upload_url   = $url_map[ $email ] ?? '';
	$zoom_url     = get_post_meta( $post_id, '_summit_intake_zoom_url', true ) ?: '';

	$days_until = '';
	if ( $booking_raw ) {
		$days_until = (string) (int) ceil( ( strtotime( $booking_raw ) - time() ) / DAY_IN_SECONDS );
	}

	// HTML link versions of actionable URLs (used in body only — subject stays plain text).
	$upload_link = $upload_url ? '<a href="' . esc_url( $upload_url ) . '" style="color:#1a73e8;">Secure File Upload</a>' : '';
	$zoom_link   = $zoom_url   ? '<a href="' . esc_url( $zoom_url )   . '" style="color:#1a73e8;">Zoom Call URL</a>'      : '';

	$tags        = [ '{counsel_name}', '{case_name}', '{booking_date}', '{mediator_name}', '{upload_url}', '{days_until_booking}', '{zoom_url}' ];
	$plain_vals  = [ $name, $case_name, $booking_date, $mediator, $upload_url,   $days_until, $zoom_url   ];
	$html_vals   = [ $name, $case_name, $booking_date, $mediator, $upload_link,  $days_until, $zoom_link  ];

	$subject    = str_replace( $tags, $plain_vals, $subject_tpl );
	$body       = str_replace( $tags, $html_vals,  $body_tpl );

	// Build content — calendar block appended for session reminders, then wrapped in branded template.
	$email_content = nl2br( $body );

	// Append HTML calendar block for session reminders.
	if ( $type === 'session_reminder' && $booking_raw ) {
		// Parse the booking time in the site's configured timezone, then express in UTC for calendar URLs.
		$site_tz    = wp_timezone();
		$dt_obj     = new DateTimeImmutable( $booking_raw, $site_tz );
		$dt_end_obj = $dt_obj->modify( '+3 hours' );
		$booking_ts = $dt_obj->getTimestamp();

		$dt_start = gmdate( 'Ymd\THis\Z', $booking_ts );
		$dt_end   = gmdate( 'Ymd\THis\Z', $dt_end_obj->getTimestamp() );
		$title    = rawurlencode( 'Mediation — ' . $case_name );
		$details  = rawurlencode( 'Mediator: ' . $mediator . ( $zoom_url ? "\nZoom: " . $zoom_url : '' ) );

		$google  = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
				 . '&text=' . $title . '&dates=' . $dt_start . '/' . $dt_end
				 . '&details=' . $details;
		$outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent'
				 . '&subject=' . $title
				 . '&startdt=' . rawurlencode( $dt_obj->format( 'Y-m-d\TH:i:s' ) )
				 . '&enddt='   . rawurlencode( $dt_end_obj->format( 'Y-m-d\TH:i:s' ) )
				 . '&body='    . $details;
		$ics_url = add_query_arg( [
			'post_id' => $post_id,
			'key'     => get_option( 'summit_intake_lookup_secret_key', '' ),
		], rest_url( 'summit/v1/session-ics' ) );

		$email_content .= '<p style="margin-top:1.5em;line-height:2.2;">'
		               . '<strong>— Add to Your Calendar —</strong><br>'
		               . '📅 <a href="' . esc_url( $google )  . '" style="color:#1a73e8;">Google Calendar</a><br>'
		               . '📆 <a href="' . esc_url( $outlook ) . '" style="color:#0078d4;">Outlook</a><br>'
		               . '🗓 <a href="' . esc_url( $ics_url ) . '" style="color:#555555;">Apple / iCal</a>'
		               . '</p>';
	}

	$headers = [
		'Content-Type: ' . $content_type . '; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];
	if ( $cc_email ) {
		$headers[] = 'Cc: ' . $cc_email;
	}

	wp_mail( $email, $subject, summit_intake_email_wrap( $email_content ), $headers );
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
	$sent = 0;
	foreach ( $parties as $party ) {
		if ( ! empty( $party['mediation_agreement_signed'] ) ) continue;
		if ( ! empty( $party['counsel_email'] ) ) {
			$cc = ! empty( $party['assistant_email'] ) ? $party['assistant_email'] : '';
			summit_reminders_send_email( $post_id, 'agreement_reminder', $party['counsel_email'], $party['counsel_name'] ?? '', $cc );
			$sent++;
		}
	}
	if ( $sent > 0 ) {
		summit_reminders_schedule_team_notification( $post_id, 'agreement_reminder', $sent );
	}
}
add_action( 'summit_agreement_reminder_check', 'summit_agreement_reminder_check' );

/**
 * Fires 20 days before booking. Sends brief request to all counsel and their assistants.
 */
function summit_brief_request_send( $post_id ) {
	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);
	$sent = 0;
	foreach ( $parties as $party ) {
		if ( ! empty( $party['counsel_email'] ) ) {
			$cc = ! empty( $party['assistant_email'] ) ? $party['assistant_email'] : '';
			summit_reminders_send_email( $post_id, 'brief_request', $party['counsel_email'], $party['counsel_name'] ?? '', $cc );
			$sent++;
		}
	}
	if ( $sent > 0 ) {
		summit_reminders_schedule_team_notification( $post_id, 'brief_request', $sent );
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
	$sent = 0;
	foreach ( $parties as $party ) {
		if ( ! empty( $party['mediation_brief_received'] ) ) continue;
		if ( ! empty( $party['counsel_email'] ) ) {
			$cc = ! empty( $party['assistant_email'] ) ? $party['assistant_email'] : '';
			summit_reminders_send_email( $post_id, 'brief_reminder', $party['counsel_email'], $party['counsel_name'] ?? '', $cc );
			$sent++;
		}
	}
	if ( $sent > 0 ) {
		summit_reminders_schedule_team_notification( $post_id, 'brief_reminder', $sent );
	}
}
add_action( 'summit_brief_reminder_check', 'summit_brief_reminder_check' );

/**
 * Shared logic for both session reminder crons.
 * Sends session reminder to all parties and fires the internal team/mediator notification.
 */
function summit_session_reminder_fire( $post_id ) {
	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);
	foreach ( $parties as $party ) {
		if ( ! empty( $party['counsel_email'] ) ) {
			$cc = ! empty( $party['assistant_email'] ) ? $party['assistant_email'] : '';
			summit_reminders_send_email( $post_id, 'session_reminder', $party['counsel_email'], $party['counsel_name'] ?? '', $cc );
		}
	}
	summit_reminders_send_session_internal( $post_id );
}

// First session reminder (default 7 days before).
function summit_session_reminder_1_send( $post_id ) { summit_session_reminder_fire( $post_id ); }
add_action( 'summit_session_reminder_1_send', 'summit_session_reminder_1_send' );

// Second session reminder (default 1 day before).
function summit_session_reminder_2_send( $post_id ) { summit_session_reminder_fire( $post_id ); }
add_action( 'summit_session_reminder_2_send', 'summit_session_reminder_2_send' );

// Legacy hook — keeps any already-queued summit_session_reminder_send events working.
add_action( 'summit_session_reminder_send', 'summit_session_reminder_fire' );

/**
 * Send the session internal notification to the Team Notification Email and the mediator.
 */
function summit_reminders_send_session_internal( $post_id ) {
	$team_email     = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	$case_name      = summit_intake_case_name( $post_id );
	$booking_date   = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$team_id        = get_field( 'team_member', $post_id );
	$mediator       = $team_id ? get_the_title( $team_id ) : '';
	$mediator_email = $team_id ? (string) get_field( 'email', $team_id ) : '';
	$zoom_url       = get_post_meta( $post_id, '_summit_intake_zoom_url', true ) ?: '';

	$intake_url = get_admin_url( null, 'post.php?post=' . $post_id . '&action=edit' );

	$tags = [ '{case_name}', '{booking_date}', '{mediator_name}', '{zoom_url}', '{intake_url}' ];
	$vals = [ $case_name, $booking_date, $mediator, $zoom_url, $intake_url ];

	$default_subject = 'Upcoming Session — {case_name} — {booking_date}';
	$default_body    = "This is an internal reminder that a mediation session is upcoming.\n\nCase: {case_name}\nDate: {booking_date}\nMediator: {mediator_name}\nZoom: {zoom_url}\n\nSession reminder emails have been sent to all parties.\n\nView intake: {intake_url}";

	$subject = str_replace( $tags, $vals, get_option( 'summit_session_internal_subject', $default_subject ) );
	$body    = str_replace( $tags, $vals, get_option( 'summit_session_internal_body', $default_body ) );

	if ( ! $subject || ! $body ) return;

	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];

	foreach ( array_unique( array_filter( [ $team_email, $mediator_email ] ) ) as $to ) {
		wp_mail( $to, $subject, summit_intake_email_wrap( nl2br( $body ) ), $headers );
	}
}

/**
 * Send the Discovery Call initiation email to the Team Notification Email.
 */
function summit_discovery_call_send( $post_id ) {
	$team_email = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	if ( ! $team_email ) return;

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';

	$intake_url = get_admin_url( null, 'post.php?post=' . $post_id . '&action=edit' );

	$tags = [ '{case_name}', '{booking_date}', '{mediator_name}', '{intake_url}' ];
	$vals = [ $case_name, $booking_date, $mediator, $intake_url ];

	$default_subject = 'Discovery Calls Due — {case_name}';
	$default_body    = "This is a reminder to initiate discovery calls for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nPlease reach out to all parties to arrange discovery calls.\n\nView intake: {intake_url}";

	$subject = str_replace( $tags, $vals, get_option( 'summit_discovery_call_subject', $default_subject ) );
	$body    = str_replace( $tags, $vals, get_option( 'summit_discovery_call_body', $default_body ) );

	if ( ! $subject || ! $body ) return;

	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];

	wp_mail( $team_email, $subject, summit_intake_email_wrap( nl2br( $body ) ), $headers );
}
add_action( 'summit_discovery_call_send', 'summit_discovery_call_send' );

/**
 * Send the post-reminder team notification (fires offset days after reminder batch).
 */
function summit_team_reminder_notification( $post_id, $type, $count ) {
	$team_email = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	if ( ! $team_email ) return;

	$type_labels = [
		'agreement_reminder' => 'Agreement Reminder',
		'brief_request'      => 'Brief Request',
		'brief_reminder'     => 'Brief Reminder',
	];

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );

	$intake_url = get_admin_url( null, 'post.php?post=' . $post_id . '&action=edit' );

	$tags = [ '{case_name}', '{booking_date}', '{reminder_type}', '{party_count}', '{intake_url}' ];
	$vals = [ $case_name, $booking_date, $type_labels[ $type ] ?? $type, (string) $count, $intake_url ];

	$default_subject = 'Reminder Follow-up — {case_name} — {reminder_type}';
	$default_body    = "{party_count} {reminder_type} email(s) were sent yesterday for {case_name} (session: {booking_date}).\n\nPlease check whether additional documents or responses have been submitted.\n\nView intake: {intake_url}";

	$subject = str_replace( $tags, $vals, get_option( 'summit_team_notification_subject', $default_subject ) );
	$body    = str_replace( $tags, $vals, get_option( 'summit_team_notification_body', $default_body ) );

	if ( ! $subject || ! $body ) return;

	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];

	wp_mail( $team_email, $subject, summit_intake_email_wrap( nl2br( $body ) ), $headers );
}
add_action( 'summit_team_reminder_notification', 'summit_team_reminder_notification', 10, 3 );

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
