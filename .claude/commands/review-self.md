---
description: Self-review the current change set with the architecture, security, PHP-quality and localization reviewers
---

Run the self-review protocol from `.agents/review/README.md` on the current change set.

## 1. Collect the change set

```bash
git status --porcelain
git diff --stat
git diff --cached --stat
git diff --stat 9.x...HEAD
```

If there are no changes, say so and stop.

## 2. Run the deterministic gate

```bash
sh .agents/scripts/verify.sh
```

If it fails, fix the failures first (`composer cs-fix` handles style automatically), then
re-run. Do not start the checklists on a red gate.

## 3. Pick the reviewers

Match the change set against the trigger table in `.agents/review/README.md`:

| Reviewer | Run when the diff touches |
| --- | --- |
| `reviewer-architecture` | PHP under `modules/`, `system/src/`, `system/src-legacy/` |
| `reviewer-security` | any PHP, `.phtml`, or template file |
| `reviewer-php-quality` | any PHP file |
| `reviewer-localization` | `*.po`, `*.pot`, `*.lng.php`, `translate.xml*`, `crowdin.yml`, or new/changed `__()` / `d__()` / `n__()` / `dn__()` strings |

Skip reviewers whose trigger does not match — an irrelevant reviewer produces noise.

## 4. Launch them in parallel

Launch every selected reviewer **in a single message with multiple Agent tool calls** so
they run concurrently. Give each one the list of changed files so it does not re-derive the
change set from scratch.

## 5. Consolidate

Merge the reports: deduplicate findings that several reviewers raised, sort by severity
(`BLOCKER` → `MAJOR` → `MINOR`), and present them in the protocol's line format with the
rule source intact.

## 6. Fix

Fix all `BLOCKER` findings and the `MAJOR` ones that do not expand the task scope into
unrelated code. Report `MINOR` findings to the user without applying them.

## 7. Confirm

Re-run `sh .agents/scripts/verify.sh` once. Then report:

* what was fixed,
* what was skipped and why,
* anything still open (per the stop rule, at most two review→fix cycles — do not keep
  iterating, hand the remainder to the user as a list).

Report the outcome honestly: if the gate did not pass, say so and include the output.
