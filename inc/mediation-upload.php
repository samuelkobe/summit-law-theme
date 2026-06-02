<?php
/**
 * Mediation Intake — Counsel Upload Portal
 *
 * Provides a unique, expiring URL per counsel for uploading their signed
 * Mediation Agreement and Mediation Brief. Files land in the protected
 * /summit-intake/ directory. Kelly is notified via wp_mail() on each upload.
 *
 * URL structure: /mediation-upload/{32-hex-token}/
 * Token stored as transient: summit_upload_{token}, expires at booking date.
 *
 * After deployment: flush permalinks once via Settings → Permalinks → Save.
 */

// =============================================================================
// Rewrite Rule + Query Var
// =============================================================================

add_action( 'init', function () {
	add_rewrite_rule(
		'^mediation-upload/([a-f0-9]{32})/?$',
		'index.php?summit_upload_token=$matches[1]',
		'top'
	);
	add_rewrite_tag( '%summit_upload_token%', '([a-f0-9]{32})' );
} );

// =============================================================================
// Upload Portal Handler
// =============================================================================

add_action( 'template_redirect', function () {
	$token = get_query_var( 'summit_upload_token' );
	if ( ! $token ) return;

	$data = get_transient( 'summit_upload_' . $token );

	// Render the portal page — passing $token and $data as locals.
	summit_upload_render_portal( $token, $data );
	exit;
}, 1 );

// =============================================================================
// Portal Renderer
// =============================================================================

/**
 * Render the upload portal — either expired/invalid or the upload form.
 *
 * @param string    $token 32-char hex token.
 * @param array|false $data  Transient data or false if expired/not found.
 */
