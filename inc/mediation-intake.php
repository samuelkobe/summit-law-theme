<?php
/**
 * Mediation Intake — CPT, AJAX Endpoints, Amelia Hook, Cron Cleanup
 *
 * Extends the Amelia booking flow with a custom intake form for mediation
 * bookings. Stores party details and file uploads as a CPT with ACF fields,
 * linked to the Amelia booking via a server-side transient.
 */

// =============================================================================
// Custom Post Type
// =============================================================================

function summit_register_mediation_intake_cpt() {
	register_post_type( 'mediation_intake', [
		'labels' => [
			'name'               => __( 'Mediation Intakes', 'summit-law-theme' ),
			'singular_name'      => __( 'Mediation Intake', 'summit-law-theme' ),
			'menu_name'          => __( 'Mediation Intakes', 'summit-law-theme' ),
			'add_new'            => __( 'Add New', 'summit-law-theme' ),
			'add_new_item'       => __( 'Add New Intake', 'summit-law-theme' ),
			'edit_item'          => __( 'Edit Intake', 'summit-law-theme' ),
			'view_item'          => __( 'View Intake', 'summit-law-theme' ),
			'all_items'          => __( 'All Intakes', 'summit-law-theme' ),
			'search_items'       => __( 'Search Intakes', 'summit-law-theme' ),
			'not_found'          => __( 'No intakes found.', 'summit-law-theme' ),
			'not_found_in_trash' => __( 'No intakes found in Trash.', 'summit-law-theme' ),
		],
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-clipboard',
		'supports'        => [ 'title' ],
		'capability_type' => 'post',
	] );
}
add_action( 'init', 'summit_register_mediation_intake_cpt' );

// =============================================================================
// Hide "Add New Intake" UI
// =============================================================================

/**
 * Remove the "Add New" submenu item and hide the list-page button
 * when manual creation is disabled in Permissions.
 */
function summit_intake_hide_add_new() {
	if ( get_option( 'summit_intake_allow_manual_create', false ) ) {
		return;
	}
	remove_submenu_page( 'edit.php?post_type=mediation_intake', 'post-new.php?post_type=mediation_intake' );
}
add_action( 'admin_menu', 'summit_intake_hide_add_new', 999 );

function summit_intake_hide_add_new_button() {
	if ( get_option( 'summit_intake_allow_manual_create', false ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && $screen->post_type === 'mediation_intake' ) {
		echo '<style>.page-title-action { display: none !important; }</style>';
	}
}
add_action( 'admin_head', 'summit_intake_hide_add_new_button' );

// =============================================================================
// Admin Columns — Booking Date + Rename Date
// =============================================================================

/**
 * Customise the columns on the Mediation Intakes list table.
 */
function summit_intake_columns( $columns ) {
	$new = [];
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) {
			$new[ $key ] = $label;
			$new['booking_date'] = 'Booking Date';
			continue;
		}
		if ( $key === 'date' ) {
			$new[ $key ] = 'Intake Created';
			continue;
		}
		$new[ $key ] = $label;
	}
	return $new;
}
add_filter( 'manage_mediation_intake_posts_columns', 'summit_intake_columns' );

/**
 * Render the Booking Date column value.
 */
function summit_intake_column_content( $column, $post_id ) {
	if ( $column !== 'booking_date' ) {
		return;
	}
	$date = get_field( 'booking_date', $post_id );
	if ( $date ) {
		echo esc_html( wp_date( 'F j, Y \a\t g:i a', strtotime( $date ) ) );
	} else {
		echo '—';
	}
}
add_action( 'manage_mediation_intake_posts_custom_column', 'summit_intake_column_content', 10, 2 );

/**
 * Register the Booking Date column as sortable.
 */
function summit_intake_sortable_columns( $columns ) {
	$columns['booking_date'] = 'booking_date';
	return $columns;
}
add_filter( 'manage_edit-mediation_intake_sortable_columns', 'summit_intake_sortable_columns' );

