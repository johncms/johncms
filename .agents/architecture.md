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

### How a class gets the current request

Controllers are container singletons, so a request stored in one outlives the request it belongs
to: in a worker runtime every later cycle would be answered with the first request of the process.

* **A controller takes `Request` as an argument of the action** — never in the constructor, never
  in a property. Only the actions that actually read the request take it; form and confirmation
  actions keep their signatures. `ActionInvoker` resolves the request by type and the route
  parameters by name, so the order is free; existing actions put the request first.
* **A private helper of a controller receives the request as a parameter.** Passing it down the
  call chain is the point; storing it in a property to save the parameter reintroduces the leak.
* **The request does not exist as a service** — `di(Request::class)` and
  `$container->get(Request::class)` throw. The only ways to it are the action argument and the
  stack below.
* **A template gets facts, not the request.** Facts about the visitor and the page arrive in the
  `app` global (`app.user`, `app.locale`, `app.is_home_page`, `app.csrf_token`), which is a thin
  object over `RequestStack`; everything else comes from the controller as data. A template
  reaches neither for the request nor for the container.
* **A service that outlives the request** and needs a fact about it has three options, in order of
  preference: take the fact as a parameter (a string address, a host — see `ClientInfoDTO`); take
  the `Request` as a parameter of the method that reads it, when a whole set of fields is needed;
  or read it from `Symfony\Component\HttpFoundation\RequestStack`. The stack is the last resort and
  is allowed **only in `system/src/`** — in a module's Application layer it is the same hidden
  capture in a different shape. It earns its place when the callers number in the dozens and their
  actions have no request to pass (`PaginationFactory`, `ColorScheme`, `Environment`).
* A shared service that caches anything per request implements
  `Symfony\Contracts\Service\ResetInterface`; the container tags it and the kernel resets it at the
  start of every cycle.

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
