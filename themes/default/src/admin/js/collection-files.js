class FileRequestError extends Error {}

const validChoice = choice => choice && typeof choice.code === 'string' && /^[1-9]\d*$/u.test(choice.code)
  && typeof choice.label === 'string' && typeof choice.url === 'string' && typeof choice.sizeLabel === 'string' && typeof choice.image === 'boolean';

function optionFor(choice, defaultSelected = false) {
  const option = new Option(`${choice.label} (${choice.sizeLabel})`, choice.code, defaultSelected);
  option.dataset.fileLabel = choice.label;
  option.dataset.fileUrl = choice.url;
  option.dataset.fileSize = choice.sizeLabel;
  option.dataset.fileImage = choice.image ? '1' : '0';
  return option;
}

function refreshPreview(widget) {
  const root = widget.closest('[data-file-field]');
  const select = widget.querySelector('[data-file-select]');
  const preview = widget.querySelector('[data-file-preview]');
  const option = select.selectedOptions[0];
  preview.replaceChildren();
  preview.hidden = !select.value;
  if (!select.value) return;
  if (!option?.dataset.fileUrl) {
    const unavailable = document.createElement('div');
    unavailable.className = 'small text-danger';
    unavailable.textContent = root.dataset.unavailable;
    preview.append(unavailable);
    return;
  }
  const line = document.createElement('div');
  line.className = 'd-flex flex-wrap gap-2 align-items-baseline';
  const link = document.createElement('a');
  link.href = option.dataset.fileUrl;
  link.target = '_blank';
  link.rel = 'noopener';
  link.className = 'text-break';
  link.textContent = option.dataset.fileLabel;
  const size = document.createElement('span');
  size.className = 'small text-body-secondary';
  size.textContent = option.dataset.fileSize;
  line.append(link, size);
  preview.append(line);
  if (option.dataset.fileImage === '1') {
    const image = document.createElement('img');
    image.src = option.dataset.fileUrl;
    image.alt = option.dataset.fileLabel;
    image.loading = 'lazy';
    image.className = 'img-thumbnail object-fit-contain mt-2';
    image.style.maxWidth = '100%';
    image.style.maxHeight = '160px';
    preview.append(image);
  }
}

function updateChoices(select, choices) {
  const value = select.value;
  const retained = [...select.options].filter(option => option.value === value || option.defaultSelected);
  const defaults = new Set(retained.filter(option => option.defaultSelected).map(option => option.value));
  const empty = [...select.options].find(option => option.value === '')?.cloneNode(true) ?? new Option('', '');
  const options = [empty, ...choices.map(choice => optionFor(choice, defaults.has(choice.code)))];
  retained.forEach(option => {
    if (option.value && !options.some(candidate => candidate.value === option.value)) options.push(option.cloneNode(true));
  });
  select.replaceChildren(...options);
  select.value = value;
}