/**
 * Handle sorting by booking_date and set it as the default sort order.
 * Default: ascending (closest upcoming booking first).
 */
function summit_intake_default_sort( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->post_type !== 'mediation_intake' ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	// Sort by booking_date (explicit click or default).
	// Uses a LEFT JOIN via meta_query so posts WITHOUT booking_date still appear.
	if ( $orderby === 'booking_date' || ! $orderby ) {
		$query->set( 'meta_query', [
			'relation' => 'OR',
			'booking_date_clause' => [
				'key'     => 'booking_date',
				'compare' => 'EXISTS',
			],
			'booking_date_missing' => [
				'key'     => 'booking_date',
				'compare' => 'NOT EXISTS',
			],
		] );
		$query->set( 'orderby', 'booking_date_clause' );

		if ( ! $orderby ) {
			$query->set( 'order', 'ASC' );
		}
	}
}
add_action( 'pre_get_posts', 'summit_intake_default_sort' );

// =============================================================================
// ACF Field Group Registration
// =============================================================================

function summit_register_intake_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'      => 'group_mediation_intake',
		'title'    => 'Mediation Intake Details',
		'location' => [
			[
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'mediation_intake',
				],
			],
		],
		'fields'   => [
			// Status
			[
				'key'           => 'field_intake_status',
				'label'         => 'Status',
				'name'          => 'intake_status',
				'type'          => 'select',
				'choices'       => [
					'pending'   => 'Pending',
					'confirmed' => 'Confirmed',
				],
				'default_value' => 'pending',
			],
			// Amelia Booking ID
			[
				'key'   => 'field_intake_amelia_booking_id',
				'label' => 'Amelia Booking ID',
				'name'  => 'amelia_booking_id',
				'type'  => 'number',
			],
			// Booking Date (from Amelia appointment)
			[
				'key'            => 'field_intake_booking_date',
				'label'          => 'Booking Date',
				'name'           => 'booking_date',
				'type'           => 'date_time_picker',
				'display_format' => 'F j, Y g:i a',
				'return_format'  => 'Y-m-d H:i:s',
			],
			// Team Member
			[
				'key'       => 'field_intake_team_member',
				'label'     => 'Mediator',
				'name'      => 'team_member',
				'type'      => 'post_object',
				'post_type' => [ 'team' ],
				'return_format' => 'id',
			],
			// Plaintiffs Repeater
			[
				'key'        => 'field_intake_plaintiffs',
				'label'      => 'Plaintiffs',
				'name'       => 'plaintiffs',
				'type'       => 'repeater',
				'layout'     => 'table',
				'sub_fields' => [
					[
						'key'   => 'field_intake_plaintiff_name',
						'label' => 'Name',
						'name'  => 'name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_plaintiff_counsel_name',
						'label' => 'Counsel Name',
						'name'  => 'counsel_name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_plaintiff_counsel_email',
						'label' => 'Counsel Email',
						'name'  => 'counsel_email',
						'type'  => 'email',
					],
				],
			],
			// Defendants Repeater
			[
				'key'        => 'field_intake_defendants',
				'label'      => 'Defendants',
				'name'       => 'defendants',
				'type'       => 'repeater',
				'layout'     => 'table',
				'sub_fields' => [
					[
						'key'   => 'field_intake_defendant_name',
						'label' => 'Name',
						'name'  => 'name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_defendant_counsel_name',
						'label' => 'Counsel Name',
						'name'  => 'counsel_name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_defendant_counsel_email',
						'label' => 'Counsel Email',
						'name'  => 'counsel_email',
						'type'  => 'email',
					],
				],
			],
			// Third Parties Repeater
			[
				'key'        => 'field_intake_third_parties',
				'label'      => 'Third Parties',
				'name'       => 'third_parties',
				'type'       => 'repeater',
				'layout'     => 'table',
				'sub_fields' => [
					[
						'key'   => 'field_intake_tp_name',
						'label' => 'Name',
						'name'  => 'name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_tp_counsel_name',
						'label' => 'Counsel Name',
						'name'  => 'counsel_name',
						'type'  => 'text',
					],
					[
						'key'   => 'field_intake_tp_counsel_email',
						'label' => 'Counsel Email',
						'name'  => 'counsel_email',
						'type'  => 'email',
					],
				],
			],
			// Title of Proceedings File
			[
				'key'           => 'field_intake_title_of_proceedings',
				'label'         => 'Title of Proceedings',
				'name'          => 'title_of_proceedings',
				'type'          => 'file',
				'return_format' => 'id',
				'mime_types'    => 'pdf,doc,docx',
			],
			// Third Party Claims Repeater
			[
				'key'        => 'field_intake_third_party_claims',
				'label'      => 'Third Party Statements of Claim',
				'name'       => 'third_party_claims',
				'type'       => 'repeater',
				'layout'     => 'table',
				'sub_fields' => [
					[
						'key'           => 'field_intake_claim_file',
						'label'         => 'Claim File',
						'name'          => 'claim_file',
						'type'          => 'file',
						'return_format' => 'id',
						'mime_types'    => 'pdf,doc,docx',
					],
				],
			],
		],
	] );
}
add_action( 'acf/init', 'summit_register_intake_acf_fields' );

