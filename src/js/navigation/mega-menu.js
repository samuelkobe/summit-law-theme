/**
 * Mega Menu Navigation
 *
 * Handles desktop mega menu interactions:
 * - Click to toggle open/close
 * - Close on outside click
 * - Close on Escape key
 * - Only one menu open at a time
 * - Focus management
 */

import FocusTrap from '../utils/focus-trap.js';

export default class MegaMenu {
  constructor() {
    this.toggleButtons = document.querySelectorAll('[data-mega-toggle]');
    this.menus = document.querySelectorAll('[data-mega-menu]');
    this.activeMenu = null;
    this.activeTrap = null;
    this.activeButton = null;

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
    // Show menu
    menu.removeAttribute('hidden');

    // Update ARIA
    button.setAttribute('aria-expanded', 'true');

    // Set active menu
    this.activeMenu = menu;
    this.activeButton = button;

    // Trap focus
    this.activeTrap = new FocusTrap(menu);
    this.activeTrap.activate();
  }

  /**
   * Close mega menu
   *
   * @param {HTMLElement} menu Menu element
   * @param {HTMLElement} button Toggle button
   */
  close(menu, button) {
    // Hide menu
    menu.setAttribute('hidden', '');

    // Update ARIA
    button.setAttribute('aria-expanded', 'false');

    // Release focus trap
    if (this.activeTrap) {
      this.activeTrap.deactivate();
      this.activeTrap = null;
    }

    // Return focus to button
    button.focus();

    // Clear active menu
    this.activeMenu = null;
    this.activeButton = null;
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
