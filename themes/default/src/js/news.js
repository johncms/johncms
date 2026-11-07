import { csrfHeaders } from './csrf';

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

// The comment form works as a plain text field; the editor, replies and quotes enhance it.
const commentEditorField = document.querySelector('[data-comment-editor]');
if (commentEditorField) {
    const form = commentEditorField.closest('form');
    if (!window.ClassicEditor) {
        console.error('CKEditor is not loaded; the comment form stays a plain text field.');
    } else {
        window.ClassicEditor.create(commentEditorField, {
            language: commentEditorField.dataset.language,
            simpleUpload: {
                uploadUrl: commentEditorField.dataset.uploadUrl,
                withCredentials: false,
                headers: csrfHeaders(),
                // An uploaded picture is posted with the comment so that it changes owner.
                savedCallback: file => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'attached_files[]';
                    input.value = file.id;
                    form.append(input);
                },
            },
        }).then(editor => {
            // The editor hides the field, so the browser could not point at it when it is empty.
            commentEditorField.required = false;
            enableCommentActions(editor, form);
        }).catch(error => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning';
            alert.setAttribute('role', 'alert');
            alert.textContent = commentEditorField.dataset.editorError;
            form.prepend(alert);
            console.error('Could not initialize the news comment editor.', error);
        });
    }
}

function enableCommentActions(editor, form) {
    const scrollToForm = () => {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        form.scrollIntoView({behavior: reduced ? 'instant' : 'smooth', block: 'center'});
        editor.editing.view.focus();
    };
    for (const comment of document.querySelectorAll('[data-comment]')) {
        const author = comment.querySelector('[data-comment-author]').textContent.trim();
        const reply = comment.querySelector('[data-comment-reply]');
        const quote = comment.querySelector('[data-comment-quote]');
        if (reply) {
            reply.hidden = false;
            reply.addEventListener('click', () => {
                editor.model.change(writer => {
                    writer.insertText(author + ', ', {}, editor.model.document.selection.getFirstPosition());
                    writer.setSelection(writer.createPositionAt(editor.model.document.getRoot(), 'end'));
                });
                scrollToForm();
            });
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
                scrollToForm();
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
