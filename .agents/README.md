# Agent Tooling

This repository ships instructions for AI coding agents so that any contributor gets the
same rules and the same review quality.

## How it is organised

**`AGENTS.md`** in the repository root is the entry point. Most agent tools read it
automatically; `CLAUDE.md` is a symlink to it for Claude Code.

**`.agents/`** holds the actual rules — plain Markdown, not tied to any tool, read on demand:

| File | Topic |
| --- | --- |
| `architecture.md` | module layers, dependency rules, repository rules |
| `access-guard.md` | guard → context → action, exception mapping |
| `escaping.md` | user input handling, output escaping, sanitizing user HTML |
| `localization.md` | translations, `.po`/`.pot`/`.lng.php`, `__()` strings |
| `pagination.md` | paginated lists, page titles / meta description |
| `caching.md` | caching values, cache tags and invalidation |
| `content-pipeline.md` | rendering a user's text: media embeds, smilies, adding a step |
| `images.md` | processing pictures: resizing, thumbnails, cached previews |
| `storage.md` | storing files: disks, the file registry, public and private disks |
| `new-module.md` | creating a new module |
| `templates.md` | Twig templates: namespaces, environments, components, traps |
| `validation.md` | form validation: rules, the null policy, messages, adding a rule |
| `review/` | self-review protocol and checklists, run on demand (see below) |
| `scripts/verify.sh` | the deterministic gate: `cs-check`, `phpstan`, `test` |

**`.claude/`** contains only thin wrappers that declare the reviewers and the
`/review-self` command in Claude Code's format and then point at `.agents/`. No rule is
written twice — if a wrapper starts containing rules instead of references, that is a bug.

## Self-review

After finishing an implementation, agents run only the deterministic gate
(`sh .agents/scripts/verify.sh`). The review checklists in `.agents/review/` are run **on
demand**, when a developer asks for a review — they are not triggered automatically, because
a full pass costs several times more tokens than the implementation itself. The protocol —
scope, finding format, severity levels, stop rule — lives in `.agents/review/README.md`.

Reviewers are read-only by design: they report findings, the implementing agent applies the
fixes. A reviewer that patches its own finding hides the problem instead of surfacing it.

Four reviewers exist: architecture, security, PHP quality, and localization. Each maps to a
checklist of the same name in `.agents/review/`.

In Claude Code, run it with `/review-self`. You can also run the gate on its own at any
time:

```bash
sh .agents/scripts/verify.sh
```

## Using a different tool

Only Claude Code has ready-made wrappers, but nothing here is Claude-specific. Point your
agent at `AGENTS.md` and `.agents/review/README.md` — that is enough to work with the same
rules and produce the same review, whether it supports subagents or walks the checklists
sequentially.

Adding wrappers for another tool (Codex CLI, opencode, Cursor, …) is welcome: keep them
thin and keep the rules in `.agents/`.

## Personal settings

Local, per-developer files (`.claude/settings.local.json`, scratch notes, runtime state) are
git-ignored. Only the shared reviewers and commands are versioned — see `.gitignore`.
