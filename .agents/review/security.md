# Security Review Checklist

Read `.agents/escaping.md` first — it defines the escaping contract. Follow the protocol in
`.agents/review/README.md`.

## Escape on Output, Not on Input

* No `htmlspecialchars()` / `$this->e()` applied to data on its way **into** the database.
* Input is validated (length, required, format, allowed values) but stored in its original
  form.
* Escaping happens exactly once, at the final output boundary — check for double escaping
  (`&amp;lt;` in rendered output, `e()` applied to already-escaped data).

## Templates

* Every user-controlled value in a text node goes through `$this->e(...)`.
* Every user-controlled value in an attribute (`href`, `title`, `value`, `alt`, …) goes
  through `$this->e(...)`.
* URLs built from user data have their scheme validated/allowlisted (`http`, `https`, or a
  local path) before output.
* No user data interpolated into inline `<script>` or event-handler attributes.
* Rich content (BBCode/HTML) passes through the dedicated sanitizer/allowlist before render.
* JSON is produced with `json_encode`, never assembled by string concatenation.

## Data Access

* No user input concatenated into `whereRaw`, `selectRaw`, `orderByRaw`, or `DB::raw`.
  Bindings/parameters only.
* Sort columns and directions coming from the request are matched against an allowlist.
* Mass assignment: no `fill()` / `create()` straight from unfiltered request data.

## Authorization

* Every state-changing action (POST/command) has an access or ownership check that runs
  before the write — see `.agents/access-guard.md`.
* IDs from the request are not trusted as proof of ownership; ownership is verified against
  the current user.
* An action reachable from two routes is guarded on both.
* CSRF protection is present on new forms, consistent with existing forms in the module.

## File Uploads

* Extension/MIME type checked against an allowlist, size limited.
* Destination path is derived server-side; user-supplied names never build the path
  (no `../` traversal).
* Uploaded files are not served as executable content.

## Secrets & Logging

* No credentials, tokens, or API keys in code, templates, or committed config.
* Passwords, tokens, and session identifiers are not written to logs or error messages.
* Error messages shown to users do not leak stack traces, SQL, or filesystem paths.
