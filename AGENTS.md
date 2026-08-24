# AGENTS.md

## Purpose

This document guides contributors and automated agents when working in this codebase. Follow these rules to keep architecture and refactoring consistent.

## Topic Guides (read on demand)

Detailed rules live in `.agents/` — see `.agents/README.md` for how the agent tooling is
organised and which tools are supported. Before touching the listed area, READ the matching
guide first:

* Module layers, dependency rules, refactoring principles, repository rules → read `.agents/architecture.md`
* Actions with access checks (guard → context → action), exception mapping → read `.agents/access-guard.md`
* Paginated lists, page titles / meta description → read `.agents/pagination.md`
* Caching values, cache tags and invalidation → read `.agents/caching.md`
* Changing the database schema, moving data, migrations → read `.agents/migrations.md`
* Rendering a user's text (posts, comments, articles), embedding media, adding a step to the content pipeline → read `.agents/content-pipeline.md`
* Resizing pictures, thumbnails, previews of an upload → read `.agents/images.md`
* Storing or deleting files, uploads, disks, the `files` table → read `.agents/storage.md`
* User input handling, output escaping in templates, sanitizing user HTML → read `.agents/escaping.md`
* Translations, `.po`/`.pot`/`.lng.php` files, `__()` strings → read `.agents/localization.md`
* Creating a new module (structure, autoload, DI, routes, templates) → read `.agents/new-module.md`
* Writing or changing a template (namespaces, environments, components) → read `.agents/templates.md`
* Validating a form, adding a validation rule, messages of a rule → read `.agents/validation.md`
* Asking a form for a captcha, adding a captcha provider → read `.agents/captcha.md`
* Writing a test: the two suites, the harness of a functional test, accounts and fixtures → read `.agents/testing.md`
* Self-review, when the user asks for it (architecture, security, PHP quality, localization) → read `.agents/review/README.md`

## Self-Review

After finishing an implementation task, **before reporting back to the user**, run only the
deterministic gate:

```bash
sh .agents/scripts/verify.sh
```

Fix what it reports. That is the whole automatic part.

The review checklists in `.agents/review/` (architecture, security, PHP quality,
localization) are **on demand only**. Do not start them — and in Claude Code do not launch
the `reviewer-*` subagents — unless the user explicitly asks for a review, for example with
`/review-self`. A full review costs several times more tokens than the implementation
itself, so the decision to spend them belongs to the user.

If a change looks like it would benefit from a review, finish the task, then say so in one
line and let the user decide.

## Project Context

JohnCMS is a multilingual CMS with a long-lived codebase that is gradually being refactored.

Tech stack:

* PHP 8.4
* MySQL
* Bootstrap
* selective Vue components
* Vite
* Twig templates

## Project Structure

* `modules/` — application modules, one directory per vendor (`modules/johncms/<module>/`)
* `system/src/` — primary application code
* `themes/` — themes: templates and asset sources
* `config/` — configuration files
* `data/` — cache, logs, temporary files
* `public/` — the document root: `index.php`, `assets/`, `build/`, `themes/`, `upload/`
* `public/install/` — the installer, deleted after the site is up

## Core Principles

* Refactor in **small, safe steps** while preserving existing behavior; keep changes minimal and scoped, do not modify unrelated modules (full list in `.agents/architecture.md`).
* Inject services and repositories via interfaces; repository contracts live in Domain as `*RepositoryInterface`.
* Security: **escape on output, not on input** — never HTML-escape data when saving to DB (details in `.agents/escaping.md`).
* Always start Eloquent queries with `Model::query()->...`, never `Model::where(...)` directly.

## PHP Style Rules

* Always include `declare(strict_types=1);`
* Namespace must follow PSR-4 and match the directory structure.
* Use 4-space indentation.
* Prefer one class per file.
* Use typed properties, arguments, and return types.
* Avoid redundant scalar casts (`(int)`, `(string)`, `(bool)`) when the type is already guaranteed by signatures or framework/API contracts.
* Use PHPDoc only when types cannot be expressed with native PHP types.
* Write all code comments, PHPDoc, and inline notes in English. This is a multilingual project reviewed by contributors from different countries, so English keeps comments accessible to everyone.
* Use constructor injection with property promotion.
* Prefer immutable design.
* Prefer `final` classes for new code.
* Do not change inheritance structure of existing classes unless explicitly required.
* Use `readonly` only for new immutable service or DTO classes.
* Do not introduce `readonly` to legacy classes during refactoring.
* Keep methods focused and reasonably short.
* Keep HTTP mapping logic in controllers only.
* If a caught exception variable is unused, omit it (e.g. `catch (EditVoteWrongDataException)`).

## Naming Conventions

Classes: `PascalCase`
Methods / properties: `camelCase`

Suffix rules:

* `*Controller`
* `*UseCase`
* `*DTO`
* `*RepositoryInterface`
* `*Command`
* `*Query`
* `*Handler`
* `*Mapper`
* `*Compiler`

## Error Handling

* Throw domain or application-specific exceptions for business failures.
* Do not silently swallow exceptions.
* Prefer guard clauses and early returns.
* Error messages should be actionable and specific.

## Commit Messages

Use **Conventional Commits**.

Format:

type(scope): subject

Examples:

refactor(home): extract homepage use case
fix(user): correct password validation

Rules:

* Use imperative English verbs.
* Keep commit subjects short.
* Use module name as scope when applicable.
* Omit scope for global changes.
* You may add a commit body (for example with multiple `-m` flags) to document key changes such as new URLs, migrations, or architectural refactoring details.

## Pre-Commit Checklist

Before committing:

* Changes are scoped to the task.
* No unrelated files were modified.
* The verification gate passes — style check, static analysis, and tests:

```bash
sh .agents/scripts/verify.sh
```

Fix style violations with:

```bash
docker exec $(docker ps -q -f name=johncms.php-fpm) composer cs-fix
```

* If the user asked for a self-review, its findings were addressed (see `.agents/review/README.md`).
* UI build succeeds if frontend code was changed.
* Services and repositories are injected via interfaces.

## Documentation

* `docs/` — GitBook documentation submodule.
* File names must be in English (e.g. `getting-started.md`), not transliterated Russian.
* When adding or removing pages, always update `docs/SUMMARY.md` to reflect the change.

## Docker Command Policy

* Run all `php` and `composer` commands inside the `php-fpm` Docker container.
* Use `docker exec $(docker ps -q -f name=johncms.php-fpm) <command>` to target the container.
* Do not rely on host PHP/Composer versions for checks, tests, or dependency operations.
