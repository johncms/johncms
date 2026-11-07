import { createRelationDefaultPicker } from './relation-default-picker';
import { initializeAdminEditors, labelAdminEditors, removeAdminEditors, focusAdminEditor, setAdminEditorValue } from './editor';

/** Ordinary POST buttons remain the fallback when this enhancement is unavailable. */
document.querySelectorAll('[data-collection-repeat]').forEach(root => {
  const list = root.querySelector('[data-repeat-rows]');
  const template = root.querySelector('[data-repeat-template]');
  const status = root.querySelector('[data-repeat-status]');
  const add = root.querySelector('[data-repeat-op="add"]');
  const limit = () => Number(root.dataset.limit);
  let serial = 0;
  let busy = false;
  const rows = () => [...list.children].filter(row => row.matches('[data-repeat-row]'));
  const refresh = () => {
    const entries = rows();
    entries.forEach((row, index) => {
      row.querySelectorAll('[data-repeat-number]').forEach(number => { number.textContent = index + 1; });
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
    add.disabled = entries.length >= limit();
    root.dispatchEvent(new CustomEvent('collection:values-changed', {bubbles: true}));
  };
  refresh();
  root.addEventListener('collection:refresh', refresh);

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
        if (rows().length >= limit()) {
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
  const useDefault = form.querySelector('input[name="use_default"]');
  const defaults = form.querySelector('[data-default-settings]');
  const dateSettings = form.querySelector('[data-date-default-settings]');
  const dateMode = form.elements.date_default_mode;
  const isDate = () => ['date', 'datetime'].includes(type.value);
  const defaultGroup = value => (['string', 'text', 'html'].includes(value) ? 'text' : value);
  const defaultDrafts = new Map();
  let shownGroup = defaultGroup(type.value);
  form.querySelector('[data-refresh-field-settings]')?.setAttribute('hidden', '');
  const lookup = form.querySelector('[data-default-lookup]');
  const lookupStatus = lookup.querySelector('[data-default-lookup-status]');
  const lookupResult = lookup.querySelector('[data-default-lookup-result]');
  let lookupOptions = JSON.parse(lookup.dataset.options);
  let lookupRequest;
  const fieldErrors = JSON.parse(form.dataset.fieldErrors);
  for (const [key, message] of Object.entries(fieldErrors)) {
    const [name, index] = key.split('.');
    const inputs = [...form.querySelectorAll('[name]')].filter(input => input.type !== 'hidden' && input.name === (index === undefined ? name : `${name}[]`));
    for (const input of index === undefined ? inputs : inputs.slice(Number(index), Number(index) + 1)) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), `error-${key.replaceAll('.', '-')}`].filter(Boolean).join(' '));
      input.classList.add('is-invalid');
    }
  }
  let htmlQueue = Promise.resolve();
  const syncHtml = () => {
    htmlQueue = htmlQueue.then(async () => {
      for (const source of defaults.querySelectorAll('textarea')) {
        const wrapper = source.closest('[data-default-html]');
        if (source.matches(':disabled') && !wrapper) continue;
        if (type.value === 'html' && !wrapper) {
          const editor = form.parentElement.querySelector('[data-default-editor-template]').content.firstElementChild.cloneNode(true);
          editor.setAttribute('data-default-html', '');
          editor.querySelector('label')?.remove();
          source.setAttribute('aria-label', form.querySelector(`label[for="${source.id}"]`)?.textContent.trim() || editor.querySelector('textarea').getAttribute('aria-label'));
          source.before(editor);
          editor.querySelector('textarea').replaceWith(source);
          editor.querySelector('textarea').hidden = false;
          await initializeAdminEditors(editor);
        } else if (type.value !== 'html' && wrapper) {
          if (!await removeAdminEditors(wrapper)) continue;
          wrapper.replaceWith(source);
        }
      }
      enhanceDefaults();
    }).catch(error => { console.error('Could not update default editors.', error); });
  };
  const updateLimit = () => {
    const root = defaults.querySelector('[data-collection-repeat]');
    const input = form.elements.max_values;
    const limit = /^\d+$/.test(input.value) && Number(input.value) >= 1 && Number(input.value) <= 1000 ? Number(input.value) : 1000;
    root.dataset.limit = String(limit);
    const sources = [...root.querySelectorAll('[data-repeat-row] textarea')];
    root.querySelector('[data-repeat-op="add"]').disabled = sources.length >= limit;
    input.setCustomValidity(useDefault.checked && (!isDate() || dateMode.value === 'fixed') && multiple.checked && sources.filter(source => source.value.trim() !== '').length > limit ? defaults.dataset.limitError : '');
  };
  const enhanceDefaults = () => {
    updateLimit();
    defaults.querySelectorAll('textarea').forEach(source => {
      if (source.closest('[data-default-html]')) return;
      const existing = source.previousElementSibling;
      if (existing?.hasAttribute('data-default-control')) existing.remove();
      source.hidden = false;
      const label = form.querySelector(`label[for="${source.id}"], label[for="${source.id}-control"]`);
      if (label) label.htmlFor = source.id;
      if (type.value === 'relation') { source.hidden = true; return; }
      const inputType = {string: 'text', integer: 'number', double: 'number', date: 'date', datetime: 'datetime-local'}[type.value];
      if (!inputType && !['boolean', 'select'].includes(type.value)) return;
      if (type.value === 'string' && /[\r\n]/.test(source.value)) return;
      const control = document.createElement(['boolean', 'select'].includes(type.value) ? 'select' : 'input');
      control.setAttribute('data-default-control', '');
      control.className = ['boolean', 'select'].includes(type.value) ? 'form-select' : 'form-control';
      if (type.value === 'select') {
        control.add(new Option(defaults.dataset.empty, ''));
        form.querySelectorAll('[data-key="options"] [data-repeat-row]').forEach(row => {
          const code = row.querySelector('[name="option_code[]"]').value;
          const label = row.querySelector('[name="option_label[]"]').value;
          const active = row.querySelector('[name="option_active[]"]').value === '1';
          if (code && active) control.add(new Option(label || code, code));
        });
        if (source.value && ![...control.options].some(option => option.value === source.value)) {
          const unavailable = new Option(source.value, source.value);
          unavailable.disabled = true;
          control.add(unavailable);
          control.setCustomValidity(defaults.dataset.invalid);
          control.setAttribute('aria-invalid', 'true');
        }
      } else if (type.value === 'boolean') {
        for (const [value, label] of [['', defaults.dataset.empty], ['1', defaults.dataset.yes], ['0', defaults.dataset.no]]) {
          control.add(new Option(label, value));
        }
        if (!['', '0', '1'].includes(source.value)) return;
      } else {
        control.type = inputType;
        if (inputType === 'number') control.step = type.value === 'integer' ? '1' : 'any';
        if (inputType === 'datetime-local') control.step = '1';
      }
      const value = type.value === 'datetime' ? source.value.replace(' ', 'T')
        : type.value === 'date' && /^\d{4}-\d{2}-\d{2} 00:00:00$/.test(source.value) ? source.value.slice(0, 10) : source.value;
      control.value = value;
      // Native controls may sanitize invalid input. Keep the original editable in that case.
      if (value !== '' && control.value === '') return;
      control.id = `${source.id}-control`;
      for (const attribute of ['aria-invalid', 'aria-describedby']) {
        if (source.hasAttribute(attribute)) control.setAttribute(attribute, source.getAttribute(attribute));
      }
      if (source.classList.contains('is-invalid')) control.classList.add('is-invalid');
      if (inputType === 'number') {
        for (const bound of ['min', 'max']) {
          const value = form.elements.namedItem(`${bound}_value`).value;
          if (value !== '') control.setAttribute(bound, value);
        }
      }
      if (inputType === 'text' || type.value === 'text') {
        for (const bound of ['min', 'max']) {
          const value = form.elements.namedItem(`${bound}_length`).value;
          if (/^\d+$/.test(value)) control.setAttribute(`${bound}length`, value);
        }
      }
      if (label) label.htmlFor = control.id;
      control.addEventListener('input', () => {
        source.value = control.value;
        control.setCustomValidity('');
        control.removeAttribute('aria-invalid');
      });
      source.before(control);
      source.hidden = true;
    });
    relationPicker.refresh();
    const rawInputVisible = [...defaults.querySelectorAll('textarea')].some(source => !source.hidden && !source.matches(':disabled'));
    defaults.querySelectorAll('[data-default-raw-help]').forEach(hint => {
      hint.hidden = hint.dataset.defaultHelpTypes !== type.value || !rawInputVisible;
    });
  };
  const toggle = (group, visible) => {
    group.hidden = !visible;
    group.disabled = !visible;
  };
  const updateRole = () => {
    const role = form.elements.role;
    let available = 0;
    for (const option of [...role.options].filter(candidate => candidate.dataset.types)) {
      const fits = option.dataset.types.split(' ').includes(type.value) && (!('multiple' in option.dataset) || multiple.checked);
      option.hidden = !fits;
      option.disabled = !fits;
      if (fits) available++;
      else if (option.selected) role.value = '';
    }
    form.querySelector('[data-field-role]').hidden = available === 0 && role.value === '';
  };
  const update = () => {
    const date = isDate();
    toggle(dateSettings, date);
    form.querySelector('[data-default-toggle]').hidden = date || type.value === 'relation';
    if (type.value === 'relation') useDefault.checked = (multiple.checked ? repeatedSources() : [single]).some(source => source.value.trim() !== '');
    if (date) useDefault.checked = dateMode.value !== 'none';
    if (type.value === 'file') useDefault.checked = false;
    defaults.hidden = type.value === 'file';
    const current = dateMode.querySelector('[value="current"]');
    current.textContent = type.value === 'date' ? current.dataset.currentDateLabel : current.dataset.currentDatetimeLabel;
    form.querySelector('[data-date-current-help]').hidden = !date || dateMode.value !== 'current';
    const fixedDefault = useDefault.checked && (!date || dateMode.value === 'fixed');
    if (date && !fixedDefault) toggle(choice, false);
    const deletion = form.elements.relation_on_delete;
    const required = form.elements.required.checked && type.value === 'relation';
    deletion.querySelector('[value="clear"]').disabled = required;
    if (required) deletion.value = 'restrict';
    form.querySelector('[data-required-relation-help]').hidden = !required;
    form.querySelectorAll('[data-relation-delete-help]').forEach(help => { help.hidden = required || help.dataset.relationDeleteHelp !== deletion.value; });
    const boolean = type.value === 'boolean';
    if (boolean) form.elements.required.checked = false;
    form.querySelector('[data-field-required]').hidden = boolean;
    form.querySelector('[data-field-multiple-switch]').hidden = boolean && !multiple.checked;
    updateRole();
    form.elements.max_length.max = type.value === 'string' ? '255' : '1000000';
    toggle(lookup, type.value === 'relation');
    toggle(form.querySelector('[data-default-single]'), fixedDefault && !multiple.checked);
    toggle(form.querySelector('[data-default-multiple]'), fixedDefault && multiple.checked);
    if (type.value === 'relation') {
      form.querySelector('[data-default-single]').hidden = true;
      form.querySelector('[data-default-multiple]').hidden = true;
    }
    form.querySelectorAll('[data-field-types]').forEach(group => toggle(group, group.dataset.fieldTypes.split(' ').includes(type.value)));
    form.querySelectorAll('[data-field-multiple]').forEach(group => toggle(group, multiple.checked));
    form.querySelectorAll('input[name="min_value"], input[name="max_value"]').forEach(input => {
      input.step = type.value === 'integer' ? '1' : 'any';
    });
    enhanceDefaults();
    syncHtml();
  };
  const choice = form.querySelector('[data-default-choice]');
  const choiceSelect = choice.querySelector('select');
  const single = form.querySelector('[name="default_value"]');
  const repeatedSources = () => [...defaults.querySelectorAll('[data-default-multiple] [data-repeat-row] textarea')];
  const selectedDefaults = () => (multiple.checked ? repeatedSources().map(source => source.value) : [single.value]).filter(value => value.trim() !== '');
  const writeRepeated = async values => {
    const root = defaults.querySelector('[data-collection-repeat]');
    toggle(form.querySelector('[data-default-multiple]'), true);
    const rows = () => [...root.querySelector('[data-repeat-rows]').children];
    const count = Math.max(1, values.length);
    for (const row of rows().slice(count)) {
      if (!await removeAdminEditors(row)) return false;
      row.remove();
    }
    while (rows().length < count) {
      const before = rows().length;
      root.querySelector('[data-repeat-op="add"]').click();
      if (rows().length === before) return false;
    }
    repeatedSources().forEach((source, index) => {
      const wrapper = source.closest('[data-default-html]');
      if (wrapper) setAdminEditorValue(wrapper, values[index] ?? '');
      else source.value = values[index] ?? '';
    });
    root.dispatchEvent(new Event('collection:refresh'));
    root.querySelector('[data-repeat-status]').textContent = '';
    return true;
  };
  const relationPicker = createRelationDefaultPicker({
    form, lookup, isActive: () => type.value === 'relation', isMultiple: () => multiple.checked,
    getOptions: () => lookupOptions, search: term => loadLookup(term),
    getLimit: () => Number(defaults.querySelector('[data-collection-repeat]').dataset.limit),
    cancelSearch: () => {
      lookupRequest?.abort();
      lookupRequest = null;
      lookup.removeAttribute('aria-busy');
    },
    getValues: () => selectedDefaults(),
    setValues: async values => {
      const target = form.querySelector('#relation-target').value;
      const shape = multiple.checked;
      await htmlQueue;
      if (type.value !== 'relation' || target !== form.querySelector('#relation-target').value || shape !== multiple.checked) return false;
      if (multiple.checked) {
        if (values.length > Number(defaults.querySelector('[data-collection-repeat]').dataset.limit)) { lookupStatus.textContent = defaults.dataset.limitError; return false; }
        if (!await writeRepeated(values)) return false;
      } else single.value = values[0] ?? '';
      useDefault.checked = values.length > 0;
      update();
      return true;
    },
  });
  const loadLookup = async term => {
    lookupRequest?.abort();
    const request = new AbortController();
    lookupRequest = request;
    lookup.setAttribute('aria-busy', 'true');
    try {
      const body = new FormData(form);
      if (term !== undefined) body.set('default_query', term);
      const response = await fetch(lookup.dataset.url, {
        method: 'POST', credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body, signal: request.signal,
      });
      const data = await response.json();
      if (!response.ok) throw new Error(typeof data.error === 'string' ? data.error : lookup.dataset.error);
      if (!Array.isArray(data.options) || !data.options.every(option => typeof option.code === 'string' && typeof option.label === 'string')) throw new Error(lookup.dataset.error);
      if (lookupRequest !== request) return null;
      lookupOptions = data.options;
      lookupResult.replaceChildren(new Option(defaults.dataset.empty, ''), ...lookupOptions.map(option => new Option(option.label, option.code)));
      lookupStatus.textContent = '';
      enhanceDefaults();
      return lookupOptions;
    } catch (error) {
      if (lookupRequest === request && error.name !== 'AbortError') lookupStatus.textContent = error instanceof TypeError ? lookup.dataset.error : (error.message || lookup.dataset.error);
      return null;
    } finally {
      if (lookupRequest === request) lookup.removeAttribute('aria-busy');
    }
  };
  lookup.querySelector('[name="lookup_defaults"]').addEventListener('click', event => { event.preventDefault(); loadLookup(); });
  lookup.querySelector('[data-default-lookup-apply]').addEventListener('click', event => {
    event.preventDefault();
    if (!lookupResult.value) return;
    useDefault.checked = true;
    if (multiple.checked) {
      let target = repeatedSources().find(source => source.value === '');
      if (!target) {
        defaults.querySelector('[data-default-multiple] [data-repeat-op="add"]').click();
        target = repeatedSources().at(-1);
      }
      if (target) target.value = lookupResult.value;
    } else single.value = lookupResult.value;
    update();
  });
  const invalidateLookup = () => {
    lookupRequest?.abort();
    lookupRequest = null;
    lookup.removeAttribute('aria-busy');
    lookupOptions = [];
    lookupResult.replaceChildren(new Option(defaults.dataset.empty, ''));
    lookupStatus.textContent = '';
    enhanceDefaults();
  };
  const target = form.querySelector('[name="target_collection_id"]');
  const reloadSelected = () => {
    if (type.value === 'relation' && target.value && selectedDefaults().length) loadLookup('');
  };
  target.addEventListener('change', () => {
    invalidateLookup();
    reloadSelected();
  });
  type.addEventListener('change', invalidateLookup);
  /** A default belongs to its kind of value: another kind starts empty, and coming back restores it. */
  const switchDefaults = () => {
    const group = defaultGroup(type.value);
    if (group === shownGroup) return;
    defaultDrafts.set(shownGroup, {value: single.value, values: repeatedSources().map(source => source.value), use: useDefault.checked});
    shownGroup = group;
    const draft = defaultDrafts.get(group) ?? {value: '', values: [], use: false};
    toggle(choice, false);
    useDefault.checked = draft.use;
    const wrapper = single.closest('[data-default-html]');
    if (wrapper) setAdminEditorValue(wrapper, draft.value);
    else single.value = draft.value;
    htmlQueue = htmlQueue.then(() => writeRepeated(draft.values)).catch(error => { console.error('Could not restore default values.', error); }).then(() => {
      update();
      reloadSelected();
    });
  };
  const fileTypeGroups = [...form.querySelectorAll('[data-file-type-group]')].map(group => {
    const toggler = group.querySelector('[data-file-type-toggle]');
    const types = [...group.querySelectorAll('[name="allowed_mime_types[]"]')];
    const sync = () => {
      const chosen = types.filter(input => input.checked).length;
      toggler.checked = chosen === types.length;
      toggler.indeterminate = chosen > 0 && chosen < types.length;
    };
    toggler.hidden = false;
    toggler.addEventListener('change', () => types.forEach(input => { input.checked = toggler.checked; }));
    types.forEach(input => input.addEventListener('change', sync));
    sync();
    return sync;
  });
  const sizeUnit = form.elements.file_size_unit;
  let currentSizeUnit = Number(sizeUnit.value);
  sizeUnit.addEventListener('change', () => {
    const size = form.elements.max_file_bytes;
    const unit = Number(sizeUnit.value);
    if (size.value !== '' && Number.isFinite(Number(size.value))) size.value = String(Number(size.value) * currentSizeUnit / unit);
    currentSizeUnit = unit;
  });
  multiple.addEventListener('change', () => {
    if (isDate() && dateMode.value !== 'fixed') { toggle(choice, false); update(); return; }
    if (multiple.checked) {
      toggle(form.querySelector('[data-default-multiple]'), true);
      if (single.value !== '' && repeatedSources().every(source => source.value === '')) {
        if (!repeatedSources().length) defaults.querySelector('[data-repeat-op="add"]').click();
        const first = repeatedSources()[0];
        if (first) {
          first.value = single.value;
          if (first.closest('[data-default-html]')) setAdminEditorValue(first.closest('[data-default-html]'), single.value);
        }
      }
    } else {
      const values = repeatedSources().map(source => source.value).filter(value => value.trim() !== '');
      if (values.length > 1) {
        multiple.checked = true;
        choiceSelect.replaceChildren(...values.map((value, index) => new Option(`${index + 1}. ${type.value === 'relation' ? relationPicker.label(value) : value}`, value)));
        toggle(choice, true);
        choiceSelect.focus();
        return;
      }
      single.value = values[0] ?? single.value;
      if (single.closest('[data-default-html]')) setAdminEditorValue(single.closest('[data-default-html]'), single.value);
    }
    toggle(choice, false);
    update();
  });
  choice.querySelector('[data-default-choose]').addEventListener('click', event => {
    event.preventDefault();
    single.value = choiceSelect.value;
    if (single.closest('[data-default-html]')) setAdminEditorValue(single.closest('[data-default-html]'), single.value);
    multiple.checked = false;
    toggle(choice, false);
    update();
    single.previousElementSibling?.matches('[data-default-control]') ? single.previousElementSibling.focus() : single.focus();
  });
  choice.querySelector('[data-default-cancel]').addEventListener('click', event => {
    event.preventDefault();
    toggle(choice, false);
    multiple.focus();
  });
  type.addEventListener('change', switchDefaults);
  type.addEventListener('change', update);
  form.elements.required.addEventListener('change', update);
  form.elements.relation_on_delete.addEventListener('change', update);
  useDefault.addEventListener('change', update);
  dateMode.addEventListener('change', update);
  form.elements.max_values.addEventListener('input', updateLimit);
  defaults.addEventListener('input', updateLimit);
  for (const input of form.querySelectorAll('[name="min_length"], [name="max_length"], [name="min_value"], [name="max_value"]')) input.addEventListener('change', enhanceDefaults);
  form.addEventListener('collection:values-changed', event => {
    if (defaults.contains(event.target) || event.target.matches('[data-key="options"]')) { enhanceDefaults(); syncHtml(); }
  });
  form.addEventListener('input', event => {
    if (event.target.closest('[data-key="options"]')) enhanceDefaults();
  });
  form.addEventListener('change', event => {
    if (event.target.closest('[data-key="options"]')) enhanceDefaults();
  });
  form.addEventListener('reset', () => queueMicrotask(() => { lookupRequest?.abort(); lookupRequest = null; lookup.removeAttribute('aria-busy'); toggle(choice, false); lookupOptions = JSON.parse(lookup.dataset.options); currentSizeUnit = Number(sizeUnit.value); fileTypeGroups.forEach(sync => sync()); shownGroup = defaultGroup(type.value); defaultDrafts.clear(); update(); }));
  form.addEventListener('submit', () => {
    form.elements.default_shape.value = multiple.checked ? '1' : '0';
    form.elements.default_type.value = type.value;
    const drafts = form.querySelector('[data-default-drafts]');
    drafts.replaceChildren();
    const values = [
      ...(single.matches(':disabled') ? [['draft_default_value', single.value]] : []),
      ...repeatedSources().filter(source => source.matches(':disabled')).map(source => ['draft_default_values[]', source.value]),
    ];
    for (const [name, value] of values) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      drafts.append(input);
    }
  });
  update();
  form.parentElement.querySelector('[data-field-error-summary]')?.focus();
});
