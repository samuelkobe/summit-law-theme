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
    document.body.style.overflow = "hidden";
    document.body.classList.add("scroll-locked");

    // Add padding to compensate for scrollbar removal
    if (scrollbarWidth > 0) {
      document.body.style.paddingRight = scrollbarWidth + "px";

      // Also add padding to fixed header to prevent shift
      // Disable transitions temporarily to prevent animation
      const header = document.querySelector("[data-header]");
      if (header) {
        const originalTransition = header.style.transition;
        header.style.transition = "none";
        header.style.paddingRight = scrollbarWidth + "px";
        // Force reflow to apply the change immediately
        header.offsetHeight;
        header.style.transition = originalTransition;
      }
    }
  }

  /**
   * Unlock body scroll
   */
  static unlock() {
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";
    document.body.classList.remove("scroll-locked");

    // Remove padding from fixed header
    // Disable transitions temporarily to prevent animation
    const header = document.querySelector("[data-header]");
    if (header) {
      const originalTransition = header.style.transition;
      header.style.transition = "none";
      header.style.paddingRight = "";
      // Force reflow to apply the change immediately
      header.offsetHeight;
      header.style.transition = originalTransition;
    }
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
