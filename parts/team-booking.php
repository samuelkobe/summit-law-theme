<?php
/**
 * Team Member Booking Section (Amelia)
 */

if ( get_field( 'bookable_services_toggle' ) != 1 ) {
	return;
}

// Use get_field to avoid repeater state issues (hero partial may have consumed have_rows)
$service_rows = get_field( 'service' );
if ( empty( $service_rows ) ) {
	return;
}

$has_service_to_book = false;
$booking_shortcode = '';
$booking_service_label = '';
foreach ( $service_rows as $row ) {
	if ( $row['acf_fc_layout'] === 'mediation_service' ) {
		$has_service_to_book = true;
		$booking_service_label = 'Mediation Session';
		$booking_shortcode = $row['mediation_shortcode'] ?? '';
		break;
	}
}

if ( ! $has_service_to_book || ! $booking_shortcode ) {
	return;
}
?>
<style>
	#amelia-container {
		margin: 0 !important;
	}
</style>
<div id="book-service" class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-16 scroll-mt-24">
	<div class="lg:col-span-4">
		<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Book a <?php echo esc_html( $booking_service_label ); ?></h2>
		<p class="text-base lg:text-lg text-brand-50 pt-2 lg:pt-4">Schedule a <?php echo esc_html( $booking_service_label ); ?> with <?php the_title(); ?>.</p>
		<ol class="transform translate-x-4 list-decimal text-sm">
			<li>Select a Date</li>
			<li>Choose a Duration</li>
			<li>Select a Timeslot</li>
			<li>Enter the <?php echo esc_html( strtolower( $booking_service_label ) ); ?> Information</li>
		</ol>
	</div>
	<div class="lg:col-span-7">
		<?php echo do_shortcode( $booking_shortcode ); ?>
	</div>
</div>
