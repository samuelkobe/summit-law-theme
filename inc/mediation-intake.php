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
		'capability_type' => [ 'mediation_intake', 'mediation_intakes' ],
		'map_meta_cap'    => true,
	] );
}
add_action( 'init', 'summit_register_mediation_intake_cpt' );

// =============================================================================
// CPT Role Capabilities — One-Time Setup
// =============================================================================

/**
 * Grant mediation intake capabilities to administrator and manager roles.
 * Runs once and stores a flag so it never repeats on every request.
 * Re-run by deleting the _summit_intake_caps_v1 option.
 */
function summit_intake_setup_caps() {
	if ( get_option( '_summit_intake_caps_v1' ) ) return;

	$caps = [
		'edit_mediation_intake',
		'read_mediation_intake',
		'delete_mediation_intake',
		'edit_mediation_intakes',
		'edit_others_mediation_intakes',
		'publish_mediation_intakes',
		'read_private_mediation_intakes',
		'delete_mediation_intakes',
		'delete_private_mediation_intakes',
		'delete_published_mediation_intakes',
		'delete_others_mediation_intakes',
		'edit_private_mediation_intakes',
		'edit_published_mediation_intakes',
		'create_mediation_intakes',
	];

	foreach ( [ 'administrator', 'manager' ] as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) continue;
		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}

	update_option( '_summit_intake_caps_v1', true );
}
add_action( 'admin_init', 'summit_intake_setup_caps' );

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

// =============================================================================
// Protect the Agreement PDF Template
// =============================================================================

/**
 * When the agreement PDF setting is saved, move the file into the protected
 * summit-intake/ directory and mark it so it's hidden from the media library
 * and served only through the token-gated proxy on the upload portal.
 *
 * Fires on update_option_summit_intake_agreement_pdf (old_value, new_value).
 */
function summit_intake_protect_agreement_pdf( $old_id, $new_id ) {
	$new_id = absint( $new_id );
	$old_id = absint( $old_id );

	// Unmark the old attachment so it returns to the media library.
	if ( $old_id && $old_id !== $new_id ) {
		delete_post_meta( $old_id, '_summit_intake_upload' );
		delete_post_meta( $old_id, '_summit_intake_agreement_template' );
	}

	if ( ! $new_id ) return;

	// Already protected — nothing to do.
	if ( get_post_meta( $new_id, '_summit_intake_agreement_template', true ) ) return;

	$old_path = get_attached_file( $new_id );
	if ( ! $old_path || ! file_exists( $old_path ) ) return;

	// Build the destination path inside summit-intake/.
	$upload_dir  = wp_upload_dir();
	$rel_path    = ltrim( str_replace( $upload_dir['basedir'], '', $old_path ), '/' );
	$new_rel     = 'summit-intake/' . $rel_path;
	$new_path    = $upload_dir['basedir'] . '/' . $new_rel;

	// Ensure the protected directory exists with its .htaccess guard.
	summit_intake_ensure_directory_protection();
	wp_mkdir_p( dirname( $new_path ) );

	// Move the file.
	if ( ! rename( $old_path, $new_path ) ) return;

	// Update WP's internal record so get_attached_file() returns the new path.
	update_post_meta( $new_id, '_wp_attached_file', $new_rel );

	// Mark as a protected intake file — hides from media library, routes through proxy.
	update_post_meta( $new_id, '_summit_intake_upload', true );
	update_post_meta( $new_id, '_summit_intake_agreement_template', true );
}
add_action( 'update_option_summit_intake_agreement_pdf', 'summit_intake_protect_agreement_pdf', 10, 2 );
// Also fires on first save (add_option).
add_action( 'add_option_summit_intake_agreement_pdf', function( $option, $new_id ) {
	summit_intake_protect_agreement_pdf( 0, $new_id );
}, 10, 2 );


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

// =============================================================================
// Microsoft Graph API — Calendar Event Enrichment
// =============================================================================

/**
 * Fetch (and cache) a Microsoft Graph API access token using client credentials.
 * Returns null if credentials are not configured or the token request fails.
 */
function summit_ms_graph_get_token() {
	$cached = get_transient( '_summit_ms_graph_token' );
	if ( $cached ) return $cached;

	$tenant = get_option( 'summit_ms_tenant_id', '' );
	$client = get_option( 'summit_ms_client_id', '' );
	$secret = get_option( 'summit_ms_client_secret', '' );
	if ( ! $tenant || ! $client || ! $secret ) return null;

	$response = wp_remote_post(
		"https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
		[
			'body' => [
				'grant_type'    => 'client_credentials',
				'client_id'     => $client,
				'client_secret' => $secret,
				'scope'         => 'https://graph.microsoft.com/.default',
			],
			'timeout' => 15,
		]
	);

	if ( is_wp_error( $response ) ) return null;

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['access_token'] ) ) {
		error_log( 'Summit MS Graph token error: ' . ( $body['error_description'] ?? wp_remote_retrieve_body( $response ) ) );
		return null;
	}

	$ttl = max( 60, (int) ( $body['expires_in'] ?? 3600 ) - 120 );
	set_transient( '_summit_ms_graph_token', $body['access_token'], $ttl );
	return $body['access_token'];
}

/**
 * Update the Microsoft Calendar event created by Amelia with the intake case name
 * and a structured description containing party and session details.
 *
 * Uses application-level Graph API access (client credentials) so no per-user
 * OAuth tokens are required — one Azure AD app covers all mailboxes in the tenant.
 *
 * @param int    $post_id              Mediation intake post ID.
 * @param string $case_name            Human-readable case label (e.g. "Smith v Jones").
 * @param string $booking_date_formatted Formatted booking date string.
 * @param array  $plaintiff_rows       Saved plaintiff ACF rows.
 * @param array  $defendant_rows       Saved defendant ACF rows.
 * @param array  $third_party_rows     Saved third-party ACF rows.
 */
