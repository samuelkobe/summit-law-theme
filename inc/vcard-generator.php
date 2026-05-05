<?php
/**
 * vCard Generation for Team Members
 *
 * @package Summit_Law_Theme
 */

// Office address constants
define( 'SUMMIT_OFFICE_STREET', '1420 Blair Towers Place, Suite 810' );
define( 'SUMMIT_OFFICE_CITY', 'Ottawa' );
define( 'SUMMIT_OFFICE_STATE', 'ON' );
define( 'SUMMIT_OFFICE_ZIP', 'K1J 9L8' );
define( 'SUMMIT_OFFICE_COUNTRY', 'Canada' );

/**
 * Generate vCard content for a team member
 *
 * @param int $team_member_id Post ID of team member
 * @return string|false vCard content or false on failure
 */
function summit_generate_vcard( $team_member_id ) {
	$post = get_post( $team_member_id );

	// Verify post exists and is team member
	if ( ! $post || $post->post_type !== 'team' ) {
		return false;
	}

	// Get ACF fields
	$full_name = get_the_title( $team_member_id );
	$role = get_field( 'role', $team_member_id );
	$email = get_field( 'email', $team_member_id );
	$phone = get_field( 'phone', $team_member_id );
	$extension = get_field( 'extension', $team_member_id );
	$fax = get_field( 'fax', $team_member_id );
	$permalink = get_permalink( $team_member_id );

	// Parse name into components
	$name_parts = summit_parse_name( $full_name );

	// Format phone with extension
	$phone_formatted = summit_format_phone( $phone, $extension );

	// Build vCard
	$vcard = "BEGIN:VCARD\r\n";
	$vcard .= "VERSION:3.0\r\n";
	$vcard .= "PRODID:-//Summit Law LLP//Team Member vCard//EN\r\n";

	// Name (N: last;first;middle;prefix;suffix)
	$vcard .= "N:" . summit_vcard_escape( $name_parts['last'] ) . ";";
	$vcard .= summit_vcard_escape( $name_parts['first'] ) . ";";
	$vcard .= summit_vcard_escape( $name_parts['middle'] ) . ";";
	$vcard .= summit_vcard_escape( $name_parts['prefix'] ) . ";";
	$vcard .= summit_vcard_escape( $name_parts['suffix'] ) . "\r\n";

	// Full name
	$vcard .= "FN:" . summit_vcard_escape( $full_name ) . "\r\n";

	// Organization
	$vcard .= "ORG:Summit Law LLP;\r\n";

	// Title/Role
	if ( $role && isset( $role['label'] ) ) {
		$vcard .= "TITLE:" . summit_vcard_escape( $role['label'] ) . "\r\n";
	}

	// Email
	if ( $email ) {
		$vcard .= "EMAIL;type=INTERNET;type=WORK;type=pref:" . summit_vcard_escape( $email ) . "\r\n";
	}

	// Phone
	if ( $phone_formatted ) {
		$vcard .= "TEL;type=WORK;type=VOICE;type=pref:" . summit_vcard_escape( $phone_formatted ) . "\r\n";
	}

	// Fax
	if ( $fax ) {
		$vcard .= "TEL;type=WORK;type=FAX:" . summit_vcard_escape( $fax ) . "\r\n";
	}

	// Office Address (ADR format: PO Box;Extended Address;Street;Locality;Region;Postal Code;Country)
	$vcard .= "ADR;type=WORK;type=pref:;;";
	$vcard .= summit_vcard_escape( SUMMIT_OFFICE_STREET ) . ";";
	$vcard .= SUMMIT_OFFICE_CITY . ',' . SUMMIT_OFFICE_STATE . ";"; // No space after comma, no escaping
	$vcard .= SUMMIT_OFFICE_ZIP . ";";
	$vcard .= ' ' . SUMMIT_OFFICE_COUNTRY . "\r\n"; // Space before country

	// URL (don't escape - URLs are valid as-is in vCard)
	if ( $permalink ) {
		$vcard .= "URL;type=pref:" . $permalink . "\r\n";
	}

	// Generate unique ID
	$vcard .= "X-ABUID:" . wp_generate_uuid4() . ":ABPerson\r\n";

	$vcard .= "END:VCARD\r\n";

	return $vcard;
}

