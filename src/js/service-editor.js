/**
 * Service Editor - Pattern Switching Logic
 *
 * Automatically switches block patterns based on the hierarchical parent selection
 * for the Service custom post type.
 *
 * @package Summit_Law_Theme
 */

console.log('Service Editor: Script file loaded');

(function(wp) {
	console.log('Service Editor: Checking WordPress packages...', { wp });

	if (!wp || !wp.data || !wp.blocks) {
		console.error('Service Editor: WordPress data/blocks packages not available');
		console.error('Service Editor: Available:', { wp, data: wp?.data, blocks: wp?.blocks });
		return;
	}

	console.log('Service Editor: WordPress packages available!');

	const { select, dispatch, subscribe } = wp.data;
	const { parse } = wp.blocks;
	const { createElement: el } = wp.element;
	const { PluginDocumentSettingPanel } = wp.editPost || {};
	const { registerPlugin } = wp.plugins || {};

	// Track the last parent ID to detect changes
	let lastParentId = null;
	let isApplyingPattern = false; // Prevent infinite loops

	/**
	 * Apply the appropriate pattern based on parent selection
	 */
	const maybeApplyPattern = () => {
		// Prevent recursive calls
		if (isApplyingPattern) {
			return;
		}

		// 1. Verify we're editing a service post
		const postType = select('core/editor')?.getCurrentPostType();
		if (postType !== 'service') {
			return;
		}

		// 2. Get current parent selection
		// Normalize: treat null, 0, and undefined as "no parent" (use 0 for consistency)
		const rawParentId = select('core/editor')?.getEditedPostAttribute('parent');
		const currentParentId = rawParentId || 0;

		// 3. Only proceed if parent changed
		if (currentParentId === lastParentId) {
			return;
		}

		console.log('Service Editor: Parent changed from', lastParentId, 'to', currentParentId);
		lastParentId = currentParentId;

		// 4. Check if post has been saved with custom content
		// We only auto-switch on brand new unsaved posts (auto-draft status)
		// TODO: When implementing real ACF block patterns (Phase 5), revisit this logic to:
		//       - Allow switching on saved posts that only contain pattern blocks
		//       - Detect modified ACF field values vs defaults
		//       - Create whitelist of allowed pattern blocks for better detection
		const postStatus = select('core/editor')?.getEditedPostAttribute('status');
		const blocks = select('core/block-editor')?.getBlocks() || [];

		// Only auto-switch if:
		// 1. Post is auto-draft (never saved), OR
		// 2. Post only contains our pattern blocks (core/html with our specific content)
		const isAutoDraft = postStatus === 'auto-draft';

		// List of ACF blocks used in our service patterns
		const patternBlockNames = [
			'acf/hero-banner',
			'acf/breadcrumbs',
			'acf/service-fields-parent',
			'acf/content-group',
			'acf/bullet-group',
			'acf/services-areas-loop',
			'acf/accordion',
			'acf/cta'
		];

		// Check if all blocks are just our patterns or empty content
		const onlyHasPatternContent = blocks.every(block => {
			// Allow empty paragraphs (WordPress auto-adds these)
			if (block.name === 'core/paragraph') {
				const content = block.attributes?.content || '';
				return content.trim() === '';
			}
			// Allow ACF blocks that are part of our service patterns
			if (patternBlockNames.includes(block.name)) {
				return true;
			}
			// Any other block means user has customized content
			return false;
		});

		const isSafeToSwitch = isAutoDraft || (onlyHasPatternContent && blocks.length > 0);

		if (!isSafeToSwitch) {
			console.log('Service Editor: Post has custom content, skipping auto-pattern');
			console.log('Service Editor: Post status:', postStatus);
			console.log('Service Editor: All blocks:', blocks.map(b => b.name));
			console.log('Service Editor: Only has pattern content:', onlyHasPatternContent);
			return;
		}

		console.log('Service Editor: Allowing pattern swap');
		console.log('Service Editor: Reason - isAutoDraft:', isAutoDraft, 'onlyHasPatternContent:', onlyHasPatternContent);

		// 5. Determine which pattern to use
		const patternName = currentParentId
			? 'summit-law/service-child-pattern'
			: 'summit-law/service-parent-pattern';

		console.log('Service Editor: Applying pattern:', patternName);

		// 6. Apply the pattern
		applyPattern(patternName);
	};

	/**
	 * Apply a pattern by name
	 *
	 * @param {string} patternName - The pattern slug to apply
	 */
	const applyPattern = (patternName) => {
		isApplyingPattern = true;

		try {
			// Get pattern content from localized data (passed from PHP)
			console.log('Service Editor: Looking for pattern:', patternName);
			console.log('Service Editor: Available patterns from PHP:', window.summitServicePatterns);

			let pattern = null;

			// Determine which pattern to use based on name
			if (patternName === 'summit-law/service-parent-pattern') {
				pattern = window.summitServicePatterns?.parent;
			} else if (patternName === 'summit-law/service-child-pattern') {
				pattern = window.summitServicePatterns?.child;
			}

			if (!pattern) {
				console.error('Service Editor: Pattern not found:', patternName);
				console.error('Service Editor: Available patterns:', window.summitServicePatterns);
				isApplyingPattern = false;
				return;
			}

			console.log('Service Editor: Found pattern:', pattern);

			// Clear existing blocks
			const blocks = select('core/block-editor')?.getBlocks() || [];
			blocks.forEach(block => {
				dispatch('core/block-editor')?.removeBlock(block.clientId);
			});

			// Parse and insert pattern blocks
			const parsedBlocks = parse(pattern.content);
			if (parsedBlocks && parsedBlocks.length > 0) {
				dispatch('core/block-editor')?.insertBlocks(parsedBlocks);
				console.log('Service Editor: Pattern applied successfully');

				// Clean up any empty paragraphs that WordPress auto-added
				setTimeout(() => {
					const allBlocks = select('core/block-editor')?.getBlocks() || [];
					allBlocks.forEach(block => {
						if (block.name === 'core/paragraph') {
							const content = block.attributes?.content || '';
							if (content.trim() === '') {
								dispatch('core/block-editor')?.removeBlock(block.clientId);
								console.log('Service Editor: Removed auto-added empty paragraph');
							}
						}
					});
				}, 50);
			} else {
				console.error('Service Editor: Failed to parse pattern content');
			}
		} catch (error) {
			console.error('Service Editor: Error applying pattern:', error);
		} finally {
			// Small delay before allowing next pattern application
			setTimeout(() => {
				isApplyingPattern = false;
			}, 100);
		}
	};

	/**
	 * Initialize on editor load
	 */
	let initAttempts = 0;
	const MAX_INIT_ATTEMPTS = 50; // Try for up to 5 seconds

	const initialize = () => {
		initAttempts++;
		console.log(`Service Editor: Initialize called (attempt ${initAttempts}/${MAX_INIT_ATTEMPTS})`);

		// Wait for editor to be fully loaded
		if (!select('core/editor') || !select('core/block-editor')) {
			if (initAttempts < MAX_INIT_ATTEMPTS) {
				console.log('Service Editor: Stores not ready, retrying in 100ms...');
				setTimeout(initialize, 100);
			} else {
				console.error('Service Editor: Stores never became ready, giving up');
			}
			return;
		}

		console.log('Service Editor: Stores are ready');

		const postType = select('core/editor')?.getCurrentPostType();
		console.log('Service Editor: Current post type:', postType);

		// If post type is null, wait and retry (post data not loaded yet)
		if (postType === null && initAttempts < MAX_INIT_ATTEMPTS) {
			console.log('Service Editor: Post type is null, waiting for post data to load...');
			setTimeout(initialize, 100);
			return;
		}

		if (postType !== 'service') {
			console.log('Service Editor: Not a service post type, exiting');
			return;
		}

		// Get initial parent value (normalize null/undefined to 0)
		const rawInitialParentId = select('core/editor')?.getEditedPostAttribute('parent');
		const initialParentId = rawInitialParentId || 0;

		console.log('Service Editor: ✅ Initialized for service post type');
		console.log('Service Editor: Initial parent ID:', initialParentId);

		// Apply pattern immediately if this is a new post
		const blocks = select('core/block-editor')?.getBlocks() || [];
		const postId = select('core/editor')?.getCurrentPostId();
		const postStatus = select('core/editor')?.getEditedPostAttribute('status');

		console.log('Service Editor: Post ID:', postId);
		console.log('Service Editor: Post status:', postStatus);
		console.log('Service Editor: Number of blocks:', blocks.length);
		console.log('Service Editor: Blocks:', blocks.map(b => b.name));

		// Apply pattern only on brand new auto-draft posts
		// Once a post is saved, we preserve all content
		const isAutoDraft = postStatus === 'auto-draft';

		if (isAutoDraft) {
			console.log('Service Editor: 🎯 New unsaved post detected, applying initial pattern');

			// Determine which pattern to use based on initial parent
			const patternName = initialParentId
				? 'summit-law/service-child-pattern'
				: 'summit-law/service-parent-pattern';

			console.log('Service Editor: Applying initial pattern:', patternName);
			applyPattern(patternName);

			// Set lastParentId AFTER initial pattern application
			lastParentId = initialParentId;
		} else {
			console.log('Service Editor: Post has been saved, preserving existing content');
			// Still set lastParentId for existing posts
			lastParentId = initialParentId;
		}

		// Subscribe to editor changes
		console.log('Service Editor: Subscribing to editor changes');
		subscribe(maybeApplyPattern);
	};

	/**
	 * Register Help Panel in Sidebar
	 */
	const registerHelpPanel = () => {
		// Only register if we have the required APIs
		if (!registerPlugin || !PluginDocumentSettingPanel) {
			console.log('Service Editor: Plugin API not available, skipping help panel');
			return;
		}

		const HelpPanel = () => {
			// Only show on service post type
			const postType = select('core/editor')?.getCurrentPostType();
			if (postType !== 'service') {
				return null;
			}

			return el(
				PluginDocumentSettingPanel,
				{
					name: 'service-pattern-help',
					title: 'Service Pattern Info',
					className: 'service-pattern-help-panel'
				},
				el('div', {
					style: {
						fontSize: '13px',
						lineHeight: '1.6',
						color: '#1e1e1e'
					}
				},
					el('p', { style: { marginTop: '0', marginBottom: '12px', fontWeight: '500' } },
						'⚡ Automatic Pattern Templates'
					),
					el('p', { style: { marginTop: '0', marginBottom: '8px' } },
						'This Service post uses automatic pattern templates based on parent selection:'
					),
					el('ul', {
						style: {
							marginTop: '0',
							marginBottom: '12px',
							paddingLeft: '20px',
							listStyleType: 'disc'
						}
					},
						el('li', { style: { marginBottom: '4px' } },
							el('strong', {}, 'No Parent:'),
							' Loads Parent Service pattern'
						),
						el('li', { style: { marginBottom: '4px' } },
							el('strong', {}, 'Parent Selected:'),
							' Loads Child Service pattern'
						)
					),
					el('div', {
						style: {
							padding: '10px',
							backgroundColor: '#f0f6fc',
							borderLeft: '3px solid #0969da',
							marginBottom: '0',
							borderRadius: '3px'
						}
					},
						el('p', {
							style: {
								margin: '0 0 8px 0',
								fontSize: '12px',
								fontWeight: '600',
								color: '#0969da'
							}
						}, '💡 Important'),
						el('p', { style: { margin: '0 0 6px 0', fontSize: '12px' } },
							'Automatic pattern switching only occurs on ',
							el('strong', {}, 'new unsaved posts'),
							' or posts containing only pattern content.'
						),
						el('p', { style: { margin: '0', fontSize: '12px' } },
							el('strong', {}, 'Once you save with custom blocks, your content is locked and preserved.'),
							' Pattern switching will stop to protect your work.'
						)
					)
				)
			);
		};

		// Register the plugin
		registerPlugin('service-pattern-help', {
			render: HelpPanel,
			icon: null
		});

		console.log('Service Editor: Help panel registered');
	};

	// Start initialization when DOM is ready
	console.log('Service Editor: Setting up initialization, document.readyState:', document.readyState);

	if (document.readyState === 'loading') {
		console.log('Service Editor: Waiting for DOMContentLoaded');
		document.addEventListener('DOMContentLoaded', () => {
			initialize();
			registerHelpPanel();
		});
	} else {
		console.log('Service Editor: DOM already ready, initializing now');
		initialize();
		registerHelpPanel();
	}

})(window.wp);
