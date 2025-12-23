/**
 * Focus Trap Utility
 *
 * Traps keyboard focus within a container element
 * Used for modals, overlays, and accessible navigation
 */

export default class FocusTrap {
  /**
   * Constructor
   *
   * @param {HTMLElement} element Container element to trap focus within
   */
  constructor(element) {
    this.element = element;
    this.focusableElements = null;
    this.firstFocusable = null;
    this.lastFocusable = null;
    this.handleTabBound = this.handleTab.bind(this);
  }

  /**
   * Activate focus trap
   */
  activate() {
    // Find all focusable elements
    this.focusableElements = this.element.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );

    if (this.focusableElements.length === 0) {
      return;
    }

    // Store first and last focusable elements
    this.firstFocusable = this.focusableElements[0];
    this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];

    // Add keydown listener
    this.element.addEventListener('keydown', this.handleTabBound);

    // Focus first element
    this.firstFocusable.focus();
  }

  /**
   * Deactivate focus trap
   */
  deactivate() {
    this.element.removeEventListener('keydown', this.handleTabBound);
  }

  /**
   * Handle Tab key press
   *
   * @param {KeyboardEvent} event Keyboard event
   */
  handleTab(event) {
    if (event.key !== 'Tab') {
      return;
    }

    if (event.shiftKey) {
      // Shift + Tab (backwards)
      if (document.activeElement === this.firstFocusable) {
        event.preventDefault();
        this.lastFocusable.focus();
      }
    } else {
      // Tab (forwards)
      if (document.activeElement === this.lastFocusable) {
        event.preventDefault();
        this.firstFocusable.focus();
      }
    }
  }
}