function summit_upload_render_portal( $token, $data ) {
	// Handle POST submission before rendering.
	$messages = [];
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && $data ) {
		$messages = summit_upload_handle_post( $token, $data );
	}

	// Minimal standalone HTML — no WP theme wrapper needed.
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Document Upload — Summit Law LLP</title>
		<style>
			*, *::before, *::after { box-sizing: border-box; }
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
				background: #f5f5f5;
				margin: 0;
				padding: 40px 16px;
				color: #1a1a1a;
			}
			.card {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 8px;
				max-width: 560px;
				margin: 0 auto;
				padding: 36px 40px;
			}
			.logo {
				font-size: 13px;
				font-weight: 600;
				letter-spacing: .08em;
				text-transform: uppercase;
				color: #555;
				margin-bottom: 28px;
			}
			h1 { font-size: 22px; margin: 0 0 6px; }
			.meta { color: #555; font-size: 14px; margin-bottom: 28px; }
			.meta span { display: block; }
			hr { border: none; border-top: 1px solid #eee; margin: 24px 0; }
			label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
			.hint { font-size: 13px; color: #666; margin-bottom: 10px; }
			input[type="file"] { display: block; width: 100%; margin-bottom: 20px; }
			button[type="submit"] {
				background: #1a3a5c;
				color: #fff;
				border: none;
				padding: 10px 24px;
				border-radius: 4px;
				font-size: 15px;
				cursor: pointer;
			}
			button[type="submit"]:hover { background: #12294a; }
			.notice {
				padding: 12px 16px;
				border-radius: 4px;
				margin-bottom: 20px;
				font-size: 14px;
			}
			.notice-success { background: #edf7ed; border-left: 4px solid #4caf50; color: #1b5e20; }
			.notice-error   { background: #fdecea; border-left: 4px solid #e53935; color: #7f0000; }
			.expired {
				text-align: center;
				padding: 40px 0 20px;
				color: #555;
			}
			.expired h2 { color: #b00; margin-bottom: 10px; }
		</style>
	</head>
	<body>
	<div class="card">
		<div class="logo">Summit Law LLP</div>

	<?php if ( ! $data ) : ?>
		<div class="expired">
			<h2>Link Expired or Invalid</h2>
			<p>This upload link is no longer active. It may have expired after the booking date or been used previously.</p>
			<p>Please contact Summit Law LLP if you need assistance.</p>
		</div>

	<?php else : ?>
		<h1>Document Upload</h1>
		<div class="meta">
			<span><strong>Case:</strong> <?php echo esc_html( $data['case_name'] ); ?></span>
			<span><strong>Counsel:</strong> <?php echo esc_html( $data['counsel_name'] ); ?></span>
			<span><strong>Booking date:</strong> <?php echo esc_html( $data['booking_date'] ); ?></span>
		</div>

		<?php foreach ( $messages as $msg ) : ?>
			<div class="notice notice-<?php echo esc_attr( $msg['type'] ); ?>">
				<?php echo esc_html( $msg['text'] ); ?>
			</div>
		<?php endforeach; ?>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'summit_upload_' . $token, 'summit_upload_nonce' ); ?>
			<input type="hidden" name="summit_upload_token" value="<?php echo esc_attr( $token ); ?>">

			<label for="agreement_file">Mediation Agreement <span style="font-weight:400;color:#666;">(optional)</span></label>
			<p class="hint">Upload your signed Mediation Agreement. Accepted formats: PDF, DOC, DOCX. Max 10 MB.</p>
			<input type="file" id="agreement_file" name="agreement_file" accept=".pdf,.doc,.docx">

			<hr>

			<label for="brief_file">Mediation Brief <span style="font-weight:400;color:#666;">(optional)</span></label>
			<p class="hint">Upload your Mediation Brief. Accepted formats: PDF, DOC, DOCX. Max 10 MB.</p>
			<input type="file" id="brief_file" name="brief_file" accept=".pdf,.doc,.docx">

			<hr>

			<button type="submit">Upload Documents</button>
		</form>
	<?php endif; ?>

	</div>
	</body>
	</html>
	<?php
}

// =============================================================================
// POST Handler
// =============================================================================

/**
 * Process file uploads from the portal form.
 *
 * @param string $token Upload token.
 * @param array  $data  Transient data {post_id, email, counsel_name, case_name, booking_date}.
 * @return array List of notice messages: [ ['type' => 'success'|'error', 'text' => '...'], ... ]
 */
function summit_upload_handle_post( $token, $data ) {
	// CSRF check.
	if ( ! isset( $_POST['summit_upload_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['summit_upload_nonce'], 'summit_upload_' . $token ) ) {
		return [ [ 'type' => 'error', 'text' => 'Security check failed. Please reload the page and try again.' ] ];
	}

	$post_id      = absint( $data['post_id'] );
	$email        = sanitize_email( $data['email'] );
	$counsel_name = sanitize_text_field( $data['counsel_name'] );
	$case_name    = sanitize_text_field( $data['case_name'] );
	$booking_date = sanitize_text_field( $data['booking_date'] );
	$email_md5    = md5( $email );
	$messages     = [];

	$file_fields = [
		'agreement_file' => [
			'meta_key'  => '_summit_intake_agreement_file_' . $email_md5,
			'acf_field' => 'mediation_agreement_file',
			'label'     => 'Mediation Agreement',
			'doc_type'  => 'Mediation Agreement',
		],
		'brief_file'     => [
			'meta_key'  => '_summit_intake_brief_file_' . $email_md5,
			'acf_field' => 'mediation_brief_file',
			'label'     => 'Mediation Brief',
			'doc_type'  => 'Mediation Brief',
		],
	];

	$any_uploaded = false;

	foreach ( $file_fields as $input_name => $config ) {
		if ( empty( $_FILES[ $input_name ]['name'] ) ) continue;

		$result = summit_upload_process_file( $_FILES[ $input_name ], $post_id, $config['meta_key'] );

		if ( is_wp_error( $result ) ) {
			$messages[] = [
				'type' => 'error',
				'text' => $config['label'] . ': ' . $result->get_error_message(),
			];
		} else {
			$messages[]   = [
				'type' => 'success',
				'text' => $config['label'] . ' uploaded successfully.',
			];
			$any_uploaded = true;

			// Write the attachment ID into the matching ACF repeater row.
			summit_upload_update_acf_file( $post_id, $email, $config['acf_field'], $result );

			// Notify assistant.
			summit_upload_notify_assistant( $post_id, $counsel_name, $email, $config['doc_type'], $case_name, $booking_date );
		}
	}

	if ( empty( $messages ) ) {
		$messages[] = [
			'type' => 'error',
			'text' => 'No files were selected. Please choose at least one file to upload.',
		];
	}

	return $messages;
}

// =============================================================================
// File Processing
// =============================================================================

/**
 * Validate, upload, and attach a single file to the intake post.
 *
 * @param array  $file     Entry from $_FILES.
 * @param int    $post_id  Mediation intake post ID.
 * @param string $meta_key Post meta key to store the attachment ID.
 * @return int|WP_Error Attachment ID on success.
 */
function summit_upload_process_file( $file, $post_id, $meta_key ) {
	// PHP upload error check.
	if ( $file['error'] !== UPLOAD_ERR_OK ) {
		return new WP_Error( 'upload_error', 'File upload error. Please try again.' );
	}

	// Size limit: 10 MB.
	if ( $file['size'] > 10 * MB_IN_BYTES ) {
		return new WP_Error( 'file_too_large', 'File exceeds the 10 MB size limit.' );
	}

	// MIME validation — check actual file content via finfo, not just extension.
	$allowed_mimes = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	];

	if ( function_exists( 'finfo_open' ) ) {
		$finfo    = finfo_open( FILEINFO_MIME_TYPE );
		$detected = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		if ( ! in_array( $detected, $allowed_mimes, true ) ) {
			return new WP_Error( 'invalid_type', 'Only PDF, DOC, and DOCX files are accepted.' );
		}
	}

	// Extension whitelist as a secondary check.
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, [ 'pdf', 'doc', 'docx' ], true ) ) {
		return new WP_Error( 'invalid_extension', 'Only PDF, DOC, and DOCX files are accepted.' );
	}

	// Route the file through WP media upload infrastructure so it lands in
	// the protected summit-intake directory (via the summit_intake_upload_dir filter).
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	// Temporarily override upload directory to summit-intake/.
	add_filter( 'upload_dir', 'summit_intake_upload_dir' );
	$attachment_id = media_handle_sideload( $file, $post_id );
	remove_filter( 'upload_dir', 'summit_intake_upload_dir' );

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	// Mark as a protected intake file.
	update_post_meta( $attachment_id, '_summit_intake_upload', true );

	// Remove orphan-cleanup timestamp so this file is never auto-deleted.
	delete_post_meta( $attachment_id, '_summit_intake_upload_time' );

	// Store attachment ID on the intake post.
	update_post_meta( $post_id, $meta_key, $attachment_id );

	return $attachment_id;
}

// =============================================================================
// ACF Repeater Update
// =============================================================================

/**
 * Find the repeater row matching the counsel's email and update a file sub-field.
 *
 * Searches plaintiffs, defendants, and third_parties repeaters in order.
 *
 * @param int    $post_id       Intake post ID.
 * @param string $email         Counsel email to match.
 * @param string $acf_field     ACF sub-field name (e.g. 'mediation_agreement_file').
 * @param int    $attachment_id WP attachment ID to store.
 * @return bool True if a matching row was found and updated.
 */
function summit_upload_update_acf_file( $post_id, $email, $acf_field, $attachment_id ) {
	foreach ( [ 'plaintiffs', 'defendants', 'third_parties' ] as $repeater ) {
		$rows = get_field( $repeater, $post_id );
		if ( ! is_array( $rows ) ) continue;
		foreach ( $rows as $i => $row ) {
			if ( ( $row['counsel_email'] ?? '' ) === $email ) {
				// update_row() uses 1-based row index.
				update_row( $repeater, $i + 1, [ $acf_field => $attachment_id ], $post_id );
				return true;
			}
		}
	}
	return false;
}

// =============================================================================
// Kelly Notification
// =============================================================================

/**
 * Send a notification email to Kelly when a counsel uploads a document.
 *
 * @param int    $post_id      Intake post ID.
 * @param string $counsel_name Uploading counsel's name.
 * @param string $email        Uploading counsel's email.
 * @param string $doc_type     Human-readable document type.
 * @param string $case_name    Intake case name.
 * @param string $booking_date Formatted booking date string.
 */
function summit_upload_notify_assistant( $post_id, $counsel_name, $email, $doc_type, $case_name, $booking_date ) {
	$to = get_option( 'summit_intake_kelly_email', '' ) ?: get_option( 'admin_email' );
	if ( ! $to ) return;

	$edit_url  = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	$login_url = wp_login_url( $edit_url );
	$subject   = '[Summit Law] ' . $doc_type . ' uploaded — ' . $case_name . ' (' . $counsel_name . ')';

	$body  = $counsel_name . ' (' . $email . ') has uploaded their ' . $doc_type . ' for ' . $case_name . ".\n";
	$body .= 'Booking date: ' . $booking_date . "\n\n";
	$body .= "Review and toggle the relevant field in WP admin:\n";
	$body .= $login_url . "\n";
	$body .= "(If you're already logged in, this link will take you straight to the intake.)\n";

	$headers = [ 'From: Mediation Notifications – Summit Law LLP <info@summitlaw.ca>' ];

	wp_mail( $to, $subject, $body, $headers );
}
