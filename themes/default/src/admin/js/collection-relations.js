/** Search for related items: the results replace the choices of the selects of the field. */
document.querySelectorAll('[data-collection-relation]').forEach(root => {
  const query = root.querySelector('[data-relation-query]');
  const button = root.querySelector('[data-relation-search]');
  const status = root.querySelector('[data-relation-status]');
  if (!query || !button || !status) return;
  let pending;
  const search = async () => {
    pending?.abort();
    const controller = new AbortController();
    pending = controller;
    button.disabled = true;
    root.setAttribute('aria-busy', 'true');
    try {
      const url = new URL(root.dataset.source, window.location.origin);
      url.searchParams.set('q', query.value);
      const response = await fetch(url, {signal: controller.signal, headers: {'Accept': 'application/json'}});
      if (!response.ok) throw new Error('Relation search failed');
      const data = await response.json();
      if (!Array.isArray(data.options) || data.options.some(option => typeof option.code !== 'string' || typeof option.label !== 'string')) throw new Error('Invalid relation options');
      if (controller !== pending) return;
      const update = select => {
        const current = select.value;
        const retained = [...select.options].find(option => option.value === current);
        const empty = [...select.options].find(option => option.value === '');
        const options = empty ? [new Option(empty.textContent, '')] : [];
        data.options.forEach(option => options.push(new Option(option.label, option.code)));
        if (current && !data.options.some(option => option.code === current) && retained) options.push(new Option(retained.textContent, current));
        select.replaceChildren(...options);
        select.value = current;
      };
      root.querySelectorAll('[data-relation-select]').forEach(update);
      root.querySelectorAll('template[data-repeat-template]').forEach(template => template.content.querySelectorAll('[data-relation-select]').forEach(update));
      status.textContent = root.dataset.loaded;
      status.classList.remove('text-danger');
    } catch (error) {
      if (error.name !== 'AbortError' && controller === pending) {
        status.textContent = root.dataset.error;
        status.classList.add('text-danger');
      }
    } finally {
      if (controller === pending) {
        button.disabled = false;
        root.removeAttribute('aria-busy');
      }
    }
  };
  button.addEventListener('click', event => { event.preventDefault(); search(); });
  query.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); search(); }
  });
  query.addEventListener('input', () => {
    pending?.abort();
    pending = null;
    button.disabled = false;
    root.removeAttribute('aria-busy');
  });
});
