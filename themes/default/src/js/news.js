import { focusEditor, insertAddressee, whenEditorReady } from './editor';

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

// Replies and quotes need the editor of the comment form, so they appear once it is ready.
const commentForm = document.querySelector('#comment-form [data-editor]');
if (commentForm) {
    whenEditorReady(commentForm).then(editor => {
        if (editor) enableCommentActions(editor);
    });
}

function enableCommentActions(editor) {
    for (const comment of document.querySelectorAll('[data-comment]')) {
        const author = comment.querySelector('[data-comment-author]').textContent.trim();
        const reply = comment.querySelector('[data-comment-reply]');
        const quote = comment.querySelector('[data-comment-quote]');
        if (reply) {
            reply.hidden = false;
            reply.addEventListener('click', () => insertAddressee(editor, author));
        }
        if (quote) {
            quote.hidden = false;
            quote.addEventListener('click', () => {
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
    }
}

for (const form of document.querySelectorAll('form[data-confirm]')) {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
}
