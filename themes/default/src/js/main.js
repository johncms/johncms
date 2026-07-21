/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

Prism.manual = true;

function initThemeToggle() {
  const toggle = document.querySelector('[data-theme-toggle]');

  if (!toggle) {
    return;
  }

  const validThemes = ['light', 'dark', 'auto'];
  const body = document.body;
  const iconUse = toggle.querySelector('use');
  const prefersDark = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function isDark(theme) {
    if (theme === 'dark') {
      return true;
    }

    return theme === 'auto' && prefersDark && prefersDark.matches;
  }

  function normalizeTheme(theme) {
    return validThemes.includes(theme) ? theme : 'auto';
  }

  function getCurrentTheme() {
    if (body.classList.contains('dark')) {
      return 'dark';
    }

    if (body.classList.contains('light')) {
      return 'light';
    }

    return 'auto';
  }

  function updateToggleState(theme) {
    const darkMode = isDark(theme);
    const title = darkMode ? toggle.dataset.titleLight : toggle.dataset.titleDark;
    const icon = darkMode ? 'sun' : 'moon';
    const iconHref = toggle.dataset.iconSprite + '#' + icon;

    toggle.setAttribute('title', title);
    toggle.setAttribute('aria-label', title);

    if (iconUse) {
      iconUse.setAttribute('xlink:href', iconHref);
      iconUse.setAttribute('href', iconHref);
    }
  }

  function applyTheme(theme) {
    const normalizedTheme = normalizeTheme(theme);
    body.classList.remove('light', 'dark', 'auto');
    body.classList.add(normalizedTheme);
    updateToggleState(normalizedTheme);
  }

  function persistTheme(theme) {
    document.cookie = 'siteTheme=' + encodeURIComponent(theme) + '; path=/; max-age=31536000; SameSite=Lax';
  }

  applyTheme(toggle.dataset.currentTheme || getCurrentTheme());

  toggle.addEventListener('click', function () {
    const currentTheme = getCurrentTheme();
    const nextTheme = isDark(currentTheme) ? 'light' : 'dark';
    applyTheme(nextTheme);
    persistTheme(nextTheme);
  });

  if (prefersDark && typeof prefersDark.addEventListener === 'function') {
    prefersDark.addEventListener('change', function () {
      if (getCurrentTheme() === 'auto') {
        updateToggleState('auto');
      }
    });
  }
}

$(function () {
  initThemeToggle();

  $(".post-body").each(function () {
    Prism.highlightAllUnder(this);
  });

  const scrollButton = document.querySelector(".to-top");

  function updateButtonState() {
    const scrollTop = window.scrollY;
    const scrollable = document.documentElement.scrollHeight > window.innerHeight;

    if (scrollable && scrollTop < 50) {
      scrollButton.classList.add("to-bottom");
      scrollButton.classList.remove("to-top_hidden", "to-header");
    } else if (scrollTop >= 50) {
      scrollButton.classList.remove("to-bottom", "to-top_hidden");
      scrollButton.classList.add("to-header");
    }
  }

  window.addEventListener("scroll", updateButtonState);
  window.addEventListener("load", updateButtonState);  // важно: после полной загрузки
  document.addEventListener("DOMContentLoaded", updateButtonState);

  document.querySelectorAll('.to-top').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const isToHeader = this.classList.contains('to-header');
      const scrollTarget = isToHeader ? 0 : document.body.scrollHeight;
      window.scrollTo({top: scrollTarget, behavior: 'smooth'});
    });
  });
});

$(document).ready(function () {
  flatpickr(".flatpickr", {
    dateFormat: 'd.m.Y',
  });
  flatpickr(".flatpickr_time", {
    dateFormat: 'd.m.Y H:i',
    enableTime: true,
  });
})
