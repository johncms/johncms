/**
 * The selection of the list of the items: a box in the head selects the page, a bar at the foot of
 * the list says how many are chosen and offers what can be done to them. The choice survives a
 * search, a filter and another page of the live list, and is sent with the records that are not
 * shown at the moment.
 *
 * Without the script the form above the table does the same for one page.
 */
document.querySelectorAll('[data-live-list]').forEach(root => {
  const bar = root.querySelector('[data-bulk-bar]');
  if (!bar) return;
  const selected = new Set();
  const limit = Number(bar.dataset.limit);
  const count = bar.querySelector('[data-bulk-count]');
  const limitMessage = bar.querySelector('[data-bulk-limit]');
  const form = () => root.querySelector('[data-bulk-form]');
  const boxes = () => [...root.querySelectorAll('input[name="ids[]"][form="collection-bulk-form"]')];

  const refresh = () => {
    const shown = boxes();
    shown.forEach(box => { box.checked = selected.has(box.value); box.closest('tr')?.classList.toggle('table-active', box.checked); });
    const all = root.querySelector('[data-bulk-all]');
    if (all) {
      const chosen = shown.filter(box => box.checked).length;
      all.hidden = shown.length === 0;
      all.checked = shown.length > 0 && chosen === shown.length;
      all.indeterminate = chosen > 0 && chosen < shown.length;
      root.querySelector('[data-bulk-all-label]').hidden = true;
    }
    const hidden = [...selected].filter(id => !shown.some(box => box.value === id)).length;
    count.textContent = bar.dataset.countLabel.replace('%d', String(selected.size)) + (hidden ? ` (${bar.dataset.hiddenLabel.replace('%d', String(hidden))})` : '');
    const over = selected.size > limit;
    limitMessage.hidden = !over;
    limitMessage.textContent = bar.dataset.limitLabel.replace('%d', String(limit));
    bar.querySelectorAll('[data-bulk-action], .dropdown-toggle').forEach(button => { button.disabled = over; });
    bar.hidden = selected.size === 0;
    // The form keeps its fields for the bar; .d-flex on it outweighs the hidden attribute.
    form()?.classList.add('d-none');
  };

  root.addEventListener('change', event => {
    if (event.target.matches('[data-bulk-all]')) {
      boxes().forEach(box => (event.target.checked ? selected.add(box.value) : selected.delete(box.value)));
    } else if (event.target.matches('input[name="ids[]"]')) {
      event.target.checked ? selected.add(event.target.value) : selected.delete(event.target.value);
    } else {
      return;
    }
    refresh();
  });

  bar.addEventListener('click', event => {
    if (event.target.closest('[data-bulk-clear]')) {
      selected.clear();
      refresh();
      return;
    }
    const button = event.target.closest('[data-bulk-action]');
    const target = form();
    if (!button || button.disabled || !target) return;
    target.elements.bulk_action.value = button.dataset.bulkAction;
    if (target.elements.target_section) target.elements.target_section.value = button.dataset.bulkTarget ?? '0';
    target.querySelectorAll('[data-bulk-carried]').forEach(input => input.remove());
    const shown = new Set(boxes().map(box => box.value));
    for (const id of selected) {
      if (shown.has(id)) continue;
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = id;
      input.setAttribute('data-bulk-carried', '');
      target.append(input);
    }
    target.setAttribute('data-admin-modal', '');
    target.requestSubmit();
  });

  root.addEventListener('live-list:update', refresh);
  refresh();
});
