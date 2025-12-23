/**
 * Mobile Menu Navigation
 *
 * Handles mobile menu drawer interactions:
 * - Open/close mobile drawer
 * - Expand/collapse submenus inline
 * - Lock body scroll when open
 * - Focus management
 */

import ScrollLock from '../utils/scroll-lock.js';
import FocusTrap from '../utils/focus-trap.js';

export default class MobileMenu {
  constructor() {
    this.toggle = document.querySelector('[data-mobile-toggle]');
    this.menu = document.querySelector('[data-mobile-menu]');
    this.submenuToggles = document.querySelectorAll('[data-mobile-submenu-toggle]');
    this.focusTrap = null;

    this.init();
  }

  /**
   * Initialize mobile menu
   */
  init() {
    if (!this.toggle || !this.menu) {
      console.log('MobileMenu: Toggle or menu not found', {
        toggle: !!this.toggle,
        menu: !!this.menu
      });
      return;
    }

    console.log(`MobileMenu: Initialized with ${this.submenuToggles.length} submenu toggles`);

    // Mobile menu drawer toggle
    this.toggle.addEventListener('click', () => this.toggleMenu());

    // Submenu toggles
    this.submenuToggles.forEach(toggle => {
      toggle.addEventListener('click', (e) => this.toggleSubmenu(e));
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => this.handleEscape(e));
  }

  /**
   * Toggle mobile menu drawer
   */
  toggleMenu() {
    const isExpanded = this.toggle.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      this.closeMenu();
    } else {
      this.openMenu();
    }
  }

  /**
   * Open mobile menu
   */
  openMenu() {
    // Show menu
    this.menu.removeAttribute('hidden');

    // Update ARIA
    this.toggle.setAttribute('aria-expanded', 'true');

    // Lock body scroll
    ScrollLock.lock();

    // Trap focus
    this.focusTrap = new FocusTrap(this.menu);
    this.focusTrap.activate();
  }

  /**
   * Close mobile menu
   */
  closeMenu() {
    // Hide menu
    this.menu.setAttribute('hidden', '');

    // Update ARIA
    this.toggle.setAttribute('aria-expanded', 'false');

    // Unlock body scroll
    ScrollLock.unlock();

    // Release focus trap
    if (this.focusTrap) {
      this.focusTrap.deactivate();
      this.focusTrap = null;
    }

    // Return focus to toggle
    this.toggle.focus();

    // Close all open submenus
    this.closeAllSubmenus();
  }

  /**
   * Toggle submenu
   *
   * @param {Event} event Click event
   */
  toggleSubmenu(event) {
    const button = event.currentTarget;
    const submenuId = button.getAttribute('aria-controls');
    const submenu = document.getElementById(submenuId);

    if (!submenu) {
      return;
    }

    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      // Close submenu
      submenu.setAttribute('hidden', '');
      button.setAttribute('aria-expanded', 'false');
    } else {
      // Open submenu
      submenu.removeAttribute('hidden');
      button.setAttribute('aria-expanded', 'true');
    }
  }

  /**
   * Close all submenus
   */
  closeAllSubmenus() {
    this.submenuToggles.forEach(toggle => {
      const submenuId = toggle.getAttribute('aria-controls');
      const submenu = document.getElementById(submenuId);

      if (submenu) {
        submenu.setAttribute('hidden', '');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * Handle Escape key
   *
   * @param {KeyboardEvent} event Keyboard event
   */
  handleEscape(event) {
    if (event.key === 'Escape') {
      const isMenuOpen = this.toggle.getAttribute('aria-expanded') === 'true';

      if (isMenuOpen) {
        this.closeMenu();
      }
    }
  }
}
