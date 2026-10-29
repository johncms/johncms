/**
 * Load admin record details into a Bootstrap offcanvas while keeping the list in place.
 *
 * Links use data-admin-offcanvas and fall back to their regular page when this script is absent.
 * The controller answers XHR requests with the offcanvas contents alone.
 */
import { Offcanvas } from 'bootstrap';

const panel = document.querySelector('[data-admin-offcanvas-panel]');

if (panel) {
  const loading = document.querySelector('template[data-admin-offcanvas-loading-template]');
  const offcanvas = Offcanvas.getOrCreateInstance(panel);
  let pending = null;

  async function open(url, trigger) {
    if (pending) {
      pending.abort();
    }

    const request = new AbortController();
    pending = request;
    panel.replaceChildren(loading.content.cloneNode(true));
    offcanvas.show(trigger);

    try {
      const response = await fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        cache: 'no-store',
        signal: request.signal,
      });

      if (!response.ok) {
        throw new Error('The offcanvas answered ' + response.status);
      }

      if (new URL(response.url).pathname !== new URL(url, location.href).pathname) {
        window.location.assign(response.url);
        return;
      }

      panel.innerHTML = await response.text();
    } catch (error) {
      if (error.name !== 'AbortError') {
        window.location.assign(url);
      }
    } finally {
      if (pending === request) {
        pending = null;
      }
    }
  }

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-admin-offcanvas]');

    if (!trigger || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
      || trigger.target || trigger.hasAttribute('download')) {
      return;
    }

    event.preventDefault();
    open(trigger.href, trigger);
  });

  panel.addEventListener('hidden.bs.offcanvas', function () {
    if (pending) {
      pending.abort();
      pending = null;
    }

    panel.replaceChildren();
  });
}