function summit_ms_update_calendar_event( $post_id, $case_name, $booking_date_formatted, $plaintiff_rows, $defendant_rows, $third_party_rows ) {
	global $wpdb;

	$booking_id = get_field( 'amelia_booking_id', $post_id );
	if ( ! $booking_id ) return;

	// Fetch the Amelia appointment: Outlook event ID + provider email.
	// outlookCalendarEventId is Amelia's standard column name; fall back gracefully if absent.
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT a.outlookCalendarEventId AS event_id, u.email AS provider_email
		 FROM {$wpdb->prefix}amelia_customer_bookings cb
		 JOIN {$wpdb->prefix}amelia_appointments a ON a.id = cb.appointmentId
		 JOIN {$wpdb->prefix}amelia_users u ON u.id = a.providerId
		 WHERE cb.id = %d
		 LIMIT 1",
		$booking_id
	) );

	if ( ! $row || empty( $row->event_id ) || empty( $row->provider_email ) ) return;

	$token = summit_ms_graph_get_token();
	if ( ! $token ) return;

	// Build HTML body.
	$intake_url  = get_admin_url( null, 'post.php?post=' . $post_id . '&action=edit' );
	$format_rows = function( array $rows, string $label ) {
		if ( ! $rows ) return '';
		$lines = [ "<strong>{$label}</strong>" ];
		foreach ( $rows as $r ) {
			$line = '&bull; ' . esc_html( $r['name'] ?? '' );
			if ( ! empty( $r['counsel_name'] ) ) {
				$line .= ' — Counsel: ' . esc_html( $r['counsel_name'] );
				if ( ! empty( $r['counsel_email'] ) ) {
					$line .= ' (' . esc_html( $r['counsel_email'] ) . ')';
				}
			}
			$lines[] = $line;
		}
		return implode( '<br>', $lines );
	};

	$sections = array_filter( [
		'<strong>Case:</strong> ' . esc_html( $case_name ),
		$booking_date_formatted ? '<strong>Date:</strong> ' . esc_html( $booking_date_formatted ) : '',
		$format_rows( $plaintiff_rows, 'Plaintiffs' ),
		$format_rows( $defendant_rows, 'Defendants' ),
		$format_rows( $third_party_rows, 'Third Parties' ),
		'<a href="' . esc_url( $intake_url ) . '">View Intake &rarr;</a>',
	] );
	$html_body = implode( '<br><br>', $sections );

	$response = wp_remote_request(
		'https://graph.microsoft.com/v1.0/users/' . rawurlencode( $row->provider_email ) . '/events/' . rawurlencode( $row->event_id ),
		[
			'method'  => 'PATCH',
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'subject' => 'Mediation: ' . $case_name,
				'body'    => [ 'contentType' => 'HTML', 'content' => $html_body ],
			] ),
			'timeout' => 15,
		]
	);

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log( 'Summit MS Graph calendar update failed (' . $code . '): ' . wp_remote_retrieve_body( $response ) );
	}
}

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

	// Retrieve Amelia booking data from transient first — needed for the title date.
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

	// Build post title — use the session date so the title reflects when the
	// mediation takes place, not when the intake was submitted.
	$plaintiff_name  = sanitize_text_field( $plaintiffs[0]['name'] ?? 'Unknown' );
	$defendant_name  = sanitize_text_field( $defendants[0]['name'] ?? 'Unknown' );
	$tp_names        = array_values( array_filter( array_map(
		fn( $tp ) => sanitize_text_field( $tp['name'] ?? '' ),
		$third_parties
	) ) );
	$case_label = $plaintiff_name . ' v ' . $defendant_name;
	if ( $tp_names ) {
		$case_label .= ' — ' . implode( ', ', $tp_names );
	}
	$title_date = $booking_date ? wp_date( 'F j, Y', strtotime( $booking_date ) ) : wp_date( 'F j, Y' );
	$post_title = 'Intake — ' . $case_label . ' — ' . $title_date;

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
		update_post_meta( $post_id, '_summit_intake_booking_date_raw', $booking_date );
	}
	update_field( 'team_member', $team_member, $post_id );

	// Save plaintiffs repeater
	$plaintiff_rows = [];
	foreach ( $plaintiffs as $p ) {
		$plaintiff_rows[] = [
			'name'            => sanitize_text_field( $p['name'] ?? '' ),
			'counsel_name'    => sanitize_text_field( $p['counsel_name'] ?? '' ),
			'counsel_email'   => sanitize_email( $p['counsel_email'] ?? '' ),
			'assistant_name'  => sanitize_text_field( $p['assistant_name'] ?? '' ),
			'assistant_email' => sanitize_email( $p['assistant_email'] ?? '' ),
		];
	}
	update_field( 'plaintiffs', $plaintiff_rows, $post_id );

	// Save defendants repeater
	$defendant_rows = [];
	foreach ( $defendants as $d ) {
		$defendant_rows[] = [
			'name'            => sanitize_text_field( $d['name'] ?? '' ),
			'counsel_name'    => sanitize_text_field( $d['counsel_name'] ?? '' ),
			'counsel_email'   => sanitize_email( $d['counsel_email'] ?? '' ),
			'assistant_name'  => sanitize_text_field( $d['assistant_name'] ?? '' ),
			'assistant_email' => sanitize_email( $d['assistant_email'] ?? '' ),
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
			'name'            => sanitize_text_field( $tp['name'] ?? '' ),
			'counsel_name'    => sanitize_text_field( $tp['counsel_name'] ?? '' ),
			'counsel_email'   => sanitize_email( $tp['counsel_email'] ?? '' ),
			'assistant_name'  => sanitize_text_field( $tp['assistant_name'] ?? '' ),
			'assistant_email' => sanitize_email( $tp['assistant_email'] ?? '' ),
			'claim_file'      => $claim_id ?: '',
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

	// Collect all counsel + assistant emails/names (deduplicated, parallel arrays).
	// Assistants are merged in so the existing OttoKit loop covers them automatically.
	$counsel_email_map = []; // email => name, insertion-ordered
	foreach ( array_merge( $plaintiff_rows, $defendant_rows, $third_party_rows ) as $party ) {
		$email = $party['counsel_email'] ?? '';
		if ( $email && ! isset( $counsel_email_map[ $email ] ) ) {
			$counsel_email_map[ $email ] = $party['counsel_name'] ?: $party['name'] ?: '';
		}
		$asst_email = $party['assistant_email'] ?? '';
		if ( $asst_email && ! isset( $counsel_email_map[ $asst_email ] ) ) {
			$counsel_email_map[ $asst_email ] = $party['assistant_name'] ?: '';
		}
	}
	$all_counsel_emails = array_keys( $counsel_email_map );
	$all_counsel_names  = array_values( $counsel_email_map );

	// Format booking date in the site's local timezone
	$booking_date_formatted = '';
	if ( $booking_date ) {
		$dt                     = new DateTimeImmutable( $booking_date, wp_timezone() );
		$booking_date_formatted = wp_date( 'F j, Y \a\t g:i a', $dt->getTimestamp() );
		update_post_meta( $post_id, '_summit_intake_booking_date', $booking_date_formatted );
	}

	// Strip "Intake — " prefix to get a clean case name
	$case_name = preg_replace( '/^Intake\s*[—\-]+\s*/u', '', $post_title );

	// Build flat, human-readable party lists for the email body
	$format_party_list = function( $rows ) {
		return implode( "\n", array_map( function( $r ) {
			$line = $r['name'];
			if ( $r['counsel_name'] ) {
				$line .= ' — Counsel: ' . $r['counsel_name'];
				if ( $r['counsel_email'] ) {
					$line .= ' (' . $r['counsel_email'] . ')';
				}
			}
			return $line;
		}, $rows ) );
	};

	// Send in-house confirmation notification to all counsel (gated by settings checkbox).
	summit_intake_send_confirmation_emails( $post_id, $plaintiff_rows, $defendant_rows, $third_party_rows );

	summit_intake_dispatch_webhook( $post_id, [
		'intake_id'          => $post_id,
		'case_name'          => $case_name,
		'booking_date'       => $booking_date_formatted,
		'booking_date_raw'   => $booking_date,
		'mediator_name'      => $mediator_name,
		'all_counsel_emails' => $all_counsel_emails,
		'counsel_emails_to'  => implode( ',', $all_counsel_emails ),
		'counsel_names_to'   => implode( ',', $all_counsel_names ),
		'plaintiff_list'     => $format_party_list( $plaintiff_rows ),
		'defendant_list'     => $format_party_list( $defendant_rows ),
		'third_party_list'   => $format_party_list( $third_party_rows ),
		'plaintiffs'         => $plaintiff_rows,
		'defendants'         => $defendant_rows,
		'third_parties'      => $third_party_rows,
		'amelia_booking_id'  => $amelia_booking_id ?: null,
	] );

	// ---
	// Reminder system: agreement requests, brief requests, and cron scheduling.
	// All logic lives in inc/mediation-reminders.php.
	// ---
	$all_parties = array_merge( $plaintiff_rows, $defendant_rows, $third_party_rows );
	$days_until  = $booking_date
		? (int) ceil( ( strtotime( $booking_date ) - time() ) / DAY_IN_SECONDS )
		: 999;
	$expedited   = $days_until <= 30;

	if ( $expedited ) {
		update_post_meta( $post_id, '_summit_intake_expedited', true );
	}

	if ( $booking_date ) {
		// Schedule agreement reminder crons.
		summit_reminders_schedule_agreement( $post_id, $booking_date );

		if ( $days_until < 20 ) {
			// Expedited: brief request fires immediately (no time to wait for 20-day cron).
			foreach ( $all_parties as $party ) {
				if ( ! empty( $party['counsel_email'] ) ) {
					summit_reminders_send_email( $post_id, 'brief_request', $party['counsel_email'], $party['counsel_name'] ?? '' );
				}
				if ( ! empty( $party['assistant_email'] ) ) {
					summit_reminders_send_email( $post_id, 'brief_request', $party['assistant_email'], $party['assistant_name'] ?? '' );
				}
			}
		} else {
			// Standard: schedule brief request at 20 days out.
			summit_reminders_schedule_brief_request( $post_id, $booking_date );
		}

		// Brief reminder at 10 days (scheduled only if still in the future).
		summit_reminders_schedule_brief_reminder( $post_id, $booking_date );

		// Session reminders (two) + internal notification.
		summit_reminders_schedule_session( $post_id, $booking_date );

		// Discovery call initiation email to team.
		// If booking is within 45 days the scheduled cron would land in the past, so fire immediately.
		if ( $days_until < 45 ) {
			wp_schedule_single_event( time() + 300, 'summit_discovery_call_send', [ $post_id ] );
		} else {
			summit_reminders_schedule_discovery_call( $post_id, $booking_date );
		}
	}

	// ---
	// Counsel upload portal: generate a unique token per counsel email.
	// Tokens are stored as transients that auto-expire at the booking date.
	// ---
	$upload_url_map = [];
	$expiry = $booking_date ? ( strtotime( $booking_date ) - time() ) : 0;

	foreach ( $all_parties as $party ) {
		$email = $party['counsel_email'] ?? '';
		if ( ! $email || isset( $upload_url_map[ $email ] ) ) continue;

		$token = bin2hex( random_bytes( 16 ) );
		$url   = home_url( '/mediation-upload/' . $token . '/' );

		if ( $expiry > 0 ) {
			set_transient( 'summit_upload_' . $token, [
				'post_id'      => $post_id,
				'email'        => $email,
				'counsel_name' => $party['counsel_name'] ?? '',
				'case_name'    => $case_name,
				'booking_date' => get_post_meta( $post_id, '_summit_intake_booking_date', true ),
			], $expiry );
		}

		$upload_url_map[ $email ] = $url;

		// Assistant shares the same upload URL as their counsel.
		$asst_email = $party['assistant_email'] ?? '';
		if ( $asst_email && ! isset( $upload_url_map[ $asst_email ] ) ) {
			$upload_url_map[ $asst_email ] = $url;
		}
	}

	if ( $upload_url_map ) {
		update_post_meta( $post_id, '_summit_counsel_upload_urls', $upload_url_map );
	}

	// Notify the team so the conflict check can be initiated.
	summit_intake_send_new_intake_notification( $post_id );

	// Schedule a delayed update so Amelia has time to write outlookCalendarEventId first.
	// Amelia writes the event ID during /bookings/success/{id} which runs concurrently with
	// this AJAX handler and finishes several seconds later — 150s delay is safe margin.
	wp_schedule_single_event(
		time() + 150,
		'summit_ms_calendar_event_update',
		[ $post_id, $case_name, $booking_date_formatted, $plaintiff_rows, $defendant_rows, $third_party_rows ]
	);

	wp_send_json_success( [
		'intake_id'         => $post_id,
		'amelia_booking_id' => $amelia_booking_id ?: null,
	] );
}
add_action( 'wp_ajax_summit_intake_submit', 'summit_intake_submit' );
add_action( 'wp_ajax_nopriv_summit_intake_submit', 'summit_intake_submit' );
add_action( 'summit_ms_calendar_event_update', 'summit_ms_update_calendar_event', 10, 6 );

// =============================================================================
// Admin Meta Box — Counsel Upload Links
// =============================================================================

function summit_intake_upload_links_meta_box() {
	add_meta_box(
		'summit_intake_upload_links',
		'Counsel Upload Links',
		'summit_intake_render_upload_links_box',
		'mediation_intake',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'summit_intake_upload_links_meta_box' );

function summit_intake_render_upload_links_box( $post ) {
	$url_map = get_post_meta( $post->ID, '_summit_counsel_upload_urls', true );

	if ( empty( $url_map ) || ! is_array( $url_map ) ) {
		echo '<p style="color:#666;">No upload links generated yet. Links are created when an intake is submitted via the booking form.</p>';
		return;
	}

	$parties = array_merge(
		get_field( 'plaintiffs', $post->ID ) ?: [],
		get_field( 'defendants', $post->ID ) ?: [],
		get_field( 'third_parties', $post->ID ) ?: []
	);
	?>
	<table class="widefat striped" style="margin-top:4px;">
		<thead>
			<tr>
				<th>Name</th>
				<th>Email</th>
				<th>Upload URL</th>
				<th style="width:80px;"></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $parties as $party ) :
			$counsel_email = $party['counsel_email'] ?? '';
			if ( ! $counsel_email || ! isset( $url_map[ $counsel_email ] ) ) continue;

			$asst_email    = $party['assistant_email'] ?? '';
			$display_name  = $party['counsel_name'] ?? '';
			$display_email = $counsel_email;

			if ( $asst_email ) {
				$asst_name    = $party['assistant_name'] ?? '';
				$display_name  .= ( $display_name && $asst_name ) ? ', ' . $asst_name : $asst_name;
				$display_email .= ', ' . $asst_email;
			}

			$url = $url_map[ $counsel_email ];
		?>
			<tr>
				<td><?php echo esc_html( $display_name ?: '—' ); ?></td>
				<td><?php echo esc_html( $display_email ); ?></td>
				<td style="font-family:monospace;font-size:12px;word-break:break-all;"><?php echo esc_html( $url ); ?></td>
				<td>
					<button type="button" class="button summit-copy-upload-url"
						data-url="<?php echo esc_attr( $url ); ?>">Copy</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<script>
	(function(){
		document.querySelectorAll('.summit-copy-upload-url').forEach(function(btn){
			btn.addEventListener('click', function(){
				var url = this.getAttribute('data-url');
				navigator.clipboard.writeText(url).then(function(){
					btn.textContent = 'Copied!';
					setTimeout(function(){ btn.textContent = 'Copy'; }, 2000);
				});
			});
		});
	})();
	</script>
	<?php
}

// =============================================================================
// Counsel Upload URLs — ACF Save Sync
// =============================================================================

/**
 * Keep _summit_counsel_upload_urls in sync when party data is edited in WP admin.
 *
 * One URL per party row — counsel and their assistant always share the same link.
 * The counsel email is the anchor: if it already has a URL, the assistant is mapped
 * to it. If it's new, a token is generated and both are mapped to it.
 *
 * Also prunes entries for emails that no longer appear in any party row.
 *
 * Fires after ACF has committed its data (priority 20).
 */
function summit_intake_sync_upload_urls( $post_id ) {
	if ( get_post_type( $post_id ) !== 'mediation_intake' ) return;

	$parties = array_merge(
		get_field( 'plaintiffs', $post_id ) ?: [],
		get_field( 'defendants', $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);

	// Collect all current valid emails for pruning.
	$all_current_emails = [];
	foreach ( $parties as $party ) {
		$e  = sanitize_email( $party['counsel_email'] ?? '' );
		$ae = sanitize_email( $party['assistant_email'] ?? '' );
		if ( $e )  $all_current_emails[] = $e;
		if ( $ae ) $all_current_emails[] = $ae;
	}
	$all_current_emails = array_unique( $all_current_emails );

	$url_map = get_post_meta( $post_id, '_summit_counsel_upload_urls', true ) ?: [];

	// Snapshot all URLs before changes so orphaned transients can be killed after sync.
	$urls_before = array_unique( array_values( $url_map ) );

	// Remove entries for emails no longer in any party row.
	foreach ( array_keys( $url_map ) as $email ) {
		if ( ! in_array( $email, $all_current_emails, true ) ) {
			unset( $url_map[ $email ] );
		}
	}

	$booking_date_raw = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	$expiry           = $booking_date_raw ? ( strtotime( $booking_date_raw ) - time() ) : 0;
	$booking_date_fmt = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$case_name        = summit_intake_case_name( $post_id );

	// Sync per party row: counsel + assistant share one URL.
	foreach ( $parties as $party ) {
		$counsel_email = sanitize_email( $party['counsel_email'] ?? '' );
		$asst_email    = sanitize_email( $party['assistant_email'] ?? '' );

		if ( ! $counsel_email ) continue;

		if ( isset( $url_map[ $counsel_email ] ) ) {
			// Counsel already has a URL — ensure assistant is mapped to the same one.
			$party_url = $url_map[ $counsel_email ];
		} else {
			// New counsel email — generate a token for this party row.
			$token     = bin2hex( random_bytes( 16 ) );
			$party_url = home_url( '/mediation-upload/' . $token . '/' );

			if ( $expiry > 0 ) {
				set_transient( 'summit_upload_' . $token, [
					'post_id'      => $post_id,
					'email'        => $counsel_email,
					'counsel_name' => $party['counsel_name'] ?? '',
					'case_name'    => $case_name,
					'booking_date' => $booking_date_fmt,
				], $expiry );
			}
		}

		$url_map[ $counsel_email ] = $party_url;
		if ( $asst_email ) {
			$url_map[ $asst_email ] = $party_url;
		}
	}

	update_post_meta( $post_id, '_summit_counsel_upload_urls', $url_map );

	// Invalidate transients for any URLs that are no longer in the map.
	// Replaced with a tombstone so the upload portal can show a "contact us" message
	// rather than a generic expiry, since the booking date hasn't passed yet.
	$urls_after = array_unique( array_values( $url_map ) );
	foreach ( $urls_before as $old_url ) {
		if ( ! in_array( $old_url, $urls_after, true ) ) {
			if ( preg_match( '#mediation-upload/([a-f0-9]{32})/#', $old_url, $m ) ) {
				set_transient( 'summit_upload_' . $m[1], [ 'status' => 'invalidated' ], 90 * DAY_IN_SECONDS );
			}
		}
	}
}
add_action( 'acf/save_post', 'summit_intake_sync_upload_urls', 20 );

/**
 * Regenerate the intake post title when party data changes in the WP admin.
 * Preserves the original date suffix and slug.
 * Fires after ACF has committed its data (priority 25).
 *
 * @param int $post_id
 */
function summit_intake_sync_post_title( $post_id ) {
	if ( get_post_type( $post_id ) !== 'mediation_intake' ) return;

	$plaintiffs    = get_field( 'plaintiffs',    $post_id ) ?: [];
	$defendants    = get_field( 'defendants',    $post_id ) ?: [];
	$third_parties = get_field( 'third_parties', $post_id ) ?: [];

	$plaintiff_name = sanitize_text_field( $plaintiffs[0]['name'] ?? 'Unknown' );
	$defendant_name = sanitize_text_field( $defendants[0]['name'] ?? 'Unknown' );
	$tp_names       = array_values( array_filter( array_map(
		fn( $tp ) => sanitize_text_field( $tp['name'] ?? '' ),
		$third_parties
	) ) );

	$case_label = $plaintiff_name . ' v ' . $defendant_name;
	if ( $tp_names ) {
		$case_label .= ' — ' . implode( ', ', $tp_names );
	}

	// Use the session date from post meta; fall back to extracting from the
	// current title only if no booking date has been stored yet.
	$current_title = get_the_title( $post_id );
	$booking_raw   = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	if ( $booking_raw ) {
		$date_suffix = wp_date( 'F j, Y', strtotime( $booking_raw ) );
	} else {
		preg_match( '/\d{4}-\d{2}-\d{2}$/', $current_title, $m );
		$date_suffix = $m[0] ?? wp_date( 'F j, Y' );
	}

	$new_title = 'Intake — ' . $case_label . ' — ' . $date_suffix;

	if ( $new_title === $current_title ) return;

	// Update title only — explicitly pass the existing slug to prevent it changing.
	wp_update_post( [
		'ID'         => $post_id,
		'post_title' => $new_title,
		'post_name'  => get_post_field( 'post_name', $post_id ),
	] );
}
add_action( 'acf/save_post', 'summit_intake_sync_post_title', 25 );

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

	// Unschedule all pending crons for this intake before the post is gone.
	summit_reminders_unschedule_all( $post_id );

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

/**
 * Unschedule crons when an intake post is moved to trash.
 * before_delete_post only fires on permanent deletion — this covers the trash action.
 */
function summit_intake_unschedule_on_trash( $post_id ) {
	if ( get_post_type( $post_id ) !== 'mediation_intake' ) return;
	summit_reminders_unschedule_all( $post_id );
}
add_action( 'wp_trash_post', 'summit_intake_unschedule_on_trash' );

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
	$key_arg = [
		'required'          => true,
		'sanitize_callback' => 'sanitize_text_field',
	];
	$booking_id_arg = [
		'required'          => true,
		'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
		'sanitize_callback' => 'absint',
	];

	register_rest_route( 'summit/v1', '/intake-by-booking', [
		'methods'             => 'GET, POST',
		'callback'            => 'summit_intake_lookup_by_booking',
		'permission_callback' => '__return_true',
		'args'                => [
			'booking_id' => $booking_id_arg,
			'key'        => $key_arg,
		],
	] );

	register_rest_route( 'summit/v1', '/zoom-by-booking', [
		'methods'             => 'GET',
		'callback'            => 'summit_intake_zoom_by_booking',
		'permission_callback' => '__return_true',
		'args'                => [
			'booking_id' => $booking_id_arg,
			'key'        => $key_arg,
		],
	] );

	register_rest_route( 'summit/v1', '/session-ics', [
		'methods'             => 'GET',
		'callback'            => 'summit_intake_session_ics',
		'permission_callback' => '__return_true',
		'args'                => [
			'post_id' => [
				'required'          => true,
				'validate_callback' => function( $v ) { return is_numeric( $v ) && $v > 0; },
				'sanitize_callback' => 'absint',
			],
			'key' => $key_arg,
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

/**
 * Return the Zoom join URL for a given Amelia customer booking ID.
 * Called by OttoKit after a delay, once Amelia has written the Zoom meeting.
 *
 * GET /wp-json/summit/v1/zoom-by-booking?booking_id=51&key=SECRET
 */
function summit_intake_zoom_by_booking( WP_REST_Request $request ) {
	$stored_key = get_option( 'summit_intake_lookup_secret_key', '' );
	if ( ! $stored_key || ! hash_equals( $stored_key, $request->get_param( 'key' ) ) ) {
		return new WP_REST_Response( [ 'error' => 'Unauthorized.' ], 401 );
	}

	$booking_id = $request->get_param( 'booking_id' );

	global $wpdb;
	$zoom_json = $wpdb->get_var( $wpdb->prepare(
		"SELECT a.zoomMeeting
		 FROM {$wpdb->prefix}amelia_appointments a
		 JOIN {$wpdb->prefix}amelia_customer_bookings cb ON a.id = cb.appointmentId
		 WHERE cb.id = %d
		 LIMIT 1",
		$booking_id
	) );

	$zoom_join_url = '';
	if ( $zoom_json ) {
		$zoom_data     = json_decode( $zoom_json, true );
		$zoom_join_url = $zoom_data['joinUrl'] ?? '';
	}

	// Persist the Zoom URL to the matching intake post so WP can use it for reminders.
	if ( $zoom_join_url ) {
		$intake_posts = get_posts( [
			'post_type'      => 'mediation_intake',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => [ [ 'key' => 'amelia_booking_id', 'value' => $booking_id ] ],
		] );
		if ( ! empty( $intake_posts ) ) {
			update_post_meta( $intake_posts[0]->ID, '_summit_intake_zoom_url', esc_url_raw( $zoom_join_url ) );
		}
	}

	return new WP_REST_Response( [
		'zoom_join_url'     => $zoom_join_url,
		'amelia_booking_id' => $booking_id,
	], 200 );
}

/**
 * Return an iCal (.ics) file for the session so counsel can add it to their calendar.
 *
 * GET /wp-json/summit/v1/session-ics?post_id=X&key=SECRET
 */
function summit_intake_session_ics( WP_REST_Request $request ) {
	$stored_key = get_option( 'summit_intake_lookup_secret_key', '' );
	if ( ! $stored_key || ! hash_equals( $stored_key, $request->get_param( 'key' ) ) ) {
		return new WP_REST_Response( [ 'error' => 'Unauthorized.' ], 401 );
	}

	$post_id = $request->get_param( 'post_id' );
	$post    = get_post( $post_id );
	if ( ! $post || 'mediation_intake' !== $post->post_type ) {
		return new WP_REST_Response( [ 'error' => 'Not found.' ], 404 );
	}

	$booking_raw = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	if ( ! $booking_raw ) {
		return new WP_REST_Response( [ 'error' => 'No booking date.' ], 422 );
	}

	$case_name  = summit_intake_case_name( $post_id );
	$team_id    = get_field( 'team_member', $post_id );
	$mediator   = $team_id ? get_the_title( $team_id ) : '';
	$zoom_url   = get_post_meta( $post_id, '_summit_intake_zoom_url', true ) ?: '';

	$site_tz  = wp_timezone();
	$dt_obj   = new DateTimeImmutable( $booking_raw, $site_tz );
	$dt_start = gmdate( 'Ymd\THis\Z', $dt_obj->getTimestamp() );
	$dt_end   = gmdate( 'Ymd\THis\Z', $dt_obj->modify( '+3 hours' )->getTimestamp() );
	$uid      = 'summit-mediation-' . $post_id . '@summitlaw.ca';

	$description = 'Mediator: ' . $mediator;
	if ( $zoom_url ) {
		$description .= '\nZoom: ' . $zoom_url;
	}

	$ics  = "BEGIN:VCALENDAR\r\n";
	$ics .= "VERSION:2.0\r\n";
	$ics .= "PRODID:-//Summit Law LLP//Mediation Portal//EN\r\n";
	$ics .= "CALSCALE:GREGORIAN\r\n";
	$ics .= "METHOD:PUBLISH\r\n";
	$ics .= "BEGIN:VEVENT\r\n";
	$ics .= "UID:" . $uid . "\r\n";
	$ics .= "DTSTART:" . $dt_start . "\r\n";
	$ics .= "DTEND:" . $dt_end . "\r\n";
	$ics .= "SUMMARY:Mediation — " . $case_name . "\r\n";
	$ics .= "DESCRIPTION:" . $description . "\r\n";
	if ( $zoom_url ) {
		$ics .= "URL:" . $zoom_url . "\r\n";
	}
	$ics .= "END:VEVENT\r\n";
	$ics .= "END:VCALENDAR\r\n";

	header( 'Content-Type: text/calendar; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="mediation.ics"' );
	header( 'Cache-Control: no-store, no-cache' );
	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

// =============================================================================
// Helpers
// =============================================================================

/**
 * Return the clean case name for a mediation intake post.
 *
 * Strips the "Intake — " prefix and the trailing intake date (e.g. " — 2026-06-03")
 * that WordPress appends to disambiguate post titles, leaving only the party names.
 *
 * @param int $post_id
 * @return string
 */
function summit_intake_case_name( $post_id ) {
	$title = get_the_title( $post_id );
	$title = preg_replace( '/^Intake\s*[—\-]+\s*/u', '', $title );
	$title = preg_replace( '/\s*[—\-]+\s*(?:[A-Za-z]+ \d{1,2}, \d{4}|\d{4}-\d{2}-\d{2})\s*$/u', '', $title );
	return $title;
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
 * AJAX handler — regenerate the lookup secret key in place (no page reload).
 */
function summit_intake_ajax_regenerate_key() {
	check_ajax_referer( 'summit_regen_key_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Unauthorized.' ], 401 );
	}

	$new_key = wp_generate_password( 32, false );
	update_option( 'summit_intake_lookup_secret_key', $new_key );

	wp_send_json_success( [
		'key'      => $new_key,
		'url'      => rest_url( 'summit/v1/intake-by-booking' ) . '?booking_id=BOOKING_ID&key=' . rawurlencode( $new_key ),
		'zoom_url' => rest_url( 'summit/v1/zoom-by-booking' ) . '?booking_id=BOOKING_ID&key=' . rawurlencode( $new_key ),
		'nonce'    => wp_create_nonce( 'summit_regen_key_nonce' ),
	] );
}
add_action( 'wp_ajax_summit_regenerate_lookup_key', 'summit_intake_ajax_regenerate_key' );

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
		const keyInput      = document.getElementById('summit-lookup-key');
		const revealBtn     = document.getElementById('summit-reveal-key');
		const copyKeyBtn    = document.getElementById('summit-copy-key');
		const regenBtn      = document.getElementById('summit-regen-key');
		const urlInput      = document.getElementById('summit-lookup-url');
		const copyUrlBtn    = document.getElementById('summit-copy-url');
		const zoomUrlInput  = document.getElementById('summit-zoom-url');
		const copyZoomBtn   = document.getElementById('summit-copy-zoom-url');

		let revealed = false;

		// Reveal / Hide — toggles key field and both URL displays together
		if (keyInput && revealBtn) {
			revealBtn.addEventListener('click', function () {
				revealed = !revealed;
				keyInput.type         = revealed ? 'text' : 'password';
				revealBtn.textContent = revealed ? 'Hide' : 'Reveal';
				if (urlInput) {
					urlInput.value = revealed
						? urlInput.dataset.realUrl
						: urlInput.dataset.redactedUrl;
				}
				if (zoomUrlInput) {
					zoomUrlInput.value = revealed
						? zoomUrlInput.dataset.realUrl
						: zoomUrlInput.dataset.redactedUrl;
				}
			});
		}

		// Copy key — reads from the actual input value
		if (copyKeyBtn && keyInput) {
			copyKeyBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(keyInput.value).then(function () {
					copyKeyBtn.textContent = 'Copied!';
					setTimeout(function () { copyKeyBtn.textContent = 'Copy'; }, 2000);
				});
			});
		}

		// Copy URL — always reads the real URL from the data attribute, never the redacted display
		if (copyUrlBtn && urlInput) {
			copyUrlBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(urlInput.dataset.realUrl || urlInput.value).then(function () {
					copyUrlBtn.textContent = 'Copied!';
					setTimeout(function () { copyUrlBtn.textContent = 'Copy'; }, 2000);
				});
			});
		}

		// Copy Zoom URL
		if (copyZoomBtn && zoomUrlInput) {
			copyZoomBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(zoomUrlInput.dataset.realUrl || zoomUrlInput.value).then(function () {
					copyZoomBtn.textContent = 'Copied!';
					setTimeout(function () { copyZoomBtn.textContent = 'Copy'; }, 2000);
				});
			});
		}

		// Regenerate — AJAX, updates both fields in place without a page reload
		if (regenBtn) {
			regenBtn.addEventListener('click', function () {
				if (!confirm('Regenerate the secret key?\n\nThe OttoKit lookup URL will stop working until you update it there.')) {
					return;
				}
				regenBtn.textContent = 'Regenerating\u2026';
				regenBtn.disabled    = true;

				fetch(ajaxurl, {
					method:  'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body:    new URLSearchParams({
						action: 'summit_regenerate_lookup_key',
						nonce:  regenBtn.dataset.nonce,
					}),
				})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.success) { return; }
					const newKey      = data.data.key;
					const newUrl      = data.data.url;
					const newZoomUrl  = data.data.zoom_url;
					const redacted    = urlInput ? urlInput.dataset.redactedUrl.replace(/•+/, '••••••••') : '';
					const redactedZoom = zoomUrlInput ? zoomUrlInput.dataset.redactedUrl.replace(/•+/, '••••••••') : '';

					// Update key field
					if (keyInput) { keyInput.value = newKey; }

					// Update lookup URL field
					if (urlInput) {
						urlInput.dataset.realUrl = newUrl;
						urlInput.value = revealed ? newUrl : redacted;
					}

					// Update Zoom URL field
					if (zoomUrlInput) {
						zoomUrlInput.dataset.realUrl = newZoomUrl;
						zoomUrlInput.value = revealed ? newZoomUrl : redactedZoom;
					}

					// Refresh nonce for the next regeneration
					regenBtn.dataset.nonce = data.data.nonce;
				})
				.finally(function () {
					regenBtn.textContent = 'Regenerate';
					regenBtn.disabled    = false;
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
 * Register the Settings sub-page under Mediation Intakes.
 */
function summit_intake_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=mediation_intake',
		'Settings',
		'Settings',
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
	register_setting( 'summit_intake_general_settings', 'summit_intake_download_roles', [
		'type'              => 'array',
		'sanitize_callback' => 'summit_intake_sanitize_roles',
		'default'           => [ 'administrator' ],
	] );

	register_setting( 'summit_intake_general_settings', 'summit_intake_allow_manual_create', [
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	] );

	register_setting( 'summit_intake_integrations_settings', 'summit_intake_ottokit_webhook_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	] );

	// Microsoft Graph API (calendar event enrichment).
	register_setting( 'summit_intake_integrations_settings', 'summit_ms_tenant_id', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
	register_setting( 'summit_intake_integrations_settings', 'summit_ms_client_id', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
	register_setting( 'summit_intake_integrations_settings', 'summit_ms_client_secret', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
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

	register_setting( 'summit_intake_general_settings', 'summit_intake_notification_email', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_email',
		'default'           => 'info@summitlaw.ca',
	] );

	register_setting( 'summit_intake_general_settings', 'summit_intake_notification_email_list', [
		'type'              => 'array',
		'sanitize_callback' => 'summit_intake_sanitize_email_list',
		'default'           => [ 'info@summitlaw.ca' ],
	] );

	add_settings_field(
		'summit_intake_notification_email',
		'Document Upload Notification Email',
		'summit_intake_render_notification_email_field',
		'summit-intake-settings',
		'summit_intake_notifications_section'
	);

	// Confirmation notification settings (in-house, replaces OttoKit email step when enabled).
	register_setting( 'summit_intake_general_settings', 'summit_intake_confirmation_enabled', [
		'type'              => 'boolean',
		'sanitize_callback' => 'rest_sanitize_boolean',
		'default'           => false,
	] );
	register_setting( 'summit_intake_general_settings', 'summit_intake_confirmation_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Mediation Intake Received — {case_name}',
	] );
	register_setting( 'summit_intake_general_settings', 'summit_intake_confirmation_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => '',
	] );

	// New intake notification settings.
	register_setting( 'summit_intake_general_settings', 'summit_intake_new_intake_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'New Mediation Intake — {case_name}',
	] );
	register_setting( 'summit_intake_general_settings', 'summit_intake_new_intake_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => '',
	] );

	// Agreement email settings.
	register_setting( 'summit_intake_agreement_settings', 'summit_intake_agreement_pdf', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	] );

	register_setting( 'summit_intake_agreement_settings', 'summit_intake_agreement_email_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Mediation Agreement — {case_name}',
	] );

	register_setting( 'summit_intake_agreement_settings', 'summit_intake_agreement_email_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nPlease find attached the Mediation Agreement for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nPlease sign and return the agreement using your secure upload link:\n{upload_url}\n\nThank you,\nSummit Law LLP",
	] );

	add_settings_section(
		'summit_intake_agreement_section',
		'Mediation Agreement Email',
		function () {
			echo '<p>Configure the PDF template and email sent to all unsigned parties when an admin clicks <strong>Send Agreement</strong> on an intake.</p>';
		},
		'summit-intake-settings'
	);

	add_settings_field(
		'summit_intake_agreement_pdf',
		'Agreement PDF Template',
		'summit_intake_render_agreement_pdf_field',
		'summit-intake-settings',
		'summit_intake_agreement_section'
	);

	add_settings_field(
		'summit_intake_agreement_email_subject',
		'Email Subject',
		'summit_intake_render_agreement_subject_field',
		'summit-intake-settings',
		'summit_intake_agreement_section'
	);

	add_settings_field(
		'summit_intake_agreement_email_body',
		'Email Body',
		'summit_intake_render_agreement_body_field',
		'summit-intake-settings',
		'summit_intake_agreement_section'
	);

	// Cancellation notification settings.
	register_setting( 'summit_intake_general_settings', 'summit_intake_cancellation_subject', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'Mediation Session Cancelled — {case_name}',
	] );

	register_setting( 'summit_intake_general_settings', 'summit_intake_cancellation_body', [
		'type'              => 'string',
		'sanitize_callback' => 'wp_kses_post',
		'default'           => "Dear {counsel_name},\n\nWe are writing to let you know that the mediation session for {case_name}, which was scheduled for {booking_date} with {mediator_name}, has been cancelled.\n\nPlease refer to the mediation agreement for your obligation to cancellation fees. We will forward any invoices shortly.\n\nPlease contact us directly to reschedule or if you have any questions.\n\nThank you,\nSummit Law LLP",
	] );
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
	echo '<p class="description">Provided by OttoKit when you create a "Catch Webhook" trigger step.</p>';
}

