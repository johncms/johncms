/**
 * The language menu of the top bar. Its links are ?setlng=<code>, which drops the rest of the
 * query of the page; here they keep it — the filters of a list, the page of it. And once the page
 * has come back in the new language, setlng is taken out of the address, so that a reload or a
 * copied link does not switch the language again.
 */
document.addEventListener('click', function (event) {
  const link = event.target.closest('[data-language-link]');
  if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    return;
  }

  event.preventDefault();
  const url = new URL(window.location.href);
  url.searchParams.set('setlng', link.dataset.languageLink);
  window.location.assign(url.toString());
});

const current = new URL(window.location.href);
if (current.searchParams.has('setlng')) {
  current.searchParams.delete('setlng');
  window.history.replaceState(window.history.state, '', current.toString());
}