/**
 * Parse full name into components for vCard N: field
 *
 * @param string $full_name Full name
 * @return array Array with keys: first, middle, last, prefix, suffix
 */
function summit_parse_name( $full_name ) {
	$parts = array(
		'prefix' => '',
		'first' => '',
		'middle' => '',
		'last' => '',
		'suffix' => ''
	);

	// Clean up name
	$name = trim( $full_name );
	$words = explode( ' ', $name );
	$word_count = count( $words );

	if ( $word_count === 0 ) {
		return $parts;
	}

	// Handle common prefixes (Dr., Mr., Ms., etc.)
	$prefixes = array( 'Dr', 'Dr.', 'Mr', 'Mr.', 'Mrs', 'Mrs.', 'Ms', 'Ms.', 'Miss' );
	if ( in_array( $words[0], $prefixes ) ) {
		$parts['prefix'] = array_shift( $words );
		$word_count--;
	}

	// Handle common suffixes (Jr., Sr., III, etc.)
	$suffixes = array( 'Jr', 'Jr.', 'Sr', 'Sr.', 'II', 'III', 'IV', 'Esq', 'Esq.', 'PhD', 'MD' );
	if ( $word_count > 0 && in_array( end( $words ), $suffixes ) ) {
		$parts['suffix'] = array_pop( $words );
		$word_count--;
	}

	// Parse remaining words
	if ( $word_count === 1 ) {
		// Single name - use as last name
		$parts['last'] = $words[0];
	} elseif ( $word_count === 2 ) {
		// First Last
		$parts['first'] = $words[0];
		$parts['last'] = $words[1];
	} else {
		// First Middle... Last
		$parts['first'] = array_shift( $words );
		$parts['last'] = array_pop( $words );
		$parts['middle'] = implode( ' ', $words );
	}

	return $parts;
}

/**
 * Format phone number with extension for vCard
 *
 * @param string $phone Phone number
 * @param string $extension Extension number
 * @return string Formatted phone number
 */
function summit_format_phone( $phone, $extension = '' ) {
	if ( empty( $phone ) ) {
		return '';
	}

	$formatted = $phone;

	// Add extension if present
	if ( ! empty( $extension ) ) {
		$formatted .= ' ext. ' . $extension;
	}

	return $formatted;
}

/**
 * Escape special characters for vCard format
 *
 * @param string $string String to escape
 * @return string Escaped string
 */
function summit_vcard_escape( $string ) {
	// vCard escaping: only backslash, comma, semicolon for field values
	// Do NOT escape forward slashes - they're valid in vCard
	$string = str_replace( '\\', '\\\\', $string );
	$string = str_replace( ',', '\,', $string );
	$string = str_replace( ';', '\;', $string );
	$string = str_replace( "\n", '\n', $string );
	$string = str_replace( "\r", '', $string );

	return $string;
}

/**
 * Handle vCard download via query parameter
 * Uses template_redirect to serve raw vCard without JSON encoding
 */
function summit_handle_vcard_download() {
	// Check if this is a vCard download request
	if ( ! isset( $_GET['summit_vcard'] ) ) {
		return;
	}

	$team_id = (int) $_GET['summit_vcard'];

	// Verify we have a valid team member ID
	if ( ! $team_id ) {
		wp_die( 'Invalid team member ID', 'Invalid Request', array( 'response' => 400 ) );
	}

	// Generate vCard
	$vcard = summit_generate_vcard( $team_id );

	if ( ! $vcard ) {
		wp_die( 'Team member not found', 'Not Found', array( 'response' => 404 ) );
	}

	// Get sanitized filename
	$post = get_post( $team_id );
	$filename = sanitize_title( $post->post_title ) . '.vcf';

	// Clear any output buffers
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	// Set headers for file download
	header( 'Content-Type: text/vcard; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $vcard ) );
	header( 'Cache-Control: no-cache, must-revalidate' );
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );

	// Output vCard and exit
	echo $vcard;
	exit;
}
add_action( 'template_redirect', 'summit_handle_vcard_download' );