/**
 * Render the lookup secret key field — read-only with reveal, copy, and regenerate controls.
 * Shows both the cancellation lookup URL and the Zoom URL endpoint.
 */
function summit_intake_render_lookup_secret_field() {
	$key              = get_option( 'summit_intake_lookup_secret_key', '' );
	$real_url         = $key
		? rest_url( 'summit/v1/intake-by-booking' ) . '?booking_id=BOOKING_ID&key=' . rawurlencode( $key )
		: '';
	$redacted_url     = $key
		? rest_url( 'summit/v1/intake-by-booking' ) . '?booking_id=BOOKING_ID&key=••••••••'
		: '';
	$real_zoom_url    = $key
		? rest_url( 'summit/v1/zoom-by-booking' ) . '?booking_id=BOOKING_ID&key=' . rawurlencode( $key )
		: '';
	$redacted_zoom_url = $key
		? rest_url( 'summit/v1/zoom-by-booking' ) . '?booking_id=BOOKING_ID&key=••••••••'
		: '';
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
		<button
			type="button"
			id="summit-regen-key"
			class="button"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'summit_regen_key_nonce' ) ); ?>"
		>Regenerate</button>
	</div>
	<p class="description">Auto-generated. Used to authenticate OttoKit's requests to the lookup endpoint. Keep this private.</p>

	<div style="margin-top:14px;">
		<strong style="display:block;margin-bottom:6px;">OttoKit Lookup URL</strong>
		<div style="display:flex;align-items:center;gap:8px;">
			<input
				type="text"
				id="summit-lookup-url"
				value="<?php echo esc_attr( $redacted_url ); ?>"
				data-real-url="<?php echo esc_attr( $real_url ); ?>"
				data-redacted-url="<?php echo esc_attr( $redacted_url ); ?>"
				readonly
				style="font-family:monospace;width:100%;max-width:580px;"
			>
			<button type="button" id="summit-copy-url" class="button">Copy</button>
		</div>
		<p class="description">Replace <code>BOOKING_ID</code> with the booking ID variable in OttoKit. Use the Copy button — the key is hidden in the display.</p>
	</div>

	<div style="margin-top:14px;">
		<strong style="display:block;margin-bottom:6px;">OttoKit Zoom URL</strong>
		<div style="display:flex;align-items:center;gap:8px;">
			<input
				type="text"
				id="summit-zoom-url"
				value="<?php echo esc_attr( $redacted_zoom_url ); ?>"
				data-real-url="<?php echo esc_attr( $real_zoom_url ); ?>"
				data-redacted-url="<?php echo esc_attr( $redacted_zoom_url ); ?>"
				readonly
				style="font-family:monospace;width:100%;max-width:580px;"
			>
			<button type="button" id="summit-copy-zoom-url" class="button">Copy</button>
		</div>
		<p class="description">Used by OttoKit to fetch the Zoom join URL after a delay. Replace <code>BOOKING_ID</code> with the booking ID variable.</p>
	</div>
	<?php
}
/**
 * Sanitize the team email list option — deduplicate and validate each address.
 */
