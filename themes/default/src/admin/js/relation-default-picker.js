/** The popup never participates in form layout; selection stays in the ordinary form controls. */
export function createRelationDefaultPicker({form, lookup, isActive, isMultiple, getValues, setValues, getOptions, getLimit, search, cancelSearch}) {
  const input = lookup.querySelector('[name="default_query"]');
  const label = lookup.querySelector('label[for="default-query"]');
  const originalLabel = label.textContent;
  const target = form.querySelector('#relation-target');
  const selected = lookup.querySelector('[data-relation-selected]');
  const control = lookup.querySelector('[data-relation-control]');
  const popup = lookup.querySelector('[data-relation-popup]');
  const results = lookup.querySelector('[data-relation-results]');
  const footer = lookup.querySelector('[data-relation-footer]');
  const count = lookup.querySelector('[data-relation-count]');
  const done = lookup.querySelector('[data-relation-done]');
  const clear = lookup.querySelector('[data-relation-clear]');
  const warning = lookup.querySelector('[data-relation-warning]');
  const status = lookup.querySelector('[data-default-lookup-status]');
  const strings = lookup.dataset;
  const labels = new Map();
  let active = false;
  let multiple = isMultiple();
  let options = [];
  let highlighted = -1;
  let shownTerm = null;
  let timer;
  let revision = 0;
  let scope = target.value;
  let busy = false;
  let loading = false;
  let focusing = false;
  const title = option => {
    const suffix = ` (#${option.code})`;
    return option.label.endsWith(suffix) ? option.label.slice(0, -suffix.length) : option.label;
  };
  const name = code => labels.get(code) ?? `${strings.relationUnavailable} (#${code})`;
  const searchTerm = () => !isMultiple() && getValues().length && input.value === name(getValues()[0]) ? '' : input.value;
  const position = () => {
    if (popup.hidden) return;
    const rect = control.getBoundingClientRect();
    const viewport = window.visualViewport;
    const viewportTop = viewport?.offsetTop ?? 0;
    const header = document.querySelector('.admin-topbar')?.getBoundingClientRect();
    const top = header && header.top <= viewportTop ? Math.max(viewportTop, header.bottom) : viewportTop;
    const bottom = viewportTop + (viewport?.height ?? window.innerHeight);
    const below = bottom - rect.bottom - 8;
    const above = rect.top - top - 8;
    const upwards = below < 240 && above > below;
    popup.classList.toggle('is-above', upwards);
    popup.style.setProperty('--relation-popup-height', `${Math.max(0, Math.min(320, upwards ? above : below))}px`);
  };
  const close = (restore = true) => {
    if (active || isActive()) cancelSearch();
    clearTimeout(timer);
    revision++;
    loading = false;
    popup.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    highlighted = -1;
    if (restore && !isMultiple()) input.value = getValues().length ? name(getValues()[0]) : '';
  };
  const open = () => {
    popup.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    position();
  };
  const focusInput = () => {
    focusing = true;
    input.focus({preventScroll: true});
    focusing = false;
  };
  const syncOptions = () => {
    const chosen = new Set(getValues());
    results.setAttribute('aria-multiselectable', String(isMultiple()));
    [...results.children].forEach((button, index) => {
      const checked = chosen.has(options[index].code);
      button.setAttribute('aria-selected', String(checked));
      const mark = button.querySelector('[data-relation-check]');
      mark.hidden = !isMultiple();
      mark.replaceChildren();
      if (checked) mark.append(lookup.querySelector('[data-relation-icon="check"]').content.cloneNode(true));
    });
    const limit = getLimit();
    count.textContent = limit < 1000
      ? strings.relationCountLimit.replace('%d', String(chosen.size)).replace('%d', String(limit))
      : strings.relationCountLabel.replace('%d', String(chosen.size));
    footer.hidden = !isMultiple();
  };
  const change = async values => {
    if (busy) return false;
    busy = true;
    try {
      const changed = await setValues([...new Set(values)]);
      refresh();
      return changed;
    } finally {
      busy = false;
    }
  };
  const choose = async code => {
    cancelSearch();
    status.textContent = '';
    clearTimeout(timer);
    const current = ++revision;
    loading = false;
    const term = input.value;
    const scroll = results.scrollTop;
    const values = getValues();
    const next = isMultiple() ? (values.includes(code) ? values.filter(value => value !== code) : [...values, code]) : [code];
    if (await change(next) === false || current !== revision || !active) return;
    if (isMultiple()) {
      input.value = term;
      syncOptions();
      results.scrollTop = scroll;
      position();
      focusInput();
    } else {
      close();
      focusInput();
    }
  };
  const highlight = index => {
    highlighted = index;
    [...results.children].forEach((button, i) => button.classList.toggle('active', i === index));
    const option = results.children[index];
    if (option) {
      input.setAttribute('aria-activedescendant', option.id);
      const rect = option.getBoundingClientRect();
      const list = results.getBoundingClientRect();
      if (rect.top < list.top) results.scrollTop += rect.top - list.top;
      else if (rect.bottom > list.bottom) results.scrollTop += rect.bottom - list.bottom;
    }
  };
  const show = (items, term) => {
    const scroll = shownTerm === term ? results.scrollTop : 0;
    const focusedCode = shownTerm === term ? options[highlighted]?.code : null;
    shownTerm = term;
    const query = term.trim().toLocaleLowerCase();
    options = items.filter(option => !query || title(option).toLocaleLowerCase().includes(query));
    results.replaceChildren();
    options.forEach((option, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'list-group-item list-group-item-action text-start d-flex align-items-center gap-2';
      button.id = `relation-default-result-${index}`;
      button.setAttribute('role', 'option');
      button.tabIndex = -1;
      const mark = document.createElement('span');
      mark.className = 'relation-default-check';
      mark.setAttribute('data-relation-check', '');
      mark.setAttribute('aria-hidden', 'true');
      const text = document.createElement('span');
      text.className = 'flex-grow-1';
      const heading = document.createElement('span');
      heading.className = 'd-block text-break';
      heading.textContent = title(option);
      const id = document.createElement('small');
      id.className = 'd-block text-muted';
      id.textContent = `#${option.code}`;
      text.append(heading, id);
      button.append(mark, text);
      button.addEventListener('pointerdown', event => event.preventDefault());
      button.addEventListener('mousedown', event => event.preventDefault());
      button.addEventListener('click', () => choose(option.code));
      results.append(button);
    });
    results.hidden = options.length === 0;
    input.removeAttribute('aria-activedescendant');
    highlighted = -1;
    status.textContent = options.length ? (items.length >= 30 ? strings.relationMore : '') : (term.trim() ? strings.empty : strings.relationNoRecords);
    syncOptions();
    open();
    results.scrollTop = scroll;
    const restored = options.findIndex(option => option.code === focusedCode);
    if (restored >= 0) highlight(restored);
  };
  const load = async term => {
    if (!isActive() || !target.value || input.matches(':disabled')) return;
    clearTimeout(timer);
    const current = ++revision;
    loading = true;
    if (getOptions().length) show(getOptions(), term);
    else { results.hidden = true; open(); }
    status.textContent = strings.relationLoading;
    const items = await search(term);
    if (current !== revision || !isActive()) return;
    loading = false;
    if (items !== null) {
      for (const option of items) labels.set(option.code, title(option));
      show(items, term);
    } else {
      results.hidden = true;
      highlighted = -1;
      input.removeAttribute('aria-activedescendant');
      open();
    }
  };
  const refresh = () => {
    const enabled = isActive();
    const shapeChanged = multiple !== isMultiple();
    multiple = isMultiple();
    if (enabled !== active || shapeChanged) {
      close();
      input.value = '';
      shownTerm = null;
    }
    active = enabled;
    selected.hidden = !enabled || !multiple || getValues().length === 0;
    clear.hidden = !enabled || multiple || getValues().length === 0;
    input.parentElement.classList.toggle('input-group', !enabled || !clear.hidden);
    if (!enabled) {
      control.after(status);
      warning.hidden = true;
      label.textContent = originalLabel;
      input.disabled = false;
      input.placeholder = '';
      for (const attribute of ['role', 'aria-controls', 'aria-describedby', 'aria-expanded', 'aria-autocomplete']) input.removeAttribute(attribute);
      input.setCustomValidity('');
      return;
    }
    if (status.parentElement !== popup) popup.prepend(status);
    if (scope !== target.value) {
      scope = target.value;
      close();
      shownTerm = null;
      status.textContent = '';
    }
    for (const option of getOptions()) labels.set(option.code, title(option));
    const values = getValues();
    const valid = new Set(getOptions().map(option => option.code));
    const invalid = values.some(code => !valid.has(code));
    input.setCustomValidity(invalid ? strings.relationInvalid : '');
    input.classList.toggle('is-invalid', invalid);
    input.setAttribute('aria-invalid', String(invalid));
    warning.hidden = !invalid;
    warning.textContent = invalid ? strings.relationInvalid : '';
    input.disabled = !target.value;
    input.placeholder = target.value ? strings.relationContext.replace('%s', target.selectedOptions[0].textContent) : strings.relationTarget;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', results.id);
    input.setAttribute('aria-describedby', 'default-lookup-status relation-default-warning');
    if (!input.hasAttribute('aria-expanded')) input.setAttribute('aria-expanded', 'false');
    label.textContent = multiple ? strings.relationPlural : strings.relationSingular;
    selected.replaceChildren();
    values.forEach((code, index) => {
      const row = document.createElement('li');
      row.className = 'relation-default-item';
      const text = document.createElement('span');
      text.className = 'relation-default-item-label';
      text.textContent = name(code);
      if (!valid.has(code)) text.classList.add('text-danger');
      const id = document.createElement('small');
      id.className = 'text-muted';
      id.textContent = `#${code}`;
      row.append(text, id);
      for (const [action, caption] of [...(values.length > 1 ? [['up', strings.relationUp], ['down', strings.relationDown]] : []), ['remove', strings.relationRemove]]) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm';
        button.append(lookup.querySelector(`[data-relation-icon="${action}"]`).content.cloneNode(true));
        button.setAttribute('aria-label', caption);
        button.title = caption;
        button.disabled = (action === 'up' && index === 0) || (action === 'down' && index === values.length - 1);
        button.addEventListener('click', async () => {
          const next = [...values];
          if (action === 'remove') next.splice(index, 1);
          else {
            const other = index + (action === 'up' ? -1 : 1);
            [next[index], next[other]] = [next[other], next[index]];
          }
          await change(next);
          const rows = selected.querySelectorAll('.relation-default-item');
          const focus = rows[Math.min(index, rows.length - 1)]?.querySelector('button:not(:disabled)');
          if (focus) focus.focus({preventScroll: true});
          else focusInput();
        });
        row.append(button);
      }
      selected.append(row);
    });
    if (!multiple && (shapeChanged || (document.activeElement !== input && popup.hidden))) input.value = values.length ? name(values[0]) : '';
    syncOptions();
    position();
  };
  input.addEventListener('focus', () => {
    if (!active || focusing) return;
    if (!isMultiple()) input.select();
    load(searchTerm());
  });
  input.addEventListener('click', () => {
    if (active && popup.hidden && !loading) {
      if (!isMultiple()) input.select();
      load(searchTerm());
    }
  });
  input.addEventListener('input', () => {
    if (!active) return;
    close(false);
    const term = input.value;
    timer = setTimeout(() => load(term), 250);
  });
  input.addEventListener('keydown', event => {
    if (!active) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
    } else if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
      event.preventDefault();
      const down = event.key === 'ArrowDown';
      if (!options.length || popup.hidden || results.hidden) load(searchTerm()).then(() => {
        if (!popup.hidden && document.activeElement === input && options.length) highlight(down ? 0 : options.length - 1);
      });
      else highlight(highlighted < 0 ? (down ? 0 : options.length - 1) : (highlighted + (down ? 1 : -1) + options.length) % options.length);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      if (!popup.hidden && !results.hidden && highlighted >= 0) choose(options[highlighted].code);
      else load(searchTerm());
    }
  });
  const outside = element => !control.contains(element) && !selected.contains(element);
  document.addEventListener('pointerdown', event => { if (active && outside(event.target)) close(); });
  document.addEventListener('focusin', event => { if (active && outside(event.target)) close(); });
  input.addEventListener('blur', event => { if (active && outside(event.relatedTarget)) close(); });
  done.addEventListener('click', () => { close(); focusInput(); });
  form.addEventListener('reset', () => queueMicrotask(() => {
    close();
    shownTerm = null;
    refresh();
    if (active && !isMultiple()) input.value = getValues().length ? name(getValues()[0]) : '';
  }));
  clear.addEventListener('click', async () => {
    close();
    await change([]);
    input.value = '';
    focusInput();
  });
  window.addEventListener('resize', position);
  window.addEventListener('scroll', position, {capture: true, passive: true});
  window.visualViewport?.addEventListener('resize', position);
  window.visualViewport?.addEventListener('scroll', position);
  return {refresh, label: name};
}
