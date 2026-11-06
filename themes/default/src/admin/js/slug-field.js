/** Name/code binding uses the same multilingual transliteration as the server writer. */
document.querySelectorAll('[data-slug-field]').forEach(root => {
  const form = root.closest('form');
  const name = form?.elements.namedItem(root.dataset.source);
  const code = root.querySelector('[data-slug-input]');
  const linked = root.querySelector('[data-slug-linked]');
  const toggle = root.querySelector('[data-slug-toggle]');
  const refresh = root.querySelector('[data-slug-refresh]');
  const status = root.querySelector('[data-slug-status]');
  const spinner = root.querySelector('[data-slug-spinner]');
  if (!form || !(name instanceof HTMLInputElement) || !code || !linked || !refresh || !status || !spinner || !toggle) return;

  let timer;
  let request;
  let applying = false;
  let dirty = false;
  let sourceName = name.value;
  let composing = false;
  let submitting = false;
  const show = (message = '', error = false) => {
    status.textContent = message;
    status.hidden = !message;
    status.classList.toggle('text-danger', error);
  };
  const setBusy = busy => {
    spinner.hidden = !busy;
    refresh.hidden = busy;
    code.setAttribute('aria-busy', String(busy));
    if (busy) root.setAttribute('aria-busy', 'true');
    else root.removeAttribute('aria-busy');
  };
  const cancel = () => {
    clearTimeout(timer);
    request?.abort();
    request = null;
    setBusy(false);
  };
  const syncMode = () => {
    code.readOnly = linked.checked;
    toggle.setAttribute('aria-pressed', String(linked.checked));
    const title = linked.checked ? toggle.dataset.linkedTitle : toggle.dataset.manualTitle;
    toggle.title = title;
    toggle.setAttribute('aria-label', title);
    root.querySelector('[data-slug-linked-icon]').hidden = !linked.checked;
    root.querySelector('[data-slug-manual-icon]').hidden = linked.checked;
  };
  const generate = async (once = false) => {
    cancel();
    if ((!linked.checked && !once) || composing) return false;
    const wasLinked = linked.checked;
    const title = name.value;
    if (!title.trim()) {
      code.value = '';
      sourceName = title;
      dirty = false;
      show();
      return true;
    }
    const controller = new AbortController();
    request = controller;
    dirty = true;
    setBusy(true);
    show();
    const body = new FormData();
    body.set('name', title);
    body.set('csrf_token', form.elements.namedItem('csrf_token')?.value ?? '');
    try {
      const response = await fetch(root.dataset.url, {
        method: 'POST', credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, body, signal: controller.signal,
      });
      if (!response.ok) throw new Error('Slug request failed');
      const data = await response.json();
      if (typeof data.slug !== 'string') throw new Error('Invalid slug response');
      if (request !== controller || linked.checked !== wasLinked || name.value !== title) return false;
      code.value = data.slug;
      sourceName = title;
      applying = true;
      code.dispatchEvent(new Event('input', {bubbles: true}));
      applying = false;
      dirty = false;
      show();
      return true;
    } catch (error) {
      if (request === controller && error.name !== 'AbortError') show(root.dataset.error, true);
      return false;
    } finally {
      if (request === controller) { request = null; setBusy(false); }
    }
  };
  const schedule = () => {
    cancel();
    if (!linked.checked) return;
    dirty = true;
    show();
    if (!composing) timer = setTimeout(() => generate(), 180);
  };
  name.addEventListener('input', schedule);
  name.addEventListener('compositionstart', () => { composing = true; schedule(); });
  name.addEventListener('compositionend', () => { composing = false; schedule(); });
  linked.addEventListener('change', () => {
    cancel();
    dirty = false;
    show();
    syncMode();
    if (linked.checked) generate();
    else code.focus();
  });
  toggle.addEventListener('click', () => {
    linked.checked = !linked.checked;
    linked.dispatchEvent(new Event('change', {bubbles: true}));
  });
  code.addEventListener('input', () => {
    if (!linked.checked && !applying) { cancel(); dirty = false; show(); }
  });
  refresh.addEventListener('click', () => generate(true));
  form.addEventListener('submit', async event => {
    if (linked.checked && name.value !== sourceName) dirty = true;
    if (!request && (!linked.checked || !dirty)) return;
    event.preventDefault();
    if (submitting || composing) return;
    submitting = true;
    const submitter = event.submitter;
    try {
      if (await generate(!linked.checked)) {
        if (submitter) form.requestSubmit(submitter);
        else form.requestSubmit();
      }
    } finally { submitting = false; }
  });
  form.addEventListener('reset', event => {
    queueMicrotask(() => {
      if (event.defaultPrevented) return;
      cancel(); dirty = false; composing = false; sourceName = name.value;
      show(); syncMode();
    });
  });
  root.querySelectorAll('[data-slug-control]').forEach(control => { control.hidden = false; });
  name.closest('[data-slug-name]')?.classList.add('admin-slug-name-column');
  root.closest('[data-slug-code]')?.classList.add('admin-slug-code-column');
  syncMode();
  // Opening an existing form must never rewrite its canonical code.
  if (linked.checked && !code.value && name.value.trim()) generate();
});
