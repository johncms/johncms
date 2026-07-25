# Architecture & Repositories

## Module Layers

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

## Dependency Rules

* Prefer **Application → Domain** dependencies.
* Controllers and use cases must not depend on infrastructure details.
* Repository contracts must be defined in **Domain** as `*RepositoryInterface`.
* Implement repository contracts in **Infrastructure**.
* Inject interfaces into services, use cases, and controllers.

### HTTP types stay in the HTTP layer

* HTTP request/response types (`Johncms\Http\Request`, `Response`, and the framework
  types they extend) are allowed **only in controllers, middleware and the HTTP layer itself**
  — `system/src/Http/` (the kernel, `RequestPathNormalizer`, `ResponseNormalizer`,
  `ExceptionResponseFactory`, pagination) and the router. Turning a request into a response is
  what those classes are for; the rule exists to keep HTTP out of everything else.
* Application and Domain must not reference them. Do not accept a `Request` in a use case,
  DTO, service, or repository, and do not leak an HTTP upload type (e.g. PSR-7
  `UploadedFileInterface`) past the controller — map it to a plain DTO first.
* Controllers translate HTTP into calls on the layers below and translate the result back
  into a response; the HTTP mapping lives there and nowhere else.

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

## Repository Rules

* Keep repositories thin: repository methods should only build and execute data queries.
* Repositories must not contain business rules, access checks, URL/place parsing, or HTTP/presentation logic.
* Complex decision logic (domain branching, post-filtering in PHP, cross-entity interpretation) belongs in Application use cases or Domain services.
* Prefer SQL/query builder expressions (joins, subqueries, `exists`, `where`) over loading broad datasets and filtering in PHP.
* Repository methods should return query results; UI-oriented mapping and formatting must be done outside repositories.
* If a repository method needs non-trivial loops, regex parsing, or deep condition trees, move that logic out of the repository.
* Prefer model query builder `get()` in repositories and return typed collections when downstream code needs model fields, mutators, and IDE autocompletion.
* Use `toBase()` only when raw DB rows are explicitly required; do not mix model and raw-row contracts in the same repository API.
* Always start queries with `Model::query()->...` instead of `Model::where(...)` directly — `::query()` returns a typed `Builder<Model>` that gives correct IDE autocompletion.

### Method naming: `find*` vs `get*`

Follow the existing convention across modules:

* `find*` — single-entity lookup that **may return `null`**: `findById(int $id): ?User`, `findContact(...): ?Contact`.
* `get*` — returns a **guaranteed** value or a `Collection` (never a bare `null` for a missing single entity): `getContacts(): Collection`, `getBlocklist(): Collection`.

Rule of thumb: if the return type is `?Entity`, name it `find*`. The use case decides what a missing result means (e.g. throw a `*NotFoundException`).
