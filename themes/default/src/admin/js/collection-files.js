class FileRequestError extends Error {}

const validChoice = choice => choice && typeof choice.code === 'string' && /^[1-9]\d*$/u.test(choice.code)
  && typeof choice.label === 'string' && typeof choice.url === 'string' && typeof choice.sizeLabel === 'string' && typeof choice.image === 'boolean';

function optionFor(choice) {
  const option = new Option(`${choice.label} (${choice.sizeLabel})`, choice.code);
  option.dataset.fileLabel = choice.label;
  option.dataset.fileUrl = choice.url;
  option.dataset.fileSize = choice.sizeLabel;
  option.dataset.fileImage = choice.image ? '1' : '0';
  return option;
}

function thumbnail(root, url, label, image) {
  if (image) {
    const picture = document.createElement('img');
    picture.src = url;
    picture.alt = label;
    picture.loading = 'lazy';
    picture.className = 'collection-file-thumb';
    return picture;
  }
  const icon = document.createElement('span');
  icon.className = 'collection-file-thumb';
  icon.append(root.querySelector('[data-file-icon]').content.cloneNode(true));
  return icon;
}

/** A file field: the drop zone, the card of the chosen file and the picker of uploaded ones. */
document.querySelectorAll('[data-file-field]').forEach(root => {
  const form = root.closest('form');
  const picker = root.querySelector('[data-file-picker]');
  const pickerQuery = root.querySelector('[data-file-picker-query]');
  const pickerResults = root.querySelector('[data-file-picker-results]');
  const pickerStatus = root.querySelector('[data-file-picker-status]');
  const addRow = root.querySelector('[data-repeat-op="add"]');
  const uploads = new Map();
  let searchController;
  let searchTimer;
  let pickerTarget = null;
  const widgets = () => [...root.querySelectorAll('[data-file-widget]')];
  const show = (node, message, error = false) => {
    node.textContent = message;
    node.classList.toggle('text-danger', error);
  };
  const render = widget => {
    widget.querySelectorAll('[data-file-native]').forEach(node => { node.hidden = true; });
    widget.querySelector('[data-file-upload-input]').multiple = addRow !== null;
    const select = widget.querySelector('[data-file-select]');
    const preview = widget.querySelector('[data-file-preview]');
    const option = select.selectedOptions[0];
    widget.querySelector('[data-file-card]').hidden = !select.value;
    widget.querySelector('[data-file-drop]').hidden = Boolean(select.value);
    widget.querySelector('[data-file-actions]').hidden = !select.value;
    preview.replaceChildren();
    if (!select.value) return;
    if (!option?.dataset.fileUrl) {
      const unavailable = document.createElement('div');
      unavailable.className = 'small text-danger';
      unavailable.textContent = root.dataset.unavailable;
      preview.append(unavailable);
      return;
    }
    const name = document.createElement('div');
    name.className = 'collection-file-name';
    const link = document.createElement('a');
    link.href = option.dataset.fileUrl;
    link.target = '_blank';
    link.rel = 'noopener';
    link.className = 'text-break';
    link.textContent = option.dataset.fileLabel;
    const size = document.createElement('span');
    size.className = 'small text-body-secondary';
    size.textContent = option.dataset.fileSize;
    name.append(link, size);
    preview.append(thumbnail(root, option.dataset.fileUrl, option.dataset.fileLabel, option.dataset.fileImage === '1'), name);
  };
  const setBusy = (widget, value) => {
    if (value) widget.setAttribute('aria-busy', 'true');
    else widget.removeAttribute('aria-busy');
    widget.querySelectorAll('[data-file-browse], [data-file-pick], [data-file-remove]').forEach(control => { control.disabled = value; });
  };
  const choose = (widget, choice) => {
    const select = widget.querySelector('[data-file-select]');
    [...select.options].filter(option => option.value === choice.code).forEach(option => option.remove());
    select.append(optionFor(choice));
    select.value = choice.code;
    render(widget);
  };
  const upload = async (widget, file) => {
    if (uploads.has(widget)) return;
    const status = widget.querySelector('[data-file-status]');
    if (file.size > Number(root.dataset.maxBytes)) { show(status, root.dataset.tooLarge, true); return; }
    const controller = new AbortController();
    uploads.set(widget, controller);
    setBusy(widget, true);
    show(status, root.dataset.uploading);
    const body = new FormData();
    body.append('upload', file);
    body.append('csrf_token', form.elements.namedItem('csrf_token').value);
    try {
      const response = await fetch(root.dataset.uploadUrl, {method: 'POST', body, signal: controller.signal, headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok || data.uploaded !== 1) throw new FileRequestError(data.error?.message || root.dataset.uploadError);
      const choice = {code: data.code ?? String(data.id), label: data.name, url: data.url, sizeLabel: data.size_label, image: data.image};
      if (!validChoice(choice)) throw new FileRequestError(root.dataset.uploadError);
      if (uploads.get(widget) !== controller || !widget.isConnected) return;
      choose(widget, choice);
      show(status, root.dataset.uploaded);
    } catch (error) {
      if (error.name !== 'AbortError' && uploads.get(widget) === controller) show(status, error instanceof FileRequestError ? error.message : root.dataset.uploadError, true);
    } finally {
      if (uploads.get(widget) === controller) { uploads.delete(widget); setBusy(widget, false); }
    }
  };
  /** The first file goes where it was dropped, the others fill the empty rows and then new ones. */
  const uploadAll = (widget, files) => {
    const free = () => widgets().filter(candidate => candidate !== widget && !uploads.has(candidate) && !candidate.querySelector('[data-file-select]').value);
    const [first, ...rest] = files;
    upload(widget, first);
    for (const file of rest) {
      const [target] = free();
      if (target) { upload(target, file); continue; }
      const count = widgets().length;
      if (addRow) addRow.click();
      if (widgets().length === count) { show(root.querySelector('[data-repeat-status]'), root.dataset.tooMany, true); return; }
      upload(widgets().at(-1), file);
    }
  };
  const showFiles = files => {
    const current = pickerTarget?.querySelector('[data-file-select]').value;
    pickerResults.replaceChildren(...files.map(choice => {
      const tile = document.createElement('button');
      tile.type = 'button';
      tile.className = 'collection-file-tile';
      if (choice.code === current) tile.setAttribute('aria-current', 'true');
      const name = document.createElement('span');
      name.className = 'collection-file-tile-name';
      name.textContent = choice.label;
      name.title = choice.label;
      const size = document.createElement('small');
      size.className = 'text-body-secondary';
      size.textContent = choice.sizeLabel;
      tile.append(thumbnail(root, choice.url, '', choice.image), name, size);
      tile.addEventListener('click', () => {
        const widget = pickerTarget;
        picker.close();
        choose(widget, choice);
        show(widget.querySelector('[data-file-status]'), root.dataset.chosen);
        widget.querySelector('[data-file-actions] [data-file-pick]').focus();
      });
      return tile;
    }));
    show(pickerStatus, files.length === 0 ? root.dataset.empty : (files.length >= 30 ? root.dataset.more : ''));
  };
  const search = async () => {
    searchController?.abort();
    const controller = new AbortController();
    searchController = controller;
    picker.setAttribute('aria-busy', 'true');
    show(pickerStatus, root.dataset.loading);
    try {
      const url = new URL(root.dataset.source, window.location.origin);
      url.searchParams.set('q', pickerQuery.value);
      const response = await fetch(url, {signal: controller.signal, headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new FileRequestError(data.error?.message || root.dataset.error);
      if (!Array.isArray(data.files) || data.files.some(choice => !validChoice(choice))) throw new FileRequestError(root.dataset.error);
      if (searchController === controller) showFiles(data.files);
    } catch (error) {
      if (error.name !== 'AbortError' && searchController === controller) show(pickerStatus, error instanceof FileRequestError ? error.message : root.dataset.error, true);
    } finally {
      if (searchController === controller) picker.removeAttribute('aria-busy');
    }
  };
  const openPicker = widget => {
    pickerTarget = widget;
    pickerQuery.value = '';
    pickerResults.replaceChildren();
    picker.showModal();
    pickerQuery.focus();
    search();
  };
  root.addEventListener('click', event => {
    if (event.target === picker || event.target.closest('[data-file-picker-close]')) { picker.close(); return; }
    const button = event.target.closest('[data-file-browse], [data-file-pick], [data-file-remove]');
    if (!button || button.disabled) return;
    const widget = button.closest('[data-file-widget]');
    if (button.matches('[data-file-browse]')) widget.querySelector('[data-file-upload-input]').click();
    else if (button.matches('[data-file-pick]')) openPicker(widget);
    else {
      widget.querySelector('[data-file-select]').value = '';
      widget.querySelector('[data-file-upload-input]').value = '';
      render(widget);
      show(widget.querySelector('[data-file-status]'), root.dataset.cleared);
      widget.querySelector('[data-file-drop] [data-file-browse]').focus();
    }
  });
  root.addEventListener('change', event => {
    if (!event.target.matches('[data-file-upload-input]') || !event.target.files.length) return;
    const files = [...event.target.files];
    event.target.value = '';
    uploadAll(event.target.closest('[data-file-widget]'), files);
  });
  root.addEventListener('dragover', event => {
    const widget = event.target.closest('[data-file-widget]');
    if (!widget || !event.dataTransfer?.types.includes('Files')) return;
    event.preventDefault();
    widget.classList.add('is-dropping');
  });
  root.addEventListener('dragleave', event => {
    const widget = event.target.closest('[data-file-widget]');
    if (widget && !widget.contains(event.relatedTarget)) widget.classList.remove('is-dropping');
  });
  root.addEventListener('drop', event => {
    const widget = event.target.closest('[data-file-widget]');
    if (!widget) return;
    event.preventDefault();
    widget.classList.remove('is-dropping');
    if (!uploads.has(widget) && event.dataTransfer.files.length) uploadAll(widget, [...event.dataTransfer.files]);
  });
  pickerQuery.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(search, 250);
  });
  pickerQuery.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      clearTimeout(searchTimer);
      search();
    }
  });
  picker.addEventListener('close', () => {
    clearTimeout(searchTimer);
    searchController?.abort();
    searchController = null;
    picker.removeAttribute('aria-busy');
  });
  root.addEventListener('collection:values-changed', () => widgets().forEach(render));
  form.addEventListener('submit', event => {
    if (!uploads.size) return;
    event.preventDefault();
    const [widget] = uploads.keys();
    show(widget.querySelector('[data-file-status]'), root.dataset.uploading, true);
  });
  form.addEventListener('reset', () => {
    uploads.forEach((controller, widget) => { controller.abort(); setBusy(widget, false); });
    uploads.clear();
    queueMicrotask(() => widgets().forEach(widget => { render(widget); show(widget.querySelector('[data-file-status]'), ''); }));
  });
  root.querySelectorAll(':scope > [data-file-native]').forEach(node => { node.hidden = true; });
  root.querySelectorAll(':scope > label[for]').forEach(label => label.removeAttribute('for'));
  widgets().forEach(render);
});
