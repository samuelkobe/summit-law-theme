// Summit Law Theme - Main JavaScript

// Import navigation modules
import MegaMenu from './navigation/mega-menu.js';
import MobileMenu from './navigation/mobile-menu.js';
import SearchOverlay from './navigation/search-overlay.js';
import ScrollBehavior from './navigation/scroll-behavior.js';
import BannerAnimation from './banner-animation.js';
import BulletGroupStyling from './bullet-group.js';
import MediationIntake from './mediation-intake.js';

document.addEventListener('DOMContentLoaded', function() {
  // Initialize navigation modules
  new MegaMenu();
  new MobileMenu();
  new SearchOverlay();
  new ScrollBehavior();

  // Initialize banner animation
  new BannerAnimation();

  // Initialize bullet group styling
  new BulletGroupStyling();

  // Initialize mediation intake form (injects into Amelia booking forms)
  new MediationIntake();
});

// Focus first error on submit for accessibility
document.addEventListener("submit", (e) => {
  const form = e.target.closest("form");
  if (!form) return;
  const firstError = form.querySelector("[data-error]");
  if (firstError) firstError.focus();
});

// Escape key blurs focused element (when no overlay is open)
document.addEventListener("keydown", (e) => {
  if (e.key !== "Escape") return;

  // Don't interfere if an overlay/menu is open (they have their own handlers)
  const mobileMenuOpen = document.querySelector('[data-mobile-menu]:not([hidden])');
  const searchOpen = document.querySelector('.search-overlay:not([hidden])');
  const megaMenuOpen = document.querySelector('.mega-menu.is-open');

  if (mobileMenuOpen || searchOpen || megaMenuOpen) return;

  // Blur the currently focused element
  if (document.activeElement && document.activeElement !== document.body) {
    document.activeElement.blur();
  }
});
