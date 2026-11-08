/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * A part of a public page that follows its own links without reloading the page: the pages of a
 * discussion below an article, for instance.
 *
 *     <section id="article-comments" data-live-list>
 *         <div data-live-list-region="comments">… <a href="?page=2#article-comments">2</a> …</div>
 *         <form>…</form>
 *     </section>
 *
 * A link inside the root to the same page asks for that address with X-Requested-With; the server
 * answers with a fragment holding the regions again, and each one replaces the region of the same
 * name. What is outside the regions, a form with an editor for instance, stays as it is. The
 * address of the page follows, so a reload, a bookmark and the back button land on the same page.
 * Once the regions are in place the root fires live-list:update, which bubbles, for what has to be
 * set up on them again.
 *
 * Nothing here is needed for the page to work: without the script, or when a request fails, the
 * links lead to the same addresses as plain pages.
 */
function initLiveList(root) {
  const path = location.pathname;
  // The query of the page on screen: a change of the address that keeps it, the jump to an anchor
  // of a comment for instance, asks for nothing.
  let shown = location.search;
  let pending = null;

  // The anchor of the link leads to the start of the list, as it does when the browser follows the
  // link itself: the new page is read from its beginning.
  function reveal(hash) {
    const target = hash ? document.getElementById(decodeURIComponent(hash.slice(1))) : null;
    target?.scrollIntoView({block: 'start'});
  }

  async function load(url, history) {
    if (pending) {
      pending.abort();
    }

    const request = new AbortController();
    pending = request;
    root.setAttribute('aria-busy', 'true');
    // The focus is lost with the region it is in, so it is moved to the start of the new one.
    const hadFocus = root.contains(document.activeElement) && document.activeElement.closest('[data-live-list-region]') !== null;

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

      // The server may have moved the request, to the last page for instance. The address of an
      // answer carries no anchor, so the one of the link is kept.
      const finalUrl = new URL(response.url);
      finalUrl.hash = url.hash;
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

      shown = finalUrl.search;

      if (hadFocus) {
        root.querySelector('[data-live-list-focus]')?.focus({preventScroll: true});
      }

      if (history !== 'none') {
        reveal(finalUrl.hash);
      }

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

  root.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');

    if (!link || !link.closest('[data-live-list-region]') || event.defaultPrevented || event.button !== 0
      || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) {
      return;
    }

    const url = new URL(link.href, location.href);

    // Only a link to another page of this list: an anchor of the same page is left to the browser.
    if (url.origin !== location.origin || url.pathname !== path || url.search === shown) {
      return;
    }

    event.preventDefault();
    load(url, 'push');
  });

  window.addEventListener('popstate', function () {
    if (location.pathname === path && location.search !== shown) {
      load(new URL(location.href), 'none');
    }
  });
}

document.querySelectorAll('[data-live-list]').forEach(initLiveList);
