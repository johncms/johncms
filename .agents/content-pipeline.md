# The Content Pipeline

Everything a user wrote — a post, a comment, an article, a message — reaches the page through
one service: `Johncms\Content\ContentRendererInterface`. It cleans the HTML, embeds the media,
renders the smilies, and hands back `Twig\Markup`.

```php
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;

public function __construct(private ContentRendererInterface $content) {}

public function format(ForumMessage $message): Markup
{
    return $this->content->render(
        $message->text,
        new ContentContext(adminSmilies: $this->staffTitles->isStaff((int) $message->user_id))
    );
}
```

| Method | Gives |
| --- | --- |
| `render()` | the markup, always — an empty text renders as empty markup |
| `renderOrNull()` | the same, and `null` when nothing is left to show |
| `toPlainText()` | the rendered content stripped to text: previews, titles, notifications |

`ContentContext` says how the text may be cleaned (`policy`, an `HtmlPolicy` or the name a
module declared — see `escaping.md`) and whether the smilies reserved for the staff render
(`adminSmilies`).

Do not call `HtmlSanitizerInterface` and `SmiliesRendererInterface` in sequence by hand: that
chain is what this service replaced, and every place that repeated it parsed the same text
three or four times over. The sanitizer on its own is still right for content that is **not**
going onto a page as markup — `toPlainText()` for a `<title>`, for instance.

## Markup is an object

`Markup` is truthy however empty it is, so `{% if text %}` is always true. A source that can
have nothing to show returns `renderOrNull()`. A payload that is serialized rather than printed
— a JSON response — takes `(string)` around the result, since `json_encode()` writes an object
with no public properties out as `{}`.

## Adding a step

A step of the pipeline is a service implementing `ContentTransformerInterface`. The container
tags it by the interface alone, so a module registers it the way it registers anything else:

```php
final readonly class NofollowTransformer implements ContentTransformerInterface
{
    public function priority(): int
    {
        return 20;
    }

    public function transform(Dom\HTMLDocument $document, ContentContext $context): void
    {
        foreach ($document->querySelectorAll('a[href^="http"]') as $link) {
            $link->setAttribute('rel', 'nofollow noopener');
        }
    }
}
```

Rules:

* **The document is parsed once and shared.** A transformer edits the tree it is given and
  returns nothing. It never parses, never serializes, and never touches the HTML as a string.
* **Markup goes in through the DOM.** `setAttribute()` and `createElement()` escape their
  values; `HtmlFragment::nodes()` turns a piece of HTML into nodes for anything larger. A tag
  concatenated out of strings is how an alt text carrying a quote used to break out of its
  attribute.
* **Priority is the order**, higher first. The built-in steps are the media (100), the image
  links (50) and the smilies (-100, over the finished text).
* **A step runs on every text of the site.** Keep it to the selector it needs.

The built-in steps live in `system/src/Content/Transformer/`.

## Adding a media site

The editor stores what the author pasted — `<oembed url="…">` — and nothing else: the markup of
a player is not content, and freezing it into the database would freeze the look of every old
post. `OembedTransformer` walks those elements and asks the providers.

A provider matches a URL and names a template. It never touches the document:

```php
final readonly class RutubeEmbedProvider implements EmbedProviderInterface
{
    public function priority(): int
    {
        return 0;
    }

    public function embed(string $url): ?EmbeddedMedia
    {
        $parts = parse_url($url);
        if (($parts['host'] ?? '') !== 'rutube.ru') {
            return null;   // not ours; the next provider is asked
        }

        // Match the id, never pass it through: it ends up in the address of an iframe.
        if (preg_match('~^/video/([0-9a-f]{32})/~', $parts['path'] ?? '', $m) !== 1) {
            return null;
        }

        return new EmbeddedMedia('@mymodule/embeds/rutube.twig', ['id' => $m[1]]);
    }
}
```

The template is a template like any other, so a theme overrides the look of a player by
mirroring its path (see `templates.md`). Core players live in
`themes/default/templates/content/embeds/`.

A URL no provider claims is not an error: the element stays in the text, so the link is not
lost and a provider added later picks the same posts up.

A provider of a higher priority is asked first — that is how a module replaces a built-in one
without the core knowing about it.

## The HTML5 parser

`Johncms\Content\Html\HtmlFragment` is the only place that parses. It is the HTML5 parser
`ext-dom` ships in PHP 8.4 (`Dom\HTMLDocument`), which is why the CMS carries no DOM library.

A post is a fragment, so it is parsed as the contents of `body` — the context the HTML5 rules
are written for — and only those contents are written back out. Do not parse content with
`DOMDocument`, and do not cut an `html/body` wrapper off a string by hand: that is what this
class exists to stop.

Note one rule of the DOM that bites: a document may hold only one element child, so a node has
to be replaced **inside** the body, never at the level of the document itself. Going through
`HtmlFragment` is what keeps that true.

## Verification

The pipeline is under `tests/Unit/Content/`. A transformer is tested by parsing a fragment,
running the transformer, and serializing it back — no container and no database:

```php
$fragment = new HtmlFragment();
$document = $fragment->parse('<p>text</p>');
(new MyTransformer())->transform($document, new ContentContext());

self::assertSame('<p>text</p>', $fragment->serialize($document));
```

`Tests\Support\Content\RecordingContentTransformer` is a step that changes nothing and writes
down that it ran, for tests about the pipeline rather than about one step.
