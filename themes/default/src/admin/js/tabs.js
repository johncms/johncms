/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * Tabs that do not fit a narrow screen scroll sideways (scss/ui/_navs.scss), so the open one may
 * be out of sight. The row is scrolled to it when the page opens and when a live list puts its
 * tabs back (live-list.js).
 */
function revealActiveTab(nav) {
  const link = nav.querySelector('.nav-link.active');

  if (!link || nav.scrollWidth <= nav.clientWidth) {
    return;
  }

  const navBox = nav.getBoundingClientRect();
  const linkBox = link.getBoundingClientRect();

  // Centred, so the tabs cut off on either side show that the row goes on.
  nav.scrollLeft += linkBox.left - navBox.left - (navBox.width - linkBox.width) / 2;
}

function revealIn(scope) {
  scope.querySelectorAll('.nav-tabs').forEach(revealActiveTab);
}

revealIn(document);

document.addEventListener('live-list:update', event => revealIn(event.target));
