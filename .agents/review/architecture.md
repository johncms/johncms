# Architecture Review Checklist

Read `.agents/architecture.md` and `.agents/access-guard.md` first — this file lists what to
verify, those files define the rules. Follow the protocol in `.agents/review/README.md`.

## Layers & Dependencies

* Application depends on Domain, not the other way round.
* Controllers and use cases do not reference Infrastructure classes (repository
  implementations, adapters, Eloquent models) directly.
* Repository contracts are declared in Domain and named `*RepositoryInterface`.
* Implementations live in Infrastructure and are bound in the module's DI config.
* Constructors receive interfaces, not concrete implementations.

## Repositories Stay Thin

* No business rules, access checks, URL/place parsing, or HTTP/presentation logic.
* No UI-oriented mapping or formatting of results.
* No non-trivial loops, regex parsing, or deep condition trees — that belongs in a use case
  or Domain service.
* Filtering happens in SQL (joins, subqueries, `exists`, `where`), not by loading a broad
  dataset and filtering in PHP.
* Queries start with `Model::query()->...`, never `Model::where(...)` directly.
* `toBase()` only where raw rows are explicitly required; a repository does not mix model
  and raw-row return contracts in the same API.

## Method Naming

* `find*` returns `?Entity` and may return `null`.
* `get*` returns a guaranteed value or a `Collection`, never a bare `null` for a missing
  single entity.
* Deciding what a missing result means is the use case's job, not the repository's.

## Access Guard Flow

* Write operations follow guard → context → action.
* `Ensure*AccessUseCase` contains checks and throws, holds no DTOs.
* `Get*ContextUseCase` returns a context DTO and performs no access checks.
* The action use case has no HTTP knowledge and does not repeat access checks.
* Conversely: a separate `Ensure*AccessUseCase` is **not** warranted when the guard is
  trivial, single-caller, and would duplicate reads already done in the context use case —
  flag over-engineering here as readily as a missing guard.
* Exception → HTTP mapping happens in the controller only.
* Exceptions are not registered as DI services; `Application/Exceptions` stays out of the
  service autoload.

## Scope Discipline

* No unrelated modules were touched.
* No new architecture, abstraction, or pattern that does not already exist in the codebase.
* Existing code was modified in preference to adding new indirection.
* Public APIs and method signatures were not changed unless the task required it.
* Class inheritance structure of existing classes was not altered.
