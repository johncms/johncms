/** Editor instances belong to their fields, including fields added to repeated inputs. */
const records = new WeakMap();
let editorModule;

function editorFields(root) {
  return [...(root.matches?.('[data-admin-editor]') ? [root] : []), ...root.querySelectorAll('[data-admin-editor]')];
}

export function initializeAdminEditors(root = document) {
  return Promise.all(editorFields(root).map(initialize));
}

function initialize(field) {
  if (records.has(field)) return records.get(field).ready;
  const textarea = field.querySelector('textarea');
  const initialValue = textarea.value;
  const form = textarea.form;
  const status = field.querySelector('[data-admin-editor-status]');
  const record = { loading: true, editor: null, removed: false, ready: null };
  const failedUploads = new Set();
  records.set(field, record);

  const busy = () => record.loading || record.editor?.plugins.get('PendingActions').hasAny;
  const showBusy = () => {
    status.textContent = record.loading ? field.dataset.loadingMessage
      : failedUploads.size > 0 ? field.dataset.uploadErrorMessage : field.dataset.uploadMessage;
    status.hidden = false;
  };
  const submit = event => {
    if (busy() || failedUploads.size > 0) {
      event.preventDefault();
      showBusy();
    }
  };
  const reset = event => {
    queueMicrotask(() => {
      if (!event.defaultPrevented && !record.removed && record.editor) {
        record.editor.setData(initialValue);
        field.querySelectorAll('input[name="attached_files[]"]').forEach(input => input.remove());
        failedUploads.clear();
        status.hidden = true;
      }
    });
  };
  form?.addEventListener('submit', submit);
  form?.addEventListener('reset', reset);
  record.canRemove = () => {
    if (busy()) {
      showBusy();
      return false;
    }
    return true;
  };
  record.remove = async () => {
    await record.editor?.destroy();
    record.removed = true;
    form?.removeEventListener('submit', submit);
    form?.removeEventListener('reset', reset);
    records.delete(field);
  };

  editorModule ??= import('./editor/create');
  record.ready = editorModule
    .then(module => module.createEditor(textarea, field.dataset))
    .then(editor => {
      record.editor = editor;
      record.loading = false;
      status.hidden = true;
      editor.editing.view.change(writer => {
        const root = editor.editing.view.document.getRoot();
        writer.setAttribute('aria-label', textarea.getAttribute('aria-label'), root);
        if (textarea.hasAttribute('aria-invalid')) {
          writer.setAttribute('aria-invalid', 'true', root);
          writer.setAttribute('aria-describedby', textarea.getAttribute('aria-describedby'), root);
        }
      });
      editor.model.document.on('change:data', () => {
        textarea.value = editor.getData();
        for (const image of failedUploads) {
          if (image.root.rootName === '$graveyard') failedUploads.delete(image);
        }
        if (failedUploads.size === 0 && status.textContent === field.dataset.uploadErrorMessage) status.hidden = true;
      });
      if (field.dataset.uploadUrl) {
        editor.plugins.get('ImageUploadEditing').on('uploadComplete', (_event, { data, imageElement }) => {
          if (!Number.isInteger(data.id) || data.id <= 0) {
            failedUploads.add(imageElement);
            status.textContent = field.dataset.uploadErrorMessage;
            status.hidden = false;
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
      editor.plugins.get('PendingActions').on('change:hasAny', (_event, _name, hasAny) => {
        if (!hasAny && failedUploads.size === 0) status.hidden = true;
      });
      return editor;
    })
    .catch(error => {
      record.loading = false;
      status.textContent = field.dataset.errorMessage;
      status.hidden = false;
      console.error('Could not initialize the admin editor.', error);
      return null;
    });
  return record.ready;
}

/** Preflight every editor before destroying any, so a pending upload keeps its whole value. */
export async function removeAdminEditors(root) {
  const fields = editorFields(root).filter(field => records.has(field));
  if (fields.some(field => !records.get(field).canRemove())) return false;
  for (const field of fields) await records.get(field).remove();
  return true;
}

export function setAdminEditorValue(root, value) {
  for (const field of editorFields(root)) {
    field.querySelector('textarea').value = value;
    records.get(field)?.editor?.setData(value);
  }
}

export function focusAdminEditor(root) {
  const editor = editorFields(root).map(field => records.get(field)?.editor).find(Boolean);
  if (!editor) return false;
  editor.editing.view.focus();
  return true;
}

export function labelAdminEditors(root, label) {
  for (const field of editorFields(root)) {
    field.querySelector('textarea').setAttribute('aria-label', label);
    const editor = records.get(field)?.editor;
    editor?.editing.view.change(writer => writer.setAttribute('aria-label', label, editor.editing.view.document.getRoot()));
  }
}

initializeAdminEditors();