/** Selects and multipart POST controls remain usable when this enhancement is absent. */
document.querySelectorAll('[data-file-field]').forEach(root => {
  const form = root.closest('form');
  const query = root.querySelector('[data-file-query]');
  const searchButton = root.querySelector('[data-file-search]');
  const searchStatus = root.querySelector('[data-file-search-status]');
  const uploads = new Map();
  let searchController;
  const widgets = () => [...root.querySelectorAll('[data-file-widget]')];
  const setBusy = (widget, value) => {
    widget.toggleAttribute('aria-busy', value);
    if (value) widget.setAttribute('aria-busy', 'true');
    widget.querySelectorAll('[data-file-select], [data-file-upload-input], [data-file-upload-button], [data-file-clear]').forEach(control => { control.disabled = value; });
  };
  const show = (node, message, error = false) => {
    node.textContent = message;
    node.classList.toggle('text-danger', error);
  };
  const search = async () => {
    searchController?.abort();
    const controller = new AbortController();
    searchController = controller;
    searchButton.disabled = true;
    root.setAttribute('aria-busy', 'true');
    show(searchStatus, root.dataset.loading);
    try {
      const url = new URL(root.dataset.source, window.location.origin);
      url.searchParams.set('q', query.value);
      const response = await fetch(url, {signal: controller.signal, headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new FileRequestError(data.error?.message || root.dataset.error);
      if (!Array.isArray(data.files) || data.files.some(choice => !validChoice(choice))) throw new FileRequestError(root.dataset.error);
      if (searchController !== controller) return;
      root.querySelectorAll('[data-file-select]').forEach(select => updateChoices(select, data.files));
      root.querySelectorAll('template[data-repeat-template]').forEach(template => template.content.querySelectorAll('[data-file-select]').forEach(select => updateChoices(select, data.files)));
      widgets().forEach(refreshPreview);
      show(searchStatus, data.files.length ? root.dataset.loaded : root.dataset.empty);
    } catch (error) {
      if (error.name !== 'AbortError' && searchController === controller) show(searchStatus, error instanceof FileRequestError ? error.message : root.dataset.error, true);
    } finally {
      if (searchController === controller) { searchButton.disabled = false; root.removeAttribute('aria-busy'); }
    }
  };
  const upload = async widget => {
    if (uploads.has(widget)) return;
    const input = widget.querySelector('[data-file-upload-input]');
    const file = input.files[0];
    const status = widget.querySelector('[data-file-status]');
    if (!file) { show(status, root.dataset.noFile, true); input.focus(); return; }
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
      const select = widget.querySelector('[data-file-select]');
      select.querySelectorAll('option').forEach(option => { if (option.value === choice.code) option.remove(); });
      select.append(optionFor(choice));
      select.value = choice.code;
      input.value = '';
      refreshPreview(widget);
      show(status, root.dataset.uploaded);
    } catch (error) {
      if (error.name !== 'AbortError' && uploads.get(widget) === controller) show(status, error instanceof FileRequestError ? error.message : root.dataset.uploadError, true);
    } finally {
      if (uploads.get(widget) === controller) { uploads.delete(widget); setBusy(widget, false); }
    }
  };
  root.addEventListener('click', event => {
    if (event.target.closest('[data-file-search]')) { event.preventDefault(); search(); return; }
    const button = event.target.closest('[data-file-upload-button], [data-file-clear]');
    if (!button || button.disabled) return;
    event.preventDefault();
    const widget = button.closest('[data-file-widget]');
    if (button.matches('[data-file-upload-button]')) { upload(widget); return; }
    widget.querySelector('[data-file-select]').value = '';
    widget.querySelector('[data-file-upload-input]').value = '';
    refreshPreview(widget);
    show(widget.querySelector('[data-file-status]'), root.dataset.cleared);
  });
  root.addEventListener('change', event => {
    const widget = event.target.closest('[data-file-widget]');
    if (!widget) return;
    if (event.target.matches('[data-file-select]')) refreshPreview(widget);
    show(widget.querySelector('[data-file-status]'), '');
  });
  query.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); search(); } });
  query.addEventListener('input', () => { searchController?.abort(); searchController = null; searchButton.disabled = false; root.removeAttribute('aria-busy'); });
  root.addEventListener('collection:values-changed', () => widgets().forEach(refreshPreview));
  form.addEventListener('submit', event => {
    if (uploads.size) { event.preventDefault(); show(searchStatus, root.dataset.uploading, true); }
  });
  form.addEventListener('reset', () => {
    searchController?.abort();
    searchController = null;
    searchButton.disabled = false;
    root.removeAttribute('aria-busy');
    uploads.forEach((controller, widget) => { controller.abort(); setBusy(widget, false); });
    uploads.clear();
    queueMicrotask(() => { widgets().forEach(widget => { refreshPreview(widget); show(widget.querySelector('[data-file-status]'), ''); }); show(searchStatus, ''); });
  });
  widgets().forEach(refreshPreview);
});
