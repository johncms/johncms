/**
 * Confirm clearing personal notifications on the page and in the panel.
 * In the panel, follow the POST redirect as XHR to refresh its contents and the header.
 */
const pending = new WeakSet();

function updateCounters(panel) {
  const state = panel.querySelector('[data-notifications-unread]');
  if (!state) {
    return;
  }

  const count = Number(state.dataset.notificationsUnread);
  document.querySelectorAll('[data-notifications-bell]').forEach(function (bell) {
    bell.classList.toggle('admin-topbar__btn--dot', count > 0);
    bell.setAttribute('aria-label', bell.dataset.notificationsLabel + (count > 0 ? ': ' + count : ''));
  });
  document.querySelectorAll('[data-notifications-badge]').forEach(function (badge) {
    badge.textContent = String(count);
    badge.hidden = count === 0;
    badge.classList.toggle('d-none', count === 0);
  });
}

document.addEventListener('admin:offcanvas-loaded', function (event) {
  updateCounters(event.target);
});

document.addEventListener('click', function (event) {
  const cancel = event.target.closest('[data-notifications-clear-cancel]');
  if (!cancel) {
    return;
  }

  const form = cancel.closest('[data-notifications-clear]');
  if (pending.has(form)) {
    return;
  }
  form.querySelector('[data-notifications-confirm]').hidden = true;
  form.querySelector('[data-notifications-actions]').hidden = false;
  form.querySelector('[data-notifications-clear-error]').hidden = true;
  form.querySelector('[data-notifications-clear-start]').focus();
});

document.addEventListener('submit', async function (event) {
  const form = event.target.closest('[data-notifications-clear]');
  if (!form) {
    return;
  }

  if (pending.has(form)) {
    event.preventDefault();
    return;
  }

  const confirmation = form.querySelector('[data-notifications-confirm]');
  if (confirmation.hidden) {
    event.preventDefault();
    form.querySelector('[data-notifications-actions]').hidden = true;
    confirmation.hidden = false;
    form.querySelector('[data-notifications-confirm-title]').focus();
    return;
  }

  const panel = form.closest('#admin-notifications');
  if (!panel) {
    return;
  }

  event.preventDefault();
  const body = new FormData(form);
  const buttons = form.querySelectorAll('button');
  const error = form.querySelector('[data-notifications-clear-error]');
  const spinner = form.querySelector('[data-notifications-clear-spinner]');
  pending.add(form);
  buttons.forEach(button => { button.disabled = true; });
  form.setAttribute('aria-busy', 'true');
  error.hidden = true;
  spinner.hidden = false;

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body,
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      credentials: 'same-origin',
      cache: 'no-store',
    });
    if (!response.ok || new URL(response.url).pathname !== '/admin/notifications') {
      throw new Error('Clearing notifications answered ' + response.status);
    }

    const html = await response.text();
    const content = new DOMParser().parseFromString(html, 'text/html');
    if (!content.querySelector('[data-notifications-unread]')) {
      throw new Error('The refreshed notifications panel is missing.');
    }

    panel.innerHTML = html;
    updateCounters(panel);
    panel.querySelector('.offcanvas-title')?.setAttribute('tabindex', '-1');
    panel.querySelector('.offcanvas-title')?.focus();
  } catch {
    error.hidden = false;
  } finally {
    pending.delete(form);
    buttons.forEach(button => { button.disabled = false; });
    form.removeAttribute('aria-busy');
    spinner.hidden = true;
  }
});
