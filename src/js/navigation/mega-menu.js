/**
 * Mega Menu Navigation
 *
 * Handles desktop mega menu interactions:
 * - Click to toggle open/close
 * - Close on outside click
 * - Close on Escape key
 * - Close on scroll
 * - Only one menu open at a time
 * - Focus management
 */

import FocusTrap from '../utils/focus-trap.js';

export default class MegaMenu {
  constructor() {
    this.toggleButtons = document.querySelectorAll('[data-mega-toggle]');
    this.menus = document.querySelectorAll('[data-mega-menu]');
    this.header = document.querySelector('[data-header]');
    this.activeMenu = null;
    this.activeTrap = null;
    this.activeButton = null;
    this.scrollHandler = null;
    this.scrollStartPosition = null;
    this.scrollThreshold = 10; // Minimum scroll distance before closing menu

    this.init();
  }

  /**
   * Initialize mega menu
   */
  init() {
    if (this.toggleButtons.length === 0) {
      console.log('MegaMenu: No toggle buttons found');
      return;
    }

    console.log(`MegaMenu: Found ${this.toggleButtons.length} toggle buttons`);
    console.log(`MegaMenu: Found ${this.menus.length} mega menus`);

    // Add click listeners to toggle buttons
    this.toggleButtons.forEach(button => {
      button.addEventListener('click', (e) => this.handleToggleClick(e));
    });

    // Close on outside click
    document.addEventListener('click', (e) => this.handleOutsideClick(e));

    // Close on Escape key
    document.addEventListener('keydown', (e) => this.handleEscape(e));

    // Create scroll handler (will be added/removed when menu opens/closes)
    this.scrollHandler = () => this.handleScroll();
  }

  /**
   * Handle toggle button click
   *
   * @param {Event} event Click event
   */
  handleToggleClick(event) {
    const button = event.currentTarget;
    const menuId = button.getAttribute('aria-controls');
    const menu = document.getElementById(menuId);

    console.log('MegaMenu: Button clicked', {
      menuId,
      menuFound: !!menu,
      buttonText: button.textContent.trim()
    });

    if (!menu) {
      console.error(`MegaMenu: Menu not found with ID: ${menuId}`);
      return;
    }

    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      this.close(menu, button);
    } else {
      // Close any open menus first
      if (this.activeMenu) {
        this.close(this.activeMenu, this.activeButton);
      }
      this.open(menu, button);
    }
  }

  /**
   * Open mega menu
   *
   * @param {HTMLElement} menu Menu element
   * @param {HTMLElement} button Toggle button
   */
  open(menu, button) {
    // Remove hidden for accessibility
    menu.removeAttribute('hidden');

    // Add class to header so it gets white background (needed for dark header on home page)
    if (this.header) {
      this.header.classList.add('has-mega-open');

      // Position the mega menu directly below the header
      const headerRect = this.header.getBoundingClientRect();
      menu.style.top = `${headerRect.bottom}px`;
    }

    // Make visible first
    menu.classList.add('is-visible');

    // Force reflow so browser registers the visibility change before adding is-open
    menu.offsetHeight;

    // Now trigger the fade-in animation
    menu.classList.add('is-open');

    // Update ARIA
    button.setAttribute('aria-expanded', 'true');

    // Set active menu
    this.activeMenu = menu;
    this.activeButton = button;

    // Trap focus
    this.activeTrap = new FocusTrap(menu);
    this.activeTrap.activate();

    // Store current scroll position and add scroll listener to close on scroll
    this.scrollStartPosition = window.pageYOffset;
    window.addEventListener('scroll', this.scrollHandler, { passive: true });
  }

  /**
   * Close mega menu
   *
   * @param {HTMLElement} menu Menu element
   * @param {HTMLElement} button Toggle button
   * @param {boolean} returnFocus Whether to return focus to button (default true)
   */
  close(menu, button, returnFocus = true) {
    // Remove is-open to trigger slide-up animation (menu stays visible during transition)
    menu.classList.remove('is-open');

    // After transition completes, hide visibility, add hidden attribute, and remove header class
    menu.addEventListener('transitionend', () => {
      if (!menu.classList.contains('is-open')) {
        menu.classList.remove('is-visible');
        menu.setAttribute('hidden', '');
        menu.style.top = ''; // Clear inline position

        // Remove header class after menu is fully closed
        if (this.header) {
          this.header.classList.remove('has-mega-open');
        }
      }
    }, { once: true });

    // Update ARIA
    button.setAttribute('aria-expanded', 'false');

    // Release focus trap
    if (this.activeTrap) {
      this.activeTrap.deactivate();
      this.activeTrap = null;
    }

    // Return focus to button (skip when closing due to scroll)
    if (returnFocus) {
      button.focus();
    }

    // Remove scroll listener
    window.removeEventListener('scroll', this.scrollHandler);

    // Clear active menu and scroll tracking
    this.activeMenu = null;
    this.activeButton = null;
    this.scrollStartPosition = null;
  }

  /**
   * Close all menus
   */
  closeAll() {
    if (this.activeMenu && this.activeButton) {
      this.close(this.activeMenu, this.activeButton);
    }
  }

  /**
   * Handle outside click
   *
   * @param {Event} event Click event
   */
  handleOutsideClick(event) {
    if (!this.activeMenu) {
      return;
    }

    const clickedInside = this.activeMenu.contains(event.target) ||
                          this.activeButton.contains(event.target);

    if (!clickedInside) {
      this.close(this.activeMenu, this.activeButton);
    }
  }

  /**
   * Handle scroll event - close menu when user scrolls past threshold
   */
  handleScroll() {
    if (this.activeMenu && this.activeButton) {
      // Only close if user has scrolled more than the threshold
      // This prevents closing on tiny scroll movements from reflow
      const scrollDistance = Math.abs(window.pageYOffset - this.scrollStartPosition);
      if (scrollDistance > this.scrollThreshold) {
        // Close without returning focus (user is scrolling, not navigating)
        this.close(this.activeMenu, this.activeButton, false);
      }
    }
  }

  /**
   * Handle Escape key
   *
   * @param {KeyboardEvent} event Keyboard event
   */
  handleEscape(event) {
    if (event.key === 'Escape' && this.activeMenu) {
      this.close(this.activeMenu, this.activeButton);
    }
  }
}
