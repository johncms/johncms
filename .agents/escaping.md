# Output Escaping & Input Handling

Security principle: **escape on output, not on input**.

* Accept user input in its original form at the boundary (request/DTO), without HTML escaping.
* Do not use `htmlspecialchars()` while saving data to DB, and do not add a `SpecialChars` cast
  to a model — a cast that escapes on read makes the value escaped twice on the page.
* Perform validation on input (length, required fields, format, allowed values), but keep original text.
* Repositories/use cases/controllers must not mix persistence with presentation escaping.

## In templates

Twig escapes everything it prints (`autoescape: 'html'`), so a plain `{{ value }}` is already
safe in a text node and in an attribute. What needs a decision is the opposite case — a value
that **is** markup:

* A service that returns finished HTML by contract returns `Twig\Markup`, and the template
  prints it with `{{ x }}`. That is the rule for pagination, sanitized post texts, rendered
  smilies, formatted places, notification bodies.
* `|raw` is for the odd case where there is no source to fix — most often a translated string
  that carries a tag (`__('… <br> …')|raw`). Its presence is a signal to the reviewer that the
  author decided this value is markup; if it appears twice for values from the same source,
  fix the source instead.
* A string literal is safe by definition, so `{{ '<b>x</b>' }}` prints as markup. Add `|e` when
  a literal has to be shown as text (an example of code in a hint, for instance).

Contexts autoescape does not cover:

* inside `<script>`: `{{ x|e('js') }}` for a JS literal, `{{ data|json_encode|raw }}` for a
  structure;
* an unquoted attribute: `{{ x|e('html_attr') }}`;
* a URL segment built from user data: `{{ x|e('url') }}`, and validate/allowlist the scheme
  (`http`, `https`, or a local path) before printing a whole URL that came from a user.

Do not put HTML escaping into the data itself. A URL built as `'&amp;mod=reply'` is escaped a
second time on output and arrives broken; build it with `&` and let the template escape it.

For rich content (HTML from an editor), apply the sanitizer before rendering and return the
result as `Markup`.
