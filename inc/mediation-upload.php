<?php
/**
 * Mediation Intake — Counsel Upload Portal
 *
 * Provides a unique, expiring URL per counsel for uploading their signed
 * Mediation Agreement and Mediation Brief. Files land in the protected
 * /summit-intake/ directory. The legal assistant is notified via wp_mail() on each upload.
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

	// Token-gated agreement PDF download — ?dl=agreement appended to the portal URL.
	if ( isset( $_GET['dl'] ) && $_GET['dl'] === 'agreement' ) {
		summit_upload_serve_agreement_pdf( $token, $data );
		exit;
	}

	// Handle POST before rendering — PRG pattern prevents re-submission on refresh.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		$is_valid = is_array( $data ) && empty( $data['status'] );
		if ( $is_valid ) {
			$messages = summit_upload_handle_post( $token, $data );
			$redirect = add_query_arg(
				'upload_result', rawurlencode( wp_json_encode( $messages ) ),
				home_url( 'mediation-upload/' . $token . '/' )
			);
		} else {
			$redirect = home_url( 'mediation-upload/' . $token . '/' );
		}
		wp_redirect( $redirect );
		exit;
	}

	// Render the portal page — GET only from here.
	summit_upload_render_portal( $token, $data );
	exit;
}, 1 );

// =============================================================================
// Agreement PDF Download (token-gated)
// =============================================================================

/**
 * Serve the agreement PDF template to a counsel who holds a valid upload token.
 * The token proves they are a named party on an active intake — no login needed.
 *
 * @param string     $token Upload token (32-char hex).
 * @param array|false $data  Transient data or false if expired.
 */
