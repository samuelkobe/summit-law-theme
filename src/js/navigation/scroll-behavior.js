/**
 * Scroll Behavior
 *
 * Handles scroll-based header behaviors:
 * - Logo resize on home page (desktop only)
 * - Menu color switch on home page (desktop only)
 * - Hide/show menu on scroll (desktop only)
 * - Mobile: always sticky, no changes
 */

export default class ScrollBehavior {
  constructor() {
    this.header = document.querySelector('[data-header]');
    this.logo = document.querySelector('[data-logo]');
    this.isHomePage = document.body.classList.contains('home');
    this.isMobile = window.innerWidth < 1024; // Tailwind lg breakpoint
    this.lastScroll = 0;
    this.scrollThreshold = 160;
    this.logoTransitionPoint = 80;
    this.ticking = false;

    this.init();
  }

  /**
   * Initialize scroll behavior
   */
  init() {
    if (!this.header) {
      return;
    }

    // Skip scroll behavior on mobile
    if (this.isMobile) {
      return;
    }

    // Add scroll listener with requestAnimationFrame for performance
    window.addEventListener('scroll', () => this.requestTick());

    // Handle resize (recalculate isMobile)
    window.addEventListener('resize', () => this.handleResize());
  }

  /**
   * Request animation frame tick
   */
  requestTick() {
    if (!this.ticking) {
      requestAnimationFrame(() => this.handleScroll());
      this.ticking = true;
    }
  }

  /**
   * Handle scroll event
   */
  handleScroll() {
    const currentScroll = window.pageYOffset;

    // Logo resize (home page only)
    if (this.isHomePage && this.logo) {
      if (currentScroll >= this.logoTransitionPoint) {
        this.logo.classList.add('logo--small');
      } else {
        this.logo.classList.remove('logo--small');
      }
    }

    // Menu color switch (home page only)
    if (this.isHomePage) {
      if (currentScroll >= this.logoTransitionPoint) {
        this.header.classList.remove('header--dark');
        this.header.classList.add('header--light');
      } else {
        this.header.classList.add('header--dark');
        this.header.classList.remove('header--light');
      }
    }

    // Menu hide/show on scroll
    if (currentScroll > this.scrollThreshold) {
      if (currentScroll > this.lastScroll) {
        // Scrolling down - hide menu
        this.header.classList.add('header--hidden');
      } else {
        // Scrolling up - show menu
        this.header.classList.remove('header--hidden');
      }
    } else {
      // Near top - always show
      this.header.classList.remove('header--hidden');
    }

    this.lastScroll = currentScroll;
    this.ticking = false;
  }

  /**
   * Handle window resize
   */
  handleResize() {
    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth < 1024;

    // If switching to mobile, remove all desktop scroll classes
    if (this.isMobile && !wasMobile) {
      this.header.classList.remove('header--hidden');
      if (this.logo) {
        this.logo.classList.remove('logo--small');
      }
    }

    // If switching to desktop, reapply scroll behavior
    if (!this.isMobile && wasMobile) {
      this.handleScroll();
    }
  }
}