/**
 * Filter the Mediator post_object field to only show team members
 * with role "partner" or "associate".
 */
function summit_intake_filter_mediator_query( $args, $field, $post_id ) {
	if ( $field['key'] !== 'field_intake_team_member' ) {
		return $args;
	}

	$args['meta_query'] = [
		'relation' => 'OR',
		[
			'key'     => 'role',
			'value'   => 'partner',
			'compare' => 'LIKE',
		],
		[
			'key'     => 'role',
			'value'   => 'associate',
			'compare' => 'LIKE',
		],
	];

	return $args;
}
add_filter( 'acf/fields/post_object/query/key=field_intake_team_member', 'summit_intake_filter_mediator_query', 10, 3 );

// =============================================================================
// Amelia Hook — Capture Booking ID
// =============================================================================

/**
 * After Amelia saves a booking, store the booking ID in a transient keyed
 * by a session identifier. The JS AJAX handler retrieves this when submitting
 * intake data, linking the two records.
 */
function summit_capture_amelia_booking_id( $booking, $service, $appointment ) {
	$session_key = summit_intake_session_key();
	if ( $session_key ) {
		$booking_id = is_object( $booking ) && method_exists( $booking, 'getId' )
			? $booking->getId()
			: ( is_array( $booking ) ? ( $booking['id'] ?? 0 ) : 0 );

		if ( $booking_id ) {
			// Amelia passes toArray() results — $appointment is an array with 'bookingStart' key.
			$booking_date = '';
			if ( is_array( $appointment ) && ! empty( $appointment['bookingStart'] ) ) {
				$booking_date = $appointment['bookingStart']; // Already 'Y-m-d H:i:s' format.
			}

			set_transient( $session_key, [
				'booking_id'   => $booking_id,
				'booking_date' => $booking_date,
			], 300 ); // 5 min TTL
		}
	}
}
add_action( 'amelia_after_appointment_booking_saved', 'summit_capture_amelia_booking_id', 10, 3 );

/**
 * Generate a stable session key for the current visitor.
 * Logged-in users use their user ID; guests use IP + UA hash.
 */
function summit_intake_session_key() {
	if ( is_user_logged_in() ) {
		return 'summit_amelia_booking_' . get_current_user_id();
	}

	$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	$ua = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );
	if ( ! $ip ) {
		return '';
	}

	return 'summit_amelia_booking_' . md5( $ip . $ua );
}

// =============================================================================
// Private Upload Directory
// =============================================================================

/**
 * Redirect intake uploads to wp-content/uploads/summit-intake/YYYY/MM/.
 * Applied temporarily via add_filter/remove_filter during upload only.
 */
