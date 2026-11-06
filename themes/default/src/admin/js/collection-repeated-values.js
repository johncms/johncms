import { initializeAdminEditors, labelAdminEditors, removeAdminEditors, focusAdminEditor } from './editor';

/** Ordinary POST buttons remain the fallback when this enhancement is unavailable. */
document.querySelectorAll('[data-collection-repeat]').forEach(root => {
  const list = root.querySelector('[data-repeat-rows]');
  const template = root.querySelector('[data-repeat-template]');
  const status = root.querySelector('[data-repeat-status]');
  const add = root.querySelector('[data-repeat-op="add"]');
  const limit = Number(root.dataset.limit);
  let serial = 0;
  let busy = false;
  const rows = () => [...list.children].filter(row => row.matches('[data-repeat-row]'));
  const refresh = () => {
    const entries = rows();
    entries.forEach((row, index) => {
      row.querySelector('[data-repeat-number]').textContent = index + 1;
      labelAdminEditors(row, `${root.querySelector('legend').textContent.trim()} ${index + 1}`);
      row.querySelectorAll('[data-file-widget]').forEach(widget => {
        widget.dataset.index = index;
        widget.querySelector('[data-file-upload-input]').name = `file_uploads[${widget.dataset.code}][${index}]`;
        widget.querySelector('[data-file-upload-button]').value = `${widget.dataset.code}:${index}`;
        widget.querySelector('[data-file-clear]').value = `${widget.dataset.code}:${index}`;
      });
      row.querySelectorAll('[data-repeat-op]').forEach(button => {
        const operation = button.dataset.repeatOp;
        button.value = `${root.dataset.key}:${operation}:${index}`;
        button.disabled = (operation === 'up' && index === 0) || (operation === 'down' && index === entries.length - 1);
      });
    });
    add.disabled = entries.length >= limit;
    root.dispatchEvent(new CustomEvent('collection:values-changed', {bubbles: true}));
  };
  refresh();

  root.addEventListener('click', async event => {
    const button = event.target.closest('[data-repeat-op]');
    if (!button || button.closest('[data-collection-repeat]') !== root) return;
    event.preventDefault();
    if (busy || button.disabled) return;
    busy = true;
    const operation = button.dataset.repeatOp;
    const row = button.closest('[data-repeat-row]');
    try {
      if (operation === 'add') {
        if (rows().length >= limit) {
          status.textContent = root.dataset.limitMessage;
          return;
        }
        const fragment = template.content.cloneNode(true);
        const next = fragment.querySelector('[data-repeat-row]');
        const token = `new${++serial}`;
        next.querySelectorAll('[id], [for], [aria-describedby]').forEach(element => {
          for (const attribute of ['id', 'for', 'aria-describedby']) {
            if (element.hasAttribute(attribute)) element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', token));
          }
        });
        list.append(fragment);
        refresh();
        next.querySelector('textarea, input, select')?.focus();
        initializeAdminEditors(next).then(() => {
          if (next.isConnected && (document.activeElement === document.body || next.contains(document.activeElement))) focusAdminEditor(next);
        });
      } else if (operation === 'remove') {
        const pendingFile = row.querySelector('[data-file-widget][aria-busy="true"]');
        if (pendingFile) {
          status.textContent = pendingFile.closest('[data-file-field]').dataset.uploading;
          return;
        }
        const index = rows().indexOf(row);
        if (!await removeAdminEditors(row)) return;
        row.remove();
        refresh();
        const focus = rows()[index] ?? rows()[index - 1];
        if (!focus || !focusAdminEditor(focus)) (focus?.querySelector('textarea:not([hidden]), input, select') ?? add).focus();
      } else if (operation === 'up') {
        if (row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        refresh();
        button.focus();
      } else if (operation === 'down') {
        if (row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        refresh();
        button.focus();
      }
      status.textContent = root.dataset.changedMessage;
    } catch (error) {
      status.textContent = root.dataset.errorMessage;
      console.error('Could not change a repeated collection value.', error);
    } finally {
      busy = false;
    }
  });
});

/** Both default inputs remain available without scripts; the server chooses their declared shape. */
document.querySelectorAll('[data-collection-field-settings]').forEach(form => {
  const multiple = form.querySelector('input[name="multiple"]');
  const type = form.querySelector('select[name="type"]');
  const fileStorage = form.querySelector('select[name="file_storage"]');
  const toggle = (group, visible) => {
    group.hidden = !visible;
    group.disabled = !visible;
  };
  const update = () => {
    form.querySelector('[data-default-single]').hidden = multiple.checked;
    form.querySelector('[data-default-multiple]').hidden = !multiple.checked;
    form.querySelectorAll('[data-field-types]').forEach(group => toggle(group, group.dataset.fieldTypes.split(' ').includes(type.value)));
    form.querySelectorAll('[data-field-multiple]').forEach(group => toggle(group, multiple.checked));
    form.querySelectorAll('[data-file-storage]').forEach(group => toggle(group, type.value === 'file' && group.dataset.fileStorage === fileStorage.value));
    form.querySelectorAll('input[name="min_value"], input[name="max_value"]').forEach(input => {
      input.step = type.value === 'integer' ? '1' : 'any';
    });
  };
  multiple.addEventListener('change', update);
  type.addEventListener('change', update);
  fileStorage.addEventListener('change', update);
  form.addEventListener('reset', () => queueMicrotask(update));
  update();
});
