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

## Sanitizing rich content

HTML that came from a user — an editor, a post, a title an administrator typed — is stored raw
and cleaned on output. Take `Johncms\Security\HtmlSanitizerInterface` and return the result as
`Markup`:

```php
public function __construct(
    private HtmlSanitizerInterface $sanitizer,
) {
}

public function format(string $text): Markup
{
    return new Markup($this->sanitizer->sanitize($text), 'UTF-8');
}
```

The caller says what kind of content it has, never how to clean it — which library does the
work is an implementation detail of `HtmlSanitizer` and `HtmlPurifierFactory`, and no other
class mentions it:

| Policy | For | Allows |
| --- | --- | --- |
| `HtmlPolicy::RichContent` (default) | posts, comments, articles — what an editor produced | block elements, images, tables, embedded media, the classes listed in `config/autoload/htmlpurifier.global.php`, and bare URLs become links |
| `HtmlPolicy::Inline` | a short text inside a label or a sentence | inline formatting and links, nothing that breaks the line it lives on |
| `HtmlPolicy::InlineWithParagraphs` | a short text that stands on its own | the above plus `p` and `span` |

`toPlainText()` is the same content stripped to text — for previews, page titles and
breadcrumbs. It sanitizes before it strips the tags, so the body of a removed element cannot
resurface as visible text; `strip_tags()` on raw input does not.

A module with a kind of content none of these fits declares a policy of its own — it does not
edit the core, and it does not build a sanitizer of its own. Register a service implementing
`HtmlPolicyProviderInterface`; the container tags it, and the policy is asked for by name:

```php
public function policies(): iterable
{
    yield new HtmlPolicyDefinition(
        name: 'my-module.signature',      // `<module>.<content>` by convention
        elements: ['a' => ['href'], 'b' => [], 'br' => []],
        allowedClasses: ['signature'],
    );
}

$this->sanitizer->sanitize($text, 'my-module.signature');
```

The definition is an allow list and cannot widen what the sanitizer permits: elements that
carry behaviour (`script`, `iframe`, `form`, `style`, …), attributes starting with `on`, and
executable link schemes are refused by `HtmlPolicyDefinition` where they are written. Asking
for a name nobody declared throws `UnknownHtmlPolicyException` — there is no fallback policy,
because falling back would clean the content by rules never meant for it.

The three built-in policies stay in `HtmlPolicy` and `HtmlPurifierFactory`; they are reachable
only through the enum, never by name.

A URL is not markup. Validate its scheme against an allow list (`http`, `https`, or a local
path) instead of passing it through the sanitizer — see `UserMutators::getWebsiteAttribute()`.
