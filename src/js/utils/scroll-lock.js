/**
 * Scroll Lock Utility
 *
 * Prevents body scrolling when overlays are open
 * Compensates for scrollbar width to prevent layout shift
 */

export default class ScrollLock {
  /**
   * Lock body scroll
   */
  static lock() {
    // Get scrollbar width before locking
    const scrollbarWidth = this.getScrollbarWidth();

    // Lock scroll
    document.body.style.overflow = 'hidden';

    // Add padding to compensate for scrollbar removal
    if (scrollbarWidth > 0) {
      document.body.style.paddingRight = scrollbarWidth + 'px';
    }
  }

  /**
   * Unlock body scroll
   */
  static unlock() {
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  }

  /**
   * Get scrollbar width
   *
   * @returns {number} Scrollbar width in pixels
   */
  static getScrollbarWidth() {
    return window.innerWidth - document.documentElement.clientWidth;
  }
}
