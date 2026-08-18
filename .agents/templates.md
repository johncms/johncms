# Templates

The view layer is Twig only. Read `escaping.md` and `localization.md` before writing a page.

## Where a template lives

```
modules/<module>/templates/public/<page>.twig     → @<module>/public/<page>.twig
modules/<module>/templates/public/components/…    → partials of that module
modules/<module>/templates/admin/<page>.twig      → @<module>/admin/<page>.twig
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
module lives in `modules/<module>/templates/*/components/`.

Before writing a wrapper for something, look for an existing component: the ckeditor wrapper
was copied into five modules before it became `@theme/components/editor.twig`.

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
