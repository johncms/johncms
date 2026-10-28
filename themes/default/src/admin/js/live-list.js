/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * A list that follows its search and its filters without reloading the page.
 *
 *     <div data-live-list>
 *         <form method="get" action="/admin/users" data-live-list-form>…</form>
 *         <div data-live-list-region="results">…</div>
 *     </div>
 *
 * Typing into a text field of the form asks for the list again once the typing pauses; changing
 * any other field, submitting the form or following a link of the list to the same page asks at
 * once. The answer is the same address rendered as a fragment — the server sees the request is
 * not a navigation by X-Requested-With — which holds the regions again: each one replaces the
 * region of the same name. The address of the page follows, so a reload, a bookmark and the back
 * button land on the same list. Once the regions are in place the root fires live-list:update,
 * which bubbles, for what has to be set up on them again.
 *
 * Nothing here is needed for the list to work: without the script, or when a request fails, the
 * form and the links lead to the same addresses as plain pages.
 */
const TYPING_PAUSE = 300;

function initLiveList(root) {
  const form = root.querySelector('[data-live-list-form]');
  const path = new URL(form ? form.action : location.href, location.href).pathname;
  let pending = null;
  let typing = null;

  function isTextField(element) {
    return element.matches('input[type="search"], input[type="text"]');
  }

  // The address the form leads to, without the fields left empty.
  function formUrl() {
    const url = new URL(form.action, location.href);

    for (const [name, value] of new FormData(form)) {
      if (typeof value === 'string' && value.trim() !== '') {
        url.searchParams.append(name, value.trim());
      }
    }

    return url;
  }

  // A field outside the regions is not replaced, so it is set from the address instead: a click on
  // a chip that drops the search empties the search field. The field being typed into is left be.
  function syncFields(url) {
    if (!form) {
      return;
    }

    form.querySelectorAll('input[name]:not([type="hidden"])').forEach(function (field) {
      if (field.closest('[data-live-list-region]') || field === document.activeElement) {
        return;
      }

      field.value = url.searchParams.get(field.name) ?? '';
    });
  }

  async function load(url, history) {
    if (pending) {
      pending.abort();
    }

    const request = new AbortController();
    pending = request;
    root.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        cache: 'no-store',
        signal: request.signal,
      });

      if (!response.ok) {
        throw new Error('The list answered ' + response.status);
      }

      // The server may have moved the request, to the last page for instance.
      const finalUrl = new URL(response.url);
      const template = document.createElement('template');
      template.innerHTML = await response.text();

      template.content.querySelectorAll('[data-live-list-region]').forEach(function (region) {
        const current = root.querySelector('[data-live-list-region="' + CSS.escape(region.dataset.liveListRegion) + '"]');

        if (current) {
          current.replaceWith(region);
        }
      });

      if (history === 'push') {
        window.history.pushState({liveList: true}, '', finalUrl);
      } else if (history === 'replace') {
        window.history.replaceState({liveList: true}, '', finalUrl);
      }

      syncFields(finalUrl);
      root.dispatchEvent(new CustomEvent('live-list:update', {bubbles: true}));
    } catch (error) {
      if (error.name !== 'AbortError') {
        window.location.assign(url);
      }
    } finally {
      if (pending === request) {
        pending = null;
        root.removeAttribute('aria-busy');
      }
    }
  }

  if (form) {
    form.addEventListener('input', function (event) {
      if (!isTextField(event.target)) {
        return;
      }

      clearTimeout(typing);
      // Typing makes no entries of its own in the history of the browser.
      typing = setTimeout(() => load(formUrl(), 'replace'), TYPING_PAUSE);
    });

    form.addEventListener('change', function (event) {
      if (!isTextField(event.target)) {
        load(formUrl(), 'push');
      }
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      clearTimeout(typing);
      load(formUrl(), 'push');
    });
  }

  root.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');

    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
      || link.target || link.hasAttribute('download')) {
      return;
    }

    const url = new URL(link.href, location.href);

    if (url.origin !== location.origin || url.pathname !== path) {
      return;
    }

    event.preventDefault();
    load(url, 'push');
  });

  window.addEventListener('popstate', function () {
    if (location.pathname === path) {
      load(new URL(location.href), 'none');
    }
  });
}

document.querySelectorAll('[data-live-list]').forEach(initLiveList);