function summit_intake_upload_dir( $dirs ) {
	$subdir             = '/summit-intake' . $dirs['subdir'];
	$dirs['path']       = $dirs['basedir'] . $subdir;
	$dirs['url']        = $dirs['baseurl'] . $subdir;
	$dirs['subdir']     = $subdir;
	return $dirs;
}

/**
 * Create .htaccess and index.php in the summit-intake directory.
 * Belt-and-suspenders: .htaccess blocks Apache, index.php blocks directory
 * listing. The primary defence is the PHP-level block in template_redirect.
 */
function summit_intake_ensure_directory_protection() {
	$upload_dir = wp_upload_dir();
	$base       = $upload_dir['basedir'] . '/summit-intake';

	if ( ! is_dir( $base ) ) {
		wp_mkdir_p( $base );
	}

	// .htaccess — blocks Apache servers
	$htaccess = $base . '/.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		file_put_contents( $htaccess, "Deny from all\n" );
	}

	// index.php — prevents directory listing
	$index = $base . '/index.php';
	if ( ! file_exists( $index ) ) {
		file_put_contents( $index, "<?php\n// Silence is golden.\n" );
	}
}

// =============================================================================
// Block Direct Access to Intake Files
// =============================================================================

/**
 * Intercept any direct request to files inside /summit-intake/ and return 403.
 * Works on Nginx, Apache, or any server — runs at the PHP level.
 * The download proxy sets a flag so its own internal read is not blocked.
 */
function summit_intake_block_direct_access() {
	if ( defined( 'SUMMIT_INTAKE_SERVING_FILE' ) ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	if ( strpos( $request_uri, '/summit-intake/' ) !== false ) {
		status_header( 403 );
		nocache_headers();
		die( 'Access denied.' );
	}
}
add_action( 'template_redirect', 'summit_intake_block_direct_access', 1 );

// =============================================================================
// AJAX — File Upload
// =============================================================================

function summit_intake_upload_file() {
	check_ajax_referer( 'summit-nonce', 'nonce' );

	if ( empty( $_FILES['file'] ) ) {
		wp_send_json_error( [ 'message' => 'No file provided.' ] );
	}

	$file = $_FILES['file'];

	// Validate file type
	$allowed_types = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	];
	$finfo    = finfo_open( FILEINFO_MIME_TYPE );
	$mimetype = finfo_file( $finfo, $file['tmp_name'] );
	finfo_close( $finfo );

	if ( ! in_array( $mimetype, $allowed_types, true ) ) {
		wp_send_json_error( [ 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX.' ] );
	}

	// Validate file size (10 MB)
	if ( $file['size'] > 10 * 1024 * 1024 ) {
		wp_send_json_error( [ 'message' => 'File too large. Maximum 10 MB.' ] );
	}

	// Require the file handling functions
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	// Preserve original filename, then randomize for storage
	$original_name = sanitize_file_name( $file['name'] );
	$ext           = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
	$file['name']  = wp_generate_uuid4() . '.' . $ext;

	// Route upload to private summit-intake/ directory
	add_filter( 'upload_dir', 'summit_intake_upload_dir' );
	summit_intake_ensure_directory_protection();

	$upload = wp_handle_upload( $file, [ 'test_form' => false ] );

	remove_filter( 'upload_dir', 'summit_intake_upload_dir' );

	if ( isset( $upload['error'] ) ) {
		wp_send_json_error( [ 'message' => $upload['error'] ] );
	}

	// Create attachment post (use original name as title for admin display)
	$attachment_id = wp_insert_attachment( [
		'post_mime_type' => $upload['type'],
		'post_title'     => $original_name,
		'post_content'   => '',
		'post_status'    => 'inherit',
	], $upload['file'] );

	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error( [ 'message' => 'Failed to save attachment.' ] );
	}

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

	// Mark as intake upload and store original filename
	update_post_meta( $attachment_id, '_summit_intake_upload', true );
	update_post_meta( $attachment_id, '_summit_intake_upload_time', time() );
	update_post_meta( $attachment_id, '_summit_intake_original_name', $original_name );

	wp_send_json_success( [
		'attachment_id' => $attachment_id,
		'filename'      => $original_name,
	] );
}
add_action( 'wp_ajax_summit_intake_upload_file', 'summit_intake_upload_file' );
add_action( 'wp_ajax_nopriv_summit_intake_upload_file', 'summit_intake_upload_file' );

// =============================================================================
// Authenticated Download Proxy
// =============================================================================

/**
 * Stream an intake file to authorized users.
 * Logged-in only (no nopriv hook). Checks role against allowed list.
 */
function summit_intake_download() {
	check_ajax_referer( 'summit_intake_download', 'nonce' );

	$attachment_id = absint( $_GET['id'] ?? 0 );
	if ( ! $attachment_id ) {
		wp_die( 'Invalid attachment.', 'Error', [ 'response' => 400 ] );
	}

	// Verify this is an intake upload
	if ( ! get_post_meta( $attachment_id, '_summit_intake_upload', true ) ) {
		wp_die( 'Not an intake file.', 'Error', [ 'response' => 400 ] );
	}

	// Check user has an allowed role
	$allowed_roles = get_option( 'summit_intake_download_roles', [ 'administrator' ] );
	$user          = wp_get_current_user();
	$has_access    = false;

	foreach ( $allowed_roles as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			$has_access = true;
			break;
		}
	}

	if ( ! $has_access ) {
		wp_die( 'You do not have permission to download this file.', 'Forbidden', [ 'response' => 403 ] );
	}

	// Get file path
	$file_path = get_attached_file( $attachment_id );
	if ( ! $file_path || ! file_exists( $file_path ) ) {
		wp_die( 'File not found.', 'Error', [ 'response' => 404 ] );
	}

	// Use original filename for the download
	$original_name = get_post_meta( $attachment_id, '_summit_intake_original_name', true );
	if ( ! $original_name ) {
		$original_name = basename( $file_path );
	}

	// Stream the file
	define( 'SUMMIT_INTAKE_SERVING_FILE', true );
	$mime = get_post_mime_type( $attachment_id ) ?: 'application/octet-stream';

	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $original_name ) . '"' );
	header( 'Content-Length: ' . filesize( $file_path ) );

	readfile( $file_path );
	exit;
}
add_action( 'wp_ajax_summit_intake_download', 'summit_intake_download' );

