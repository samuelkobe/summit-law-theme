/**
 * Banner Line Animation
 * Animates the decorative line in banner sections
 * Runs once per session (persists until browser refresh)
 */

export default class BannerAnimation {
  constructor() {
    // Use current page path as part of the key so animation plays once per page
    const currentPath = window.location.pathname;
    this.sessionKey = `summit-banner-animated-${currentPath}`;
    this.init();
  }

  init() {
    // Check if animation has already played on this specific page in this session
    const hasAnimated = sessionStorage.getItem(this.sessionKey);

    console.log('Banner Animation Check:', {
      hasAnimated: hasAnimated,
      sessionStorageValue: sessionStorage.getItem(this.sessionKey),
      sessionStorageKeys: Object.keys(sessionStorage)
    });

    // Find all banner elements and h2 line elements
    const banners = document.querySelectorAll('.banner-after-content');
    const h2Lines = document.querySelectorAll('.h2-line-after');
    const allElements = [...banners, ...h2Lines];

    if (!allElements.length) {
      console.log('No banner or h2 line elements found');
      return;
    }

    console.log(`Found ${banners.length} banner element(s) and ${h2Lines.length} h2 line element(s)`);

    if (hasAnimated) {
      // Skip animation, just show the line
      console.log('Animation already played - showing lines immediately');
      allElements.forEach(element => {
        element.classList.add('show-line');
      });
    } else {
      // Run the animation
      console.log('First visit - running animation');
      allElements.forEach((element, index) => {
        // Stagger the h2 animations slightly
        const delay = element.classList.contains('h2-line-after') ? 100 + (index * 50) : 100;
        setTimeout(() => {
          element.classList.add('animate-line');
        }, delay);
      });

      // Mark as animated in session storage
      sessionStorage.setItem(this.sessionKey, 'true');
      console.log('Marked animation as complete in sessionStorage');
    }
  }
}
