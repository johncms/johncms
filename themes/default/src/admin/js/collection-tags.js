/** The textarea remains the form contract and the fallback without JavaScript. */
document.querySelectorAll('[data-collection-tags]').forEach(root => {
  const textarea = root.querySelector('textarea');
  if (!textarea) return;
  const normalize = value => value.trim().replace(/\s+/gu, ' ').toLowerCase();
  let tags = [...new Set(textarea.value.split(/\r?\n/u).map(normalize).filter(Boolean))];
  let pending;
  let timer;
  const chips = document.createElement('div');
  chips.className = 'd-flex flex-wrap gap-2 mb-2';
  const controls = document.createElement('div');
  controls.className = 'input-group';
  const input = document.createElement('input');
  input.type = 'text';
  input.maxLength = 255;
  input.id = textarea.id;
  textarea.id = `${input.id}-source`;
  input.className = textarea.classList.contains('is-invalid') ? 'form-control is-invalid' : 'form-control';
  if (textarea.classList.contains('is-invalid')) input.setAttribute('aria-invalid', 'true');
  input.placeholder = root.dataset.placeholder;
  input.setAttribute('aria-label', root.dataset.placeholder);
  const suggestions = document.createElement('datalist');
  suggestions.id = `${textarea.id}-suggestions`;
  input.setAttribute('list', suggestions.id);
  const add = document.createElement('button');
  add.type = 'button';
  add.className = 'btn btn-outline-secondary';
  add.textContent = root.dataset.addLabel;
  const status = document.createElement('div');
  status.className = 'form-text text-danger';
  status.setAttribute('role', 'status');
  controls.append(input, add);
  root.append(chips, controls, suggestions, status);
  textarea.hidden = true;
  root.querySelector('.form-text')?.setAttribute('hidden', '');

  const render = () => {
    textarea.value = tags.join('\n');
    chips.replaceChildren();
    tags.forEach(tag => {
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-sm btn-outline-secondary';
      remove.textContent = `${tag} ×`;
      remove.setAttribute('aria-label', `${root.dataset.removeLabel}: ${tag}`);
      remove.addEventListener('click', () => {
        tags = tags.filter(value => value !== tag);
        render();
        input.focus();
      });
      chips.append(remove);
    });
  };
  const append = () => {
    const value = normalize(input.value);
    if (value && !tags.includes(value)) tags.push(value);
    input.value = '';
    render();
  };
  add.addEventListener('click', append);
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      append();
    }
  });
  root.closest('form')?.addEventListener('submit', append);
  input.addEventListener('input', () => {
    clearTimeout(timer);
    pending?.abort();
    timer = setTimeout(async () => {
      pending = new AbortController();
      try {
        const url = new URL(root.dataset.source, window.location.origin);
        url.searchParams.set('q', input.value);
        const response = await fetch(url, { signal: pending.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error(`Tag suggestions returned ${response.status}`);
        const data = await response.json();
        if (!Array.isArray(data.tags)) throw new Error('Invalid tag suggestions');
        suggestions.replaceChildren(...data.tags.filter(value => typeof value === 'string').map(value => {
          const option = document.createElement('option');
          option.value = value;
          return option;
        }));
        status.textContent = '';
      } catch (error) {
        if (error.name !== 'AbortError') status.textContent = root.dataset.error;
      }
    }, 200);
  });
  render();
});