// =============================================================================
// Rewrite Intake Attachment URLs to Proxy
// =============================================================================

/**
 * For intake uploads, replace the direct file URL with the authenticated
 * download proxy URL. This makes ACF file links in WP Admin work transparently.
 */
function summit_intake_proxy_url( $url, $attachment_id ) {
	if ( ! get_post_meta( $attachment_id, '_summit_intake_upload', true ) ) {
		return $url;
	}

	return admin_url( sprintf(
		'admin-ajax.php?action=summit_intake_download&id=%d&nonce=%s',
		$attachment_id,
		wp_create_nonce( 'summit_intake_download' )
	) );
}
add_filter( 'wp_get_attachment_url', 'summit_intake_proxy_url', 10, 2 );

// =============================================================================
// Hide Intake Attachments from Media Library
// =============================================================================

/**
 * Exclude intake uploads from Media Library queries so they don't clutter
 * the grid/list view. ACF file fields, the download proxy, and the orphan
 * cleanup cron all query by specific ID or their own meta_query, so they
 * are unaffected.
 */
function summit_intake_hide_from_media_library( $query ) {
	if ( ! isset( $query['meta_query'] ) ) {
		$query['meta_query'] = [];
	}
	$query['meta_query'][] = [
		'key'     => '_summit_intake_upload',
		'compare' => 'NOT EXISTS',
	];
	return $query;
}
add_filter( 'ajax_query_attachments_args', 'summit_intake_hide_from_media_library' );

// =============================================================================
// AJAX — Submit Intake
// =============================================================================

