// The public drawer is progressive enhancement: without this module the sidebar stays in flow.
const sidebar = document.getElementById('site-sidebar');
const main = document.querySelector('.site-main');
const opener = document.querySelector('[data-site-nav-open]');

if (sidebar && main && opener) {
  const mobile = window.matchMedia('(max-width: 991.98px)');
  let returnFocus = opener;

  function isOpen() {
    return document.body.classList.contains('site-nav-open');
  }

  function close(restoreFocus = true) {
    document.body.classList.remove('site-nav-open');
    main.inert = false;
    sidebar.inert = mobile.matches;
    sidebar.removeAttribute('role');
    sidebar.removeAttribute('aria-modal');
    opener.setAttribute('aria-expanded', 'false');
    if (restoreFocus) returnFocus.focus({preventScroll: true});
  }

  opener.addEventListener('click', event => {
    if (!mobile.matches) return;
    event.preventDefault();
    returnFocus = document.activeElement;
    document.querySelectorAll('[data-site-popover][open]').forEach(details => { details.open = false; });
    document.body.classList.add('site-nav-motion', 'site-nav-open');
    sidebar.inert = false;
    main.inert = true;
    sidebar.setAttribute('role', 'dialog');
    sidebar.setAttribute('aria-modal', 'true');
    opener.setAttribute('aria-expanded', 'true');
    sidebar.querySelector('[data-site-nav-close]').focus({preventScroll: true});
  });

  document.querySelectorAll('[data-site-nav-close]').forEach(button => {
    button.addEventListener('click', () => close());
  });

  document.addEventListener('keydown', event => {
    if (!isOpen()) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
    } else if (event.key === 'Tab') {
      const focusable = [...sidebar.querySelectorAll('a[href], button, summary, input, select, textarea, [tabindex="0"]')]
        .filter(element => !element.disabled && element.getClientRects().length > 0);
      const first = focusable[0];
      const last = focusable.at(-1);
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus({preventScroll: true});
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus({preventScroll: true});
      }
    }
  });

  mobile.addEventListener('change', () => {
    const focused = document.activeElement;
    const wasInSidebar = sidebar.contains(focused);
    document.body.classList.remove('site-nav-motion');
    close(false);
    if (wasInSidebar && (mobile.matches || focused.getClientRects().length === 0)) {
      (mobile.matches ? opener : sidebar.querySelector('a[href]')).focus();
    }
  });
  document.body.classList.add('site-nav-ready');
  close(false);
  document.documentElement.classList.remove('site-nav-pending');
}

// Match the most specific section URL without changing module menu contracts.
const current = new URL(window.location.href);
const candidates = [...document.querySelectorAll('[data-site-menu] a[href]')].filter(link => {
  const url = new URL(link.href);
  const path = url.pathname.replace(/\/+$/, '');
  return url.origin === current.origin && (current.pathname.replace(/\/+$/, '') === path || (path !== '' && current.pathname.startsWith(path + '/')))
    && [...url.searchParams].every(([name, value]) => current.searchParams.get(name) === value);
});
const longest = Math.max(0, ...candidates.map(link => new URL(link.href).pathname.replace(/\/+$/, '').length));
candidates.forEach(link => {
  const path = new URL(link.href).pathname.replace(/\/+$/, '');
  if (path.length === longest) link.setAttribute('aria-current', 'location');
});
