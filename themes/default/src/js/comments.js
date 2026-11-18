/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import { clearEditor, focusEditor, whenEditorReady } from './editor';

// A discussion of the core (components/comments/discussion.twig), on a page of its own or within
// the page of a module, and the guestbook, whose entries answer one another the same way. The
// comments are swapped by the live list, so the clicks are taken on the whole discussion.
document.querySelectorAll('[data-comments]').forEach(discussion => {
  const field = discussion.querySelector('[data-comment-reply-field]')?.form.querySelector('[data-editor]');
  const reply = replyLabel(discussion);
  if (field) {
    whenEditorReady(field).then(editor => {
      if (editor) enableQuotes(discussion, editor, reply);
    });
  }

  discussion.addEventListener('click', event => {
    const answer = event.target.closest('[data-comment-reply]');
    if (answer && reply) {
      event.preventDefault();
      reply.set(answer.closest('[data-comment]'));
      whenEditorReady(field).then(editor => editor ? focusEditor(editor) : field.querySelector('textarea').focus());
      return;
    }

    // The comment an answer recalls is shown where it is when it is on this page; its address
    // finds the page it is on otherwise.
    const recalled = event.target.closest('[data-comment-reply-to]');
    if (recalled && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
      const target = discussion.querySelector('[data-comment-id="' + CSS.escape(recalled.dataset.commentReplyTo) + '"]');
      if (target) {
        event.preventDefault();
        location.hash = target.id;
      }
    }
  });

  // The comment sent by the live list was taken unless the answer brought an error: the form
  // starts empty then, and answers nobody.
  discussion.addEventListener('live-list:update', event => {
    const form = event.detail.form;
    if (!form?.matches('[data-comment-form]') || discussion.querySelector('[data-live-list-region="comment-error"] .alert')) return;
    const editorField = form.querySelector('[data-editor]');
    if (editorField) clearEditor(editorField);
    reply?.clear();
  });
});

// The label above the form that says which comment the text answers, and the field that sends it.
function replyLabel(discussion) {
  const input = discussion.querySelector('[data-comment-reply-field]');
  const label = discussion.querySelector('[data-comment-reply-label]');
  if (!input || !label) return null;

  const clear = () => {
    input.value = '';
    label.hidden = true;
  };
  label.querySelector('[data-comment-reply-cancel]').addEventListener('click', clear);

  return {
    set(comment) {
      // Built from text nodes: the name and the text are user content.
      input.value = comment.dataset.commentId;
      label.querySelector('[data-comment-reply-name]').textContent = comment.querySelector('[data-comment-author]').textContent.trim();
      label.querySelector('[data-comment-reply-excerpt]').textContent = ownWords(comment);
      label.hidden = false;
    },
    clear,
  };
}

// The start of the words of the comment, the quotes it holds left out.
function ownWords(comment) {
  const body = comment.querySelector('[data-comment-body]').cloneNode(true);
  body.querySelectorAll('blockquote').forEach(quote => quote.remove());
  const text = body.textContent.replace(/\s+/g, ' ').trim();
  return text.length > 80 ? text.slice(0, 80).trimEnd() + '…' : text;
}

// A quote needs the editor of the form, so its buttons appear once the editor is ready. The text
// quoted is answered too.
function enableQuotes(discussion, editor, reply) {
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
    // In the forum the button is a link to the page of the answer, the way without the script.
    event.preventDefault();
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
    reply?.set(comment);
    focusEditor(editor);
  });
}
