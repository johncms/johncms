/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import { clearEditor } from './editor';

// A discussion of the core (templates/pages/comments.twig). The comment sent by the live list was
// taken unless the answer brought an error: the form starts empty then.
document.querySelectorAll('[data-comments]').forEach(discussion => {
  discussion.addEventListener('live-list:update', event => {
    const form = event.detail.form;
    if (!form?.matches('[data-comment-form]') || discussion.querySelector('[data-live-list-region="comment-error"] .alert')) return;
    const field = form.querySelector('[data-editor]');
    if (field) clearEditor(field);
  });
});
