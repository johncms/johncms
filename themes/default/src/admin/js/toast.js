/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * Toasts of the panel: short results of an action at the top of the page, where they are seen
 * wherever the action was taken.
 *
 *     import { toast, announce } from './toast';
 *     toast(message, {type: 'error'});
 *
 * The server asks for one with @admin/components/ui/toast.twig: an alert marked data-admin-toast,
 * which stays an alert without the script. Such alerts on the page as it loads, and in what a live
 * list or a side panel brings in, are taken out and shown here. A button marked
 * data-admin-toast-show="<type>" with data-admin-toast-message shows its toast on a click.
 *
 * A toast closes by itself once its bar runs out; the bar stops while the pointer or the focus is
 * on it, and while the page is hidden. The stack (components/ui/toasts.twig) is a polite live
 * region, an error toast is an alert of its own, so a screen reader reads out what comes in.
 */
const DURATION = {success: 5000, info: 5000, warning: 7000, error: 8000};
const ICON = {success: 'circle-check', info: 'info', warning: 'triangle-alert', error: 'circle-x'};
// Older toasts make way: a long stack would cover the page.
const LIMIT = 4;

const stack = document.querySelector('[data-admin-toasts]');

function icon(name, className) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('class', 'admin-icon ' + className);
  svg.setAttribute('width', '18');
  svg.setAttribute('height', '18');
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('focusable', 'false');
  const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
  use.setAttribute('href', stack.dataset.iconSprite + '#' + name);
  svg.append(use);
  return svg;
}

function dismiss(element) {
  if (element.classList.contains('admin-toast--leaving')) {
    return;
  }

  element.classList.add('admin-toast--leaving');
  // Without an animation, under reduced motion for instance, no animationend comes.
  const remove = () => element.remove();
  element.addEventListener('animationend', event => event.target === element && remove());
  setTimeout(remove, 400);
}

/** Shows a toast: success, info, warning or error. The message is text. */
export function toast(message, {type = 'success', duration = DURATION[type] ?? DURATION.info} = {}) {
  if (!stack || !message) {
    return;
  }

  const element = document.createElement('div');
  element.className = 'admin-toast admin-toast--' + type;

  if (type === 'error') {
    element.setAttribute('role', 'alert');
  }

  const text = document.createElement('div');
  text.className = 'admin-toast__text';
  text.textContent = message;

  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'admin-toast__close';
  close.setAttribute('aria-label', stack.dataset.closeLabel);
  close.append(icon('x', 'admin-toast__close-icon'));
  close.addEventListener('click', () => dismiss(element));

  const bar = document.createElement('div');
  bar.className = 'admin-toast__bar';
  bar.style.animationDuration = duration + 'ms';
  bar.addEventListener('animationend', () => dismiss(element));

  element.append(icon(ICON[type] ?? ICON.info, 'admin-toast__icon'), text, close, bar);
  stack.append(element);

  const shown = stack.querySelectorAll('.admin-toast:not(.admin-toast--leaving)');
  for (let i = 0; i < shown.length - LIMIT; i++) {
    dismiss(shown[i]);
  }
}

/** Reads a message out to a screen reader without showing it: the page shows the result already. */
export function announce(message) {
  if (!stack || !message) {
    return;
  }

  const text = document.createElement('p');
  text.className = 'visually-hidden';
  text.textContent = message;
  stack.append(text);
  setTimeout(() => text.remove(), DURATION.info);
}

// The alerts the server marked as toasts, wherever they are on the page now.
function showMarked() {
  for (const marked of document.querySelectorAll('[data-admin-toast]')) {
    const message = marked.textContent.trim();

    if (marked.hasAttribute('data-admin-toast-announce')) {
      announce(message);
    } else {
      toast(message, {type: marked.dataset.adminToast});
    }

    marked.remove();
  }
}

if (stack) {
  showMarked();
  document.addEventListener('live-list:update', showMarked);
  document.addEventListener('admin:offcanvas-loaded', showMarked);

  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-admin-toast-show]');

    if (trigger) {
      toast(trigger.dataset.adminToastMessage, {type: trigger.dataset.adminToastShow});
    }
  });

  document.addEventListener('visibilitychange', () => {
    stack.classList.toggle('admin-toasts--paused', document.visibilityState === 'hidden');
  });
}