function summit_intake_sanitize_email_list( $raw ) {
	if ( ! is_array( $raw ) ) {
		return [ 'info@summitlaw.ca' ];
	}
	$clean = [];
	foreach ( $raw as $email ) {
		$sanitized = sanitize_email( $email );
		if ( $sanitized && is_email( $sanitized ) ) {
			$clean[] = $sanitized;
		}
	}
	$clean = array_values( array_unique( $clean ) );
	return $clean ?: [ 'info@summitlaw.ca' ];
}

/**
 * Render the team notification email manager.
 * Shows a radio-selectable list of emails with add/remove controls.
 */
function summit_intake_render_notification_email_field() {
	$list   = get_option( 'summit_intake_notification_email_list', [ 'info@summitlaw.ca' ] );
	$active = get_option( 'summit_intake_notification_email', 'info@summitlaw.ca' );

	if ( ! is_array( $list ) || empty( $list ) ) {
		$list = [ 'info@summitlaw.ca' ];
	}
	if ( ! in_array( $active, $list, true ) ) {
		$active = $list[0];
	}
	?>
	<div id="summit-email-list-manager" style="max-width:420px;">
		<div id="summit-email-rows">
			<?php foreach ( $list as $email ) : ?>
			<div class="summit-email-row" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
				<label style="display:flex;align-items:center;gap:8px;flex:1;cursor:pointer;">
					<input type="radio"
						   name="summit_intake_notification_email"
						   value="<?php echo esc_attr( $email ); ?>"
						   <?php checked( $active, $email ); ?>>
					<input type="hidden"
						   name="summit_intake_notification_email_list[]"
						   value="<?php echo esc_attr( $email ); ?>">
					<span><?php echo esc_html( $email ); ?></span>
				</label>
				<button type="button" class="button summit-remove-email" style="padding:1px 8px;line-height:1.8;" aria-label="Remove">&times;</button>
			</div>
			<?php endforeach; ?>
		</div>

		<div style="display:flex;gap:8px;margin-top:10px;">
			<input type="email" id="summit-new-email-input" placeholder="Add email address…" style="flex:1;max-width:260px;" class="regular-text">
			<button type="button" id="summit-add-email" class="button">Add</button>
		</div>
		<p class="description" style="margin-top:8px;">Select one active email. All internal notifications — reminders, uploads, session alerts — go to the selected address.</p>
	</div>

	<script>
	(function () {
		var rows    = document.getElementById('summit-email-rows');
		var input   = document.getElementById('summit-new-email-input');
		var addBtn  = document.getElementById('summit-add-email');

		function bindRemove(btn) {
			btn.addEventListener('click', function () {
				var row      = this.closest('.summit-email-row');
				var wasActive = row.querySelector('input[type="radio"]').checked;
				row.remove();
				if (wasActive) {
					var first = rows.querySelector('input[type="radio"]');
					if (first) first.checked = true;
				}
			});
		}

		document.querySelectorAll('.summit-remove-email').forEach(bindRemove);

		addBtn.addEventListener('click', function () {
			var email = input.value.trim();
			if (!email || !email.includes('@')) return;

			// Check for duplicate
			var existing = rows.querySelectorAll('input[type="hidden"]');
			for (var i = 0; i < existing.length; i++) {
				if (existing[i].value === email) { input.value = ''; return; }
			}

			var row = document.createElement('div');
			row.className = 'summit-email-row';
			row.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:6px;';
			row.innerHTML =
				'<label style="display:flex;align-items:center;gap:8px;flex:1;cursor:pointer;">' +
					'<input type="radio" name="summit_intake_notification_email" value="' + email + '">' +
					'<input type="hidden" name="summit_intake_notification_email_list[]" value="' + email + '">' +
					'<span>' + email + '</span>' +
				'</label>' +
				'<button type="button" class="button summit-remove-email" style="padding:1px 8px;line-height:1.8;" aria-label="Remove">&times;</button>';

			rows.appendChild(row);
			bindRemove(row.querySelector('.summit-remove-email'));
			input.value = '';
		});

		// Allow pressing Enter in the add input
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); }
		});
	})();
	</script>
	<?php
}

/**
 * Render the Agreement PDF template upload field.
 */
function summit_intake_render_agreement_pdf_field() {
	$attachment_id = absint( get_option( 'summit_intake_agreement_pdf', 0 ) );
	$filename      = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
	?>
	<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
		<input type="hidden" name="summit_intake_agreement_pdf" id="summit_agreement_pdf_id"
			   value="<?php echo esc_attr( $attachment_id ?: '' ); ?>">
		<button type="button" class="button" id="summit-select-agreement-pdf">
			<?php echo $attachment_id ? 'Change PDF' : 'Select PDF'; ?>
		</button>
		<span id="summit-agreement-pdf-name" style="font-family:monospace;font-size:13px;color:#555;">
			<?php echo esc_html( $filename ?: 'No file selected' ); ?>
		</span>
		<?php if ( $attachment_id ) : ?>
			<button type="button" class="button-link" id="summit-clear-agreement-pdf"
					style="color:#b32d2e;">Remove</button>
		<?php endif; ?>
	</div>
	<p class="description">Upload the blank Mediation Agreement PDF. This file is attached to every agreement email. Replace it here if the template changes.</p>
	<script>
	(function () {
		var frame;
		document.getElementById('summit-select-agreement-pdf').addEventListener('click', function () {
			if ( frame ) { frame.open(); return; }
			frame = wp.media({
				title: 'Select Mediation Agreement PDF',
				button: { text: 'Use this PDF' },
				multiple: false,
				library: { type: 'application/pdf' }
			});
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				document.getElementById('summit_agreement_pdf_id').value = attachment.id;
				document.getElementById('summit-agreement-pdf-name').textContent = attachment.filename || attachment.title;
				var clearBtn = document.getElementById('summit-clear-agreement-pdf');
				if ( ! clearBtn ) {
					clearBtn = document.createElement('button');
					clearBtn.type = 'button';
					clearBtn.id = 'summit-clear-agreement-pdf';
					clearBtn.className = 'button-link';
					clearBtn.style.color = '#b32d2e';
					clearBtn.textContent = 'Remove';
					document.getElementById('summit-agreement-pdf-name').after(clearBtn);
					clearBtn.addEventListener('click', summitClearAgreementPdf);
				}
				document.getElementById('summit-select-agreement-pdf').textContent = 'Change PDF';
			});
			frame.open();
		});
		function summitClearAgreementPdf() {
			document.getElementById('summit_agreement_pdf_id').value = '';
			document.getElementById('summit-agreement-pdf-name').textContent = 'No file selected';
			document.getElementById('summit-select-agreement-pdf').textContent = 'Select PDF';
			var clearBtn = document.getElementById('summit-clear-agreement-pdf');
			if ( clearBtn ) clearBtn.remove();
		}
		var existingClear = document.getElementById('summit-clear-agreement-pdf');
		if ( existingClear ) existingClear.addEventListener('click', summitClearAgreementPdf);
	})();
	</script>
	<?php
}

