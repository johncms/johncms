# PHP Quality Review Checklist

Rules come from `AGENTS.md` (PHP Style Rules, Naming Conventions, Error Handling).
Follow the protocol in `.agents/review/README.md`.

## Deterministic Gate

Report the outcome of `sh .agents/scripts/verify.sh` — `composer cs-check`, `composer phpstan`
and `composer test`. Every failure is a `BLOCKER`. Quote the failing output, do not summarize it
as "some tests fail".

PHPStan runs at level 5 against a baseline (`phpstan-baseline.neon`) that accepts the existing
legacy debt, so anything it reports was introduced by the change under review. A change that
*grows* the baseline needs a stated reason — silently regenerating it to make the gate green
is the same as disabling the check.

## Structure

* `declare(strict_types=1);` in every PHP file.
* Namespace matches the directory structure (PSR-4).
* One class per file, 4-space indentation.
* Class names `PascalCase`, methods and properties `camelCase`.
* Suffixes used correctly: `*Controller`, `*UseCase`, `*DTO`, `*RepositoryInterface`,
  `*Command`, `*Query`, `*Handler`, `*Mapper`, `*Compiler`.
* A new class with a scalar constructor argument (exception, DTO, value object) that lives under
  a directory registered with a wide `$services->load(...)` must be excluded there — otherwise
  autowiring cannot resolve the argument and **the whole container stops compiling**, taking
  every page down. `cs-check`, PHPStan and the unit tests all stay green on that failure;
  `tests/Unit/Container/ContainerCompilationTest.php` is what catches it.

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
* A broad `catch (\Throwable)` / `catch (\Exception)` must not wrap code that can perform
  HTTP control flow — `redirect()`, `pageNotFound()`, or anything reaching them. Those raise
  exceptions the kernel is meant to turn into a response, so swallowing one silently converts
  a redirect or a 404 into whatever the catch block returns. Either narrow the catch to the
  types the `try` can actually throw, or move the control-flow call outside the `try`.

## Reuse

* No helper or logic duplicated — an existing implementation elsewhere in the codebase
  should have been reused. Grep before accepting a new helper as justified.
* All comments, PHPDoc, and inline notes are written in English.
