/**
 * Bullet Group Styling
 * Applies padding styles to the first bullet_group in each consecutive group
 */

export default class BulletGroupStyling {
  constructor() {
    this.init();
  }

  /**
   * Find the previous meaningful sibling, skipping style tags and text nodes
   */
  getPreviousMeaningfulSibling(element) {
    let sibling = element.previousElementSibling;

    while (sibling) {
      // Skip style tags (ACF blocks generate these)
      if (sibling.tagName === 'STYLE') {
        sibling = sibling.previousElementSibling;
        continue;
      }

      // Found a meaningful element
      return sibling;
    }

    return null;
  }

  /**
   * Find the next meaningful sibling, skipping style tags and text nodes
   */
  getNextMeaningfulSibling(element) {
    let sibling = element.nextElementSibling;

    while (sibling) {
      // Skip style tags (ACF blocks generate these)
      if (sibling.tagName === 'STYLE') {
        sibling = sibling.nextElementSibling;
        continue;
      }

      // Found a meaningful element
      return sibling;
    }

    return null;
  }

  init() {
    // Find all bullet_group blocks
    const bulletGroups = document.querySelectorAll('.block-bullet_group');

    if (!bulletGroups.length) {
      return;
    }

    // Track whether the previous/next meaningful siblings are bullet_groups
    bulletGroups.forEach((group) => {
      const previousElement = this.getPreviousMeaningfulSibling(group);
      const nextElement = this.getNextMeaningfulSibling(group);

      // Check if previous meaningful sibling is also a bullet_group
      const isPreviousBulletGroup = previousElement && previousElement.classList.contains('block-bullet_group');

      // Check if next meaningful sibling is also a bullet_group
      const isNextBulletGroup = nextElement && nextElement.classList.contains('block-bullet_group');

      // If there's no previous bullet_group, this is the first in a group
      if (!isPreviousBulletGroup) {
        group.classList.add('bullet-group-first');
      } else {
        group.classList.remove('bullet-group-first');
      }

      // If there's no next bullet_group, this is the last in a group
      if (!isNextBulletGroup) {
        group.classList.add('bullet-group-last');
      } else {
        group.classList.remove('bullet-group-last');
      }
    });
  }
}
