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
			$new['bundle']       = 'Actions';
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
	if ( $column === 'booking_date' ) {
		$date = get_field( 'booking_date', $post_id );
		if ( $date ) {
			$dt = new DateTimeImmutable( $date, wp_timezone() );
			echo esc_html( wp_date( 'F j, Y \a\t g:i a', $dt->getTimestamp() ) );
		} else {
			echo '—';
		}
	}

	if ( $column === 'bundle' ) {
		echo '<div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start;">';

		if ( summit_intake_current_user_can_download() ) {
			$bundle_url = wp_nonce_url(
				add_query_arg(
					[ 'action' => 'summit_intake_download_bundle', 'post_id' => $post_id ],
					admin_url( 'admin-post.php' )
				),
				'summit_intake_bundle_' . $post_id,
				'_bundle_nonce'
			);
			echo '<a href="' . esc_url( $bundle_url ) . '" class="button button-small" target="_blank" rel="noopener noreferrer">&#8675; Download</a>';
		} else {
			echo '<span style="color:#999;font-size:12px;" title="Contact your administrator to gain download access.">No access</span>';
		}

		$mailto = summit_intake_mailto_url( $post_id );
		if ( $mailto ) {
			echo '<a href="' . esc_attr( $mailto ) . '" class="button button-small" target="_blank" rel="noopener noreferrer">&#9993; Email Parties</a>';
		}

		echo '</div>';
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
// ACF Field Group — loaded from acf-json/group_mediation_intake.json
// =============================================================================

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
/**
 * Render a branded access-denied page with the theme header and footer.
 * Used for both unauthenticated and unauthorised download attempts.
 *
 * @param int    $status  HTTP status code (401 or 403).
 * @param string $heading Page heading.
 * @param string $message Explanatory paragraph shown beneath the heading.
 */
function summit_intake_render_access_denied( $status, $heading, $message ) {
	status_header( $status );
	nocache_headers();

	get_header();
	?>
	<main id="main" class="site-main">
		<div class="container mx-auto px-4">
			<div class="max-w-2xl mx-auto text-center py-20">

				<div class="mb-8">
					<svg class="w-12 h-12 mx-auto text-green-deep opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
					</svg>
				</div>

				<div class="mb-12">
					<h1 class="text-3xl md:text-4xl font-bold text-green-deep mb-4">
						<?php echo esc_html( $heading ); ?>
					</h1>
					<p class="text-lg text-gray-600">
						<?php echo esc_html( $message ); ?>
					</p>
				</div>

				<div class="flex flex-col sm:flex-row gap-4 justify-center">

					<?php if ( $status === 401 ) : ?>
					<a href="<?php echo esc_url( wp_login_url( home_url() ) ); ?>"
					   class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-lg hover:border-green-deep hover:shadow-lg transition-all duration-300">
						<svg class="w-12 h-12 text-green-deep mb-3 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
						</svg>
						<span class="text-lg font-medium text-green-deep"><?php esc_html_e( 'Log In', 'summit-law-theme' ); ?></span>
						<span class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Access your account', 'summit-law-theme' ); ?></span>
					</a>
					<?php endif; ?>

					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"
					   class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-lg hover:border-green-deep hover:shadow-lg transition-all duration-300">
						<svg class="w-12 h-12 text-green-deep mb-3 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
						</svg>
						<span class="text-lg font-medium text-green-deep"><?php esc_html_e( 'Home', 'summit-law-theme' ); ?></span>
						<span class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Return to homepage', 'summit-law-theme' ); ?></span>
					</a>

				</div>

			</div>
		</div>
	</main>
	<?php
	get_footer();
	die();
}

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
		summit_intake_render_access_denied(
			403,
			'Access Restricted',
			'You don\'t have permission to download this file. Contact your administrator to have your role updated under Mediation Intakes → Permissions.'
		);
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

function summit_intake_download_nopriv() {
	summit_intake_render_access_denied(
		401,
		'Login Required',
		'This file is restricted to authorised staff. Please log in to continue.'
	);
}
add_action( 'wp_ajax_nopriv_summit_intake_download', 'summit_intake_download_nopriv' );

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
// OttoKit Webhook Dispatch
// =============================================================================

/**
 * Fire a non-blocking POST to the configured OttoKit webhook URL.
 * If no URL is saved, returns immediately without doing anything.
 *
 * @param int   $post_id The mediation intake post ID (for reference).
 * @param array $data    Associative array to send as JSON payload.
 */
function summit_intake_dispatch_webhook( $post_id, $data ) {
	$webhook_url = get_option( 'summit_intake_ottokit_webhook_url', '' );
	if ( ! $webhook_url ) {
		return;
	}

	wp_remote_post( $webhook_url, [
		'headers'  => [ 'Content-Type' => 'application/json' ],
		'body'     => wp_json_encode( $data ),
		'timeout'  => 10,
		'blocking' => false, // Fire-and-forget — don't delay the user's success response.
	] );
}

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

	// Validate counsel emails — sanitize_email() returns '' for invalid input
	foreach ( [ 'plaintiff' => $plaintiffs, 'defendant' => $defendants, 'third party' => $third_parties ] as $label => $parties ) {
		foreach ( $parties as $i => $p ) {
			$raw = trim( $p['counsel_email'] ?? '' );
			if ( $raw !== '' && sanitize_email( $raw ) === '' ) {
				wp_send_json_error( [
					'message' => sprintf(
						'Invalid email address for %s %d: "%s". Please go back and correct it.',
						$label,
						$i + 1,
						$raw
					),
				] );
			}
		}
	}

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

	// Save third parties repeater — claim file is stored inline per row
	$third_party_rows = [];
	foreach ( $third_parties as $i => $tp ) {
		$claim_id = absint( $claim_ids[ $i ] ?? 0 );
		if ( $claim_id ) {
			delete_post_meta( $claim_id, '_summit_intake_upload_time' );
		}
		$third_party_rows[] = [
			'name'          => sanitize_text_field( $tp['name'] ?? '' ),
			'counsel_name'  => sanitize_text_field( $tp['counsel_name'] ?? '' ),
			'counsel_email' => sanitize_email( $tp['counsel_email'] ?? '' ),
			'claim_file'    => $claim_id ?: '',
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

	// Resolve mediator name for webhook payload
	$mediator_post = $team_member ? get_post( $team_member ) : null;
	$mediator_name = ( $mediator_post instanceof WP_Post ) ? $mediator_post->post_title : $team_member_name;

	// Collect all counsel emails (deduplicated)
	$all_counsel_emails = [];
	foreach ( array_merge( $plaintiff_rows, $defendant_rows, $third_party_rows ) as $party ) {
		$email = $party['counsel_email'] ?? '';
		if ( $email ) {
			$all_counsel_emails[] = $email;
		}
	}
	$all_counsel_emails = array_values( array_unique( $all_counsel_emails ) );

	// Format booking date in the site's local timezone
	$booking_date_formatted = '';
	if ( $booking_date ) {
		$dt                     = new DateTimeImmutable( $booking_date, wp_timezone() );
		$booking_date_formatted = wp_date( 'F j, Y \a\t g:i a', $dt->getTimestamp() );
	}

	// Strip "Intake — " prefix to get a clean case name
	$case_name = preg_replace( '/^Intake\s*[—\-]+\s*/u', '', $post_title );

	summit_intake_dispatch_webhook( $post_id, [
		'intake_id'          => $post_id,
		'case_name'          => $case_name,
		'booking_date'       => $booking_date_formatted,
		'booking_date_raw'   => $booking_date,
		'mediator_name'      => $mediator_name,
		'all_counsel_emails' => $all_counsel_emails,
		'counsel_emails_to'  => implode( ', ', $all_counsel_emails ),
		'plaintiffs'         => $plaintiff_rows,
		'defendants'         => $defendant_rows,
		'third_parties'      => $third_party_rows,
		'amelia_booking_id'  => $amelia_booking_id ?: null,
	] );

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

	// Third Party Statements of Claim (stored inline within third_parties repeater)
	$third_parties = get_field( 'third_parties', $post_id );
	if ( is_array( $third_parties ) ) {
		foreach ( $third_parties as $row ) {
			$claim_id = absint( $row['claim_file'] ?? 0 );
			if ( $claim_id ) {
				wp_delete_attachment( $claim_id, true );
			}
		}
	}
}
add_action( 'before_delete_post', 'summit_intake_delete_attached_files' );

// =============================================================================
// REST API — Intake Lookup by Amelia Booking ID
// =============================================================================

/**
 * Register the intake lookup endpoint.
 * Used by OttoKit to retrieve custom intake data during cancellation workflows.
 *
 * GET /wp-json/summit/v1/intake-by-booking?booking_id=39&key=SECRET
 */
function summit_intake_register_rest_routes() {
	register_rest_route( 'summit/v1', '/intake-by-booking', [
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'summit_intake_lookup_by_booking',
		'permission_callback' => '__return_true', // Auth handled inside via secret key
		'args'                => [
			'booking_id' => [
				'required'          => true,
				'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
				'sanitize_callback' => 'absint',
			],
			'key' => [
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		],
	] );
}
add_action( 'rest_api_init', 'summit_intake_register_rest_routes' );

/**
 * Handle the intake lookup request.
 */
function summit_intake_lookup_by_booking( WP_REST_Request $request ) {
	// Validate secret key
	$stored_key = get_option( 'summit_intake_lookup_secret_key', '' );
	if ( ! $stored_key || ! hash_equals( $stored_key, $request->get_param( 'key' ) ) ) {
		return new WP_REST_Response( [ 'error' => 'Unauthorized.' ], 401 );
	}

	$booking_id = $request->get_param( 'booking_id' );

	// Find the intake post with this Amelia booking ID
	$posts = get_posts( [
		'post_type'      => 'mediation_intake',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'   => 'amelia_booking_id',
				'value' => $booking_id,
			],
		],
	] );

	if ( empty( $posts ) ) {
		return new WP_REST_Response( [ 'error' => 'No intake found for this booking ID.' ], 404 );
	}

	$post_id = $posts[0]->ID;

	// Gather ACF data
	$booking_date  = get_field( 'booking_date', $post_id );
	$team_member   = get_field( 'team_member', $post_id );
	$plaintiffs    = get_field( 'plaintiffs', $post_id ) ?: [];
	$defendants    = get_field( 'defendants', $post_id ) ?: [];
	$third_parties = get_field( 'third_parties', $post_id ) ?: [];

	$mediator_post = $team_member ? get_post( absint( is_array( $team_member ) ? ( $team_member['ID'] ?? 0 ) : $team_member ) ) : null;
	$mediator_name = ( $mediator_post instanceof WP_Post ) ? $mediator_post->post_title : '';

	$booking_date_formatted = '';
	if ( $booking_date ) {
		$dt                     = new DateTimeImmutable( $booking_date, wp_timezone() );
		$booking_date_formatted = wp_date( 'F j, Y \a\t g:i a', $dt->getTimestamp() );
	}

	// Collect all counsel emails
	$all_counsel_emails = [];
	foreach ( array_merge( $plaintiffs, $defendants, $third_parties ) as $party ) {
		$email = $party['counsel_email'] ?? '';
		if ( $email ) {
			$all_counsel_emails[] = $email;
		}
	}
	$all_counsel_emails = array_values( array_unique( $all_counsel_emails ) );

	$case_name = preg_replace( '/^Intake\s*[—\-]+\s*/u', '', $posts[0]->post_title );

	return new WP_REST_Response( [
		'intake_id'          => $post_id,
		'case_name'          => $case_name,
		'booking_date'       => $booking_date_formatted,
		'booking_date_raw'   => $booking_date,
		'mediator_name'      => $mediator_name,
		'all_counsel_emails' => $all_counsel_emails,
		'counsel_emails_to'  => implode( ', ', $all_counsel_emails ),
		'plaintiffs'         => $plaintiffs,
		'defendants'         => $defendants,
		'third_parties'      => $third_parties,
		'amelia_booking_id'  => $booking_id,
	], 200 );
}

// =============================================================================
// Secret Key — Auto-generation, Regeneration, Admin UI
// =============================================================================

/**
 * Auto-generate the lookup secret key on first use if none is set.
 */
function summit_intake_maybe_generate_secret_key() {
	if ( ! get_option( 'summit_intake_lookup_secret_key', '' ) ) {
		update_option( 'summit_intake_lookup_secret_key', wp_generate_password( 32, false ) );
	}
}
add_action( 'admin_init', 'summit_intake_maybe_generate_secret_key' );

/**
 * Handle the Regenerate Key form action.
 */
function summit_intake_handle_regenerate_key() {
	check_admin_referer( 'summit_regenerate_lookup_key' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized.' );
	}

	update_option( 'summit_intake_lookup_secret_key', wp_generate_password( 32, false ) );

	wp_redirect( add_query_arg(
		[ 'page' => 'summit-intake-permissions', 'key_regenerated' => '1' ],
		admin_url( 'edit.php?post_type=mediation_intake' )
	) );
	exit;
}
add_action( 'admin_post_summit_regenerate_lookup_key', 'summit_intake_handle_regenerate_key' );

/**
 * Show a success notice after key regeneration.
 */
function summit_intake_regenerate_notice() {
	if ( empty( $_GET['key_regenerated'] ) || empty( $_GET['page'] ) || $_GET['page'] !== 'summit-intake-permissions' ) {
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p><strong>Lookup secret key regenerated.</strong> Update the URL in OttoKit with the new value shown below.</p></div>';
}
add_action( 'admin_notices', 'summit_intake_regenerate_notice' );

/**
 * Enqueue reveal/copy JS on the Permissions settings page only.
 */
function summit_intake_settings_footer_js() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->id !== 'mediation_intake_page_summit-intake-permissions' ) {
		return;
	}
	?>
	<script>
	(function () {
		// Reveal / Hide toggle
		const keyInput  = document.getElementById('summit-lookup-key');
		const revealBtn = document.getElementById('summit-reveal-key');
		if (keyInput && revealBtn) {
			revealBtn.addEventListener('click', function () {
				if (keyInput.type === 'password') {
					keyInput.type    = 'text';
					revealBtn.textContent = 'Hide';
				} else {
					keyInput.type    = 'password';
					revealBtn.textContent = 'Reveal';
				}
			});
		}

		// Copy key
		const copyKeyBtn = document.getElementById('summit-copy-key');
		if (copyKeyBtn && keyInput) {
			copyKeyBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(keyInput.value).then(function () {
					copyKeyBtn.textContent = 'Copied!';
					setTimeout(function () { copyKeyBtn.textContent = 'Copy'; }, 2000);
				});
			});
		}

		// Copy URL
		const urlInput  = document.getElementById('summit-lookup-url');
		const copyUrlBtn = document.getElementById('summit-copy-url');
		if (copyUrlBtn && urlInput) {
			copyUrlBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(urlInput.value).then(function () {
					copyUrlBtn.textContent = 'Copied!';
					setTimeout(function () { copyUrlBtn.textContent = 'Copy'; }, 2000);
				});
			});
		}
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'summit_intake_settings_footer_js' );

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

	register_setting( 'summit_intake_settings', 'summit_intake_ottokit_webhook_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
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

	add_settings_section(
		'summit_intake_notifications_section',
		'OttoKit Notifications',
		function () {
			echo '<p>Configure OttoKit integration for booking confirmation and cancellation notifications.</p>';
		},
		'summit-intake-settings'
	);

	add_settings_field(
		'summit_intake_ottokit_webhook_url',
		'OttoKit Webhook URL',
		'summit_intake_render_webhook_url_field',
		'summit-intake-settings',
		'summit_intake_notifications_section'
	);

	add_settings_field(
		'summit_intake_lookup_secret_key',
		'Lookup Secret Key',
		'summit_intake_render_lookup_secret_field',
		'summit-intake-settings',
		'summit_intake_notifications_section'
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

/**
 * Render the OttoKit webhook URL input.
 */
function summit_intake_render_webhook_url_field() {
	$url = get_option( 'summit_intake_ottokit_webhook_url', '' );
	printf(
		'<input type="url" name="summit_intake_ottokit_webhook_url" value="%s" class="regular-text" placeholder="https://...">',
		esc_attr( $url )
	);
	echo '<p class="description">This URL is provided by OttoKit when you create a "Catch Webhook" trigger step.</p>';
}

/**
 * Render the lookup secret key field — read-only with reveal, copy, and regenerate controls.
 */
function summit_intake_render_lookup_secret_field() {
	$key        = get_option( 'summit_intake_lookup_secret_key', '' );
	$lookup_url = $key
		? rest_url( 'summit/v1/intake-by-booking' ) . '?booking_id=BOOKING_ID&key=' . rawurlencode( $key )
		: '';
	$regen_url  = wp_nonce_url(
		admin_url( 'admin-post.php?action=summit_regenerate_lookup_key' ),
		'summit_regenerate_lookup_key'
	);
	?>
	<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
		<input
			type="password"
			id="summit-lookup-key"
			value="<?php echo esc_attr( $key ); ?>"
			readonly
			style="font-family:monospace;width:320px;"
		>
		<button type="button" id="summit-reveal-key" class="button">Reveal</button>
		<button type="button" id="summit-copy-key" class="button">Copy</button>
		<a
			href="<?php echo esc_url( $regen_url ); ?>"
			class="button"
			onclick="return confirm('Regenerate the secret key?\n\nThe OttoKit lookup URL will stop working until you update it there.');"
		>Regenerate</a>
	</div>
	<p class="description">Auto-generated. Used to authenticate OttoKit's requests to the lookup endpoint. Keep this private.</p>

	<?php if ( $lookup_url ) : ?>
	<div style="margin-top:14px;">
		<strong style="display:block;margin-bottom:6px;">OttoKit Lookup URL</strong>
		<div style="display:flex;align-items:center;gap:8px;">
			<input
				type="text"
				id="summit-lookup-url"
				value="<?php echo esc_attr( $lookup_url ); ?>"
				readonly
				style="font-family:monospace;width:100%;max-width:580px;"
			>
			<button type="button" id="summit-copy-url" class="button">Copy</button>
		</div>
		<p class="description">Replace <code>BOOKING_ID</code> with <code>@{Appointmentid}</code> in OttoKit.</p>
	</div>
	<?php endif; ?>
	<?php
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

// =============================================================================
// Conflict Search Bundle — Email Parties Mailto Helper
// =============================================================================

/**
 * Build a mailto: URL addressed to all counsel on the intake.
 * Subject line includes the case name and booking date.
 * Returns an empty string if no counsel emails are found.
 */
function summit_intake_mailto_url( $post_id ) {
	$plaintiffs    = get_field( 'plaintiffs', $post_id ) ?: [];
	$defendants    = get_field( 'defendants', $post_id ) ?: [];
	$third_parties = get_field( 'third_parties', $post_id ) ?: [];

	$emails = [];
	foreach ( array_merge( $plaintiffs, $defendants, $third_parties ) as $party ) {
		$email = sanitize_email( $party['counsel_email'] ?? '' );
		if ( $email ) {
			$emails[] = $email;
		}
	}
	$emails = array_values( array_unique( $emails ) );

	if ( empty( $emails ) ) {
		return '';
	}

	// Build subject: strip the "Intake — " prefix from the post title, then append booking date
	$post      = get_post( $post_id );
	$case_name = $post ? preg_replace( '/^Intake\s*[—\-]+\s*/u', '', $post->post_title ) : '';
	$subject   = 'Mediation — ' . $case_name;

	$booking_date = get_field( 'booking_date', $post_id );
	if ( $booking_date ) {
		$dt = new DateTimeImmutable( $booking_date, wp_timezone() );
		$subject .= ' — ' . wp_date( 'F j, Y', $dt->getTimestamp() );
	}

	return 'mailto:' . implode( ',', $emails ) . '?subject=' . rawurlencode( $subject );
}

// =============================================================================
// Conflict Search Bundle — Role Helper
// =============================================================================

/**
 * Return true if the current logged-in user has a role allowed to download
 * intake files (same permission set used by the individual file proxy).
 */
function summit_intake_current_user_can_download() {
	$user = wp_get_current_user();
	if ( ! $user->ID ) {
		return false;
	}
	$allowed_roles = get_option( 'summit_intake_download_roles', [ 'administrator' ] );
	foreach ( $allowed_roles as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			return true;
		}
	}
	return false;
}

// =============================================================================
// Conflict Search Bundle — Download Action
// =============================================================================

/**
 * Stream a ZIP bundle containing an HTML conflict-search summary and all
 * uploaded files (Title of Proceedings + Third Party Statements of Claim).
 *
 * Triggered via admin-post.php (both GET and POST) so PHP can output raw
 * file headers directly without an AJAX JSON wrapper.
 */
function summit_intake_download_bundle() {
	$post_id = absint( $_REQUEST['post_id'] ?? 0 );

	// Nonce check
	if ( ! wp_verify_nonce( $_REQUEST['_bundle_nonce'] ?? '', 'summit_intake_bundle_' . $post_id ) ) {
		wp_die(
			'<p>Security check failed. Please go back and try again.</p>',
			'Access Denied',
			[ 'response' => 403, 'back_link' => true ]
		);
	}

	// Validate post
	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'mediation_intake' ) {
		wp_die(
			'<p>Intake not found.</p>',
			'Not Found',
			[ 'response' => 404, 'back_link' => true ]
		);
	}

	// Role check with a descriptive message for unauthorised users
	if ( ! summit_intake_current_user_can_download() ) {
		wp_die(
			'<p>You do not have permission to download the conflict search bundle.</p>'
			. '<p>Contact your administrator to have your role updated under '
			. '<strong>Mediation Intakes &rarr; Permissions</strong>.</p>',
			'Access Denied',
			[ 'response' => 403, 'back_link' => true ]
		);
	}

	// Server capability check
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die(
			'<p>ZIP file generation is not available on this server. Please contact your site administrator.</p>',
			'Server Error',
			[ 'response' => 500, 'back_link' => true ]
		);
	}

	// Gather ACF data
	$booking_date      = get_field( 'booking_date', $post_id );
	$team_member_id    = get_field( 'team_member', $post_id );
	$team_member_post  = $team_member_id ? get_post( absint( $team_member_id ) ) : null;
	$mediator_name     = ( $team_member_post instanceof WP_Post ) ? $team_member_post->post_title : '—';
	$plaintiffs        = get_field( 'plaintiffs', $post_id ) ?: [];
	$defendants        = get_field( 'defendants', $post_id ) ?: [];
	$third_parties     = get_field( 'third_parties', $post_id ) ?: [];
	$title_file        = get_field( 'title_of_proceedings', $post_id );

	$booking_date_formatted = $booking_date
		? wp_date( 'F j, Y \a\t g:i a', ( new DateTimeImmutable( $booking_date, wp_timezone() ) )->getTimestamp() )
		: '—';

	// Resolve uploaded files to disk paths + display names
	$files = [];

	if ( $title_file ) {
		$att_id  = is_array( $title_file ) ? ( $title_file['ID'] ?? 0 ) : absint( $title_file );
		$path    = $att_id ? get_attached_file( $att_id ) : '';
		$orignal = $att_id ? ( get_post_meta( $att_id, '_summit_intake_original_name', true ) ?: basename( $path ) ) : '';
		if ( $path && file_exists( $path ) ) {
			$files[ 'Title-of-Proceedings-' . $orignal ] = $path;
		}
	}

	$claim_num = 1;
	foreach ( $third_parties as $tp_row ) {
		$claim_id = absint( $tp_row['claim_file'] ?? 0 );
		if ( ! $claim_id ) {
			continue;
		}
		$path    = get_attached_file( $claim_id );
		$orignal = get_post_meta( $claim_id, '_summit_intake_original_name', true ) ?: basename( $path );
		if ( $path && file_exists( $path ) ) {
			$files[ 'Third-Party-Claim-' . $claim_num . '-' . $orignal ] = $path;
			$claim_num++;
		}
	}

	// Generate HTML summary
	$html = summit_intake_bundle_html(
		$post,
		$booking_date_formatted,
		$mediator_name,
		$plaintiffs,
		$defendants,
		$third_parties,
		$title_file
	);

	// Build ZIP
	$post_slug = sanitize_file_name( $post->post_title );
	$zip_name  = 'Conflict-Bundle-' . $post_slug . '.zip';
	$zip_path  = trailingslashit( sys_get_temp_dir() ) . $zip_name;

	$zip = new ZipArchive();
	if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		wp_die(
			'<p>Could not create the ZIP file. Please try again or contact your administrator.</p>',
			'Error',
			[ 'response' => 500, 'back_link' => true ]
		);
	}

	$zip->addFromString( 'Conflict-Search-Summary.html', $html );
	foreach ( $files as $zip_filename => $file_path ) {
		$zip->addFile( $file_path, $zip_filename );
	}
	$zip->close();

	// Clear any buffered output before streaming binary data
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $zip_name . '"' );
	header( 'Content-Length: ' . filesize( $zip_path ) );
	flush();

	readfile( $zip_path );
	@unlink( $zip_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	exit;
}
add_action( 'admin_post_summit_intake_download_bundle', 'summit_intake_download_bundle' );

