# Output Escaping & Input Handling

Security principle: **escape on output, not on input**.

* Accept user input in its original form at the boundary (request/DTO), without HTML escaping.
* Do not use `htmlspecialchars()` / `$this->e()` while saving data to DB.
* Perform validation on input (length, required fields, format, allowed values), but keep original text.
* Escape only at render time and in the correct context:
  * HTML text node → `$this->e(...)`
  * HTML attribute (`href`, `title`, `value`, etc.) → `$this->e(...)`
  * URLs from user data → validate/allowlist scheme (`http` / `https` or local path) before output
  * JSON output → use `json_encode`, do not build JSON manually
* For rich content (BBCode/HTML), apply a dedicated sanitizer/allowlist before rendering.
* Avoid double-escaping: data should be escaped exactly once, at the final output boundary.
* Repositories/use cases/controllers must not mix persistence with presentation escaping.
