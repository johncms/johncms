/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import { Modal } from 'bootstrap';

/**
 * Asking before an action, in the dialog of the page (components/confirm-dialog.twig) instead of
 * the window of the browser.
 *
 *     <form method="post" action="…" data-confirm="Delete this comment?" data-confirm-button="Delete">
 *
 *     import { confirmAction } from './confirm';
 *     if (await confirmAction({title: '…', button: '…'})) { … }
 *
 * The form is held back while the dialog is open and sent again, with the same button, once the
 * answer is yes. The answer travels with it as the confirmed field: a route that removes something
 * takes RequireConfirmationMiddleware, which refuses a form without it, so a page whose script did
 * not run cannot delete anything. The field stays in the form until the next question, so a list
 * that falls back to sending the form natively still carries it. The question is asked while the submit event goes down, so it comes before any
 * other handler of the form: a live list sends only a form that was let through. The cancel
 * button has the focus, so Enter does not go ahead by mistake; the focus goes back to the button
 * that asked once the dialog closes.
 */
const element = document.querySelector('[data-confirm-dialog]');
const approved = new WeakSet();
const FIELD = 'confirmed';
let modal = null;

/** Resolves to true when the user goes ahead, to false when the dialog is closed otherwise. */
export function confirmAction({title, text = '', button = '', tone = 'danger'}) {
  if (!element) {
    return Promise.resolve(window.confirm(title));
  }

  modal ??= new Modal(element);
  const accept = element.querySelector('[data-confirm-accept]');
  const description = element.querySelector('[data-confirm-text]');
  element.querySelector('[data-confirm-title]').textContent = title;
  description.textContent = text;
  description.hidden = text === '';
  accept.textContent = button || element.dataset.defaultButton;
  accept.className = 'btn btn-' + (tone === 'primary' ? 'primary' : 'danger');

  return new Promise(resolve => {
    let answer = false;
    const onAccept = () => {
      answer = true;
      modal.hide();
    };
    const onShown = () => element.querySelector('[data-confirm-cancel]').focus();

    accept.addEventListener('click', onAccept);
    element.addEventListener('shown.bs.modal', onShown, {once: true});
    element.addEventListener('hidden.bs.modal', () => {
      accept.removeEventListener('click', onAccept);
      resolve(answer);
    }, {once: true});
    modal.show();
  });
}

document.addEventListener('submit', async event => {
  const form = event.target.closest('form[data-confirm]');

  if (!form) {
    return;
  }

  if (approved.has(form)) {
    approved.delete(form);
    return;
  }

  event.preventDefault();
  setConfirmed(form, false);
  const submitter = event.submitter;
  const yes = await confirmAction({
    title: form.dataset.confirm,
    text: form.dataset.confirmText ?? '',
    button: form.dataset.confirmButton ?? '',
    tone: form.dataset.confirmTone ?? 'danger',
  });

  // Back on the button before the form is sent: a live list that replaces it then moves the focus on.
  if (submitter?.isConnected) {
    submitter.focus();
  }

  if (yes && form.isConnected) {
    setConfirmed(form, true);
    approved.add(form);
    form.requestSubmit(submitter?.isConnected ? submitter : null);
  }
}, true);

/** Puts the answer into the form as the field the server checks, or takes it out. */
function setConfirmed(form, confirmed) {
  form.querySelectorAll(':scope > input[data-confirm-answer]').forEach(input => input.remove());

  if (!confirmed) {
    return;
  }

  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = FIELD;
  input.value = '1';
  input.dataset.confirmAnswer = '';
  form.append(input);
}
