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

## Pre-Commit Checklist

Before committing:

* Changes are scoped to the task.
* No unrelated files were modified.
* Backend style check passes:

composer cs-check

* UI build succeeds if frontend code was changed.
* Services and repositories are injected via interfaces.
