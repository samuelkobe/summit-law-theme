/**
 * Search Overlay
 *
 * Handles search overlay interactions:
 * - Open/close search overlay (desktop)
 * - Focus search input (mobile)
 * - Lock body scroll when open
 * - Close on outside click
 * - Close on Escape key
 */

import ScrollLock from '../utils/scroll-lock.js';
import FocusTrap from '../utils/focus-trap.js';

export default class SearchOverlay {
  constructor() {
    this.toggleButton = document.querySelector('[data-search-toggle]');
    this.overlay = document.querySelector('[data-search-overlay]');
    this.backdrop = document.querySelector('[data-search-backdrop]');
    this.closeButton = document.querySelector('[data-search-close]');
    this.searchInput = document.querySelector('[data-search-input]');
    this.mobileSearchInput = document.querySelector('[data-mobile-search-input]');
    this.focusTrap = null;

    this.init();
  }

  /**
   * Initialize search overlay
   */
  init() {
    // Desktop search toggle
    if (this.toggleButton && this.overlay) {
      this.toggleButton.addEventListener('click', () => this.openOverlay());
    }

    // Close button
    if (this.closeButton) {
      this.closeButton.addEventListener('click', () => this.closeOverlay());
    }

    // Close on outside click
    if (this.overlay) {
      this.overlay.addEventListener('click', (e) => this.handleOutsideClick(e));
    }

    // Close on backdrop click
    if (this.backdrop) {
      this.backdrop.addEventListener('click', () => this.closeOverlay());
    }

    // Close on Escape
    document.addEventListener('keydown', (e) => this.handleEscape(e));
  }

  /**
   * Open search overlay (desktop)
   */
  openOverlay() {
    if (!this.overlay) {
      return;
    }

    // Show overlay
    this.overlay.removeAttribute('hidden');

    // Show backdrop
    if (this.backdrop) {
      this.backdrop.removeAttribute('hidden');
    }

    // Update ARIA
    this.toggleButton.setAttribute('aria-expanded', 'true');

    // Lock body scroll
    ScrollLock.lock();

    // Focus search input
    if (this.searchInput) {
      this.searchInput.focus();
    }

    // Trap focus
    this.focusTrap = new FocusTrap(this.overlay);
    this.focusTrap.activate();
  }

  /**
   * Close search overlay
   */
  closeOverlay() {
    if (!this.overlay) {
      return;
    }

    // Hide overlay
    this.overlay.setAttribute('hidden', '');

    // Hide backdrop
    if (this.backdrop) {
      this.backdrop.setAttribute('hidden', '');
    }

    // Update ARIA
    this.toggleButton.setAttribute('aria-expanded', 'false');

    // Unlock body scroll
    ScrollLock.unlock();

    // Release focus trap
    if (this.focusTrap) {
      this.focusTrap.deactivate();
      this.focusTrap = null;
    }

    // Return focus to toggle button
    this.toggleButton.focus();

    // Clear search input
    if (this.searchInput) {
      this.searchInput.value = '';
    }
  }

  /**
   * Handle outside click
   *
   * @param {Event} event Click event
   */
  handleOutsideClick(event) {
    // Only close if clicking the overlay background (not its children)
    if (event.target === this.overlay) {
      this.closeOverlay();
    }
  }

  /**
   * Handle Escape key
   *
   * @param {KeyboardEvent} event Keyboard event
   */
  handleEscape(event) {
    if (event.key === 'Escape') {
      const isOverlayOpen = this.overlay && !this.overlay.hasAttribute('hidden');

      if (isOverlayOpen) {
        this.closeOverlay();
      }
    }
  }
}
