/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * The chrome of the panel (layouts/default.twig): the menu opened over the page on a narrow
 * screen, and the sidebar folded to its icons on a wide one.
 *
 * Both are classes of <html>. The folded state is kept in the browser and read back by the head
 * of the layout before the first paint, under the same key.
 *
 * A folded sidebar shows no labels, so its links get them back as a tooltip: the title of what
 * carries data-tip, set only while folded, where it would repeat the label otherwise.
 */
const STORAGE_KEY = 'johncms.admin.sidebar';
const root = document.documentElement;

function setNavOpen(open) {
  root.classList.toggle('admin-nav-open', open);
  document.querySelectorAll('[data-admin-nav-open]').forEach(button => button.setAttribute('aria-expanded', String(open)));
}

function syncTips() {
  const folded = root.classList.contains('admin-sidebar-folded');

  document.querySelectorAll('.admin-sidebar [data-tip]').forEach(function (element) {
    if (folded) {
      element.setAttribute('title', element.dataset.tip);
    } else {
      element.removeAttribute('title');
    }
  });
}

function toggleFolded() {
  const folded = root.classList.toggle('admin-sidebar-folded');
  syncTips();

  try {
    if (folded) {
      localStorage.setItem(STORAGE_KEY, 'folded');
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  } catch {
    // Storage may be off; the sidebar is then folded until the next page only.
  }
}

document.addEventListener('click', function (event) {
  if (event.target.closest('[data-admin-nav-open]')) {
    setNavOpen(true);
  } else if (event.target.closest('[data-admin-nav-close]')) {
    setNavOpen(false);
  } else if (event.target.closest('[data-admin-sidebar-fold]')) {
    toggleFolded();
  }
});

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape' && root.classList.contains('admin-nav-open')) {
    setNavOpen(false);
  }
});

syncTips();
