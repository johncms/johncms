# Templates

The view layer is Twig only. Read `escaping.md` and `localization.md` before writing a page.

## Where a template lives

```
modules/<vendor>/<module>/templates/public/<page>.twig  → @<module>/public/<page>.twig
modules/<vendor>/<module>/templates/public/components/… → partials of that module
modules/<vendor>/<module>/templates/admin/<page>.twig   → @<module>/admin/<page>.twig
themes/<theme>/templates/…                        → @theme/…
themes/<theme>/templates/admin/…                  → @admin/…
themes/<theme>/templates/emails/…                 → @theme/emails/… (the mail environment)
public/install/templates/…                        → @install/… (the installer environment)
```

Nothing is registered anywhere: the namespaces come from the installed modules and the theme
chain. A theme overrides any template of a module by mirroring its path
(`themes/<theme>/templates/<module>/public/<page>.twig`).

After adding a module or a theme override directory, regenerate the file the IDE reads:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) php system/bin/console twig:ide-config
```

`ide-twig.json` is committed, and the verification gate fails while it is stale.

## Environments

| Environment | Renders | What it has |
| --- | --- | --- |
| `web` | every HTTP page, `@theme/…` and `@admin/…` alike | the request: `app`, csrf, assets, `vite()` |
| `mail` | the emails | no request; `asset()` gives absolute URLs, plus `home_url()`, `copyright()`; the locale is an argument of `MailRenderer::render()` |
| `install` | the installer | runs before there is a site: no request, no modules, only the shipped theme |

A page of the panel and a page of the site differ by namespace, not by environment — the
template names the layout it wants, so nothing has to work out "are we in the admin area?" at
runtime.

## Writing a page

1. **Controller.** Return `Johncms\Http\View\ViewResponse` — the template name and the data:

   ```php
   return new ViewResponse('@news/public/index.twig', ['articles' => $articles]);
   ```

   Page title, `page_title`, description and canonical go into the same array. A controller
   that also redirects is typed `Response|ViewResponse`. A middleware has to return a
   `Response`, so it renders through `RendererInterface` and wraps the string itself.

2. **Header of the template.** Every variable it expects is declared in the opening comment as
   `@var <name> <type>`, with the leading backslash and the full namespace for a class — that is
   the form the IDE completes from:

   ```twig
   {#
       Contents of a category.

       @var files      array
       @var pagination \Twig\Markup
       @var show_user  \Johncms\Users\User
   #}
   ```

3. **Layout.** `{% extends '@theme/layouts/default.twig' %}` for the site,
   `{% extends '@admin/layouts/default.twig' %}` for the panel, and the body goes into
   `{% block content %}`. A fragment loaded over AJAX or shown in a modal extends nothing.

4. **Facts, not services.** Anything a template would have to work out itself — `config(...)`,
   a query, `date()`, a comparison of rights — belongs in the controller and arrives as data.
   Facts about the request itself (`app.user`, `app.locale`, `app.color_scheme`) stay in the
   `app` global. The whole configuration array is never handed over: pass the values the page
   actually reads.

5. **Every key the template reads must exist.** `strict_variables` is on in development, so a
   row that carries a key only sometimes throws. Fill the key in with `null`/`''`/`[]` in the
   mapper instead of guarding every read with `|default`.

## Components

`{% include '@theme/components/x.twig' with {…} only %}` — always with `only`, so a component
never reads the context around it. Shared ones live in `themes/<theme>/templates/components/`
(alert, breadcrumbs, editor, field-errors, pagination, user-row, …), a component used by one
module lives in `modules/<vendor>/<module>/templates/*/components/`.

Before writing a wrapper for something, look for an existing component: the ckeditor wrapper
was copied into five modules before it became `@theme/components/editor.twig`.

The panel uses its own `@admin/components/ui/editor.twig`: an ordinary textarea enhanced by
`src/admin/js/editor.js`, with CKEditor and its CSS loaded lazily through Vite. It uses no Vue
and must not load the public editor component or its legacy build. Features and translations
are configured in `src/admin/js/editor/create.js`; `src/admin/scss/editor.scss` maps the editor
to the panel's palette. The public editor stays independent.

The components of the panel — stat cards, the search field and the filter dropdowns, the chips
of applied filters, sortable column heads, the user cell, the menu of a row, the empty state —
live in `themes/<theme>/templates/admin/components/ui/` and are shown with their markup on
`/admin/style-guide`. A component added there gets an example in
`modules/johncms/admin/templates/style-guide/examples/` at once.

The public theme has its own `/style-guide`, available with `admin.access`, rendered with the
public layout and bundle. Add examples of new public components to
`themes/default/templates/components/style-guide/examples/`; the guide shows their Twig source
alongside the rendered example. Do not import admin templates or assets into this page. Shared
public content panels and empty states are `@theme/components/content-card.twig` (embed its
`body` block) and `@theme/components/empty-state.twig`.

Modules contribute home-page content through `HomepageBlockProviderInterface`. The optional
`HomepageBlock.position` defaults to `HomepageBlockPosition::Content`; use `Bottom` for a block
below the section tiles. Weights still order blocks within each position. Existing providers
need no change and keep full-width rendering unless they opt into the shared content card.

## Toasts

The result of an action — saved, deleted, refused — is a toast at the top centre of the page,
seen wherever the action was taken: not an alert above a list the user may be far below. Each
bundle has its own implementation with the same contract, since the bundles share no code:

| | public site | panel |
| --- | --- | --- |
| server | `@theme/components/toast.twig` | `@admin/components/ui/toast.twig` |
| script | `src/js/toast.js` | `src/admin/js/toast.js` |
| stack | `layouts/base.twig` | `admin/layouts/base.twig` |
| example | `/style-guide`, Messages | `/admin/style-guide`, Alerts |

The server component takes `message` and `type` (success, info, warning or error). Without the
script it stays an alert in place; with it the script takes it out and shows it, both on the page
as it loads and in what a live list (or, in the panel, a side panel) brings in. A script calls
`toast(message, {type})` of its own bundle.

* **When the page shows the result itself**, the comment just added for instance, pass
  `announce: true`: the message is only read out to a screen reader.
* **An error of a form** stays beside the form, where the user is; a toast is for an action
  that has no form to answer at, or whose user may have none.

## Bootstrap components

When a block of a page is a card, a list group, a dropdown, a modal or another Bootstrap
component, start from the Bootstrap class and add a modifier of the module for what differs:
`<article class="card site-news-card"><div class="card-body">…`, not a card written from
scratch. The colours of these components are already bound to the theme palette in
`themes/<theme>/src/scss/_theme-components.scss`, so the modifier does not repeat the
background, the text colour or the border.

The modifier tunes the component through its CSS variables (`--bs-card-spacer-y`,
`--bs-card-spacer-x`, `--bs-card-border-radius`, …) instead of overriding `padding`,
`border-radius` or `background` directly, so a later change of the theme reaches every card.
It keeps only what Bootstrap has no notion of: the hover state, the elements of its own
(`__meta`, `__rating`, …), the layout inside the body. A Bootstrap component that does not yet
follow the palette gets its variables mapped in `_theme-components.scss`, not in the module.

Buttons come in the sizes of the theme: `btn-xs` (26px, the row of actions under a post or a
comment: reply, quote, delete), `btn-sm`, the default and `btn-lg`; `btn-icon` turns any of
them into a square holding only an icon. A module does not write its own button size or a
button from scratch — a missing size is added to `themes/<theme>/src/scss/_buttons.scss`
through the `--bs-btn-*` variables and shown on `/style-guide`. A modifier of the module may
only retune the variables of a state, like the red hover of a delete button.

A card that is a link as a whole:

* has no links or rich content inside → the card itself is the `<a class="card …">`;
* contains a link, a button or user HTML (previews with links and embeds) → keep the link on
  the title with `.stretched-link` and lift the inner interactive elements above it with
  `position: relative; z-index: 2`. Never wrap such a card in `<a>`: nested links are invalid
  HTML and an embedded video stops receiving clicks.

## Scripts in the panel

The panel needs JavaScript for whatever is interactive: reordering the rows of a list, a picker
that searches as you type, uploading a file, a dialog. Build such a part for the script alone and
do **not** add a second way to do the same by posting the form — an "up" button that submits and
re-renders the page, a search button that reloads it, a hidden draft that survives the round trip.
Each of those doubles the controller and the tests and breeds bugs of its own.

What keeps working without the script, because it costs nothing and lets an administrator repair
a site whose scripts broke:

* a form posts as an ordinary form, and the controller validates everything it receives;
* a link with `data-admin-modal` leads to the same dialog shown as a page;
* the links of a live list are real addresses.

A button only a script handles is `type="button"`, so a missing script never turns it into a
submit of the whole form. The no-script fallbacks that already exist go away as their forms are
reworked, one form at a time, not in a sweep.

## Live lists

A part of a page that changes without a full reload — an admin list with tabs, search and
filters, the pages of the comments below an article — follows one protocol. The panel and the
public site each have their own script for it, since their bundles share no code:
`src/admin/js/live-list.js` (reference: `admin::users`) and `src/js/live-list.js` (reference:
the discussion of `news`). Never import one bundle's script into the other.

```twig
<div data-live-list>
    <form method="get" action="/admin/users" data-live-list-form>
        {% include '@admin/components/ui/search-field.twig' with {…} only %}
        {% include '@admin/components/list-state.twig' with {…} only %}   {# data-live-list-region="state" #}
    </form>
    {% include '@admin/users/results.twig' with {…} only %}     {# data-live-list-region="results" #}
</div>
```

* **Every changing part is a region** — an element with `data-live-list-region="<name>"`, drawn
  by a template of its own. The page includes them; a second template (`users/live.twig`,
  `news/public/comments-live.twig`) includes all of them and nothing else.
* **The controller answers the script with the fragment**: `$request->isXmlHttpRequest()` picks
  the second template, with the same data. Same address, same pagination guard. Take the branch
  before the work only the full page needs: a view counter, side blocks, breadcrumbs.
* **Links and the form are the API.** Without the script the same links and form work as plain
  pages, so build every link on the server (`UserListFilterMapper` is the pattern) and never only
  from JavaScript. The address follows (`history`).
* **What must survive stays outside the regions**: the search field keeps the focus and the caret,
  a form with an editor keeps its text.
* **Behaviour on the regions is delegated** from the root, or set up again on `live-list:update`,
  which the root fires once the regions are in place: the buttons inside them are new elements.
* A fragment test asks with `HTTP_X_REQUESTED_WITH`: the page around it carries the query log of
  the whole process when debugging is on.

The panel's script also follows its form: typing reloads after a pause, other fields at once,
and the rest of the filter rides along as hidden fields in a region inside the form.

The public script follows links inside a region to another page of the same address. A list
below a long text gives its pagination a fragment (`PaginationFactory::create(fragment: …)`), so
both the plain page and the script open it on the list; the script scrolls to that anchor and
moves the focus to `[data-live-list-focus]` when the focus was in the replaced region. Its regions
are looked up on the whole page, so a region may sit outside the root (the number of comments in
the header of the article); keep their names unique on the page. A `<title>` in the fragment,
printed with `@theme/components/document-title.twig` from the same `PageMeta` as the full page,
becomes the title of the document.

It also posts the forms marked `data-live-list-form`. The action keeps its plain contract — a
redirect back to the page with flash messages — and `fetch` follows the redirect with the same
header, so the fragment comes from the page controller as usual:

* **The result goes into a region.** An error of the form is a region of its own beside the form
  (`news/public/components/comment-error.twig`); the page controller reads every flash of the
  action in both branches, so a message shown by the script does not come back on the next page.
* **The anchor of the redirect does not reach the server.** What the page should bring into view —
  the comment just added — is passed by a flash and marked `data-live-list-target` in the fragment.
* **A form outside the regions is reset by its owner** on `live-list:update`, whose `detail.form`
  is the form sent: `clearEditor()` empties an editor and its attachments.
* **A confirmation listens in the capture phase** (`addEventListener('submit', …, true)`), so it
  cancels the form before the script sends it; the script skips a cancelled form.

## Dialogs (the panel)

A confirmation that needs data from the server (what deleting an account leaves behind) is a
dialog, not a page — `admin::users/delete-dialog` is the reference:

* The link carries `data-admin-modal`; `src/admin/js/modal.js` opens its address in the modal of
  the layout.
* The controller answers `isXmlHttpRequest()` with a template that `{% embed %}`s
  `@admin/components/ui/dialog.twig` (blocks `body` and `footer`), and a plain visit with a page
  that puts the same template into `@admin/components/ui/dialog-page.twig`. Close and Cancel are
  links with `data-bs-dismiss="modal"`: they close the modal, and lead back on the page.
* The form posts as any form does and redirects. Where it returns to comes from the caller
  (`back`), checked against the pages it may lead to — never followed as it is.

## Offcanvas details (the panel)

Use an offcanvas to inspect a record while keeping its admin list in view. Mark detail links with
`data-admin-offcanvas`, include a panel on the list page, and have the controller return the
panel contents for XHR requests and the full page for ordinary visits. Forms in the panel post
normally and redirect back to the list so its state and flash messages refresh.

## Traps

**An Eloquent attribute a guest does not carry.** Twig looks the attribute up, does not find
it, sees `__call()` on the model and calls it as a method — `app.user.rights` for a guest ends
up in `User::rights()` and throws. `|default` does not help, since Twig considers a method to
exist. Guard it: `{% if app.user.isValid and app.user.rights >= 7 %}`.

**A nullable column falls through the same way.** `isset($model->flag)` is false when the value
is null, so `topic.deleted` lands on `Model::deleted()` and throws — and a `'boolean'` cast does
not help, since a null stays null. Give the model an accessor that types the value
(`getDeletedAttribute(mixed $value): bool { return (bool) $value; }`); the attribute then stays
an attribute for every template.

**`Markup` is an object, so it is always truthy.** `{% if x %}` on empty markup is true. A
source that can have nothing to show returns `null`, not an empty `Markup`; where that is not
possible, test it with `|length`.

**A layout `{% set %}` leaks into the page.** Blocks of the page are rendered in the context of
the layout, so an unprefixed name shadows the data of the page. Every variable a layout keeps
for itself is prefixed `layout_`; `LayoutVariableIsolationTest` enforces it. Components are
safe — they are included with `only`.

**`{% set %}` does not keep a value safe.** `{% set x = '&laquo;'|raw %}` is escaped again when
printed; write the entity as a literal in the markup instead.

**`app.*` names are snake_case** (`app.is_home_page`, `app.color_scheme`, `app.csrf_token`).

**Public templates must not reference `@admin/`** — `PublicTemplateIsolationTest` fails on it.
The other direction is allowed.

## Verification

```bash
sh .agents/scripts/verify.sh                    # cs-check, phpstan, tests, twig:lint, twig:ide-config --check
docker exec $(docker ps -q -f name=johncms.php-fpm) composer translate-scan
git diff -- '*.pot' | grep -E '^[+-]msgid'      # must print nothing unless strings were added
```

`translate-scan` also refreshes line numbers of files nobody touched. Keep only the domains you
worked on and revert the rest:

```bash
git checkout -- $(git diff --name-only -- '*.pot' | grep -v <your-domain>)
```

Finally, open the page: request it and read the HTML. Escaping regressions (tags shown as text,
or markup printed twice-escaped) are invisible to every check above.
