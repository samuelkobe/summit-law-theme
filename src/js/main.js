// Summit Law Theme - Main JavaScript

// Import navigation modules
import MegaMenu from './navigation/mega-menu.js';
import MobileMenu from './navigation/mobile-menu.js';
import SearchOverlay from './navigation/search-overlay.js';
import ScrollBehavior from './navigation/scroll-behavior.js';
import BannerAnimation from './banner-animation.js';
import BulletGroupStyling from './bullet-group.js';

document.addEventListener('DOMContentLoaded', function() {
  console.log('Summit Law Theme loaded');

  // Initialize navigation modules
  new MegaMenu();
  new MobileMenu();
  new SearchOverlay();
  new ScrollBehavior();

  // Initialize banner animation
  new BannerAnimation();

  // Initialize bullet group styling
  new BulletGroupStyling();
});

// Focus first error on submit for accessibility
document.addEventListener("submit", (e) => {
  const form = e.target.closest("form");
  if (!form) return;
  const firstError = form.querySelector("[data-error]");
  if (firstError) firstError.focus();
});