/**
 * Render the agreement email subject input.
 */
function summit_intake_render_agreement_subject_field() {
	$subject = get_option( 'summit_intake_agreement_email_subject', 'Mediation Agreement — {case_name}' );
	printf(
		'<input type="text" name="summit_intake_agreement_email_subject" value="%s" class="large-text">',
		esc_attr( $subject )
	);
	echo '<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{team_email}</code></p>';
}

/**
 * Render the agreement email body textarea.
 */
function summit_intake_render_agreement_body_field() {
	$default = "Dear {counsel_name},\n\nOur conflict check is complete. We are pleased to confirm that Summit Law LLP is able to proceed with this mediation.\n\nPlease find attached the Mediation Agreement for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nPlease sign and return the agreement using your secure upload link:\n{upload_url}\n\nZoom Meeting: {zoom_url}\n\nThank you,\nSummit Law LLP";
	$body    = get_option( 'summit_intake_agreement_email_body', $default );
	printf(
		'<textarea name="summit_intake_agreement_email_body" rows="12" class="large-text" style="font-family:monospace;">%s</textarea>',
		esc_textarea( $body )
	);
	echo '<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{upload_url}</code>, <code>{zoom_url}</code>, <code>{team_email}</code>. Calendar links (Google, Outlook, iCal) are appended automatically.</p>';
}

/**
 * Render the cancellation email subject input.
 */
function summit_intake_render_cancellation_subject_field() {
	$subject = get_option( 'summit_intake_cancellation_subject', 'Mediation Session Cancelled — {case_name}' );
	printf(
		'<input type="text" name="summit_intake_cancellation_subject" value="%s" class="large-text">',
		esc_attr( $subject )
	);
	echo '<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{team_email}</code></p>';
}

/**
 * Render the cancellation email body textarea.
 */
function summit_intake_render_cancellation_body_field() {
	$default = "Dear {counsel_name},\n\nWe are writing to let you know that the mediation session for {case_name}, which was scheduled for {booking_date} with {mediator_name}, has been cancelled.\n\nPlease contact us directly to reschedule or if you have any questions.\n\nThank you,\nSummit Law LLP";
	$body    = get_option( 'summit_intake_cancellation_body', $default );
	printf(
		'<textarea name="summit_intake_cancellation_body" rows="8" class="large-text" style="font-family:monospace;">%s</textarea>',
		esc_textarea( $body )
	);
	echo '<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{team_email}</code></p>';
}

add_action( 'admin_init', 'summit_intake_register_settings' );

/**
 * Enqueue wp.media on the Mediation Intake settings page so the PDF picker works.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook === 'mediation_intake_page_summit-intake-permissions' ) {
		wp_enqueue_media();
	}
} );

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
		$counsel_email = sanitize_email( $party['counsel_email'] ?? '' );
		if ( $counsel_email ) {
			$emails[] = $counsel_email;
		}
		$asst_email = sanitize_email( $party['assistant_email'] ?? '' );
		if ( $asst_email ) {
			$emails[] = $asst_email;
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
			return '<tr><td colspan="5" style="color:#888;">None recorded</td></tr>';
		}
		$rows = '';
		foreach ( $parties as $p ) {
			$rows .= '<tr>'
				. '<td>' . esc_html( $p['name'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['counsel_name'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['counsel_email'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['assistant_name'] ?? '' ) . '</td>'
				. '<td>' . esc_html( $p['assistant_email'] ?? '' ) . '</td>'
				. '</tr>';
		}
		return $rows;
	};

	$plaintiff_rows    = $build_party_rows( $plaintiffs );
	$defendant_rows    = $build_party_rows( $defendants );
	$third_party_block = '';
	if ( ! empty( $third_parties ) ) {
		$third_party_block = '<h2>Third Parties</h2>'
			. '<table><thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th><th>Legal Assistant</th><th>Assistant Email</th></tr></thead>'
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
  <thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th><th>Legal Assistant</th><th>Assistant Email</th></tr></thead>
  <tbody>{$plaintiff_rows}</tbody>
</table>

<h2>Defendants</h2>
<table>
  <thead><tr><th>Name</th><th>Counsel</th><th>Counsel Email</th><th>Legal Assistant</th><th>Assistant Email</th></tr></thead>
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

	$btn_primary  = 'display:inline-block;padding:6px 14px;border-radius:3px;font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:1px solid transparent;background:#29472d;color:#fff;';
	$btn_accent   = 'display:inline-block;padding:6px 14px;border-radius:3px;font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:1px solid transparent;background:#d1d436;color:#292725;';
	$btn_outlined = 'display:inline-block;padding:6px 14px;border-radius:3px;font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:1px solid #b7b0ab;background:#efe8e6;color:#292725;';

	echo '<div style="margin:12px 0 0;background:#f9f9f9;border:1px solid #ddd;border-radius:3px;overflow:hidden;">';

	// Row 1 — Conflict bundle + email parties.
	echo '<div style="padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #ddd;">';
	echo '<strong style="flex-shrink:0;font-size:13px;">Actions:</strong>';

	if ( summit_intake_current_user_can_download() ) {
		$url = wp_nonce_url(
			add_query_arg(
				[ 'action' => 'summit_intake_download_bundle', 'post_id' => $post->ID ],
				admin_url( 'admin-post.php' )
			),
			'summit_intake_bundle_' . $post->ID,
			'_bundle_nonce'
		);
		echo '<a href="' . esc_url( $url ) . '" style="' . $btn_primary . '" target="_blank" rel="noopener noreferrer">&#8675; Download Conflict Bundle</a>';
	} else {
		echo '<span style="color:#666;font-size:13px;">You do not have permission to download this bundle. '
			. 'Contact your administrator to update your role under '
			. '<strong>Mediation Intakes &rarr; Permissions</strong>.</span>';
	}

	$mailto = summit_intake_mailto_url( $post->ID );
	if ( $mailto ) {
		echo '<a href="' . esc_attr( $mailto ) . '" style="' . $btn_outlined . '" target="_blank" rel="noopener noreferrer">&#9993; Email Parties</a>';
	}

	echo '</div>';

	// Row 2 — Send Agreement.
	if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_mediation_intakes' ) ) {
		$send_url = wp_nonce_url(
			add_query_arg(
				[ 'action' => 'summit_intake_send_agreement', 'post_id' => $post->ID ],
				admin_url( 'admin-post.php' )
			),
			'summit_send_agreement_' . $post->ID,
			'_send_nonce'
		);
		$sent_ts = get_post_meta( $post->ID, '_summit_agreement_sent', true );

		$tz_abbr = ( new DateTime( 'now', wp_timezone() ) )->format( 'T' );

		echo '<div style="padding:10px 16px;display:flex;align-items:center;gap:10px;">';
		echo '<span style="font-size:13px;font-weight:600;color:#444;">Mediation Agreement</span>';
		echo '<a href="' . esc_url( $send_url ) . '" style="' . $btn_accent . '"
			onclick="return confirm(\'Send the Mediation Agreement to all unsigned parties for this intake? This action will send emails immediately.\')">&#9993; Send Agreement</a>';

		if ( $sent_ts ) {
			echo '<span style="color:#666;font-size:12px;">Last sent: ' . esc_html( wp_date( 'M j, Y g:ia', (int) $sent_ts ) ) . ' ' . esc_html( $tz_abbr ) . '</span>';
		}
		echo '</div>';
	}

	// Row 3 — Zoom URL (shown once saved by zoom-by-booking endpoint).
	$zoom_url = get_post_meta( $post->ID, '_summit_intake_zoom_url', true );
	if ( $zoom_url ) {
		echo '<div style="padding:10px 16px;display:flex;align-items:center;gap:10px;border-top:1px solid #ddd;">';
		echo '<strong style="font-size:13px;flex-shrink:0;">Zoom:</strong>';
		echo '<span style="font-family:monospace;font-size:12px;color:#555;word-break:break-all;flex:1;">' . esc_html( $zoom_url ) . '</span>';
		echo '<button type="button" class="button" style="flex-shrink:0;"'
			. ' onclick="var b=this;navigator.clipboard.writeText(\'' . esc_js( $zoom_url ) . '\').then(function(){'
			. 'b.textContent=\'Copied!\';setTimeout(function(){b.textContent=\'Copy URL\'},2000)})">Copy URL</button>';
		echo '</div>';
	}

	// Admin notice from a previous send action.
	$result = isset( $_GET['summit_agreement_result'] ) ? sanitize_text_field( wp_unslash( $_GET['summit_agreement_result'] ) ) : '';
	if ( $result ) {
		if ( str_starts_with( $result, 'sent=' ) ) {
			$count = (int) substr( $result, 5 );
			$msg   = $count > 0
				? 'Mediation Agreement sent to ' . $count . ' recipient' . ( $count === 1 ? '' : 's' ) . '.'
				: 'No emails sent — all parties have already signed.';
			$type  = 'notice-success';
		} elseif ( $result === 'error=no_pdf' ) {
			$msg  = 'Error: No agreement PDF has been configured. Please upload one in Settings → Mediation Intake.';
			$type = 'notice-error';
		} elseif ( $result === 'error=file_missing' ) {
			$msg  = 'Error: The agreement PDF file could not be found on disk. Please re-upload in Settings → Mediation Intake.';
			$type = 'notice-error';
		} else {
			$msg  = 'An unknown error occurred while sending the agreement.';
			$type = 'notice-error';
		}
		echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible" style="margin:0 16px 10px;"><p>' . esc_html( $msg ) . '</p></div>';
	}

	echo '</div>';
}
add_action( 'edit_form_after_title', 'summit_intake_bundle_button' );

/**
 * Render the settings page — 4 tabbed sections.
 */
