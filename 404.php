<?php
/**
 * 404 Error Page Template
 *
 * Displayed when a page is not found
 */

get_header();
?>

<main id="main" class="site-main error-404 not-found">

	<div class="container mx-auto px-4">

		<div class="max-w-2xl mx-auto text-center py-20">

			<!-- Large 404 Number -->
			<div class="mb-8">
				<h1 class="text-8xl md:text-9xl font-bold text-green-deep opacity-20">404</h1>
			</div>

			<!-- Error Message -->
			<div class="mb-12">
				<h2 class="text-3xl md:text-4xl font-bold text-green-deep mb-4">
					<?php esc_html_e( 'Page Not Found', 'summit-law-theme' ); ?>
				</h2>
				<p class="text-lg text-gray-600">
					<?php esc_html_e( 'Sorry, the page you are looking for doesn\'t exist or has been moved.', 'summit-law-theme' ); ?>
				</p>
			</div>

			<!-- Navigation Options -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl mx-auto">

				<!-- Home Button -->
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"
				   class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-lg hover:border-green-deep hover:shadow-lg transition-all duration-300">
					<svg class="w-12 h-12 text-green-deep mb-3 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
					</svg>
					<span class="text-lg font-medium text-green-deep">Home</span>
					<span class="text-sm text-gray-500 mt-1">Return to homepage</span>
				</a>

				<!-- Services Button -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'service' ) ?: home_url( '/services/' ) ); ?>"
				   class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-lg hover:border-green-deep hover:shadow-lg transition-all duration-300">
					<svg class="w-12 h-12 text-green-deep mb-3 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
					</svg>
					<span class="text-lg font-medium text-green-deep">Services</span>
					<span class="text-sm text-gray-500 mt-1">View our services</span>
				</a>

				<!-- Contact Button -->
				<?php
				// Try to get contact page - check for common slugs
				$contact_page = get_page_by_path( 'contact' );
				if ( ! $contact_page ) {
					$contact_page = get_page_by_path( 'contact-us' );
				}
				$contact_url = $contact_page ? get_permalink( $contact_page->ID ) : home_url( '/contact/' );
				?>
				<a href="<?php echo esc_url( $contact_url ); ?>"
				   class="group flex flex-col items-center justify-center p-6 bg-white border-2 border-gray-200 rounded-lg hover:border-green-deep hover:shadow-lg transition-all duration-300">
					<svg class="w-12 h-12 text-green-deep mb-3 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
					</svg>
					<span class="text-lg font-medium text-green-deep">Contact</span>
					<span class="text-sm text-gray-500 mt-1">Get in touch</span>
				</a>

			</div>

		</div>

	</div>

</main>

<?php
get_footer();
