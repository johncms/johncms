# Self-Review Protocol

Run this **only when the user explicitly asks for a review** (in Claude Code: `/review-self`,
or a direct request to run one of the reviewers). It is not part of finishing a task.

After an ordinary implementation task an agent runs the deterministic gate
(`sh .agents/scripts/verify.sh`) and nothing else. The checklists below are expensive — a
full pass costs several times more tokens than the implementation — so spending them is the
user's call, not the agent's.

The checklists in this directory are tool-agnostic — any agent reads the same files.
Tool-specific wrappers (for Claude Code: `.claude/commands/review-self.md` and
`.claude/agents/reviewer-*.md`) only orchestrate; the rules live here.

## Scope

Review **only the changes of the current task**, never the whole repository:

```bash
git status --porcelain          # uncommitted work
git diff                        # unstaged changes
git diff --cached               # staged changes
git diff --stat 9.x...HEAD      # commits made on the current branch
```

Everything outside that change set is out of scope. Pre-existing violations in untouched
code are not findings — mention them at most as a one-line note.

## Order

1. **Deterministic gate first** — run `sh .agents/scripts/verify.sh`.
   If it fails, fix it before starting the checklists; a failing style check or test makes
   every later finding unreliable. Style violations are auto-fixable:
   `docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-fix`.
2. **Checklists** — run the relevant reviewers (see below).
3. **Fix** — apply fixes for `BLOCKER` and `MAJOR` findings.
4. **Re-run the gate once** to confirm nothing broke.

## Reviewers

| Reviewer | Checklist | Run when the diff touches |
| --- | --- | --- |
| Architecture | `.agents/review/architecture.md` | any PHP under `modules/`, `system/src/` |
| Security | `.agents/review/security.md` | any PHP or `.twig` file |
| PHP quality | `.agents/review/php-quality.md` | any PHP file |
| Localization | `.agents/review/localization.md` | `*.po`, `*.pot`, `*.lng.php`, `translate.xml*`, `crowdin.yml`, or new/changed `__()` / `d__()` / `n__()` / `dn__()` strings |

Skip a reviewer whose trigger does not match the diff. Do not run all four out of habit —
an irrelevant reviewer produces noise, not signal.

In Claude Code the four reviewers are subagents and are launched **in parallel in a single
message**. A tool without subagents works through the applicable checklists sequentially in
one session — complete one pass fully before starting the next.

## Finding Format

One line per finding:

```
SEVERITY | ./path/to/file.php:42 | what rule is violated | rule source
```

Example:

```
BLOCKER | ./modules/forum/src/Infrastructure/TopicRepository.php:57 | access check inside repository | .agents/architecture.md — Repository Rules
MAJOR   | ./modules/forum/templates/public/topic.twig:18 | user data printed with |raw | .agents/escaping.md
```

Always cite the rule source. A finding that cannot be traced to a rule in `.agents/` or
`AGENTS.md` is an opinion — state it as `MINOR` and mark it as such.

## Severity

* `BLOCKER` — bug, security hole, layer violation, broken build/tests. Must be fixed.
* `MAJOR` — clear rule violation with no correctness impact. Fix unless the fix would
  expand the task scope into unrelated code.
* `MINOR` — style preference, possible improvement. Report it; do not apply it silently.

## Rules for Reviewers

* Reviewers are **read-only**: they report, they do not edit. The implementing agent
  applies the fixes. A reviewer that patches its own finding hides the problem instead of
  surfacing it.
* No praise, no summaries of what the code does. Findings only, or the literal line
  `No findings.`
* Do not invent rules. If the codebase already does something a certain way consistently,
  that is the convention — see `.agents/architecture.md`: do not introduce new patterns.

## Stop Rule

At most **two** review → fix → re-review cycles. Anything still open after that goes to the
user as an explicit list — do not keep iterating. Report honestly which findings were fixed,
which were skipped and why, and any gate step that did not pass.
