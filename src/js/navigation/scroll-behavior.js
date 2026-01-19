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
    this.header = document.querySelector("[data-header]");
    this.logo = document.querySelector("[data-logo]");
    this.isHomePage = document.body.classList.contains("home");
    this.isMobile = window.innerWidth < 1024; // Tailwind lg breakpoint
    this.lastScroll = 0;
    this.scrollThreshold = this.isHomePage ? window.innerHeight * 0.75 : 160; // 75% of viewport height on home, 160px elsewhere
    this.logoTransitionPoint = 80;
    this.ticking = false;
    this.scrollListenerActive = false;
    this.boundRequestTick = () => this.requestTick();

    this.init();
  }

  /**
   * Initialize scroll behavior
   */
  init() {
    if (!this.header) {
      return;
    }

    // Apply initial state based on current scroll position (for page refresh while scrolled)
    // This runs for both mobile and desktop to set correct logo/header state
    this.applyScrollState();

    // Reveal header after state is applied (prevents flash of wrong state)
    this.header.classList.add("header--ready");

    // Always add resize listener to handle viewport changes
    window.addEventListener("resize", () => this.handleResize());

    // Add scroll listener if on desktop
    if (!this.isMobile) {
      this.addScrollListener();
    }
  }

  /**
   * Add scroll event listener
   */
  addScrollListener() {
    if (!this.scrollListenerActive) {
      window.addEventListener("scroll", this.boundRequestTick);
      this.scrollListenerActive = true;
    }
  }

  /**
   * Remove scroll event listener
   */
  removeScrollListener() {
    if (this.scrollListenerActive) {
      window.removeEventListener("scroll", this.boundRequestTick);
      this.scrollListenerActive = false;
    }
  }

  /**
   * Apply scroll-based state without affecting hide/show
   * Used on initial load to handle page refresh while scrolled
   */
  applyScrollState() {
    const currentScroll = window.pageYOffset;

    // On mobile, always use light header - no dark/light switching
    if (this.isMobile) {
      if (this.isHomePage) {
        this.header.classList.remove("header--dark");
        this.header.classList.add("header--light");
      }
      this.lastScroll = currentScroll;
      return;
    }

    // Desktop only: Logo resize (home page only)
    if (this.isHomePage && this.logo) {
      if (currentScroll >= this.logoTransitionPoint) {
        this.logo.classList.add("logo--small");
      } else {
        this.logo.classList.remove("logo--small");
      }
    }

    // Desktop only: Menu color switch (home page only)
    if (this.isHomePage) {
      if (currentScroll >= this.logoTransitionPoint) {
        this.header.classList.remove("header--dark");
        this.header.classList.add("header--light");
      } else {
        this.header.classList.add("header--dark");
        this.header.classList.remove("header--light");
      }
    }

    // Set lastScroll so first scroll event calculates direction correctly
    this.lastScroll = currentScroll;
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
        this.logo.classList.add("logo--small");
      } else {
        this.logo.classList.remove("logo--small");
      }
    }

    // Menu color switch (home page only)
    if (this.isHomePage) {
      if (currentScroll >= this.logoTransitionPoint) {
        this.header.classList.remove("header--dark");
        this.header.classList.add("header--light");
      } else {
        this.header.classList.add("header--dark");
        this.header.classList.remove("header--light");
      }
    }

    // Menu hide/show on scroll
    if (currentScroll > this.scrollThreshold) {
      if (currentScroll > this.lastScroll) {
        // Scrolling down - hide menu
        this.header.classList.add("header--hidden");
      } else {
        // Scrolling up - show menu
        this.header.classList.remove("header--hidden");
      }
    } else {
      // Near top - always show
      this.header.classList.remove("header--hidden");
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

    // Recalculate scroll threshold based on new viewport height
    this.scrollThreshold = this.isHomePage ? window.innerHeight * 0.75 : 160;

    // If switching to mobile, remove scroll listener and reset to light header
    if (this.isMobile && !wasMobile) {
      this.removeScrollListener();
      this.header.classList.remove("header--hidden");
      if (this.logo) {
        this.logo.classList.remove("logo--small");
      }
      // Always use light header on mobile (even on home page)
      if (this.isHomePage) {
        this.header.classList.remove("header--dark");
        this.header.classList.add("header--light");
      }
    }

    // If switching to desktop, add scroll listener and apply current scroll state
    if (!this.isMobile && wasMobile) {
      this.addScrollListener();
      this.applyScrollState();
      this.handleScroll();
    }
  }
}
