# Images

Every picture the CMS stores goes through `Johncms\Image\ImageProcessorInterface`. Inject it —
there is no facade and no helper.

**Never reach for `Intervention\Image` directly.** The library is a detail of one class,
`InterventionImageProcessor`, and it is what makes an upgrade of it a one-file change instead of
a sweep across nine call sites. The rule is the same one `HtmlSanitizerInterface` follows for
HTMLPurifier.

## Storing an upload

```php
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;

final readonly class UploadAvatarUseCase
{
    public function __construct(private ImageProcessorInterface $imageProcessor) {}

    public function execute(int $userId, UploadedFileDTO $file): void
    {
        try {
            $this->imageProcessor->saveScaledDown($file->tmpPath, UPLOAD_PATH . 'users/avatar/' . $userId . '.png', 150, 150);
        } catch (ImageProcessingException $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
```

The methods are named after what the site needs rather than after operations of the library.

Fitting within bounds:

| Method | What it writes |
| --- | --- |
| `saveScaledDown($source, $target, ?$width, ?$height, $quality)` | a copy that fits within the bounds, aspect ratio kept, **never enlarged**; a null side is unconstrained, so a width alone scales by width |
| `saveConverted($source, $target, $quality)` | the same picture at its original size, re-encoded |

Arriving at an exact size — they differ in what they give up for it:

| Method | What it writes | Gives up |
| --- | --- | --- |
| `saveCropped($source, $target, $width, $height, $position, $quality)` | fills the frame, cuts off what sticks out | the edges |
| `savePadded($source, $target, $width, $height, $background, $quality)` | the whole picture, the rest filled with the background | margins on two sides |
| `saveBlurredThumbnail($source, $target, $width, $height, $quality)` | the source blurred to fill the frame, the scaled-down source centered on top | nothing, but the backdrop is blurred |
| `saveStretched($source, $target, $width, $height, $quality)` | pulls the picture to the frame | **the proportions** |

And `saveWatermarked($source, $target, $watermark, $position, $opacity, $offset, $quality)` lays
a second picture over the first.

`$position` is `Johncms\Image\ImagePosition`, the nine-way enum of the CMS —
`InterventionImageProcessor::alignment()` maps it to the one of the library, case by case. Do not
let `Intervention\Image\Alignment` into a signature. `$background` takes any color string the
driver understands, or `ImageProcessorInterface::TRANSPARENT`.

`saveStretched()` is the only method that does not keep the proportions, and the name is the
warning. When a caller reaches for it, check that the source really is of the proportions asked
for — otherwise the intention was `saveCropped()` or `savePadded()`.

Both arguments are paths, because that is what every caller has: an upload lands on disk
(`UploadedFileDTO::$tmpPath`) and the result belongs on disk too.

The **output format comes from the extension of the target path**. Storing an upload under a
fixed extension therefore means `saveConverted()`, not `copy()` — a copy would put JPEG bytes in
a file named `.png`.

## Errors

Everything the processor cannot do arrives as `Johncms\Image\ImageProcessingException`: a broken
file, an unsupported format, an unwritable target. Catch that and nothing wider — `catch
(Exception)` around image work swallows genuine bugs along with the upload that failed.

## Previews

A preview is generated once and kept on disk. `Johncms\Image\ThumbnailGenerator` returns the path
of the cached file, building it only when it is missing or older than its source:

```php
$preview = $this->thumbnails->blurredBackdrop($path, 220, 300);   // a tile, always JPEG
$preview = $this->thumbnails->scaledDown($path, 100, 100);        // keeps the source format
```

The cache lives in `data/cache/thumbnails`, outside the document root **on purpose**: a preview is
served by a controller, which is what decides whether this visitor may see the picture at all. It
is removed by `cache:clear` along with everything else; nothing extra to wire up.

Serve the file with `Johncms\Http\CachedImageResponse`, which carries the headers a
`BinaryFileResponse` would otherwise only get in `Response::prepare()` — the kernel never calls
it (see `ResponseNormalizer`):

```php
$response = new CachedImageResponse($preview);
$response->isNotModified($request);   // turns a conditional request into a 304

return $response;
```

**Address a picture by the id of the row that owns it, never by a path from the request.** Both
preview controllers take an id and resolve the path themselves; the scripts they replaced took
the path from the query string, and one of them could be talked into reading any image on the
disk with `../`. Where a name is unavoidable (a screenshot inside a directory), run it through
`basename()` *and* check the resolved `realpath()` still starts with the directory it must be in.

## Traps of the library

Relevant when editing `InterventionImageProcessor`, and only there:

* **Quality must be a named argument.** `save($path, 100)` is silently ignored — the options are
  matched against the parameter names of the encoder that was picked, so a positional one is
  dropped and the picture is written at the default quality. Always `save($path, quality: $q)`.
* **Modifiers change the image in place.** Building two variants from one source means `clone`
  (deep, and cheaper than decoding the file twice), not calling a second modifier on the same
  object.
* **`insert()` counts opacity the other way round**, as a fraction of full opacity where `1.0`
  is opaque — the CMS takes 0-100 and divides. Out-of-range values raise an exception of the
  library, so the contract checks the range itself and reports it as an
  `ImageProcessingException`.
* **The string `"transparent"` is gone in v4.** `savePadded()` recognises
  `ImageProcessorInterface::TRANSPARENT` and passes `Color::transparent()` on, so callers never
  meet the change.
* Decoding options are set once, in `manager()`: `autoOrientation` (needs `ext-exif`, degrades to
  no rotation without it), `decodeAnimation: false`, `strip: true` — the last one keeps the GPS
  coordinates of a phone photo out of a public file.

## Tests

`tests/Unit/Image/InterventionImageProcessorTest.php` asserts on the **result** — its size, its
format, the color of a pixel — never on calls made to the library. That is what makes it useful
across a major upgrade of the dependency. Sources are generated with GD in `setUp()`, so there
are no fixture files to keep.

Cover every method of the contract, including the ones no module calls yet: the interface
promises them to module authors, and nothing else would notice them breaking.

## Adding an operation

Add a method to `ImageProcessorInterface`, named after the intention, and implement it in
`InterventionImageProcessor`. Do **not** widen the contract into a mirror of the library's API
(`resize()`, `crop()`, `blur()`): an interface that repeats what it wraps buys nothing and leaks
the library back into every caller.