function summit_intake_render_settings_page() {
	$tabs = [
		'general'      => 'General',
		'agreement'    => 'Agreement Email',
		'reminders'    => 'Reminders',
		'integrations' => 'Integrations',
		'test'         => 'Test Send',
	];
	$active = ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) )
		? sanitize_key( $_GET['tab'] )
		: 'general';

	$page_url = admin_url( 'edit.php?post_type=mediation_intake&page=summit-intake-permissions' );
	?>
	<div class="wrap">
		<h1>Mediation Intake Settings</h1>
		<nav class="nav-tab-wrapper" style="margin-bottom:0;">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( $page_url . '&tab=' . $slug ); ?>"
				   class="nav-tab<?php echo $active === $slug ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php if ( $active === 'general' ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'summit_intake_general_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Allowed Download Roles</th>
					<td><?php summit_intake_render_roles_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Manual Intake Creation</th>
					<td><?php summit_intake_render_manual_create_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Team Notification Email</th>
					<td><?php summit_intake_render_notification_email_field(); ?></td>
				</tr>
				<tr>
					<th scope="row" style="padding-top:24px;border-top:1px solid #eee;"><strong>New Intake Notification</strong></th>
					<td style="padding-top:24px;border-top:1px solid #eee;">
						<p class="description" style="margin-top:0;">Sent to the Team Notification Email immediately when a new intake is submitted, prompting the conflict check.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_intake_new_intake_subject"
							value="<?php echo esc_attr( get_option( 'summit_intake_new_intake_subject', 'New Mediation Intake — {case_name}' ) ); ?>"
							class="large-text">
						<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{intake_url}</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_intake_new_intake_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							$default_body = "A new mediation intake has been submitted and is ready for review.\n\nCase: {case_name}\nSession Date: {booking_date}\nMediator: {mediator_name}\n\nPlease initiate the conflict check and review all party details before sending the Mediation Agreement.\n\n{intake_url}";
							echo esc_textarea( get_option( 'summit_intake_new_intake_body', $default_body ) );
						?></textarea>
						<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{intake_url}</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row" style="padding-top:24px;border-top:1px solid #eee;"><strong>Confirmation Notification</strong></th>
					<td style="padding-top:24px;border-top:1px solid #eee;">
						<label>
							<input type="checkbox" name="summit_intake_confirmation_enabled" value="1"
								<?php checked( get_option( 'summit_intake_confirmation_enabled', false ) ); ?>>
							Send in-house confirmation email to all counsel on submission
						</label>
						<p class="description" style="margin-top:6px;">When enabled, confirmation emails are sent from this system instead of OttoKit. Disable OttoKit's email step first to avoid duplicate sends. Uncheck to revert to OttoKit.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_intake_confirmation_subject"
							value="<?php echo esc_attr( get_option( 'summit_intake_confirmation_subject', 'Mediation Intake Received — {case_name}' ) ); ?>"
							class="large-text">
						<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_intake_confirmation_body" rows="14" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_intake_confirmation_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{plaintiff_list}</code>, <code>{defendant_list}</code>, <code>{third_party_section}</code>, <code>{team_email}</code>. (<code>{third_party_section}</code> renders as a labelled line only when third parties exist, empty otherwise.)</p>
					</td>
				</tr>
				<tr>
					<th scope="row" style="padding-top:24px;border-top:1px solid #eee;"><strong>Cancellation Notification</strong></th>
					<td style="padding-top:24px;border-top:1px solid #eee;">
						<p class="description" style="margin-top:0;">Sent automatically to all parties when an Amelia booking is cancelled.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td><?php summit_intake_render_cancellation_subject_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td><?php summit_intake_render_cancellation_body_field(); ?></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<?php elseif ( $active === 'agreement' ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'summit_intake_agreement_settings' ); ?>
			<p class="description" style="margin-top:16px;">Configure the PDF template and email sent to all unsigned parties when an admin clicks <strong>Send Agreement</strong> on an intake.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Agreement PDF Template</th>
					<td><?php summit_intake_render_agreement_pdf_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Email Subject</th>
					<td><?php summit_intake_render_agreement_subject_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Email Body</th>
					<td><?php summit_intake_render_agreement_body_field(); ?></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<?php elseif ( $active === 'reminders' ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'summit_reminders_settings' ); ?>
			<div class="notice notice-warning inline" style="margin:16px 0 0;">
				<p><strong>Note:</strong> Changes to reminder timing only affect intakes submitted <em>after</em> saving. Already-scheduled crons for existing intakes are not updated — they will fire at the times set when those intakes were originally submitted.</p>
			</div>
			<p class="description" style="margin-top:16px;">All reminders are sent via <code>wp_mail()</code>. Available merge tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{upload_url}</code>, <code>{days_until_booking}</code>, <code>{zoom_url}</code></p>

			<h2 class="title">Agreement Reminder</h2>
			<p class="description">Sent automatically on a schedule to unsigned parties until the agreement is received.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Send Intervals</th>
					<td><?php summit_intake_render_reminders_tab_intervals( 'summit_agreement_reminder_intervals', summit_reminders_default_agreement_intervals() ); ?></td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_agreement_reminder_subject"
							value="<?php echo esc_attr( get_option( 'summit_agreement_reminder_subject', 'Reminder: Mediation Agreement Required — {case_name}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_agreement_reminder_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_agreement_reminder_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{upload_url}</code>, <code>{days_until_booking}</code>, <code>{team_email}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Brief Request</h2>
			<p class="description">Sent at 20 days before the booking (or immediately for expedited intakes).</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_brief_request_subject"
							value="<?php echo esc_attr( get_option( 'summit_brief_request_subject', 'Mediation Brief Request — {case_name}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_brief_request_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_brief_request_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{upload_url}</code>, <code>{team_email}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Brief Reminder</h2>
			<p class="description">Sent to parties who have not yet submitted their brief.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Send Intervals</th>
					<td><?php summit_intake_render_reminders_tab_intervals( 'summit_brief_reminder_intervals', summit_reminders_default_brief_intervals() ); ?></td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_brief_reminder_subject"
							value="<?php echo esc_attr( get_option( 'summit_brief_reminder_subject', 'Reminder: Mediation Brief Due — {case_name}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_brief_reminder_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_brief_reminder_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{upload_url}</code>, <code>{days_until_booking}</code>, <code>{team_email}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Session Reminder</h2>
			<p class="description">Sent to all parties before the session. Two reminders fire — first at 7 days out, second at 1 day out (both configurable). Both use the same subject/body template. Includes Zoom URL and calendar links.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">First Reminder (days before)</th>
					<td>
						<input type="number" name="summit_session_reminder_1_days" min="1" max="90"
							value="<?php echo esc_attr( absint( get_option( 'summit_session_reminder_1_days', 7 ) ) ); ?>"
							style="width:80px;">
					</td>
				</tr>
				<tr>
					<th scope="row">Second Reminder (days before)</th>
					<td>
						<input type="number" name="summit_session_reminder_2_days" min="1" max="90"
							value="<?php echo esc_attr( absint( get_option( 'summit_session_reminder_2_days', 1 ) ) ); ?>"
							style="width:80px;">
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_session_reminder_subject"
							value="<?php echo esc_attr( get_option( 'summit_session_reminder_subject', 'Upcoming Mediation — {case_name} — {booking_date}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_session_reminder_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_session_reminder_body', '' ) );
						?></textarea>
						<p class="description">Calendar links (Google, Outlook, iCal) are appended automatically when a Zoom URL is available. Available tags: <code>{counsel_name}</code>, <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{zoom_url}</code>, <code>{team_email}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Session Internal Notification</h2>
			<p class="description">Sent to the Team Notification Email and the mediator whenever a session reminder fires. Separate from the party-facing reminder above.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_session_internal_subject"
							value="<?php echo esc_attr( get_option( 'summit_session_internal_subject', 'Upcoming Session — {case_name} — {booking_date}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_session_internal_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_session_internal_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{zoom_url}</code>, <code>{intake_url}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Discovery Call Initiation</h2>
			<p class="description">Sent to the Team Notification Email to prompt discovery call scheduling with all parties.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Days Before Session</th>
					<td>
						<input type="number" name="summit_discovery_call_days" min="1" max="180"
							value="<?php echo esc_attr( absint( get_option( 'summit_discovery_call_days', 45 ) ) ); ?>"
							style="width:80px;">
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_discovery_call_subject"
							value="<?php echo esc_attr( get_option( 'summit_discovery_call_subject', 'Discovery Calls Due — {case_name}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_discovery_call_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_discovery_call_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{mediator_name}</code>, <code>{intake_url}</code></p>
					</td>
				</tr>
			</table>

			<h2 class="title">Team Reminder Notification</h2>
			<p class="description">Sent to the Team Notification Email after Agreement Reminder, Brief Request, and Brief Reminder batches go out to parties. Fires a configurable number of days later so the team can follow up.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Notify Offset (days after)</th>
					<td>
						<input type="number" name="summit_team_notification_offset_days" min="0" max="7"
							value="<?php echo esc_attr( absint( get_option( 'summit_team_notification_offset_days', 1 ) ) ); ?>"
							style="width:80px;">
						<p class="description">0 = notify immediately when the reminder fires.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Subject</th>
					<td>
						<input type="text" name="summit_team_notification_subject"
							value="<?php echo esc_attr( get_option( 'summit_team_notification_subject', 'Reminder Follow-up — {case_name} — {reminder_type}' ) ); ?>"
							class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row">Body</th>
					<td>
						<textarea name="summit_team_notification_body" rows="8" class="large-text" style="font-family:monospace;"><?php
							echo esc_textarea( get_option( 'summit_team_notification_body', '' ) );
						?></textarea>
						<p class="description">Available tags: <code>{case_name}</code>, <code>{booking_date}</code>, <code>{reminder_type}</code>, <code>{party_count}</code>, <code>{intake_url}</code></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<?php elseif ( $active === 'integrations' ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'summit_intake_integrations_settings' ); ?>
			<p class="description" style="margin-top:16px;">OttoKit is used only for Workflow 1 (Confirmation Notification). The lookup endpoints below are called by OttoKit to retrieve intake data and Zoom URLs.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">OttoKit Webhook URL</th>
					<td><?php summit_intake_render_webhook_url_field(); ?></td>
				</tr>
				<tr>
					<th scope="row">Lookup Secret Key</th>
					<td><?php summit_intake_render_lookup_secret_field(); ?></td>
				</tr>
			</table>

			<h2 class="title" style="margin-top:2em;">Microsoft Calendar Integration</h2>
			<p class="description">When a new intake is submitted, the Amelia-created Microsoft Calendar event is automatically updated with the case name and party details. Requires an Azure AD app with <code>Calendars.ReadWrite</code> application permission and admin consent granted for the whole tenant.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Tenant ID</th>
					<td>
						<input type="text" name="summit_ms_tenant_id"
							value="<?php echo esc_attr( get_option( 'summit_ms_tenant_id', '' ) ); ?>"
							class="regular-text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
						<p class="description">Azure Portal → Azure Active Directory → Overview → Directory (tenant) ID.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Application (Client) ID</th>
					<td>
						<input type="text" name="summit_ms_client_id"
							value="<?php echo esc_attr( get_option( 'summit_ms_client_id', '' ) ); ?>"
							class="regular-text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
						<p class="description">Azure Portal → App registrations → your app → Overview → Application (client) ID.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Client Secret Value</th>
					<td>
						<input type="password" name="summit_ms_client_secret"
							value="<?php echo esc_attr( get_option( 'summit_ms_client_secret', '' ) ); ?>"
							class="regular-text" autocomplete="new-password">
						<p class="description">Azure Portal → App registrations → your app → Certificates &amp; secrets. Copy the value immediately — it is only shown once.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php elseif ( $active === 'test' ) : ?>
		<?php
		if ( isset( $_GET['test_sent'] ) ) {
			if ( $_GET['test_sent'] === '1' ) {
				echo '<div class="notice notice-success inline" style="margin:16px 0 0;"><p>Test email sent successfully.</p></div>';
			} else {
				echo '<div class="notice notice-error inline" style="margin:16px 0 0;"><p>Failed to send: ' . esc_html( urldecode( $_GET['test_sent'] ) ) . '</p></div>';
			}
			// Remove test_sent from the URL so the message doesn't reappear on refresh.
			echo '<script>history.replaceState(null,"",location.pathname+location.search.replace(/[?&]test_sent=[^&]*/,"").replace(/^&/,"?"));</script>';
		}
		$saved_type = get_option( 'summit_intake_test_type', 'confirmation' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
			<?php wp_nonce_field( 'summit_test_send_email', 'summit_test_send_nonce' ); ?>
			<input type="hidden" name="action" value="summit_test_send_email">
			<p class="description">Sends the saved subject and body for the selected email type to a test address. Merge tags are left as literal text (e.g. <code>{case_name}</code>) so you can check formatting and layout without needing a real intake.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Email Type</th>
					<td>
						<?php
						$test_types = [
							'confirmation'      => 'Confirmation Notification',
							'agreement_reminder'=> 'Agreement Reminder',
							'brief_request'     => 'Brief Request',
							'brief_reminder'    => 'Brief Reminder',
							'session_reminder'  => 'Session Reminder',
							'agreement_email'   => 'Agreement Email',
							'cancellation'      => 'Cancellation Notification',
							'new_intake'        => 'New Intake Notification',
							'discovery_call'    => 'Discovery Call Initiation',
							'session_internal'  => 'Session Internal Notification',
							'team_notification' => 'Team Reminder Notification',
							'upload_notification' => 'Upload Notification',
						];
						$party_types    = array_slice( $test_types, 0, 7, true );
						$internal_types = array_slice( $test_types, 7, null, true );
						?>
						<select name="summit_test_type" style="min-width:260px;">
							<optgroup label="Party Emails">
								<?php foreach ( $party_types as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $saved_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</optgroup>
							<optgroup label="Internal / Team Emails">
								<?php foreach ( $internal_types as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $saved_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="summit_test_email">Send To</label></th>
					<td>
						<input type="email" id="summit_test_email" name="summit_test_email"
							value="<?php echo esc_attr( get_option( 'summit_intake_test_email', '' ) ); ?>"
							class="regular-text" placeholder="test@example.com">
						<p class="description">This address is saved automatically for future test sends.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Send Test Email' ); ?>
		</form>

		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render an interval table for the Reminders tab.
 * Used for agreement_reminder and brief_reminder interval schedules.
 */
function summit_intake_render_reminders_tab_intervals( $option_name, $defaults ) {
	$intervals = get_option( $option_name, $defaults );
	if ( ! is_array( $intervals ) || empty( $intervals ) ) {
		$intervals = $defaults;
	}
	echo '<table style="border-collapse:collapse;">';
	echo '<thead><tr><th style="padding:4px 12px 4px 0;text-align:left;">Days Before</th><th style="padding:4px 12px 4px 0;text-align:left;">Enabled</th></tr></thead><tbody>';
	foreach ( $intervals as $i => $row ) {
		$days    = absint( $row['days'] );
		$enabled = ! empty( $row['enabled'] );
		printf(
			'<tr>
				<td style="padding:4px 12px 4px 0;">
					<input type="number" name="%s[%d][days]" value="%d" min="1" max="365" style="width:70px;">
				</td>
				<td style="padding:4px 12px 4px 0;">
					<input type="checkbox" name="%s[%d][enabled]" value="1" %s>
				</td>
			</tr>',
			esc_attr( $option_name ), $i, $days,
			esc_attr( $option_name ), $i, checked( $enabled, true, false )
		);
	}
	echo '</tbody></table>';
	echo '<p class="description" style="margin-top:8px;">Edit days or uncheck to disable individual reminders. Add rows by duplicating with browser dev tools or contact your developer.</p>';
}

// =============================================================================
// Shared Email Wrapper
// =============================================================================

/**
 * Wrap HTML email content in a branded Summit Law LLP email template.
 * Used by all party-facing emails. Inline styles only — email-client safe.
 *
 * @param string $content The body HTML to embed (already nl2br'd if needed).
 *                        Spurious <br> tags after block-level closing tags are stripped
 *                        automatically so headings and paragraphs don't get double-spaced.
 * @return string Full HTML email document.
 */
function summit_intake_email_wrap( $content ) {
	// Remove <br> tags that nl2br() inserts after block-level closing tags.
	// Without this, headings and paragraphs get double-spaced (tag margin + <br>).
	$content = preg_replace( '#</(h[1-6]|p|ul|ol|li|blockquote)>\s*<br\s*/?>(\s*)#i', '</$1>$2', $content );

	// Fetch the site logo set in Appearance → Customize → Site Identity.
	$logo_url = '';
	$logo_id  = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_src = wp_get_attachment_image_src( $logo_id, 'full' );
		$logo_url = $logo_src ? esc_url( $logo_src[0] ) : '';
	}
	$logo_html = $logo_url
		? '<td style="padding-right:20px;vertical-align:middle;width:60px;"><img src="' . $logo_url . '" alt="Summit Law LLP" width="96" style="display:block;width:96px;height:auto;border:0;"></td>'
		: '';

	return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f7f3f2;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f7f3f2" style="background-color:#f7f3f2;">
  <tr>
    <td align="center" style="padding:32px 16px;">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">
        <tr>
          <td bgcolor="#29472d" style="background-color:#29472d;padding:28px 40px;border-radius:4px 4px 0 0;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                ' . $logo_html . '
                <td style="vertical-align:middle;">
                  <p style="margin:0;font-family:Georgia,\'Times New Roman\',serif;font-size:22px;font-weight:400;color:#ffffff;letter-spacing:0.02em;">Summit Law LLP</p>
                  <p style="margin:5px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#a8c4ab;letter-spacing:0.12em;text-transform:uppercase;">Mediation Services</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td bgcolor="#ffffff" style="background-color:#ffffff;padding:40px;border-left:1px solid #efe8e6;border-right:1px solid #efe8e6;">
            <div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#292725;">
              ' . $content . '
            </div>
          </td>
        </tr>
        <tr>
          <td bgcolor="#f7f3f2" style="background-color:#f7f3f2;padding:24px 40px;border:1px solid #efe8e6;border-top:3px solid #29472d;border-radius:0 0 4px 4px;">
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#b7b0ab;line-height:1.7;">
              Summit Law LLP &nbsp;&middot;&nbsp; Ottawa, Ontario<br>
              <a href="mailto:info@summitlaw.ca" style="color:#b7b0ab;text-decoration:none;">info@summitlaw.ca</a> &nbsp;&middot;&nbsp; (613) 749-4700
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>';
}

// =============================================================================
// Mediation Agreement — Send via wp_mail()
// =============================================================================

/**
 * Admin-post handler for the Send Agreement button.
 *
 * Validates auth/nonce, delegates to summit_intake_send_agreement_emails(),
 * then redirects back to the intake edit screen with a result query arg.
 */
function summit_intake_handle_send_agreement() {
	$post_id = absint( $_GET['post_id'] ?? 0 );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'You do not have permission to perform this action.' );
	}

	check_admin_referer( 'summit_send_agreement_' . $post_id, '_send_nonce' );

	if ( get_post_type( $post_id ) !== 'mediation_intake' ) {
		wp_die( 'Invalid post type.' );
	}

	$result   = summit_intake_send_agreement_emails( $post_id );
	$redirect = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( 'summit_agreement_result', 'error=' . $result->get_error_code(), $redirect ) );
	} else {
		if ( $result > 0 ) {
			update_post_meta( $post_id, '_summit_agreement_sent', time() );
		}
		wp_safe_redirect( add_query_arg( 'summit_agreement_result', 'sent=' . $result, $redirect ) );
	}
	exit;
}
add_action( 'admin_post_summit_intake_send_agreement', 'summit_intake_handle_send_agreement' );

