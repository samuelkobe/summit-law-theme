<?php
/**
 * Block template file: accordion.php
 *
 * Accordion Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'accordion-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-accordion';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}
?>

<style type="text/css">
	<?php echo '#' . $id; ?> {
		/* Add styles that use ACF values here */
	}

	<?php echo '#' . $id; ?> .accordion-heading::after {
		content: '';
		position: absolute;
		right: 2rem;
		top: 50%;
		transform: translateY(-50%) rotate(90deg);
		width: 1.25rem;
		height: 1.25rem;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23d1d436'%3E%3Cpath d='M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z'/%3E%3C/svg%3E");
		background-size: contain;
		background-repeat: no-repeat;
		transition: transform 0.5s ease;
	}

	<?php echo '#' . $id; ?> .accordion-heading.active::after {
		transform: translateY(-50%) rotate(-90deg);
	}

	<?php echo '#' . $id; ?> .accordion-content {
		max-height: 0;
		overflow: hidden;
	}

	<?php echo '#' . $id; ?> .accordion-content.active {
		max-height: 1000px;
	}

	<?php echo '#' . $id; ?> .accordion-content p {
		opacity: 0;
		transition: opacity 0s ease;
	}

	<?php echo '#' . $id; ?> .accordion-content.active p {
		opacity: 1;
		transition: opacity 0.3s ease 0.3s;
	}

	<?php echo '#' . $id; ?> .accordion-heading:focus-visible {
		outline: 2px solid #d1d436;
		outline-offset: 4px;
	}
</style>

<?php
$accordion_title      = get_field( 'title' );
$has_accordion_items = have_rows( 'accordion_object' );
?>

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white block-white 2xl:pt-24 2xl:pb-48 <?php echo esc_attr( $classes ); ?>">
	<div class="md:container px-6 md:mx-auto grid grid-cols-1">

		<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px] xl:text-h2 mb-4 lg:mb-8 font-museum capitalize text-green-deep underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-4 transition-colors duration-300">
			<?php if ( $accordion_title ) : ?>
				<?php echo esc_html( $accordion_title ); ?>
			<?php elseif ( $is_preview ) : ?>
				<span class="text-brand-40">Add a title in block settings.</span>
			<?php endif; ?>
		</h2>

		<?php if ( $has_accordion_items ) : ?>
			<?php $item_index = 0; ?>
			<?php while ( have_rows( 'accordion_object' ) ) : the_row(); ?>
				<?php
				$is_first = $item_index === 0;
				$heading_id = $id . '-heading-' . $item_index;
				$content_id = $id . '-content-' . $item_index;
				$item_index++;
				?>
				<div class="accordion-item border-b-2 lg:border-b-[3px] border-brand-30 pt-4 lg:pt-6 <?php echo $is_first ? 'pb-8 lg:pb-12' : 'pb-4 lg:pb-6'; ?> transition-all duration-300">
					<h3>
						<button
							id="<?php echo esc_attr( $heading_id ); ?>"
							class="accordion-heading w-full <?php echo $is_first ? 'active' : ''; ?> relative text-left text-xl 2xl:text-2xl font-normal antialiased <?php echo $is_first ? 'text-green-deep' : 'text-brand-40'; ?> cursor-pointer py-4 pr-20 hover:text-green-deep transition-colors"
							aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $content_id ); ?>"
						>
							<?php the_sub_field( 'heading' ); ?>
						</button>
					</h3>
					<div
						id="<?php echo esc_attr( $content_id ); ?>"
						class="accordion-content <?php echo $is_first ? 'active' : ''; ?>"
						role="region"
						aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
						<?php if ( ! $is_first ) : ?>hidden<?php endif; ?>
					>
						<p class="font-normal text-base md:text-lg 2xl:text-xl pb-4 max-w-[95%]"><?php the_sub_field( 'content' ); ?></p>
					</div>
				</div>
			<?php endwhile; ?>
		<?php elseif ( $is_preview ) : ?>
			<div class="accordion-item border-b-2 lg:border-b-[3px] border-brand-30 pt-4 lg:pt-6 pb-4 lg:pb-6">
				<h3>
					<span class="w-full relative text-left text-2xl 2xl:text-3xl font-normal antialiased text-brand-40 py-4 pr-20 block">
						Add accordion items in block settings.
					</span>
				</h3>
			</div>
		<?php endif; ?>

	</div>
</section>

<script>
(function() {
	const accordion = document.getElementById('<?php echo esc_js( $id ); ?>');
	if (!accordion) return;

	const buttons = accordion.querySelectorAll('.accordion-heading');

	function toggleAccordion(button) {
		const content = document.getElementById(button.getAttribute('aria-controls'));
		const accordionItem = button.closest('.accordion-item');
		const isExpanded = button.getAttribute('aria-expanded') === 'true';

		// Close all items in this accordion block
		accordion.querySelectorAll('.accordion-heading').forEach(btn => {
			btn.classList.remove('active', 'text-green-deep');
			btn.classList.add('text-brand-40');
			btn.setAttribute('aria-expanded', 'false');

			const btnContent = document.getElementById(btn.getAttribute('aria-controls'));
			if (btnContent) {
				btnContent.classList.remove('active');
				btnContent.setAttribute('hidden', '');
			}
		});
		accordion.querySelectorAll('.accordion-item').forEach(item => {
			item.classList.remove('pb-8', 'lg:pb-12');
			item.classList.add('pb-4', 'lg:pb-6');
		});

		// Open clicked item if it wasn't already open
		if (!isExpanded) {
			button.classList.add('active', 'text-green-deep');
			button.classList.remove('text-brand-40');
			button.setAttribute('aria-expanded', 'true');
			// Remove hidden first so the element enters the render tree at opacity:0,
			// then add active on the next frame so the browser has a "from" state
			// to animate the opacity transition from.
			content.removeAttribute('hidden');
			requestAnimationFrame(() => requestAnimationFrame(() => {
				content.classList.add('active');
			}));
			accordionItem.classList.remove('pb-4', 'lg:pb-6');
			accordionItem.classList.add('pb-8', 'lg:pb-12');
		}
	}

	// Click event
	buttons.forEach(button => {
		button.addEventListener('click', function() {
			toggleAccordion(this);
		});

		// Keyboard navigation
		button.addEventListener('keydown', function(e) {
			const currentIndex = Array.from(buttons).indexOf(this);

			switch(e.key) {
				case 'ArrowDown':
					e.preventDefault();
					const nextButton = buttons[currentIndex + 1] || buttons[0];
					nextButton.focus();
					break;
				case 'ArrowUp':
					e.preventDefault();
					const prevButton = buttons[currentIndex - 1] || buttons[buttons.length - 1];
					prevButton.focus();
					break;
				case 'Home':
					e.preventDefault();
					buttons[0].focus();
					break;
				case 'End':
					e.preventDefault();
					buttons[buttons.length - 1].focus();
					break;
			}
		});
	});
})();
</script>