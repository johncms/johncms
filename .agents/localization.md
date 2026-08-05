# Localization (i18n)

## Pipeline

Source strings are **English msgids in code** (gettext-style):

```
PHP/phtml/twig sources → composer translate-scan → <domain>.pot → <lang>.po → composer translate → <lang>.lng.php
```

Crowdin is synced **manually, via CLI commands only**. There is no automatic sync: nothing is pushed or pulled in the background, and no CI job updates the repo. The repository is the source of truth — a `.po` edited and committed here stays as-is until someone explicitly runs a Crowdin command.

* `composer translate-scan` (`i18n:scan`) — scans sources per domain from `translate.xml` / `translate.xml.dist` and regenerates `<domain>.pot` templates.
* `composer translate` (`i18n:translate`) — converts every `.po` file (`system/locale/*.po`, `modules/*/locale/*.po`, `public/install/locale/*.po`) into `.lng.php` dictionaries used at runtime.

Run both via the php-fpm container:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate-scan
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer translate
```

## Crowdin — Manual Commands

Crowdin CLI runs **on the host** (not in the container) and is driven by `crowdin.yml`. Targets live in the `makefile` and default to the `9.x` branch (`CROWDIN_BRANCH`):

```bash
make crowdin-upload                # upload sources (.pot)
make crowdin-upload-translations   # upload translations (.po)
make crowdin-upload-all            # both, in the correct order
make crowdin-download              # download translations from Crowdin into .po
```

Rules:

* **Sources first, translations second.** `crowdin upload translations` can only attach translations to strings that already exist in Crowdin, so new or changed msgids must be uploaded as `.pot` first. `make crowdin-upload-all` does this in order.
* Uploading sources and uploading translations are **separate commands** — `crowdin upload` alone only pushes sources and will not publish your `.po` edits.
* Do **not** run any Crowdin command on your own initiative. Uploading and downloading are outward-facing actions; run them only when explicitly asked.
* After `make crowdin-download`, always run `composer translate` to regenerate `.lng.php`, and review the `.po` diff before committing.

## Hard Rules for Generated Files

* **Never edit `.lng.php` files by hand** — they are generated from `.po` by `composer translate`. Any manual change will be lost.
* **Never edit `.pot` files by hand** — they are generated from sources by `composer translate-scan`.
* `.po` files are the only localization files that may be edited manually. Translate only the languages you were explicitly asked to; do not machine-translate the remaining languages on your own initiative.
* After changing a `.po` file, always run `composer translate` to regenerate the matching `.lng.php` and commit both together.
* When editing a `.po`, change **only the `msgstr` lines you are translating**. Do not reformat the file, rewrap lines, or reorder entries — tools like `polib` rewrite whole files by default and produce huge, unreviewable diffs.

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
* A new module needs **two** registrations, otherwise its strings silently stay untranslated:
  1. a `<domain>` entry in `translate.xml.dist` (`name`, `target` locale dir, `sourceDir`) — without it `translate-scan` produces no `.pot`;
  2. a `files` entry in `crowdin.yml` (`source: <domain>.pot`, `translation: %two_letters_code%.po`) — without it the domain never reaches Crowdin.
* A module with a `.pot` but no `<lang>.po` falls back to English for that language. Adding a language means creating `<lang>.po` in the module's `locale/` directory.

## Adding / Changing Strings — Checklist

1. Add or edit the English string in code via `__()` / `d__()` / `n__()`.
2. Run `composer translate-scan` to refresh the domain's `.pot`.
3. If asked to provide a translation (usually `ru.po`): add the `msgid`/`msgstr` pair to that `.po` only.
4. Run `composer translate` to regenerate `.lng.php`.
5. Commit source changes together with the regenerated `.pot`, edited `.po`, and `.lng.php` files.
6. Push to Crowdin only if asked: `make crowdin-upload-all`.