/**
 * Admin-post handler for the Test Send tab.
 * Sends the saved subject/body for the selected email type to a test address.
 * Merge tags are left unreplaced so layout and formatting can be checked without real intake data.
 */
function summit_intake_handle_test_send() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized.' );
	}

	check_admin_referer( 'summit_test_send_email', 'summit_test_send_nonce' );

	$page_url   = admin_url( 'edit.php?post_type=mediation_intake&page=summit-intake-permissions&tab=test' );
	$test_email = sanitize_email( $_POST['summit_test_email'] ?? '' );
	$type       = sanitize_key( $_POST['summit_test_type'] ?? '' );

	if ( ! $test_email ) {
		wp_redirect( add_query_arg( 'test_sent', rawurlencode( 'No email address provided.' ), $page_url ) );
		exit;
	}

	// Save for next time.
	update_option( 'summit_intake_test_email', $test_email );
	update_option( 'summit_intake_test_type', $type );

	$type_map = [
		'confirmation'       => [ 'summit_intake_confirmation_subject',    'summit_intake_confirmation_body'    ],
		'agreement_reminder' => [ 'summit_agreement_reminder_subject',     'summit_agreement_reminder_body'     ],
		'brief_request'      => [ 'summit_brief_request_subject',          'summit_brief_request_body'          ],
		'brief_reminder'     => [ 'summit_brief_reminder_subject',         'summit_brief_reminder_body'         ],
		'session_reminder'   => [ 'summit_session_reminder_subject',       'summit_session_reminder_body'       ],
		'agreement_email'    => [ 'summit_intake_agreement_email_subject', 'summit_intake_agreement_email_body' ],
		'new_intake'         => [ 'summit_intake_new_intake_subject',      'summit_intake_new_intake_body'      ],
		'cancellation'       => [ 'summit_intake_cancellation_subject',    'summit_intake_cancellation_body'    ],
		'discovery_call'     => [ 'summit_discovery_call_subject',         'summit_discovery_call_body'         ],
		'session_internal'   => [ 'summit_session_internal_subject',       'summit_session_internal_body'       ],
		'team_notification'  => [ 'summit_team_notification_subject',      'summit_team_notification_body'      ],
	];

	// Hardcoded email types — not stored in options, sent directly with dummy values.
	if ( $type === 'upload_notification' ) {
		$subject = '[TEST] Mediation Agreement Uploaded — {case_name} - Summit Law LLP';
		$body    = '<b>{counsel_name}</b> (counsel@example.com) has uploaded their <b>Mediation Agreement</b> for <b>{case_name}</b>.'
			. "\n\nBooking Date: {booking_date}"
			. "\n\nPlease log in to review the submission and update the relevant status field on the intake record."
			. "\n\n<a href=\"#\" style=\"color:#29472d;font-weight:600;\">View Intake Record</a>";
		$sent = wp_mail(
			$test_email,
			$subject,
			summit_intake_email_wrap( nl2br( $body ) ),
			[
				'Content-Type: text/html; charset=UTF-8',
				'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
			]
		);
		wp_redirect( add_query_arg( 'test_sent', $sent ? '1' : rawurlencode( 'wp_mail() returned false — check your mail configuration.' ), $page_url ) );
		exit;
	}

	if ( ! isset( $type_map[ $type ] ) ) {
		wp_redirect( add_query_arg( 'test_sent', rawurlencode( 'Invalid email type.' ), $page_url ) );
		exit;
	}

	[ $subject_key, $body_key ] = $type_map[ $type ];

	// Fall back to the registered setting default when nothing has been explicitly saved yet.
	$registered = get_registered_settings();
	$subject     = get_option( $subject_key ) ?: ( $registered[ $subject_key ]['default'] ?? '' );
	$body        = get_option( $body_key )    ?: ( $registered[ $body_key ]['default']    ?? '' );

	if ( ! $subject && ! $body ) {
		wp_redirect( add_query_arg( 'test_sent', rawurlencode( 'No subject or body saved for this type yet.' ), $page_url ) );
		exit;
	}
	if ( ! $subject ) {
		wp_redirect( add_query_arg( 'test_sent', rawurlencode( 'Subject is empty — check Settings → Reminders.' ), $page_url ) );
		exit;
	}
	if ( ! $body ) {
		wp_redirect( add_query_arg( 'test_sent', rawurlencode( 'Body is empty — check Settings → Reminders.' ), $page_url ) );
		exit;
	}

	$sent = wp_mail(
		$test_email,
		'[TEST] ' . $subject,
		summit_intake_email_wrap( nl2br( $body ) ),
		[
			'Content-Type: text/html; charset=UTF-8',
			'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
		]
	);

	wp_redirect( add_query_arg( 'test_sent', $sent ? '1' : rawurlencode( 'wp_mail() returned false — check your mail configuration.' ), $page_url ) );
	exit;
}
add_action( 'admin_post_summit_test_send_email', 'summit_intake_handle_test_send' );

/**
 * Send the Mediation Agreement PDF to all unsigned parties for a given intake.
 *
 * Skips any party whose mediation_agreement_signed ACF toggle is true.
 * Sends individually to counsel and, if present, their assistant.
 * Both receive the same upload URL (shared per party row).
 *
 * @param int $post_id Mediation intake post ID.
 * @return int|WP_Error Number of emails sent, or WP_Error on configuration failure.
 */
