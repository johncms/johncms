/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import { Modal } from 'bootstrap';

/**
 * The dialogs of the panel: a link with data-admin-modal opens its address in the modal of the
 * layout (#admin-modal) instead of going there.
 *
 *     <a href="/admin/users/7/delete" data-admin-modal>Delete</a>
 *
 * The address is asked for with X-Requested-With, and the controller answers with the dialog
 * alone — the .modal-content of components/ui/dialog.twig. A form in it posts as any form does,
 * and the page that answers replaces this one. Without the script, or when the request fails,
 * the link leads to the same dialog shown as a page.
 */
const element = document.getElementById('admin-modal');

if (element) {
  const dialog = element.querySelector('.modal-dialog');
  const loading = element.querySelector('template[data-admin-modal-loading]');
  const modal = Modal.getOrCreateInstance(element);
  let pending = null;

  async function open(url, trigger) {
    if (pending) {
      pending.abort();
    }

    const request = new AbortController();
    pending = request;
    dialog.replaceChildren(loading.content.cloneNode(true));
    modal.show(trigger);

    try {
      const response = await fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        cache: 'no-store',
        signal: request.signal,
      });

      if (!response.ok) {
        throw new Error('The dialog answered ' + response.status);
      }

      dialog.innerHTML = await response.text();
      // A dialog that asks to delete something gives the focus to nothing in particular, so that
      // Enter does not confirm it; one with a field to fill names it with autofocus.
      dialog.querySelector('[autofocus]')?.focus();
    } catch (error) {
      if (error.name !== 'AbortError') {
        window.location.assign(url);
      }
    } finally {
      if (pending === request) {
        pending = null;
      }
    }
  }

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-admin-modal]');

    if (!trigger || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    event.preventDefault();
    open(trigger.getAttribute('href') ?? trigger.dataset.adminModal, trigger);
  });

  element.addEventListener('hidden.bs.modal', function () {
    if (pending) {
      pending.abort();
    }

    dialog.replaceChildren();
  });
}
