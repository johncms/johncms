/**
 * A filter of a list with many options (components/ui/filter-dropdown.twig, searchable): the field
 * above them narrows the options to those whose title holds what is typed. Bootstrap keeps the menu
 * open for a click into a field, and the menu keeps working after a live list redraws it.
 */
document.addEventListener('input', event => {
  const field = event.target.closest('[data-filter-search]');
  if (!field) return;
  const query = field.value.trim().toLocaleLowerCase();
  field.closest('.dropdown-menu').querySelectorAll('.dropdown-item').forEach(item => {
    item.closest('li').hidden = query !== '' && !item.textContent.toLocaleLowerCase().includes(query);
  });
});

document.addEventListener('shown.bs.dropdown', event => {
  event.target.closest('.dropdown')?.querySelector('[data-filter-search]')?.focus();
});

/**
 * One's own value of a filter. Its fields sit inside the form of a live list, which reloads on any
 * change of its fields: their events stop here, and the button leads to the list with the value
 * through a link, which the live list follows in place.
 */
const apply = custom => {
  const url = new URL(custom.dataset.action, window.location.href);
  for (const [name, value] of Object.entries(JSON.parse(custom.dataset.hidden))) url.searchParams.set(name, value);
  custom.querySelectorAll('[data-filter-field]').forEach(field => {
    if (field.value !== '') url.searchParams.set(field.dataset.filterField, field.value);
  });
  const link = document.createElement('a');
  link.href = url.toString();
  link.hidden = true;
  custom.append(link);
  link.click();
  link.remove();
};

for (const type of ['input', 'change', 'keydown']) {
  document.addEventListener(type, event => {
    const custom = event.target.closest?.('[data-filter-custom]');
    if (!custom) return;
    event.stopPropagation();
    if (type === 'keydown' && event.key === 'Enter') {
      event.preventDefault();
      apply(custom);
    }
  }, true);
}

document.addEventListener('click', event => {
  const button = event.target.closest('[data-filter-apply]');
  if (button) apply(button.closest('[data-filter-custom]'));
});
