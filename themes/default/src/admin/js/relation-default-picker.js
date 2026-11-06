/** Search and selection share one control; canonical values belong to the ordinary form. */
export function createRelationDefaultPicker({form, lookup, isActive, isMultiple, getValues, setValues, getOptions, search}) {
  const input = lookup.querySelector('[name="default_query"]');
  const label = lookup.querySelector('label[for="default-query"]');
  const originalLabel = label.textContent;
  const target = form.querySelector('#relation-target');
  const selected = lookup.querySelector('[data-relation-selected]');
  const results = lookup.querySelector('[data-relation-results]');
  const clear = lookup.querySelector('[data-relation-clear]');
  const context = lookup.querySelector('[data-relation-context]');
  const status = lookup.querySelector('[data-default-lookup-status]');
  const searchButton = lookup.querySelector('[name="lookup_defaults"]');
  const nativeResult = lookup.querySelector('[data-default-lookup-result]');
  const apply = lookup.querySelector('[data-default-lookup-apply]');
  const strings = lookup.dataset;
  const labels = new Map();
  let active = false;
  let options = [];
  let highlighted = -1;
  let timer;
  let revision = 0;
  let scope = target.value;
  let busy = false;
  const title = option => {
    const suffix = ` (#${option.code})`;
    return option.label.endsWith(suffix) ? option.label.slice(0, -suffix.length) : option.label;
  };
  const name = code => labels.get(code) ?? `${strings.relationUnavailable} (#${code})`;
  const close = () => {
    results.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    highlighted = -1;
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
    clearTimeout(timer);
    revision++;
    if (await change(isMultiple() ? [...getValues(), code] : [code]) === false) return;
    input.value = isMultiple() ? '' : name(code);
    close();
    input.focus();
    // Focus can open the list again; selection is already complete.
    close();
  };
  const highlight = index => {
    highlighted = index;
    [...results.children].forEach((button, i) => {
      button.classList.toggle('active', i === index);
      button.setAttribute('aria-selected', String(i === index));
    });
    const option = results.children[index];
    if (option) {
      input.setAttribute('aria-activedescendant', option.id);
      option.scrollIntoView({block: 'nearest'});
    }
  };
  const show = (items, term) => {
    const chosen = new Set(isMultiple() ? getValues() : []);
    const query = term.trim().toLocaleLowerCase();
    options = items.filter(option => !chosen.has(option.code) && (!query || title(option).toLocaleLowerCase().includes(query)));
    results.replaceChildren();
    options.forEach((option, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'list-group-item list-group-item-action text-start';
      button.id = `relation-default-result-${index}`;
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', 'false');
      button.tabIndex = -1;
      const heading = document.createElement('span');
      heading.className = 'd-block text-break';
      heading.textContent = title(option);
      const id = document.createElement('small');
      id.className = 'd-block text-muted';
      id.textContent = `#${option.code}`;
      button.append(heading, id);
      button.addEventListener('pointerdown', event => event.preventDefault());
      button.addEventListener('mousedown', event => event.preventDefault());
      button.addEventListener('click', () => choose(option.code));
      results.append(button);
    });
    results.hidden = options.length === 0;
    input.setAttribute('aria-expanded', String(options.length > 0));
    input.removeAttribute('aria-activedescendant');
    highlighted = -1;
    status.textContent = options.length ? '' : strings.empty;
  };
  const load = async term => {
    if (!isActive() || !target.value || input.matches(':disabled')) return;
    const current = ++revision;
    status.textContent = strings.relationLoading;
    const items = await search(term);
    if (current !== revision || !isActive()) return;
    if (items !== null) {
      for (const option of items) labels.set(option.code, title(option));
      show(items, term);
    } else close();
  };
  const refresh = () => {
    const enabled = isActive();
    if (enabled !== active) {
      clearTimeout(timer);
      revision++;
      close();
      input.value = '';
    }
    active = enabled;
    searchButton.hidden = enabled;
    nativeResult.hidden = enabled;
    apply.hidden = enabled;
    selected.hidden = !enabled || !isMultiple();
    context.hidden = !enabled;
    clear.hidden = !enabled || isMultiple() || getValues().length === 0;
    input.parentElement.classList.toggle('input-group', !enabled || !clear.hidden);
    form.querySelector('[data-relation-fallback]').hidden = true;
    if (!enabled) {
      label.textContent = originalLabel;
      input.disabled = false;
      input.placeholder = '';
      input.removeAttribute('role');
      input.removeAttribute('aria-controls');
      input.removeAttribute('aria-describedby');
      input.removeAttribute('aria-expanded');
      input.setCustomValidity('');
      return;
    }
    if (scope !== target.value) {
      scope = target.value;
      clearTimeout(timer);
      revision++;
      close();
      status.textContent = '';
    }
    for (const option of getOptions()) labels.set(option.code, title(option));
    const values = getValues();
    const valid = new Set(getOptions().map(option => option.code));
    const invalid = values.some(code => !valid.has(code));
    input.setCustomValidity(invalid ? strings.relationInvalid : '');
    input.classList.toggle('is-invalid', invalid);
    input.setAttribute('aria-invalid', String(invalid));
    input.disabled = !target.value;
    input.placeholder = target.value ? strings.relationPlaceholder : strings.relationTarget;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', results.id);
    input.setAttribute('aria-describedby', 'relation-default-context default-lookup-status');
    if (!input.hasAttribute('aria-expanded')) input.setAttribute('aria-expanded', 'false');
    label.textContent = isMultiple() ? strings.relationPlural : strings.relationSingular;
    context.textContent = target.value ? strings.relationContext.replace('%s', target.selectedOptions[0].textContent) : strings.relationTarget;
    if (invalid) status.textContent = strings.relationInvalid;
    selected.replaceChildren();
    values.forEach((code, index) => {
      const row = document.createElement('li');
      row.className = 'list-group-item d-flex flex-wrap align-items-center gap-2';
      const text = document.createElement('span');
      text.className = 'flex-grow-1 text-break';
      text.textContent = name(code);
      if (!valid.has(code)) text.classList.add('text-danger');
      const id = document.createElement('small');
      id.className = 'text-muted';
      id.textContent = `#${code}`;
      row.append(text, id);
      for (const [action, caption] of [['up', strings.relationUp], ['down', strings.relationDown], ['remove', strings.relationRemove]]) {
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
          const focus = selected.children[Math.min(index, selected.children.length - 1)]?.querySelector('button:not(:disabled)');
          (focus ?? input).focus();
        });
        row.append(button);
      }
      selected.append(row);
    });
    if (document.activeElement !== input) input.value = !isMultiple() && values.length ? name(values[0]) : '';
  };
  input.addEventListener('focus', () => {
    if (!active) return;
    input.select();
    load('');
  });
  input.addEventListener('input', () => {
    if (!active) return;
    clearTimeout(timer);
    revision++;
    close();
    const term = input.value;
    timer = setTimeout(() => load(term), 250);
  });
  input.addEventListener('keydown', event => {
    if (!active) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      clearTimeout(timer);
      revision++;
      close();
      input.value = !isMultiple() && getValues().length ? name(getValues()[0]) : '';
    } else if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
      event.preventDefault();
      const down = event.key === 'ArrowDown';
      if (!options.length || results.hidden) load('').then(() => {
        if (!results.hidden && document.activeElement === input) highlight(down ? 0 : options.length - 1);
      });
      else highlight(highlighted < 0 ? (down ? 0 : options.length - 1) : (highlighted + (down ? 1 : -1) + options.length) % options.length);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      if (!results.hidden && highlighted >= 0) choose(options[highlighted].code);
      else load(input.value);
    }
  });
  input.addEventListener('blur', () => {
    if (!active) return;
    clearTimeout(timer);
    revision++;
    close();
    input.value = !isMultiple() && getValues().length ? name(getValues()[0]) : '';
  });
  form.addEventListener('reset', () => queueMicrotask(() => {
    clearTimeout(timer);
    revision++;
    close();
    refresh();
    if (active) input.value = !isMultiple() && getValues().length ? name(getValues()[0]) : '';
  }));
  clear.addEventListener('click', async () => {
    clearTimeout(timer);
    revision++;
    await change([]);
    input.value = '';
    input.focus();
  });
  return {refresh, label: name};
}
