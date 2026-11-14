/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import { clearEditor, focusEditor, whenEditorReady } from './editor';

// A discussion of the core (components/comments/discussion.twig), on a page of its own or within
// the page of a module. The comments are swapped by the live list, so the clicks are taken on the
// whole discussion.
document.querySelectorAll('[data-comments]').forEach(discussion => {
  const field = discussion.querySelector('#comment-form [data-editor]');
  if (field) {
    whenEditorReady(field).then(editor => {
      if (editor) enableQuotes(discussion, editor);
    });
  }

  // The comment sent by the live list was taken unless the answer brought an error: the form
  // starts empty then.
  discussion.addEventListener('live-list:update', event => {
    const form = event.detail.form;
    if (!form?.matches('[data-comment-form]') || discussion.querySelector('[data-live-list-region="comment-error"] .alert')) return;
    const editorField = form.querySelector('[data-editor]');
    if (editorField) clearEditor(editorField);
  });
});

// A quote needs the editor of the form, so its buttons appear once the editor is ready.
function enableQuotes(discussion, editor) {
  const reveal = () => {
    for (const button of discussion.querySelectorAll('[data-comment-quote]')) {
      button.hidden = false;
    }
  };
  reveal();
  discussion.addEventListener('live-list:update', reveal);

  discussion.addEventListener('click', event => {
    const comment = event.target.closest('[data-comment-quote]')?.closest('[data-comment]');
    if (!comment) return;
    // Built from text nodes: the quoted comment is user content.
    const author = comment.querySelector('[data-comment-author]').textContent.trim();
    const date = comment.querySelector('[data-comment-date]').textContent.trim();
    const text = comment.querySelector('[data-comment-body]').textContent.trim();
    const blockquote = document.createElement('blockquote');
    const paragraph = document.createElement('p');
    paragraph.textContent = author + ', ' + date + ': ' + text;
    blockquote.append(paragraph);
    const view = editor.data.processor.toView(blockquote.outerHTML + '<p></p>');
    editor.model.insertContent(editor.data.toModel(view));
    focusEditor(editor);
  });
}
