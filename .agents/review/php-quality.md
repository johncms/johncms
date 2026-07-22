# PHP Quality Review Checklist

Rules come from `AGENTS.md` (PHP Style Rules, Naming Conventions, Error Handling, Legacy
Code Rules). Follow the protocol in `.agents/review/README.md`.

## Deterministic Gate

Report the outcome of `sh .agents/scripts/verify.sh` — `composer cs-check` and
`composer test`. Every failure is a `BLOCKER`. Quote the failing output, do not summarize it
as "some tests fail".

## Structure

* `declare(strict_types=1);` in every PHP file.
* Namespace matches the directory structure (PSR-4).
* One class per file, 4-space indentation.
* Class names `PascalCase`, methods and properties `camelCase`.
* Suffixes used correctly: `*Controller`, `*UseCase`, `*DTO`, `*RepositoryInterface`,
  `*Command`, `*Query`, `*Handler`, `*Mapper`, `*Compiler`.

## Types

* Typed properties, arguments, and return types everywhere.
* PHPDoc only where a native type cannot express the shape (e.g. `array<int, User>`) —
  not as a restatement of the signature.
* No redundant scalar casts (`(int)`, `(string)`, `(bool)`) where the type is already
  guaranteed by a signature or framework contract.

## Class Design

* Constructor injection with property promotion.
* New classes are `final`.
* `readonly` used for new immutable service/DTO classes only — never introduced into legacy
  classes during refactoring.
* Inheritance structure of existing classes unchanged.
* Methods stay focused and reasonably short.
* HTTP mapping logic lives in controllers only.

## Error Handling

* Domain/application-specific exceptions for business failures — no bare `\Exception`.
* No silently swallowed exceptions (empty `catch`, `catch` that only logs and continues
  where the caller needs to know).
* Guard clauses and early returns over nested conditionals.
* Error messages are specific and actionable.
* Unused caught exception variables are omitted: `catch (EditVoteWrongDataException)`.

## Legacy & Reuse

* No new functionality added to `system/src-legacy`; legacy touched only where the
  refactoring required it, with extracted logic moved to `system/src`.
* No helper or logic duplicated — an existing implementation elsewhere in the codebase
  should have been reused. Grep before accepting a new helper as justified.
* All comments, PHPDoc, and inline notes are written in English.