function summit_upload_serve_agreement_pdf( $token, $data ) {
	// Reject expired or invalid tokens.
	$is_valid = is_array( $data ) && empty( $data['status'] );
	if ( ! $is_valid ) {
		status_header( 403 );
		die( 'This download link has expired or is no longer valid.' );
	}

	$pdf_id = absint( get_option( 'summit_intake_agreement_pdf', 0 ) );
	if ( ! $pdf_id ) {
		status_header( 404 );
		die( 'Agreement PDF not found.' );
	}

	$file_path = get_attached_file( $pdf_id );
	if ( ! $file_path || ! file_exists( $file_path ) ) {
		status_header( 404 );
		die( 'Agreement PDF not found on disk.' );
	}

	// Bypass the direct-access block so our server-side readfile() is allowed.
	define( 'SUMMIT_INTAKE_SERVING_FILE', true );

	$filename = get_the_title( $pdf_id ) ?: 'Mediation-Agreement';
	$filename = sanitize_file_name( $filename ) . '.pdf';

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . filesize( $file_path ) );
	readfile( $file_path );
	exit;
}

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
	// Treat invalidated tombstones as non-uploadable (but distinct from fully expired).
	$is_valid      = is_array( $data ) && empty( $data['status'] );
	$is_invalidated = is_array( $data ) && ( $data['status'] ?? '' ) === 'invalidated';

	// Decode messages passed via redirect from the POST handler (PRG pattern).
	$messages = [];
	if ( isset( $_GET['upload_result'] ) ) {
		$decoded = json_decode( urldecode( $_GET['upload_result'] ), true );
		if ( is_array( $decoded ) ) {
			$messages = $decoded;
		}
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
				overflow: hidden;
			}
			.card-header {
				background: #29472d;
				padding: 28px 36px 32px;
				display: flex;
				align-items: center;
				gap: 16px;
			}
			.card-header img {
				display: block;
				height: 44px;
				width: auto;
				flex-shrink: 0;
			}
			.card-header-text .name {
				font-size: 18px;
				font-weight: 400;
				color: #ffffff;
				letter-spacing: 0.02em;
				margin: 0;
				font-family: Georgia, 'Times New Roman', serif;
			}
			.card-header-text .sub {
				font-size: 11px;
				color: #a8c4ab;
				letter-spacing: 0.12em;
				text-transform: uppercase;
				margin: 4px 0 0;
			}
			.card-body {
				padding: 32px 36px 36px;
			}
			h1 { font-size: 22px; margin: 0 0 6px; }
			.meta { color: #555; font-size: 14px; margin-bottom: 28px; }
			.meta span { display: block; }
			hr { border: none; border-top: 1px solid #eee; margin: 24px 0; }
			label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
			.hint { font-size: 13px; color: #666; margin-bottom: 10px; }
			input[type="file"] { display: block; width: 100%; margin-bottom: 20px; }
			button[type="submit"] {
				background: #29472d;
				color: #fff;
				border: none;
				padding: 12px 24px;
				border-radius: 8px;
				font-size: 15px;
				font-weight: 600;
				letter-spacing: 0.05em;
				cursor: pointer;
				box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
				transition: background 0.3s, box-shadow 0.3s;
			}
			button[type="submit"]:hover {
				background: #292725;
				box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
			}
			.notice {
				padding: 12px 16px;
				border-radius: 4px;
				margin-bottom: 20px;
				font-size: 14px;
			}
			.notice-success { background: #edf7ed; border-left: 4px solid #4caf50; color: #1b5e20; }
			.notice-error   { background: #fdecea; border-left: 4px solid #e53935; color: #7f0000; }
			.uploaded-state {
				display: flex;
				align-items: flex-start;
				gap: 10px;
				padding: 12px 16px;
				background: #f0f4f0;
				border-left: 4px solid #29472d;
				border-radius: 4px;
				font-size: 14px;
				color: #333;
				margin-bottom: 20px;
			}
			.uploaded-state .checkmark {
				flex-shrink: 0;
				width: 20px;
				height: 20px;
				background: #29472d;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-top: 1px;
			}
			.uploaded-state .checkmark svg {
				display: block;
			}
			.uploaded-state .uploaded-info strong {
				display: block;
				color: #29472d;
				margin-bottom: 2px;
			}
			.uploaded-state .uploaded-info .reupload-note {
				margin-top: 4px;
				font-size: 13px;
				color: #666;
			}
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
		<?php
	$portal_logo_url = '';
	$portal_logo_id  = get_theme_mod( 'custom_logo' );
	if ( $portal_logo_id ) {
		$portal_logo_src = wp_get_attachment_image_src( $portal_logo_id, 'full' );
		$portal_logo_url = $portal_logo_src ? esc_url( $portal_logo_src[0] ) : '';
	}
	?>
	<div class="card-header">
		<?php if ( $portal_logo_url ) : ?>
			<img src="<?php echo $portal_logo_url; ?>" alt="Summit Law LLP">
		<?php endif; ?>
		<div class="card-header-text">
			<p class="name">Summit Law LLP</p>
			<p class="sub">Mediation Services</p>
		</div>
	</div>
	<div class="card-body">

	<?php if ( $is_invalidated ) :
		$contact_email = get_option( 'summit_intake_notification_email', '' ) ?: get_option( 'admin_email' );
		$mailto_subject = rawurlencode( 'Request: Updated Document Upload Link' );
		$mailto_body    = rawurlencode(
			"Hello,\n\n" .
			"I received a Mediation Agreement email from Summit Law LLP, but my document upload link appears to be no longer active.\n\n" .
			"Could you please send me an updated upload link at your earliest convenience?\n\n" .
			"Thank you,"
		);
		$mailto_href = 'mailto:' . $contact_email . '?subject=' . $mailto_subject . '&body=' . $mailto_body;
	?>
		<div class="expired">
			<h2>Link No Longer Valid</h2>
			<p>This upload link has been updated. Your previous link is no longer active.</p>
			<p>Please email <a href="<?php echo esc_attr( $mailto_href ); ?>"><?php echo esc_html( $contact_email ); ?></a> to request an updated upload link.</p>
		</div>

	<?php elseif ( ! $is_valid ) : ?>
		<div class="expired">
			<h2>Link Expired</h2>
			<p>This upload link has expired. The mediation booking date has passed.</p>
			<p>Please contact Summit Law LLP if you need assistance.</p>
		</div>

	<?php else :
		$agreement_pdf_id = absint( get_option( 'summit_intake_agreement_pdf', 0 ) );
		$contact_email    = get_option( 'summit_intake_notification_email', '' ) ?: get_option( 'admin_email' );
		$email_md5        = md5( sanitize_email( $data['email'] ) );
		$post_id          = absint( $data['post_id'] );

		$agreement_id   = absint( get_post_meta( $post_id, '_summit_intake_agreement_file_' . $email_md5, true ) );
		$brief_id       = absint( get_post_meta( $post_id, '_summit_intake_brief_file_'    . $email_md5, true ) );

		$agreement_date = $agreement_id ? get_the_date( 'F j, Y \a\t g:i a', $agreement_id ) : '';
		$brief_date     = $brief_id     ? get_the_date( 'F j, Y \a\t g:i a', $brief_id )     : '';

		$checkmark = '<div class="checkmark"><svg width="11" height="9" viewBox="0 0 11 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 4L4 7L10 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';

		$show_form = ! $agreement_id || ! $brief_id;
	?>
		<h1>Document Upload</h1>
		<div class="meta">
			<span><strong>Case:</strong> <?php echo esc_html( $data['case_name'] ); ?></span>
			<span><strong>Counsel:</strong> <?php echo esc_html( $data['counsel_name'] ); ?></span>
			<span><strong>Booking date:</strong> <?php echo esc_html( $data['booking_date'] ); ?></span>
		</div>

		<?php if ( $agreement_pdf_id && ! $agreement_id ) :
			$agreement_dl_url = add_query_arg( 'dl', 'agreement', home_url( 'mediation-upload/' . $token . '/' ) );
		?>
		<div style="margin-bottom:20px;padding:12px 16px;background:#f0f4f8;border-left:4px solid #1a3a5c;border-radius:4px;font-size:14px;">
			Need a copy of the Mediation Agreement?
			<a href="<?php echo esc_url( $agreement_dl_url ); ?>" style="color:#1a3a5c;font-weight:600;margin-left:4px;">Download it here &darr;</a>
		</div>
		<?php endif; ?>

		<?php foreach ( $messages as $msg ) : ?>
			<div class="notice notice-<?php echo esc_attr( $msg['type'] ); ?>">
				<?php echo esc_html( $msg['text'] ); ?>
			</div>
		<?php endforeach; ?>

		<?php if ( $agreement_id ) : ?>
			<label>Mediation Agreement</label>
			<div class="uploaded-state">
				<?php echo $checkmark; ?>
				<div class="uploaded-info">
					<strong>Agreement received</strong>
					Uploaded on <?php echo esc_html( $agreement_date ); ?>
					<p class="reupload-note">If you need to replace this file, please contact <a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="color:#29472d;"><?php echo esc_html( $contact_email ); ?></a>.</p>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $brief_id ) : ?>
			<?php if ( $agreement_id ) : ?><hr><?php endif; ?>
			<label>Mediation Brief</label>
			<div class="uploaded-state">
				<?php echo $checkmark; ?>
				<div class="uploaded-info">
					<strong>Brief received</strong>
					Uploaded on <?php echo esc_html( $brief_date ); ?>
					<p class="reupload-note">If you need to replace this file, please contact <a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="color:#29472d;"><?php echo esc_html( $contact_email ); ?></a>.</p>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $show_form ) : ?>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'summit_upload_' . $token, 'summit_upload_nonce' ); ?>
			<input type="hidden" name="summit_upload_token" value="<?php echo esc_attr( $token ); ?>">

			<?php if ( ! $agreement_id ) : ?>
				<?php if ( $brief_id ) : ?><hr><?php endif; ?>
				<label for="agreement_file">Mediation Agreement</label>
				<p class="hint">Upload your signed Mediation Agreement. You can return to this page at any time to submit your Mediation Brief when it is ready. Accepted formats: PDF, DOC, DOCX. Max 10 MB.</p>
				<input type="file" id="agreement_file" name="agreement_file" accept=".pdf,.doc,.docx">
			<?php endif; ?>

			<?php if ( ! $brief_id ) : ?>
				<?php if ( ! $agreement_id ) : ?><hr><?php endif; ?>
				<label for="brief_file">Mediation Brief</label>
				<p class="hint">Upload your Mediation Brief. If you have not yet submitted your signed Mediation Agreement, you may also do so above. Accepted formats: PDF, DOC, DOCX. Max 10 MB.</p>
				<input type="file" id="brief_file" name="brief_file" accept=".pdf,.doc,.docx">
			<?php endif; ?>

			<hr>
			<button type="submit">Upload Document(s)</button>
		</form>
		<?php endif; ?>
	<?php endif; ?>

	</div><!-- /.card-body -->
	</div><!-- /.card -->
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
// Upload Notification
// =============================================================================

/**
 * Send a notification email to the legal assistant when a counsel uploads a document.
 *
 * @param int    $post_id      Intake post ID.
 * @param string $counsel_name Uploading counsel's name.
 * @param string $email        Uploading counsel's email.
 * @param string $doc_type     Human-readable document type.
 * @param string $case_name    Intake case name.
 * @param string $booking_date Formatted booking date string.
 */
function summit_upload_notify_assistant( $post_id, $counsel_name, $email, $doc_type, $case_name, $booking_date ) {
	$to = get_option( 'summit_intake_notification_email', '' ) ?: get_option( 'admin_email' );
	if ( ! $to ) return;

	$edit_url  = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	$login_url = wp_login_url( $edit_url );
	$subject   = $doc_type . ' Uploaded — ' . $case_name . ' — Summit Law LLP';

	$body = '<b>' . esc_html( $counsel_name ) . '</b> (' . esc_html( $email ) . ') has uploaded their <b>' . esc_html( $doc_type ) . '</b> for <b>' . esc_html( $case_name ) . '</b>.'
		. "\n\nBooking Date: " . esc_html( $booking_date )
		. "\n\nPlease log in to review the submission and update the relevant status field on the intake record."
		. "\n\n<a href=\"" . esc_url( $login_url ) . "\" style=\"color:#29472d;font-weight:600;\">View Intake Record</a>";

	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];

	wp_mail( $to, $subject, summit_intake_email_wrap( nl2br( $body ) ), $headers );
}