function summit_intake_send_agreement_emails( $post_id ) {
	$pdf_id = absint( get_option( 'summit_intake_agreement_pdf', 0 ) );
	if ( ! $pdf_id ) {
		return new WP_Error( 'no_pdf', 'No agreement PDF configured.' );
	}

	$pdf_path = get_attached_file( $pdf_id );
	if ( ! $pdf_path || ! file_exists( $pdf_path ) ) {
		return new WP_Error( 'file_missing', 'Agreement PDF file not found on disk.' );
	}

	$parties = array_merge(
		get_field( 'plaintiffs',    $post_id ) ?: [],
		get_field( 'defendants',    $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$booking_raw  = get_post_meta( $post_id, '_summit_intake_booking_date_raw', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';
	$url_map      = get_post_meta( $post_id, '_summit_counsel_upload_urls', true ) ?: [];
	$zoom_url     = get_post_meta( $post_id, '_summit_intake_zoom_url', true ) ?: '';

	$default_subject = 'Mediation Agreement — {case_name}';
	$default_body    = "Dear {counsel_name},\n\nOur conflict check is complete, and we are pleased to confirm that Summit Law LLP is able to proceed with this mediation.\n\nPlease find attached the Mediation Agreement for {case_name}, scheduled for {booking_date} with {mediator_name}.\n\nPlease sign and return the agreement using your secure upload link:\n{upload_url}\n\nZoom Meeting: {zoom_url}\n\nThank you,\nSummit Law LLP";
	$subject_tpl     = get_option( 'summit_intake_agreement_email_subject', $default_subject );
	$body_tpl        = get_option( 'summit_intake_agreement_email_body', $default_body );

	// Resolve {team_email} before per-party loop (same value for all recipients).
	$_team_email_addr = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	$_team_email_link = $_team_email_addr
		? '<a href="mailto:' . esc_attr( $_team_email_addr ) . '" style="color:#1a73e8;">' . esc_html( $_team_email_addr ) . '</a>'
		: '';
	$subject_tpl = str_replace( '{team_email}', $_team_email_addr, $subject_tpl );
	$body_tpl    = str_replace( '{team_email}', $_team_email_link, $body_tpl );

	// Build HTML calendar block when booking date is available.
	$calendar_html = '';
	$use_html      = false;
	if ( $booking_raw ) {
		// Parse the booking time in the site's configured timezone, then express in UTC for calendar URLs.
		$site_tz    = wp_timezone();
		$dt_obj     = new DateTimeImmutable( $booking_raw, $site_tz );
		$dt_end_obj = $dt_obj->modify( '+3 hours' );

		$dt_start = gmdate( 'Ymd\THis\Z', $dt_obj->getTimestamp() );
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

		$use_html      = true;
		$calendar_html = '<p style="font-family:sans-serif;font-size:14px;margin-top:1.5em;line-height:2.2;">'
		               . '<strong>— Add to Your Calendar —</strong><br>'
		               . '📅 <a href="' . esc_url( $google )  . '" style="color:#1a73e8;">Google Calendar</a><br>'
		               . '📆 <a href="' . esc_url( $outlook ) . '" style="color:#0078d4;">Outlook</a><br>'
		               . '🗓 <a href="' . esc_url( $ics_url ) . '" style="color:#555555;">Apple / iCal</a>'
		               . '</p>';
	}

	$base_headers = [
		'Content-Type: ' . ( $use_html ? 'text/html' : 'text/plain' ) . '; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	];

	$sent = 0;

	foreach ( $parties as $party ) {
		if ( ! empty( $party['mediation_agreement_signed'] ) ) continue;

		$counsel_email = sanitize_email( $party['counsel_email'] ?? '' );
		$upload_url    = $url_map[ $counsel_email ] ?? '';

		if ( $counsel_email ) {
			$name         = $party['counsel_name'] ?? '';
			$asst_email   = sanitize_email( $party['assistant_email'] ?? '' );
			$send_headers = $base_headers;
			if ( $asst_email ) {
				$send_headers[] = 'Cc: ' . $asst_email;
			}

			// Build HTML anchor links for body; keep raw URLs for subject.
			$upload_link = $upload_url ? '<a href="' . esc_url( $upload_url ) . '" style="color:#1a73e8;">Secure File Upload</a>' : '';
			$zoom_link   = $zoom_url   ? '<a href="' . esc_url( $zoom_url )   . '" style="color:#1a73e8;">Zoom Call URL</a>'      : '';

			$body_text  = summit_intake_agreement_merge( $body_tpl, $case_name, $booking_date, $mediator, $name, $upload_link, $zoom_link );
			$final_body = $use_html
				? summit_intake_email_wrap( nl2br( $body_text ) . $calendar_html )
				: $body_text;

			wp_mail(
				$counsel_email,
				summit_intake_agreement_merge( $subject_tpl, $case_name, $booking_date, $mediator, $name, $upload_url, $zoom_url ),
				$final_body,
				$send_headers,
				[ $pdf_path ]
			);
			$sent++;
		}
	}

	return $sent;
}

/**
 * Send a new-intake notification to the Team Notification Email so the conflict
 * check can be initiated immediately after submission.
 *
 * @param int $post_id Mediation intake post ID.
 */
function summit_intake_send_new_intake_notification( $post_id ) {
	$team_email = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	if ( ! $team_email ) return;

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';
	$intake_url  = get_admin_url( null, 'post.php?post=' . $post_id . '&action=edit' );
	$intake_link = '<a href="' . esc_url( $intake_url ) . '" style="color:#1a73e8;">View Intake →</a>';

	$default_subject = 'New Mediation Intake — {case_name}';
	$default_body    = "A new mediation intake has been submitted and is ready for review.\n\nCase: {case_name}\nSession Date: {booking_date}\nMediator: {mediator_name}\n\nPlease initiate the conflict check and review all party details before sending the Mediation Agreement.\n\n{intake_url}";

	$tags = [ '{case_name}', '{booking_date}', '{mediator_name}', '{intake_url}' ];
	$vals = [ $case_name, $booking_date, $mediator, $intake_link ];

	$subject = str_replace( $tags, $vals, get_option( 'summit_intake_new_intake_subject', $default_subject ) );
	$body    = str_replace( $tags, $vals, get_option( 'summit_intake_new_intake_body', $default_body ) );

	if ( ! $subject || ! $body ) return;

	wp_mail( $team_email, $subject, summit_intake_email_wrap( nl2br( $body ) ), [
		'Content-Type: text/html; charset=UTF-8',
		'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
	] );
}

/**
 * Send a confirmation email to all counsel (CC: assistant) immediately on intake submission.
 * No Zoom URL yet at this stage — {zoom_url} is excluded from this template.
 *
 * Gated by `summit_intake_confirmation_enabled` option so it can be toggled without
 * code changes — disable OttoKit's email step first, then tick the checkbox.
 *
 * @param int   $post_id         Mediation intake post ID.
 * @param array $plaintiff_rows  Sanitized plaintiff party rows.
 * @param array $defendant_rows  Sanitized defendant party rows.
 * @param array $third_party_rows Sanitized third-party rows (may be empty).
 */
function summit_intake_send_confirmation_emails( $post_id, $plaintiff_rows, $defendant_rows, $third_party_rows ) {
	if ( ! get_option( 'summit_intake_confirmation_enabled', false ) ) return;

	$default_subject = 'Mediation Session Booked — {case_name}';
	$default_body    = "Dear {counsel_name},\n\nA mediation session has been booked with Summit Law LLP in which you or your client has been listed as a party. The details on file are included below for your reference.\n\nIf you believe you have received this in error, please contact us at {team_email} or (613) 749-4700.\n\nParties on record:\n\n   Plaintiff(s): {plaintiff_list}\n   Defendant(s): {defendant_list}\n{third_party_section}\nSession details:\n\n   Case:      {case_name}\n   Date:      {booking_date}\n   Mediator:  {mediator_name}\n\nWhat happens next:\n\n1. Conflict Check — Our team will conduct a conflict check and be in touch shortly to confirm that Summit Law LLP is able to proceed.\n\n2. Mediation Agreement — Once confirmed, we will send a Mediation Agreement to all parties for review and signature, along with full session details.\n\n3. Document Upload — After signing, you will receive a secure upload link to return your signed agreement. A separate request for your mediation brief will follow closer to the session date.\n\nThank you,\nSummit Law LLP\n(613) 749-4700";

	$subject_tpl = get_option( 'summit_intake_confirmation_subject', $default_subject );
	$body_tpl    = get_option( 'summit_intake_confirmation_body',    $default_body );

	if ( ! $subject_tpl || ! $body_tpl ) return;

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';

	// Format a list of party rows into a single readable string.
	$format = function( $rows ) {
		return implode( "\n   ", array_map( function( $r ) {
			$line = $r['name'];
			if ( $r['counsel_name'] ) {
				$line .= ' — Counsel: ' . $r['counsel_name'];
				if ( $r['counsel_email'] ) {
					$line .= ' (' . $r['counsel_email'] . ')';
				}
			}
			return $line;
		}, $rows ) );
	};

	$plaintiff_list = $format( $plaintiff_rows );
	$defendant_list = $format( $defendant_rows );

	// Conditional third-party block — full labelled line when present, empty string when absent.
	$third_party_section = '';
	if ( ! empty( $third_party_rows ) ) {
		$tp_label            = count( $third_party_rows ) > 1 ? 'Third Parties' : 'Third Party';
		$third_party_section = "   <b>{$tp_label}:</b>\n   " . $format( $third_party_rows );
	}

	$_team_email_addr = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	$_team_email_link = $_team_email_addr
		? '<a href="mailto:' . esc_attr( $_team_email_addr ) . '" style="color:#1a73e8;">' . esc_html( $_team_email_addr ) . '</a>'
		: '';

	$all_parties = array_merge( $plaintiff_rows, $defendant_rows, $third_party_rows );

	foreach ( $all_parties as $party ) {
		$counsel_email = $party['counsel_email'] ?? '';
		if ( ! $counsel_email ) continue;

		$name       = $party['counsel_name'] ?? '';
		$asst_email = $party['assistant_email'] ?? '';

		$tags = [
			'{counsel_name}', '{case_name}', '{booking_date}', '{mediator_name}',
			'{plaintiff_list}', '{defendant_list}', '{third_party_section}', '{team_email}',
		];
		$vals = [
			$name, $case_name, $booking_date, $mediator,
			$plaintiff_list, $defendant_list, $third_party_section, $_team_email_link,
		];

		$subject    = str_replace( $tags, $vals, $subject_tpl );
		$body       = str_replace( $tags, $vals, $body_tpl );
		$final_body = summit_intake_email_wrap( nl2br( $body ) );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
		];
		if ( $asst_email ) {
			$headers[] = 'Cc: ' . $asst_email;
		}

		wp_mail( $counsel_email, $subject, $final_body, $headers );
	}
}

/**
 * Replace merge tags in an agreement email template string.
 *
 * @param string $tpl          Template string containing {tags}.
 * @param string $case_name    Case name.
 * @param string $booking_date Formatted booking date.
 * @param string $mediator     Mediator name.
 * @param string $name         Recipient name.
 * @param string $upload_url   Recipient's unique upload URL.
 * @return string
 */
function summit_intake_agreement_merge( $tpl, $case_name, $booking_date, $mediator, $name, $upload_url, $zoom_url = '' ) {
	return str_replace(
		[ '{counsel_name}', '{case_name}', '{booking_date}', '{mediator_name}', '{upload_url}', '{zoom_url}' ],
		[ $name, $case_name, $booking_date, $mediator, $upload_url, $zoom_url ],
		$tpl
	);
}

// =============================================================================
// Cancellation / Deletion — Amelia Hooks
// =============================================================================

/**
 * Shared cleanup for any Amelia appointment event that should close an intake.
 * Extracts booking IDs from the appointment data array, finds the matching
 * intake post, unschedules crons, sends cancellation emails, and trashes the post.
 *
 * @param array $appointment_data Full appointment array from Amelia.
 */
function summit_intake_close_from_amelia( $appointment_data ) {
	if ( empty( $appointment_data['bookings'] ) || ! is_array( $appointment_data['bookings'] ) ) {
		return;
	}

	$booking_ids = [];
	foreach ( $appointment_data['bookings'] as $booking ) {
		if ( ! empty( $booking['id'] ) ) {
			$booking_ids[] = absint( $booking['id'] );
		}
	}

	if ( empty( $booking_ids ) ) {
		return;
	}

	// Find the intake post whose amelia_booking_id matches any of these bookings.
	$intake_posts = get_posts( [
		'post_type'      => 'mediation_intake',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => [ [
			'key'     => 'amelia_booking_id',
			'value'   => $booking_ids,
			'compare' => 'IN',
		] ],
	] );

	if ( empty( $intake_posts ) ) {
		return;
	}

	$post_id = $intake_posts[0]->ID;

	// 1. Unschedule all pending crons (independent of post data — safe to run first).
	summit_reminders_unschedule_all( $post_id );

	// 2. Send cancellation emails (needs post data — must run before trash).
	summit_intake_send_cancellation_emails( $post_id );

	// 3. Move intake to trash — recoverable if the action was accidental.
	wp_trash_post( $post_id );
}

/**
 * Fires when an appointment status changes to "canceled" in Amelia.
 *
 * @param array  $appointment_data Full appointment array from Amelia.
 * @param string $status           The new status string.
 */
function summit_intake_handle_amelia_cancellation( $appointment_data, $status ) {
	if ( $status !== 'canceled' ) {
		return;
	}
	summit_intake_close_from_amelia( $appointment_data );
}
add_action( 'amelia_after_appointment_status_updated', 'summit_intake_handle_amelia_cancellation', 10, 2 );

/**
 * Fires when an appointment is hard-deleted in Amelia (without cancelling first).
 * Triggers the same cleanup as a cancellation so crons and the intake post
 * are not left orphaned.
 *
 * @param array $appointment_data Full appointment array from Amelia.
 */
function summit_intake_handle_amelia_deletion( $appointment_data ) {
	summit_intake_close_from_amelia( $appointment_data );
}
add_action( 'amelia_after_appointment_deleted', 'summit_intake_handle_amelia_deletion' );

/**
 * Send the cancellation notification to all parties on an intake.
 *
 * @param int $post_id Mediation intake post ID.
 */
function summit_intake_send_cancellation_emails( $post_id ) {
	$default_subject = 'Mediation Session Cancelled — {case_name}';
	$default_body    = "Dear {counsel_name},\n\nWe are writing to let you know that the mediation session for {case_name}, which was scheduled for {booking_date} with {mediator_name}, has been cancelled.\n\nPlease refer to the mediation agreement for your obligation to cancellation fees. We will forward any invoices shortly.\n\nPlease contact us directly to reschedule or if you have any questions.\n\nThank you,\nSummit Law LLP";

	$subject_tpl  = get_option( 'summit_intake_cancellation_subject', $default_subject );
	$body_tpl     = get_option( 'summit_intake_cancellation_body', $default_body );

	$case_name    = summit_intake_case_name( $post_id );
	$booking_date = get_post_meta( $post_id, '_summit_intake_booking_date', true );
	$team_id      = get_field( 'team_member', $post_id );
	$mediator     = $team_id ? get_the_title( $team_id ) : '';

	$parties = array_merge(
		get_field( 'plaintiffs',    $post_id ) ?: [],
		get_field( 'defendants',    $post_id ) ?: [],
		get_field( 'third_parties', $post_id ) ?: []
	);

	$_team_email_addr = get_option( 'summit_intake_notification_email', get_option( 'admin_email' ) );
	$_team_email_link = $_team_email_addr
		? '<a href="mailto:' . esc_attr( $_team_email_addr ) . '" style="color:#1a73e8;">' . esc_html( $_team_email_addr ) . '</a>'
		: '';

	$tags = [ '{counsel_name}', '{case_name}', '{booking_date}', '{mediator_name}', '{team_email}' ];

	foreach ( $parties as $party ) {
		$counsel_email = sanitize_email( $party['counsel_email'] ?? '' );
		if ( $counsel_email ) {
			$vals       = [ $party['counsel_name'] ?? '', $case_name, $booking_date, $mediator, $_team_email_link ];
			$body       = str_replace( $tags, $vals, $body_tpl );
			$headers    = [
				'Content-Type: text/html; charset=UTF-8',
				'From: Mediation — Summit Law LLP <info@summitlaw.ca>',
			];
			$asst_email = sanitize_email( $party['assistant_email'] ?? '' );
			if ( $asst_email ) {
				$headers[] = 'Cc: ' . $asst_email;
			}
			wp_mail(
				$counsel_email,
				str_replace( $tags, $vals, $subject_tpl ),
				summit_intake_email_wrap( nl2br( $body ) ),
				$headers
			);
		}
	}
}
