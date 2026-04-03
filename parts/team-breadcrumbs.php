<?php
/**
 * Team Member Breadcrumbs + Social Links
 */

$team_page = get_page_by_path( 'team' );
$team_url = $team_page ? get_permalink( $team_page->ID ) : home_url( '/team/' );
?>
<section class="bg-white">

	<div class="container mx-auto flex flex-col md:flex-row md:justify-between">
		<nav aria-label="Breadcrumb" class="hidden md:block py-8">
			<ol class="flex items-center gap-2 text-sm font-semibold">
				<li>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						Summit Law LLP
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-brand-40">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>
				<li>
					<a href="<?php echo esc_url( $team_url ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						Team
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>
				<li aria-current="page" class="text-green-deep">
					<?php the_title(); ?>
				</li>
			</ol>
		</nav>
		<?php if ( have_rows('social_links') ) : ?>
			<div class="py-8 flex flex-row gap-x-4">
				<span class="text-base lowercase">Connect</span>
					<ul class="team-social flex flex-row items-center gap-2">
							<?php while ( have_rows('social_links') ) : the_row();
									$type   = get_sub_field('social_type');
									$handle = trim( (string) get_sub_field('social_handle') );

									if ( ! $type || ! $handle ) {
											continue;
									}

									// Build base URL by type
									switch ( $type ) {
											case 'linkedin':
											default:
													$handle = ltrim($handle, '/');
													$url    = 'https://www.linkedin.com/' . $handle;
													break;

											case 'facebook':
													$handle = ltrim($handle, '/@');
													$url    = 'https://www.facebook.com/' . $handle;
													break;

											case 'x':
													$handle = ltrim($handle, '@');
													$url    = 'https://twitter.com/' . $handle;
													break;

											case 'instagram':
													$handle = ltrim($handle, '/@');
													$url    = 'https://www.instagram.com/' . $handle;
													break;

											case 'youtube':
													$handle = ltrim($handle, '/');
													$url    = 'https://www.youtube.com/' . $handle;
													break;

											case 'email':
													$url = 'mailto:' . $handle;
													break;

											case 'phone':
													$clean_phone = preg_replace('/[\s\-\(\)]/', '', $handle);
													$url = 'tel:' . $clean_phone;
													break;
									}

									$icon_path = get_template_directory_uri() . '/assets/icons/social-' . $type . '.svg';
							?>
									<li class="team-social__item">
											<a href="<?php echo esc_url( $url ); ?>"
												class="team-social__link"
												target="_blank"
												rel="noopener noreferrer"
												aria-label="<?php echo esc_attr( ucfirst($type) ); ?>">
												<?php echo summit_get_svg_icon( $type, ['class' => 'w-6 h-6 fill-green-deep'] ); ?>
											</a>
									</li>
							<?php endwhile; ?>
					</ul>
				</div>
			<?php endif; ?>
	</div>

</section>