function summit_intake_submit() {
	check_ajax_referer( 'summit-nonce', 'nonce' );

	$data = json_decode( file_get_contents( 'php://input' ), true );
	if ( ! $data ) {
		// Fallback to POST data
		$data = $_POST;
	}

	$plaintiffs    = $data['plaintiffs'] ?? [];
	$defendants    = $data['defendants'] ?? [];
	$third_parties = $data['thirdParties'] ?? [];
	$team_member      = absint( $data['teamMemberId'] ?? 0 );
	$team_member_name = sanitize_text_field( $data['teamMemberName'] ?? '' );
	$title_file_id    = absint( $data['titleOfProceedingsId'] ?? 0 );

	// If no team member ID but we have a name from Amelia's DOM, match against team posts
	if ( ! $team_member && $team_member_name ) {
		// Try exact title match first
		$matches = get_posts( [
			'post_type'      => 'team',
			'posts_per_page' => 1,
			'title'          => $team_member_name,
			'post_status'    => 'publish',
		] );

		// If no exact match, search by partial title (handles cases like
		// "Debbie Orth Attorney" where Amelia appends the role label)
		if ( empty( $matches ) ) {
			global $wpdb;
			$like = '%' . $wpdb->esc_like( $team_member_name ) . '%';
			// Check if any team post title is contained within the received name
			$found_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = 'team'
				 AND post_status = 'publish'
				 AND %s LIKE CONCAT('%%', post_title, '%%')
				 ORDER BY CHAR_LENGTH(post_title) DESC
				 LIMIT 1",
				$team_member_name
			) );
			if ( $found_id ) {
				$team_member = absint( $found_id );
			}
		} else {
			$team_member = $matches[0]->ID;
		}
	}
	$claim_ids     = array_map( 'absint', $data['thirdPartyClaimIds'] ?? [] );

	// Validate minimum data
	if ( empty( $plaintiffs ) || empty( $defendants ) ) {
		wp_send_json_error( [ 'message' => 'At least one plaintiff and one defendant are required.' ] );
	}

	// Build post title
	$plaintiff_name  = sanitize_text_field( $plaintiffs[0]['name'] ?? 'Unknown' );
	$defendant_name  = sanitize_text_field( $defendants[0]['name'] ?? 'Unknown' );
	$post_title      = sprintf( 'Intake — %s v %s — %s', $plaintiff_name, $defendant_name, wp_date( 'Y-m-d' ) );

	// Retrieve Amelia booking data from transient
	$session_key    = summit_intake_session_key();
	$transient_data = $session_key ? get_transient( $session_key ) : null;
	if ( $session_key ) {
		delete_transient( $session_key );
	}

	// Handle both new (array) and legacy (int) transient format
	if ( is_array( $transient_data ) ) {
		$amelia_booking_id = $transient_data['booking_id'] ?? 0;
		$booking_date      = $transient_data['booking_date'] ?? '';
	} else {
		$amelia_booking_id = $transient_data ?: 0;
		$booking_date      = '';
	}

	// Create the intake post
	$post_id = wp_insert_post( [
		'post_type'   => 'mediation_intake',
		'post_title'  => $post_title,
		'post_status' => 'publish',
	] );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( [ 'message' => 'Failed to create intake record.' ] );
	}

	// Set ACF fields
	update_field( 'intake_status', 'confirmed', $post_id );
	update_field( 'amelia_booking_id', $amelia_booking_id ?: '', $post_id );
	if ( $booking_date ) {
		update_field( 'booking_date', $booking_date, $post_id );
	}
	update_field( 'team_member', $team_member, $post_id );

	// Save plaintiffs repeater
	$plaintiff_rows = [];
	foreach ( $plaintiffs as $p ) {
		$plaintiff_rows[] = [
			'name'          => sanitize_text_field( $p['name'] ?? '' ),
			'counsel_name'  => sanitize_text_field( $p['counsel_name'] ?? '' ),
			'counsel_email' => sanitize_email( $p['counsel_email'] ?? '' ),
		];
	}
	update_field( 'plaintiffs', $plaintiff_rows, $post_id );

	// Save defendants repeater
	$defendant_rows = [];
	foreach ( $defendants as $d ) {
		$defendant_rows[] = [
			'name'          => sanitize_text_field( $d['name'] ?? '' ),
			'counsel_name'  => sanitize_text_field( $d['counsel_name'] ?? '' ),
			'counsel_email' => sanitize_email( $d['counsel_email'] ?? '' ),
		];
	}
	update_field( 'defendants', $defendant_rows, $post_id );

	// Save third parties repeater
	$third_party_rows = [];
	foreach ( $third_parties as $tp ) {
		$third_party_rows[] = [
			'name'          => sanitize_text_field( $tp['name'] ?? '' ),
			'counsel_name'  => sanitize_text_field( $tp['counsel_name'] ?? '' ),
			'counsel_email' => sanitize_email( $tp['counsel_email'] ?? '' ),
		];
	}
	update_field( 'third_parties', $third_party_rows, $post_id );

	// Save title of proceedings file
	if ( $title_file_id ) {
		update_field( 'title_of_proceedings', $title_file_id, $post_id );
		// Remove orphan timestamp so cron won't delete it; keep _summit_intake_upload
		// so the download proxy continues to recognize it as an intake file.
		delete_post_meta( $title_file_id, '_summit_intake_upload_time' );
	}

	// Save third party claim files repeater
	$claim_rows = [];
	foreach ( $claim_ids as $claim_id ) {
		if ( $claim_id ) {
			$claim_rows[] = [ 'claim_file' => $claim_id ];
			delete_post_meta( $claim_id, '_summit_intake_upload_time' );
		}
	}
	if ( ! empty( $claim_rows ) ) {
		update_field( 'third_party_claims', $claim_rows, $post_id );
	}

	wp_send_json_success( [
		'intake_id'        => $post_id,
		'amelia_booking_id' => $amelia_booking_id ?: null,
	] );
}
add_action( 'wp_ajax_summit_intake_submit', 'summit_intake_submit' );
add_action( 'wp_ajax_nopriv_summit_intake_submit', 'summit_intake_submit' );