// =============================================================================
// Conflict Search Bundle — HTML Summary Generator
// =============================================================================

/**
 * Return a self-contained, print-friendly HTML document summarising the intake.
 */
function summit_intake_bundle_html( $post, $booking_date, $mediator, $plaintiffs, $defendants, $third_parties, $title_file ) {
	$generated = wp_date( 'F j, Y \a\t g:i a' );
	$title     = esc_html( $post->post_title );

	// Build file list for the "Files Included" section
	$file_list   = [];
	$file_list[] = 'Conflict-Search-Summary.html (this document)';

	if ( $title_file ) {
		$att_id  = is_array( $title_file ) ? ( $title_file['ID'] ?? 0 ) : absint( $title_file );
		$orignal = $att_id ? ( get_post_meta( $att_id, '_summit_intake_original_name', true ) ?: '' ) : '';
		if ( $orignal ) {
			$file_list[] = esc_html( 'Title-of-Proceedings-' . $orignal );
		}
	}

	$n = 1;
	foreach ( $third_parties as $tp_row ) {
		$claim_id = absint( $tp_row['claim_file'] ?? 0 );
		if ( ! $claim_id ) {
			continue;
		}
		$orignal = get_post_meta( $claim_id, '_summit_intake_original_name', true ) ?: '';
		if ( $orignal ) {
			$file_list[] = esc_html( 'Third-Party-Claim-' . $n . '-' . $orignal );
			$n++;
		}
	}

	// Build party rows
	$build_party_rows = function( array $parties ) {
		if ( empty( $parties ) ) {
			return '<tr><td colspan="3" style="color:#888;">None recorded</td></tr>';
		}
		$rows = '';
		foreach ( $parties as $p ) {
			$rows .= '<tr>'
				. '<td>' . esc_html( $p['name'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['counsel_name'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['counsel_email'] ?? '' ) . '</td>'
				. '</tr>';
		}
		return $rows;
	};

	$plaintiff_rows    = $build_party_rows( $plaintiffs );
	$defendant_rows    = $build_party_rows( $defendants );
	$third_party_block = '';
	if ( ! empty( $third_parties ) ) {
		$third_party_block = '<h2>Third Parties</h2>'
			. '<table><thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th></tr></thead>'
			. '<tbody>' . $build_party_rows( $third_parties ) . '</tbody></table>';
	}

	$files_html = '<ul>' . implode( '', array_map( fn( $f ) => '<li>' . $f . '</li>', $file_list ) ) . '</ul>';

	return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Conflict Search &mdash; {$title}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Georgia, "Times New Roman", serif; color: #1a1a1a; max-width: 960px; margin: 40px auto; padding: 0 24px; font-size: 14px; line-height: 1.6; }
  header { border-bottom: 3px solid #1c3d4a; padding-bottom: 16px; margin-bottom: 28px; }
  header h1 { font-size: 20px; color: #1c3d4a; margin-bottom: 4px; }
  header p { font-size: 12px; color: #666; }
  .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: #f4f7f8; border: 1px solid #dde3e6; border-radius: 4px; margin-bottom: 32px; overflow: hidden; }
  .meta dt { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #888; padding: 10px 16px 2px; }
  .meta dd { font-size: 15px; font-weight: bold; padding: 0 16px 12px; }
  h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .09em; color: #1c3d4a; border-bottom: 1px solid #dde3e6; padding-bottom: 6px; margin: 32px 0 12px; }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #f4f7f8; }
  th { text-align: left; padding: 8px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #666; border-bottom: 2px solid #dde3e6; }
  td { padding: 9px 12px; border-bottom: 1px solid #eef0f1; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  td:first-child { font-weight: bold; }
  .files { background: #f9f9f9; border: 1px solid #e4e4e4; border-radius: 4px; padding: 12px 20px; margin-top: 8px; }
  .files ul { padding-left: 18px; }
  .files li { font-size: 13px; padding: 2px 0; color: #444; }
  footer { margin-top: 48px; padding-top: 12px; border-top: 1px solid #eee; font-size: 11px; color: #aaa; }
  @media print {
    body { margin: 20px; font-size: 12px; }
  }
</style>
</head>
<body>
<header>
  <h1>Summit Law LLP &mdash; Conflict Search Package</h1>
  <p>Generated: {$generated} &nbsp;&bull;&nbsp; Intake: {$title}</p>
</header>

<dl class="meta">
  <dt>Booking Date</dt><dd>{$booking_date}</dd>
  <dt>Mediator</dt><dd>{$mediator}</dd>
</dl>

<h2>Plaintiffs</h2>
<table>
  <thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th></tr></thead>
  <tbody>{$plaintiff_rows}</tbody>
</table>

<h2>Defendants</h2>
<table>
  <thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th></tr></thead>
  <tbody>{$defendant_rows}</tbody>
</table>

{$third_party_block}

<h2>Files Included in This Bundle</h2>
<div class="files">{$files_html}</div>

<footer>Summit Law LLP &mdash; Conflict Search Bundle &mdash; For internal use only.</footer>
</body>
</html>
HTML;
}

// =============================================================================
// Conflict Search Bundle — Button on Intake Edit Screen
// =============================================================================

/**
 * Render a download button (or a permission notice) directly below the intake
 * title field so it is immediately visible when opening an intake record.
 */
function summit_intake_bundle_button( $post ) {
	if ( ! $post || $post->post_type !== 'mediation_intake' ) {
		return;
	}

	echo '<div style="margin:12px 0 0;padding:12px 16px;background:#f9f9f9;border:1px solid #ddd;border-radius:3px;display:flex;align-items:center;gap:12px;">';
	echo '<strong style="flex-shrink:0;">Conflict Search Bundle:</strong> ';

	if ( summit_intake_current_user_can_download() ) {
		$url = wp_nonce_url(
			add_query_arg(
				[ 'action' => 'summit_intake_download_bundle', 'post_id' => $post->ID ],
				admin_url( 'admin-post.php' )
			),
			'summit_intake_bundle_' . $post->ID,
			'_bundle_nonce'
		);
		echo '<a href="' . esc_url( $url ) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">&#8675; Download Conflict Bundle</a>';
	} else {
		echo '<span style="color:#666;font-size:13px;">You do not have permission to download this bundle. '
			. 'Contact your administrator to update your role under '
			. '<strong>Mediation Intakes &rarr; Permissions</strong>.</span>';
	}

	$mailto = summit_intake_mailto_url( $post->ID );
	if ( $mailto ) {
		echo ' <a href="' . esc_attr( $mailto ) . '" class="button" target="_blank" rel="noopener noreferrer">&#9993; Email Parties</a>';
	}

	echo '</div>';
}
add_action( 'edit_form_after_title', 'summit_intake_bundle_button' );

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
