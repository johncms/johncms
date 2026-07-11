# Localization (i18n)

## Pipeline

Source strings are **English msgids in code** (gettext-style). The flow is one-directional:

```
PHP/phtml sources → composer translate-scan → <domain>.pot → Crowdin → <lang>.po → composer translate → <lang>.lng.php
```

* `composer translate-scan` (`i18n:scan`) — scans sources per domain from `translate.xml` / `translate.xml.dist` and regenerates `<domain>.pot` templates.
* `composer translate` (`i18n:translate`) — converts every `.po` file (`system/locale/*.po`, `modules/*/locale/*.po`, `install/locale/*.po`) into `.lng.php` dictionaries used at runtime.

Run both via the php-fpm container:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate-scan
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate
```

## Hard Rules for Generated Files

* **Never edit `.lng.php` files by hand** — they are generated from `.po` by `composer translate`. Any manual change will be lost.
* **Never edit `.pot` files by hand** — they are generated from sources by `composer translate-scan`.
* `.po` files are the only localization files that may be edited manually, and they are also synced with Crowdin. When asked to translate, edit only the requested language's `.po`; do not machine-translate other languages — those come from Crowdin.
* After changing a `.po` file, always run `composer translate` to regenerate the matching `.lng.php` and commit both together.

## Translation Functions

The scanner (and runtime) recognizes:

* `__('Text')` — current domain (module) translation.
* `d__('domain', 'Text')` — explicit domain, e.g. `d__('system', 'Page')`.
* `n__('One item', '%d items', $count)` — plural forms.
* `dn__('domain', 'One item', '%d items', $count)` — plural with explicit domain.

Rules:

* Write msgids in **English** — they are both the key and the fallback text.
* Use `sprintf`-style placeholders (`%s`, `%d`) instead of string concatenation, so translators can reorder words.
* Do not build sentences from fragments; a msgid should be a complete phrase.

## Domains

* Each module has its own domain named after the module (`forum`, `mail`, …) with files in `modules/<module>/locale/`.
* The `system` domain (`system/locale/`) covers `system/src`, shared templates, and a few small modules (login, language, notifications, redirect) — see `translate.xml.dist`.
* A new module needs a `<domain>` entry in `translate.xml.dist` (name, `target` locale dir, `sourceDir`) before `translate-scan` can produce its `.pot`.

## Adding / Changing Strings — Checklist

1. Add or edit the English string in code via `__()` / `d__()` / `n__()`.
2. Run `composer translate-scan` to refresh the domain's `.pot`.
3. If asked to provide a translation (usually `ru.po`): add the `msgid`/`msgstr` pair to that `.po` only.
4. Run `composer translate` to regenerate `.lng.php`.
5. Commit source changes together with the regenerated `.pot`, edited `.po`, and `.lng.php` files.