// =============================================================================
// WP-Cron — Orphaned Upload Cleanup
// =============================================================================

/**
 * Schedule daily cleanup on theme activation.
 */
function summit_intake_schedule_cleanup() {
	if ( ! wp_next_scheduled( 'summit_intake_cleanup_orphans' ) ) {
		wp_schedule_event( time(), 'daily', 'summit_intake_cleanup_orphans' );
	}
}
add_action( 'after_setup_theme', 'summit_intake_schedule_cleanup' );

/**
 * Remove the scheduled event on theme deactivation.
 */
function summit_intake_deactivate_cleanup() {
	wp_clear_scheduled_hook( 'summit_intake_cleanup_orphans' );
}
add_action( 'switch_theme', 'summit_intake_deactivate_cleanup' );

/**
 * Delete orphaned intake file uploads older than 24 hours.
 */
function summit_intake_cleanup_orphaned_uploads() {
	$cutoff = time() - DAY_IN_SECONDS;

	$orphans = get_posts( [
		'post_type'      => 'attachment',
		'posts_per_page' => 50,
		'meta_query'     => [
			[
				'key'     => '_summit_intake_upload',
				'value'   => '1',
			],
			[
				'key'     => '_summit_intake_upload_time',
				'value'   => $cutoff,
				'compare' => '<',
				'type'    => 'NUMERIC',
			],
		],
	] );

	foreach ( $orphans as $orphan ) {
		wp_delete_attachment( $orphan->ID, true );
	}
}
add_action( 'summit_intake_cleanup_orphans', 'summit_intake_cleanup_orphaned_uploads' );

// =============================================================================
// Auto-delete Attached Files on Intake Deletion
// =============================================================================

/**
 * When a mediation intake post is permanently deleted, remove its attached
 * files (Title of Proceedings, Statements of Claim) so they don't orphan
 * in the summit-intake/ directory.
 */
