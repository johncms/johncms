/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

Prism.manual = true;

$(function () {
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
  if (typeof wysibb_input != "undefined") {
    $(wysibb_input).wysibb(wysibb_settings);
  }

  $(".flatpickr").flatpickr({
    dateFormat: 'd.m.Y',
  });
  $(".flatpickr_time").flatpickr({
    dateFormat: 'd.m.Y H:i',
    enableTime: true,
  });
})
