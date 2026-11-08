/**
 * The rich text editor of the public forms. A field marked data-editor keeps working as a plain
 * textarea; the editor is loaded only on pages that have one and replaces it once ready.
 */
const editors = new WeakMap();
let editorModule;

export function editorFields(root = document) {
  return [...root.querySelectorAll('[data-editor]')];
}

/** Resolves to the editor of the field, or null when it could not be created. */
export function whenEditorReady(field) {
  return editors.get(field)?.ready ?? Promise.resolve(null);
}

function initialize(field) {
  const textarea = field.querySelector('textarea');
  const form = textarea.form;
  const status = field.querySelector('[data-editor-status]');
  const record = { loading: true, editor: null, ready: null };
  const failedUploads = new Set();
  editors.set(field, record);

  const show = message => {
    status.textContent = message;
    status.hidden = false;
  };
  form?.addEventListener('submit', event => {
    if (record.loading) {
      return;
    }
    if (record.editor?.plugins.get('PendingActions').hasAny) {
      event.preventDefault();
      show(field.dataset.uploadMessage);
    } else if (failedUploads.size > 0) {
      event.preventDefault();
      show(field.dataset.uploadErrorMessage);
    }
  });

  editorModule ??= import('./editor/create');
  record.ready = editorModule
    .then(module => module.createEditor(textarea, field.dataset))
    .then(editor => {
      record.editor = editor;
      record.loading = false;
      // The editor hides the field, so the browser could not point at it when it is empty.
      textarea.required = false;
      editor.editing.view.change(writer => {
        writer.setAttribute('aria-label', textarea.getAttribute('aria-label') ?? '', editor.editing.view.document.getRoot());
      });
      editor.model.document.on('change:data', () => {
        textarea.value = editor.getData();
        for (const image of failedUploads) {
          if (image.root.rootName === '$graveyard') failedUploads.delete(image);
        }
        if (failedUploads.size === 0) status.hidden = true;
      });
      if (field.dataset.uploadUrl) {
        // An uploaded picture is posted with the text so that it changes owner.
        editor.plugins.get('ImageUploadEditing').on('uploadComplete', (_event, { data, imageElement }) => {
          if (!Number.isInteger(data.id) || data.id <= 0) {
            failedUploads.add(imageElement);
            show(field.dataset.uploadErrorMessage);
            console.error('The image upload response must contain a positive integer file id.');
            return;
          }
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'attached_files[]';
          input.value = data.id;
          field.append(input);
        });
      }
      return editor;
    })
    .catch(error => {
      record.loading = false;
      show(field.dataset.errorMessage);
      console.error('Could not initialize the editor.', error);
      return null;
    });
}

/** Empties the field once its text was sent without leaving the page: the text and its pictures. */
export function clearEditor(field) {
  field.querySelectorAll('input[name="attached_files[]"]').forEach(input => input.remove());
  field.querySelector('textarea').value = '';
  return whenEditorReady(field).then(editor => editor?.setData(''));
}

/** Puts the caret at the end of the editor and scrolls the form into view. */
export function focusEditor(editor) {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  editor.ui.element.scrollIntoView({ behavior: reduced ? 'instant' : 'smooth', block: 'center' });
  editor.editing.view.focus();
}

/** Starts an answer: "name, " at the caret, then the caret at the end. */
export function insertAddressee(editor, name) {
  editor.model.change(writer => {
    writer.insertText(name + ', ', {}, editor.model.document.selection.getFirstPosition());
    writer.setSelection(writer.createPositionAt(editor.model.document.getRoot(), 'end'));
  });
  focusEditor(editor);
}

// Links and buttons that answer someone, for pages with one editor: data-editor-reply="name".
document.addEventListener('click', event => {
  const trigger = event.target.closest('[data-editor-reply]');
  const field = editorFields()[0];
  if (!trigger || !field) return;
  event.preventDefault();
  whenEditorReady(field).then(editor => editor && insertAddressee(editor, trigger.dataset.editorReply));
});

editorFields().forEach(initialize);
