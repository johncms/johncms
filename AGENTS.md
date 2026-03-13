# AGENTS.md

## Purpose & Scope
This document helps contributors and automated agents work consistently in this
codebase. It captures the project context, architecture expectations, and
coding rules that should be followed when making changes.

## Project Overview
- JohnCMS is a CMS for building websites.
- The project is multilingual and has a long-lived codebase.
- We are gradually refactoring the system over time.

## Tech Stack
- Backend: PHP 8.2, MySQL
- Frontend: Bootstrap, selective Vue components
- Bundler: Webpack
- Templates: Plates (near-native PHP templates)

## Quick Start
- Install PHP deps: `composer install`
- Install JS deps: `npm ci`
- Initialize env: `cp .env.example .env`

## Build & Assets
- Public site build: `npm run prod`
- Admin build: `npm run prod-admin`

## Admin vs Public UI
- Admin and public UI have separate templates and build outputs.
- Admin templates live in `themes/admin/`.
- Public templates live in `themes/default/`.

## Architecture & Modules
- Modules live in `modules/`.
- Typical layers per context:
  - Application: controllers, DTOs, use cases, handlers
  - Domain: entities, enums, interfaces, pure services
  - Infrastructure: repository implementations, adapters

## Project Structure
- `modules/` holds the standard modules.
- `system/src/` contains the primary application code.
- `system/src-legacy/` is legacy code targeted for removal after refactoring.
- `themes/` contains admin and public templates.
- `assets/` holds shared static assets.
- `config/` contains configuration files.
- `data/` is used for cache, logs, and temporary data.
- `install/` contains installer assets.
- `upload/` contains user uploads.

## i18n
- The project uses gettext for localization.
- Core locale files live in `system/locale/`.
- Module-specific locale files live inside each module.

## Refactoring Approach
- Refactor in small, safe steps and preserve existing behavior.
- Current priority: move code toward controllers, repositories, models, and use cases.

## Dependency Rules
- Prefer Application -> Domain dependencies.
- Keep infrastructure details out of controllers and use cases.
- Define repository contracts in Domain as `*RepositoryInterface`.
- Implement repository contracts in Infrastructure.
- Inject interfaces into services, use cases, and controllers.

## PHP Style Rules
- Always include `declare(strict_types=1);`.
- Namespace must mirror directory structure (PSR-4).
- Use 4 spaces indentation.
- Keep imports explicit and tidy.
- Prefer one class per file.
- Use typed properties, arguments, and returns.
- Use PHPDoc for generic arrays/lists/shapes when needed.
- Use constructor injection and property promotion.
- Prefer immutable design when possible.
- Prefer `final` classes unless extension is intentional.
- Prefer `readonly` for immutable service/DTO classes.
- Keep methods focused and short where practical.
- Keep HTTP mapping logic in controllers; business logic elsewhere.

## Naming Conventions
- Class names: PascalCase.
- Methods/variables/properties: camelCase.
- Interface suffix: `Interface`.
- Data transfer objects: `*DTO`.
- Use cases: `*UseCase`.
- Controllers: `*Controller`.
- Commands/Queries: `*Command`, `*Query`.
- Handlers: `*Handler`.
- Compilers/mappers: clear intent names (`*Compiler`, `*Mapper`).

## Error Handling Guidelines
- Throw domain/app-specific exceptions for business failures.
- In HTTP layer, map missing resources to 404.
- Avoid swallowing exceptions silently.
- Keep error messages actionable and specific.
- Prefer early return/guard clauses for invalid state.

## Commit Message Rules
- Commit messages are reviewed by the technical team.
- Use Conventional Commits format (`type: subject`).
- If a change belongs to a specific module, include the module name as a scope:
  `type(module): subject`.
- If a change is cross-cutting or global, omit the scope.
- Use infinitive/imperative English subjects (e.g. `refactor: extract homepage use case`).
- Keep commit subjects short and concise.
- Do not add long descriptions unless explicitly requested.

## Pre-Commit Checklist
- Changes are scoped to the task.
- No unrelated files are modified.
- Backend style check passes: `composer cs-check`.
- Frontend build passes for UI-impacting changes.
- New repositories/services are injected via interfaces.
