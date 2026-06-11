# AGENTS.md

## Purpose

This document guides contributors and automated agents when working in this codebase. Follow these rules to keep architecture and refactoring consistent.

## Project Context

JohnCMS is a multilingual CMS with a long-lived codebase that is gradually being refactored.

Tech stack:

* PHP 8.2
* MySQL
* Bootstrap
* selective Vue components
* Webpack
* Plates templates

## Project Structure

* `modules/` — application modules
* `system/src/` — primary application code
* `system/src-legacy/` — legacy code targeted for gradual removal
* `themes/` — admin and public templates
* `assets/` — shared static assets
* `config/` — configuration files
* `data/` — cache, logs, temporary files
* `install/` — installer assets
* `upload/` — user uploads

## Architecture

Typical module layers:

Application

* controllers
* DTOs
* use cases
* handlers

Domain

* entities
* enums
* interfaces
* pure services

Infrastructure

* repository implementations
* adapters

### Dependency Rules

* Prefer **Application → Domain** dependencies.
* Controllers and use cases must not depend on infrastructure details.
* Repository contracts must be defined in **Domain** as `*RepositoryInterface`.
* Implement repository contracts in **Infrastructure**.
* Inject interfaces into services, use cases, and controllers.

## Refactoring Principles

Refactor in **small, safe steps** while preserving existing behavior.

Guidelines:

* Extract logic into use cases or services.
* Move database logic out of controllers.
* Introduce repository interfaces when persistence is involved.
* Avoid large rewrites.
* Keep changes minimal and focused.
* Do not modify unrelated modules.
* Do not introduce new architecture or patterns unless they already exist in the codebase.
* Prefer modifying existing code over introducing new abstractions.
* Do not change public APIs or method signatures unless required by the task.

### Repository Rules

* Keep repositories thin: repository methods should only build and execute data queries.
* Repositories must not contain business rules, access checks, URL/place parsing, or HTTP/presentation logic.
* Complex decision logic (domain branching, post-filtering in PHP, cross-entity interpretation) belongs in Application use cases or Domain services.
* Prefer SQL/query builder expressions (joins, subqueries, `exists`, `where`) over loading broad datasets and filtering in PHP.
* Repository methods should return query results; UI-oriented mapping and formatting must be done outside repositories.
* If a repository method needs non-trivial loops, regex parsing, or deep condition trees, move that logic out of the repository.
* Prefer model query builder `get()` in repositories and return typed collections when downstream code needs model fields, mutators, and IDE autocompletion.
* Use `toBase()` only when raw DB rows are explicitly required; do not mix model and raw-row contracts in the same repository API.
* Always start queries with `Model::query()->...` instead of `Model::where(...)` directly — `::query()` returns a typed `Builder<Model>` that gives correct IDE autocompletion.

### Legacy Code Rules

Legacy code lives in `system/src-legacy`.

Rules:

* Do not introduce new features into legacy code.
* Only modify legacy code when required for refactoring.
* Move extracted logic into `system/src`.

## Access Guard Pattern

For actions that combine access checks and a state-changing operation, split the flow into three use cases:

* `Ensure*AccessUseCase`

    * performs access, ownership, and time-window checks
    * throws exceptions on failure
    * contains no DTOs

* `Get*ContextUseCase`

    * returns a context DTO (for example `topicId` or `page`)
    * performs no access checks

* `*UseCase`

    * performs the action itself
    * contains no HTTP knowledge
    * does not repeat access checks

Controller flow:

guard → context → action

Execute the action only for write operations (for example POST requests in forms or equivalent command-style operations).

#### When NOT to create `Ensure*AccessUseCase`

Do not introduce a separate `Ensure*AccessUseCase` if all conditions below are true:

* guard logic is trivial (for example: 1–2 simple rights/ownership checks)
* guard is used by only one controller/action
* keeping guard separate would duplicate repository reads already needed in `Get*ContextUseCase`

In this case, move guard checks into `Get*ContextUseCase` and keep one preflight call in controller.

If guard logic grows (multiple branches, time windows, reusable policy, or shared usage in 2+ controllers), extract it back into dedicated `Ensure*AccessUseCase`.

### Exception Mapping

* Access denied → HTTP 403
* Not found / ownership mismatch → user-facing “Wrong data”
* Expired window → timeout message
* Validation or upload errors → user-facing validation errors

Do not register exceptions as DI services.
Exclude `Application/Exceptions` from service autoload.

## PHP Style Rules

* Always include `declare(strict_types=1);`
* Namespace must follow PSR-4 and match the directory structure.
* Use 4-space indentation.
* Prefer one class per file.
* Use typed properties, arguments, and return types.
* Avoid redundant scalar casts (`(int)`, `(string)`, `(bool)`) when the type is already guaranteed by signatures or framework/API contracts.
* Use PHPDoc only when types cannot be expressed with native PHP types.
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

## Output Escaping & Input Handling

Security principle: **escape on output, not on input**.

