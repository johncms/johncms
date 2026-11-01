// Public preferences use the same server contracts as before: siteTheme and ?setlng=.
const picker = document.querySelector('[data-site-theme-picker]');
if (picker) {
  const system = window.matchMedia('(prefers-color-scheme: dark)');
  const schemes = ['light', 'dark', 'auto'];
  let selected = schemes.includes(picker.dataset.currentTheme) ? picker.dataset.currentTheme : 'auto';

  function update() {
    document.body.classList.remove(...schemes);
    document.body.classList.add(selected);
    const dark = selected === 'dark' || (selected === 'auto' && system.matches);
    const use = picker.querySelector('summary use');
    use.setAttribute('href', picker.dataset.iconSprite + '#' + (dark ? 'moon' : 'sun'));
    picker.querySelectorAll('[data-site-theme]').forEach(button => {
      button.setAttribute('aria-pressed', String(button.dataset.siteTheme === selected));
    });
    const colors = selected === 'auto'
      ? [['#fafaf7', '(prefers-color-scheme: light)'], ['#0e1218', '(prefers-color-scheme: dark)']]
      : [[dark ? '#0e1218' : '#fafaf7', null]];
    const metas = [...document.querySelectorAll('meta[name="theme-color"]')];
    colors.forEach(([color, media], index) => {
      const meta = metas[index] || document.createElement('meta');
      meta.name = 'theme-color';
      meta.content = color;
      if (media) meta.setAttribute('media', media);
      else meta.removeAttribute('media');
      if (!meta.isConnected) document.head.append(meta);
    });
    metas.slice(colors.length).forEach(meta => meta.remove());
  }

  picker.addEventListener('click', event => {
    const button = event.target.closest('[data-site-theme]');
    if (!button || !schemes.includes(button.dataset.siteTheme)) return;
    selected = button.dataset.siteTheme;
    document.cookie = 'siteTheme=' + selected + '; path=/; max-age=31536000; SameSite=Lax';
    update();
    picker.open = false;
    picker.querySelector('summary').focus();
  });
  system.addEventListener('change', update);
  update();
}

const current = new URL(window.location.href);
document.querySelectorAll('[data-site-language]').forEach(link => {
  const url = new URL(current);
  url.searchParams.set('setlng', link.dataset.siteLanguage);
  link.href = url.toString();
});
if (current.searchParams.has('setlng')) {
  current.searchParams.delete('setlng');
  window.history.replaceState(window.history.state, '', current.toString());
}

// Native disclosures also close on Escape and when clicking outside them.
document.addEventListener('click', event => {
  document.querySelectorAll('[data-site-popover][open]').forEach(details => {
    if (!details.contains(event.target)) details.open = false;
  });
});
document.addEventListener('keydown', event => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('[data-site-popover][open]').forEach(details => {
    details.open = false;
    details.querySelector('summary').focus();
  });
});