function summit_intake_delete_attached_files( $post_id ) {
	if ( get_post_type( $post_id ) !== 'mediation_intake' ) {
		return;
	}

	// Title of Proceedings
	$title_id = get_field( 'title_of_proceedings', $post_id, false );
	if ( $title_id ) {
		wp_delete_attachment( (int) $title_id, true );
	}

	// Third Party Statements of Claim
	$claims = get_field( 'third_party_claims', $post_id );
	if ( is_array( $claims ) ) {
		foreach ( $claims as $row ) {
			$claim_id = $row['claim_file'] ?? 0;
			if ( $claim_id ) {
				wp_delete_attachment( (int) $claim_id, true );
			}
		}
	}
}
add_action( 'before_delete_post', 'summit_intake_delete_attached_files' );

// =============================================================================
// Settings Page — Role-Based Download Access
// =============================================================================

/**
 * Register the Permissions sub-page under Mediation Intakes.
 */
function summit_intake_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=mediation_intake',
		'Permissions',
		'Permissions',
		'manage_options',
		'summit-intake-permissions',
		'summit_intake_render_settings_page'
	);
}
add_action( 'admin_menu', 'summit_intake_add_settings_page' );

/**
 * Register the setting and field.
 */
function summit_intake_register_settings() {
	register_setting( 'summit_intake_settings', 'summit_intake_download_roles', [
		'type'              => 'array',
		'sanitize_callback' => 'summit_intake_sanitize_roles',
		'default'           => [ 'administrator' ],
	] );

	register_setting( 'summit_intake_settings', 'summit_intake_allow_manual_create', [
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	] );

	add_settings_section(
		'summit_intake_download_section',
		'File Download Access',
		function () {
			echo '<p>Select which WordPress roles are allowed to download mediation intake files (Title of Proceedings, Statements of Claim).</p>';
		},
		'summit-intake-settings'
	);

	add_settings_field(
		'summit_intake_download_roles',
		'Allowed Roles',
		'summit_intake_render_roles_field',
		'summit-intake-settings',
		'summit_intake_download_section'
	);

	add_settings_section(
		'summit_intake_general_section',
		'General',
		'__return_false',
		'summit-intake-settings'
	);

	add_settings_field(
		'summit_intake_allow_manual_create',
		'Manual Intake Creation',
		'summit_intake_render_manual_create_field',
		'summit-intake-settings',
		'summit_intake_general_section'
	);
}

/**
 * Render the manual creation toggle checkbox.
 */
function summit_intake_render_manual_create_field() {
	$enabled = get_option( 'summit_intake_allow_manual_create', false );
	printf(
		'<label><input type="checkbox" name="summit_intake_allow_manual_create" value="1" %s> Allow "Add New Intake" in the admin dashboard (Leave unchecked).</label>',
		checked( $enabled, true, false )
	);
}
add_action( 'admin_init', 'summit_intake_register_settings' );

/**
 * Sanitize the roles array — only keep valid WP role slugs.
 */
function summit_intake_sanitize_roles( $input ) {
	if ( ! is_array( $input ) ) {
		return [ 'administrator' ];
	}

	$valid_roles = array_keys( wp_roles()->roles );
	return array_values( array_intersect( $input, $valid_roles ) );
}

/**
 * Render the multi-checkbox roles field.
 */
function summit_intake_render_roles_field() {
	$selected = get_option( 'summit_intake_download_roles', [ 'administrator' ] );
	$roles    = wp_roles()->roles;

	foreach ( $roles as $slug => $role ) {
		printf(
			'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="summit_intake_download_roles[]" value="%s" %s> %s</label>',
			esc_attr( $slug ),
			checked( in_array( $slug, $selected, true ), true, false ),
			esc_html( $role['name'] )
		);
	}
}

/**
 * Render the settings page.
 */
function summit_intake_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Mediation Intake — Permissions</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'summit_intake_settings' );
			do_settings_sections( 'summit-intake-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
