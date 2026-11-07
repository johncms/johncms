import { createRelationPicker } from './relation-picker';

/**
 * A relation field of an item: the search of components/relation-picker.twig over the target
 * collection, with the chosen records kept as hidden fields named after the field.
 */
document.querySelectorAll('[data-collection-relation]').forEach(root => {
  const form = root.closest('form');
  const values = root.querySelector('[data-relation-values]');
  const status = root.querySelector('[data-relation-status]');
  const multiple = root.dataset.multiple === '1';
  const limit = Number(root.dataset.limit);
  let options = JSON.parse(root.dataset.options);
  let pending;

  const getValues = () => [...values.querySelectorAll('input')].map(input => input.value).filter(value => value !== '');
  const write = list => {
    const inputs = (multiple ? list : [list[0] ?? '']).map(value => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = multiple ? `${root.dataset.name}[]` : root.dataset.name;
      input.value = value;
      return input;
    });
    values.replaceChildren(...inputs);
  };

  const picker = createRelationPicker({
    form,
    lookup: root.querySelector('[data-relation-picker]'),
    isActive: () => true,
    isMultiple: () => multiple,
    // Without access to the target the search stays disabled and says why.
    getTarget: () => (root.dataset.available === '1' ? {value: '1', label: root.dataset.target} : {value: '', label: ''}),
    getLimit: () => limit,
    getOptions: () => options,
    getValues,
    setValues: async list => {
      if (multiple && list.length > limit) {
        status.textContent = root.dataset.limitError;
        return false;
      }
      write(list);
      return true;
    },
    search: async term => {
      pending?.abort();
      const request = new AbortController();
      pending = request;
      try {
        const url = new URL(root.dataset.source, window.location.origin);
        url.searchParams.set('q', term);
        const response = await fetch(url, {signal: request.signal, headers: {'Accept': 'application/json'}});
        const data = await response.json();
        if (!response.ok || !Array.isArray(data.options) || data.options.some(option => typeof option.code !== 'string' || typeof option.label !== 'string')) {
          throw new Error(root.dataset.error);
        }
        if (pending !== request) return null;
        // The chosen records keep their names even when the search no longer finds them.
        const known = new Map(options.map(option => [option.code, option]));
        data.options.forEach(option => known.set(option.code, option));
        options = getValues().map(code => known.get(code)).filter(Boolean);
        data.options.forEach(option => { if (!options.includes(option)) options.push(option); });
        return data.options;
      } catch (error) {
        if (error.name !== 'AbortError' && pending === request) status.textContent = root.dataset.error;
        return null;
      }
    },
    cancelSearch: () => { pending?.abort(); pending = null; },
  });
  picker.refresh();
});