* Accept user input in its original form at the boundary (request/DTO), without HTML escaping.
* Do not use `htmlspecialchars()` / `$this->e()` while saving data to DB.
* Perform validation on input (length, required fields, format, allowed values), but keep original text.
* Escape only at render time and in the correct context:
  * HTML text node → `$this->e(...)`
  * HTML attribute (`href`, `title`, `value`, etc.) → `$this->e(...)`
  * URLs from user data → validate/allowlist scheme (`http` / `https` or local path) before output
  * JSON output → use `json_encode`, do not build JSON manually
* For rich content (BBCode/HTML), apply a dedicated sanitizer/allowlist before rendering.
* Avoid double-escaping: data should be escaped exactly once, at the final output boundary.
* Repositories/use cases/controllers must not mix persistence with presentation escaping.

## Page Title and Description with Pagination

Use `Johncms\Http\PageMeta` to build the document `title` and meta `description` for paginated pages. It appends the page suffix automatically from page 2 onward.

```php
use Johncms\Http\PageMeta;

$meta = new PageMeta($documentTitle, $page);
// or with explicit description:
$meta = new PageMeta($documentTitle, $page, $description);

$this->render->addData([
    'title'       => $meta->title,
    'page_title'  => __('Page Heading'),
    'description' => $meta->description,
]);
```

Rules:

* Page 1 receives no suffix — `title` and `description` stay unchanged.
* If `description` is omitted or empty, `PageMeta` uses `title` as the base for the description.
* The separator is ` — ` (em dash with spaces), followed by the translated word "Page" from `d__('system', 'Page')`.

## Pagination

Use `Johncms\Http\Pagination` for all paginated lists in new and refactored code. **Do not use** the legacy fork `johncms/johncms-pagination` (Laravel `LengthAwarePaginator` / `->paginate()`) or `Tools::displayPagination` — both are being removed together with `system/src-legacy`.

Components:

* `PaginationFactory` (DI service) — builds a `Pagination` from a total count. Inject it into controllers.
* `Pagination` — immutable, no service-locator: holds page math (`getOffset()`, `getPerPage()`, `getCurrentPage()`, `getTotalPages()`, `hasPages()`), builds URLs (`getUrl()`), exposes items (`getItems()`), and renders the template (`render()`).
* `PaginationGuard` (DI service) — returns a redirect URL for non-canonical pages; it does **not** redirect itself (keeps it testable). The controller calls `redirect()` explicitly.

### Controller flow

```php
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;

// in constructor: PaginationFactory $paginationFactory, PaginationGuard $paginationGuard

// 1. Build pagination from the total count (a cheap COUNT query in the use case/repository).
$pagination = $this->paginationFactory->create($this->listUseCase->count());

// 2. Canonical-page guard — only on the GET/render branch, never after a POST action.
if ($this->request->getMethod() !== 'POST') {
    $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
    if ($redirectUrl !== null) {
        redirect($redirectUrl);
    }
}

// 3. Fetch the page slice with offset/limit from pagination.
$items = $this->listUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

// 4. Build meta from the resolved current page and render the HTML pagination.
$meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
return $this->render->render('module::index', [
    'items'      => $items,
    'pagination' => $pagination->render(),
    // ...
]);
```

`PaginationFactory::create(int $total, ?int $perPage = null, string $pageParamName = 'page', ?int $currentPage = null)`:

* `perPage` defaults to the current user's `config->kmess`. Pass an explicit value only when the list has its own page size.
* `currentPage` defaults to the `page` query parameter; pass it explicitly only in non-HTTP contexts.

### Repository / use case split

The use case must **not** know about HTML rendering or the Laravel paginator. Split data access into two methods so the controller controls slicing:

* `count(): int` → repository `count*(...)` (a `COUNT` query).
* `getPage(int $limit, int $offset): array` → repository `get*(..., int $limit, int $offset): Collection`, mapped to DTOs.

Repositories take explicit `limit`/`offset` and return `Collection`; never call `->paginate()`.

### Rules

* Page 1 is canonical without a `page` query parameter; `getUrl(1)` strips the parameter (handled by `PaginationGuard`: `?page=1`, junk, or `page < 1` → URL without `page`; `page > totalPages` → last page).
* Escape on output: `Pagination` items carry raw `type`/`page`/`url`/`active`; the `system::app/pagination` template (`themes/default` and `themes/admin`) escapes URLs with `$this->e(...)`. Do not pre-escape in PHP.
* Render in the controller/template via `$pagination->render()` (or `$pagination->getItems()` for custom markup / JSON endpoints), not inside the use case.
* Redirect statuses are 302 for now (matching the old fork); the canonical strip may become 301 later if `redirect()` gains a status parameter.

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
* Backend style check and tests pass:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-check
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer test
```

Fix style violations with:

```bash
docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-fix
```

* UI build succeeds if frontend code was changed.
* Services and repositories are injected via interfaces.

## Documentation

* `docs/` — GitBook documentation submodule.
* File names must be in English (e.g. `getting-started.md`), not transliterated Russian.
* When adding or removing pages, always update `docs/SUMMARY.md` to reflect the change.

## Docker Command Policy

* Run all `php` and `composer` commands inside the `php-fpm` Docker container.
* Use `docker exec $(docker ps -q -f name=johncms9.php-fpm) <command>` to target the container.
* Do not rely on host PHP/Composer versions for checks, tests, or dependency operations.
