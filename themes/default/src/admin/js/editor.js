/** The editor and its styles are downloaded only on pages that contain an editor field. */
const fields = document.querySelectorAll('[data-admin-editor]');

if (fields.length > 0) {
  const editorModule = import('./editor/create');

  fields.forEach(function (field) {
    const textarea = field.querySelector('textarea');
    const initialValue = textarea.value;
    const status = field.querySelector('[data-admin-editor-status]');
    let loading = true;
    let editor;

    textarea.form?.addEventListener('submit', function (event) {
      if (loading || editor?.plugins.get('PendingActions').hasAny) {
        event.preventDefault();
        status.textContent = loading ? field.dataset.loadingMessage : field.dataset.uploadMessage;
        status.hidden = false;
      }
    });

    editorModule
      .then(module => module.createEditor(textarea, field.dataset))
      .then(instance => {
        editor = instance;
        loading = false;
        status.hidden = true;

        editor.editing.view.change(writer => {
          const root = editor.editing.view.document.getRoot();
          writer.setAttribute('aria-label', textarea.getAttribute('aria-label'), root);
          if (textarea.hasAttribute('aria-invalid')) {
            writer.setAttribute('aria-invalid', 'true', root);
            writer.setAttribute('aria-describedby', textarea.getAttribute('aria-describedby'), root);
          }
        });

        editor.model.document.on('change:data', () => { textarea.value = editor.getData(); });

        if (field.dataset.uploadUrl) {
          editor.plugins.get('ImageUploadEditing').on('uploadComplete', (_event, { data }) => {
            if (!Number.isInteger(data.id) || data.id <= 0) {
              throw new Error('The image upload response must contain a positive integer file id.');
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'attached_files[]';
            input.value = data.id;
            field.append(input);
          });
        }

        editor.plugins.get('PendingActions').on('change:hasAny', (_event, _name, hasAny) => {
          if (!hasAny) {
            status.hidden = true;
          }
        });

        textarea.form?.addEventListener('reset', function (event) {
          // Reset runs after the event, and a different listener may cancel it.
          queueMicrotask(() => {
            if (!event.defaultPrevented) {
              editor.setData(initialValue);
              field.querySelectorAll('input[name="attached_files[]"]').forEach(input => input.remove());
            }
          });
        });
      })
      .catch(error => {
        loading = false;
        status.textContent = field.dataset.errorMessage;
        status.hidden = false;
        console.error('Could not initialize the admin editor.', error);
      });
  });
}
