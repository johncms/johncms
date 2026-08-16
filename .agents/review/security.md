# Security Review Checklist

Read `.agents/escaping.md` first — it defines the escaping contract. Follow the protocol in
`.agents/review/README.md`.

## Escape on Output, Not on Input

* No `htmlspecialchars()` applied to data on its way **into** the database, and no
  `SpecialChars` cast on a model — a cast that escapes on read escapes the value twice.
* Input is validated (length, required, format, allowed values) but stored in its original
  form.
* Escaping happens exactly once, at the final output boundary — check the rendered page for
  `&amp;lt;`, and the data for HTML escaping baked in (a URL carrying `&amp;`, for instance).

## Templates

Twig escapes what it prints, so the review is about the places that opt out of it:

* Every `|raw` is justified: the value is markup by contract, and there is no source to fix.
  Repeated `|raw` on values from one source means the source should return `Twig\Markup`.
* Every service returning `Twig\Markup` sanitizes what it wraps — user HTML goes through
  `HtmlSanitizerInterface` before it becomes `Markup`.
* The policy fits the content: `HtmlPolicy::RichContent` for what an editor produced,
  `Inline` / `InlineWithParagraphs` for a short text that must not carry block markup. A
  caller that builds an `HTMLPurifier` (or any other sanitizer) of its own is a finding — a
  module declares its own policy through `HtmlPolicyProviderInterface`, and the core adds one
  to `HtmlPolicy` and `HtmlPurifierFactory`.
* URLs built from user data have their scheme validated/allowlisted (`http`, `https`, or a
  local path) before output. A URL is not markup: an HTML sanitizer is not a substitute for
  the scheme check (see `UserMutators::getWebsiteAttribute()`).
* No user data interpolated into inline `<script>` or event-handler attributes;
  `|e('js')` for a JS literal, `|json_encode|raw` for a structure.
* An unquoted attribute uses `|e('html_attr')`.
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
