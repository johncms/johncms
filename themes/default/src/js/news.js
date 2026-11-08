import { clearEditor, focusEditor, insertAddressee, whenEditorReady } from './editor';

// Copy the canonical article address; keep feedback beside the action.
for (const share of document.querySelectorAll('[data-news-share]')) {
    if (!navigator.clipboard?.writeText) continue;
    const button = share.querySelector('[data-news-copy]');
    const status = share.querySelector('[role="status"]');
    button.hidden = false;
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await navigator.clipboard.writeText(new URL(share.dataset.url, window.location.href).href);
            status.classList.remove('text-danger');
            status.textContent = share.dataset.copied;
        } catch (error) {
            status.classList.add('text-danger');
            status.textContent = share.dataset.error;
            console.error('Could not copy the article link.', error);
        } finally {
            button.disabled = false;
        }
    });
}

// Replies and quotes need the editor of the comment form, so they appear once it is ready. The
// comments are swapped by the live list, so the clicks are taken on the whole discussion.
const discussion = document.querySelector('#article-comments');
const commentForm = discussion?.querySelector('#comment-form [data-editor]');
if (commentForm) {
    whenEditorReady(commentForm).then(editor => {
        if (editor) enableCommentActions(discussion, editor);
    });
}

function enableCommentActions(discussion, editor) {
    const reveal = () => {
        for (const button of discussion.querySelectorAll('[data-comment-reply], [data-comment-quote]')) {
            button.hidden = false;
        }
    };
    reveal();
    discussion.addEventListener('live-list:update', reveal);

    discussion.addEventListener('click', event => {
        const button = event.target.closest('[data-comment-reply], [data-comment-quote]');
        const comment = button?.closest('[data-comment]');
        if (!comment) return;
        const author = comment.querySelector('[data-comment-author]').textContent.trim();
        if (button.matches('[data-comment-reply]')) {
            insertAddressee(editor, author);
            return;
        }
        // Built from text nodes: the quoted comment is user content.
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

// The comment sent by the live list was taken unless the answer brought an error: the form starts empty.
discussion?.addEventListener('live-list:update', event => {
    const form = event.detail.form;
    if (!form?.matches('[data-comment-form]') || discussion.querySelector('[data-live-list-region="comment-error"] .alert')) return;
    const field = form.querySelector('[data-editor]');
    if (field) clearEditor(field);
});

// Taken on the document while the event goes down, before a live list sends the form: forms
// swapped in by a live list ask as well.
document.addEventListener('submit', event => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
}, true);
