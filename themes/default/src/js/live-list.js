/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * A part of a public page that follows its own links and sends its own forms without reloading
 * the page: the discussion below an article, for instance.
 *
 *     <section id="article-comments" data-live-list>
 *         <div data-live-list-region="comments">… <a href="?page=2#article-comments">2</a> …</div>
 *         <form method="post" action="…" data-live-list-form>…</form>
 *     </section>
 *
 * A link inside a region to another page of the same address is asked for with X-Requested-With;
 * the server answers with a fragment holding the regions again, and each one replaces the region
 * of the same name — inside the root or anywhere on the page, since the names are unique on it —
 * while a <title> in the fragment becomes the title of the page. A form marked data-live-list-form
 * is posted the same way: the server answers it with its usual redirect, which the browser follows
 * with the same header, so the fragment comes back after it. What is outside the regions, a form
 * with an editor for instance, stays as it is. The address of the page follows, so a reload, a bookmark and the back button land on the
 * same page. Once the regions are in place the root fires live-list:update, which bubbles, with
 * the form that was sent, if any, for what has to be set up on them again.
 *
 * After a link the page goes to the anchor of the link, as it does when the browser follows it.
 * After a form it stays where it is, unless the answer marks an element with data-live-list-target
 * — the comment just added, for instance — which is then brought into view.
 *
 * Nothing here is needed for the page to work: without the script the links and the forms lead to
 * the same addresses as plain pages, and a failed request falls back to them.
 */
function initLiveList(root) {
  const path = location.pathname;
  // The query of the page on screen: a change of the address that keeps it, the jump to an anchor
  // of a comment for instance, asks for nothing.
  let shown = location.search;
  let pending = null;
  // A form being sent is not cut off: its answer is what tells the page the text was taken.
  let sending = false;

  async function load(url, options = {}) {
    const {form = null, submitter = null, history = 'push'} = options;

    if (sending) {
      return;
    }

    if (pending) {
      pending.abort();
    }

    sending = form !== null;

    const request = new AbortController();
    pending = request;
    root.setAttribute('aria-busy', 'true');
    const buttons = form ? [...form.querySelectorAll('button[type="submit"], input[type="submit"]')] : [];
    buttons.forEach(button => button.disabled = true);
    // The focus is lost with the region it is in, so it is moved to the start of the new one.
    const active = document.activeElement;
    const hadFocus = active !== null && root.contains(active) && active.closest('[data-live-list-region]') !== null;
    let response = null;

    try {
      response = await fetch(url, {
        method: form ? 'POST' : 'GET',
        body: form ? new FormData(form, submitter) : undefined,
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        credentials: 'same-origin',
        cache: 'no-store',
        signal: request.signal,
      });

      if (!response.ok) {
        throw new Error('The list answered ' + response.status);
      }

      // The server may have moved the request: to the last page, or back to the page after a form.
      // The address of an answer carries no anchor, so the one of the link is kept.
      const finalUrl = new URL(response.url);
      finalUrl.hash = form ? '' : url.hash;
      const template = document.createElement('template');
      template.innerHTML = await response.text();

      // A region may sit outside the root: the number of comments in the header of the article.
      template.content.querySelectorAll('[data-live-list-region]').forEach(function (region) {
        const current = document.querySelector('[data-live-list-region="' + CSS.escape(region.dataset.liveListRegion) + '"]');

        if (current) {
          current.replaceWith(region);
        }
      });

      const title = template.content.querySelector('title');

      if (title) {
        document.title = title.textContent;
      }

      const target = form ? root.querySelector('[data-live-list-target]') : null;

      if (target?.id) {
        finalUrl.hash = target.id;
      }

      if (history === 'push' && finalUrl.search !== shown) {
        window.history.pushState({liveList: true}, '', finalUrl);
      } else if (history !== 'none') {
        window.history.replaceState({liveList: true}, '', finalUrl);
      }

      shown = finalUrl.search;

      if (hadFocus) {
        root.querySelector('[data-live-list-focus]')?.focus({preventScroll: true});
      }

      if (target) {
        target.scrollIntoView({block: 'center'});
      } else if (!form && history !== 'none' && url.hash) {
        // The anchor of the link leads to the start of the list: the new page is read from its beginning.
        document.getElementById(decodeURIComponent(url.hash.slice(1)))?.scrollIntoView({block: 'start'});
      }

      root.dispatchEvent(new CustomEvent('live-list:update', {bubbles: true, detail: {form}}));
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      if (!form) {
        window.location.assign(url);
      } else if (response?.redirected) {
        // The form was taken and only the page after it failed: it is not sent again.
        window.location.assign(response.url);
      } else {
        HTMLFormElement.prototype.submit.call(form);
      }
    } finally {
      buttons.forEach(button => button.disabled = false);
      sending = false;

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
    load(url);
  });

  // A handler that cancels the form — a confirmation, an upload still running — has had its say:
  // they listen on the form itself or while the event goes down.
  root.addEventListener('submit', function (event) {
    const form = event.target.closest('form[data-live-list-form]');

    if (!form || event.defaultPrevented || form.method.toLowerCase() !== 'post') {
      return;
    }

    event.preventDefault();
    load(new URL(form.action, location.href), {form, submitter: event.submitter});
  });

  window.addEventListener('popstate', function () {
    if (location.pathname === path && location.search !== shown) {
      load(new URL(location.href), {history: 'none'});
    }
  });
}

document.querySelectorAll('[data-live-list]').forEach(initLiveList);
