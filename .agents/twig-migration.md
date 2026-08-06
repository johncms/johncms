# Migrating a Module to Twig

The view layer runs two engines: Twig for what has been migrated, Plates (`.phtml`) for the
rest. The dispatcher picks by the shape of the template name — `@namespace/file.twig` goes to
Twig, `namespace::file` to Plates — so a module moves on its own, without touching any other.

This guide is the recipe for moving one module. Read `escaping.md` and `localization.md`
before the first one.

## Layout of a migrated module

```
modules/<module>/templates/public/<page>.twig            → @<module>/public/<page>.twig
modules/<module>/templates/public/components/<part>.twig → partials of that module
modules/<module>/templates/admin/<page>.twig             → @<module>/admin/<page>.twig
```

A theme overrides any of them by mirroring the path:
`themes/<theme>/templates/<module>/public/<page>.twig`. Nothing is registered anywhere — the
namespaces come from the installed modules and the theme chain.

## Steps

1. **Controller.** Return `Johncms\Http\View\ViewResponse` instead of a rendered string:

   ```php
   return new ViewResponse('@news/public/index.twig', ['articles' => $articles]);
   ```

   Drop the `Render` dependency and fold `$render->addData([...])` (title, page_title,
   description, canonical) into the same data array. A controller that also returns a
   `RedirectResponse` is typed `Response|ViewResponse`.

2. **Header of the template.** Every variable the template expects is declared in the opening
   comment as `@var <name> <type>`, with the leading backslash and the full namespace for a
   class — that is the form the IDE completes from:

   ```twig
   {#
       Contents of a category.

       @var files      array
       @var pagination \Twig\Markup
       @var show_user  \Johncms\Users\User
   #}
   ```

3. **Template.** `$this->layout('system::layout/default')` becomes
   `{% extends '@theme/layouts/default.twig' %}` and the body goes into `{% block content %}`.
   A fragment loaded over AJAX extends nothing.

4. **Every output reviewed one by one** — the table below. This is the part that cannot be
   done mechanically.

5. **Facts, not services.** Anything the old template computed itself — `config(...)`,
   `di(...)`, `$container->get(...)`, `parse_url()`, a query — moves to the controller and
   arrives as data. Facts about the request itself (`app.user`, `app.locale`,
   `app.color_scheme`) stay in the `app` global.

6. **Delete the `.phtml`** in the same commit. Two copies of a page drift apart silently.

7. **Verify** (see the last section).

## Output review

| What it was | What it becomes |
| --- | --- |
| `<?= $this->e($x) ?>` | `{{ x }}` |
| `<?= $x ?>` where escaping was simply forgotten | `{{ x }}` — the bug is fixed by moving |
| `<?= $x ?>` where the value is HTML by contract | make the source return `Twig\Markup`; `\|raw` only when there is no source to fix |
| `htmlspecialchars()` in the **controller** | remove it — the environment escapes, twice is visible |
| `<?= $cond ? ' checked="checked"' : '' ?>` | `{% if cond %} checked{% endif %}` |
| `sprintf(__('…%s…'), '<b>' . $x . '</b>')` | `{{ __('…%s…', '<b>' ~ x\|e ~ '</b>')\|raw }}` — msgid unchanged |
| `<?= $this->fetch('ns::partial', [...]) ?>` | `{% include '@ns/partial.twig' with {...} only %}` |
| `<?= $this->asset(...) ?>`, `avatar()`, `formatNumber()` | `asset()`, `avatar()`, `\|format_number` |
| `isset($x)` / `! empty($x)` | `x is defined`, `x\|default('')`, `{% if x %}` |

`|raw` is a signal to the reviewer that the author decided the value is markup. If it appears
more than once for values from the same source, fix the source instead.

Services that already return `Markup`: `Pagination::render()`,
`UserPlaceFormatterInterface::format()`, `ForumVisitorPlaceFormatter::format()`,
`Notification::$message`, `DownloadFile::$about_html`, `vite()`.

## Traps

**An Eloquent attribute a guest does not carry.** Twig looks the attribute up, does not find
it, sees `__call()` on the model and calls it as a method — `app.user.rights` for a guest ends
up in `User::rights()` and throws. `|default` does not help, since Twig considers a method to
exist. Guard it: `{% if app.user.isValid and app.user.rights >= 7 %}`.

**`Markup` is an object, so it is always truthy.** `{% if x %}` on empty markup is true. A
source that can have nothing to show returns `null`, not an empty `Markup`; where that is not
possible, test it with `|length`.

**A layout `{% set %}` leaks into the page.** Blocks of the page are rendered in the context of
the layout, so an unprefixed name shadows the data of the page. Every variable a layout keeps
for itself is prefixed `layout_`; `LayoutVariableIsolationTest` enforces it. Components are
safe — they are included with `only`.

**String literals are safe by definition.** The escaper does not touch a constant string, so
`{{ '<b>x</b>' }}` prints as markup. Add `|e` when a literal has to be shown as text.

**`app.*` names are snake_case** (`app.is_home_page`, `app.color_scheme`, `app.csrf_token`).

**Public templates must not reference `@admin/`** — `PublicTemplateIsolationTest` fails on it.
The other direction is allowed.

## Verification

```bash
sh .agents/scripts/verify.sh                    # cs-check, phpstan, tests, twig:lint
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate-scan
git diff -- '*.pot' | grep -E '^[+-]msgid'      # must print nothing
```

A moved template changes the file references in `.pot`, never the strings themselves. If a
`msgid` appears or disappears, a string was lost or reworded — fix it before committing.

`translate-scan` also refreshes line numbers of files nobody touched. Keep only the domains
of the module being migrated and revert the rest:

```bash
git checkout -- $(git diff --name-only -- '*.pot' | grep -v <your-domain>)
```

Finally, open the page: request it and read the HTML. Escaping regressions (tags shown as
text, or markup printed twice-escaped) are invisible to every check above.
