# Storage & stored files

Two things, and they are not the same one:

* **`Johncms\Storage\StorageInterface`** — a disk. Bytes at a path. Knows nothing about the
  database.
* **`Johncms\Files\FileStore`** — files the CMS keeps track of: written to a disk **and**
  registered in the `files` table. This is what modules use.

**Never reach for `League\Flysystem` directly.** The library is a detail of one class,
`FlysystemStorage`, which is what makes an upgrade of it a one-file change. Same rule as
`ImageProcessorInterface` for Intervention and `HtmlSanitizerInterface` for HTMLPurifier.

## Storing an upload

The registry is the only correct way to put an attachment somewhere: it writes the file and the
row together, and undoes the file if the row fails.

```php
use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Http\UploadedFileMapper;

final readonly class UploadFileController
{
    public function __construct(
        private FileStore $files,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $upload = $request->files->get('upload');
        if (! $upload instanceof UploadedFile) {
            return new JsonResponse(['error' => ['message' => __('Wrong data')]]);
        }

        try {
            $file = $this->files->storeUpload($this->uploadedFileMapper->fromUploadedFile($upload), 'guestbook');
        } catch (FileStoreException $exception) {
            return new JsonResponse(['error' => ['message' => $exception->getMessage()]], 500);
        }

        return new JsonResponse(['id' => $file->id, 'name' => $file->name, 'url' => $file->url]);
    }
}
```

The upload arrives as `UploadedFileDTO` — map it in the controller, never hand `Request` to a
service (`.agents/architecture.md`, "HTTP types stay in the HTTP layer").

| Method | Source of the file |
| --- | --- |
| `storeUpload($upload, $directory, $disk = null)` | one `UploadedFileDTO` |
| `storeUploads($uploads, $directory, $disk = null)` | several; the ones the browser failed to send are skipped |
| `storeLocalFile($path, $directory, $name = null, $disk = null)` | a file already on this server: an import, or what `ImageProcessorInterface` just wrote |
| `storeContents($contents, $name, $directory, $disk = null)` | something the CMS generated |

All four return `StoredFileDTO` (`id`, `name`, `size`, `url`) and throw `FileStoreException`.

Reading and removing:

| Method | What it does |
| --- | --- |
| `find($id)` / `getByIds($ids)` | `StoredFileDTO`, or null / a list |
| `openStream($id)` | `StoredFileStream` for serving the file yourself |
| `delete($id)` / `deleteMany($ids)` | removes the row **and** the file |
| `filterIdsInDirectory($ids, $directory)` | of the given ids, the ones stored under that directory |

`deleteMany()` ignores anything in the array that is not an identifier, because attachments come
out of JSON columns. Both deletes are **idempotent**: deleting what is not there is not an error,
so no caller needs a `try/catch` around them.

## What happens when half of it fails

Decided in `FileStore`, and the two halves are ordered on purpose:

* **Storing** writes the file first; if the row cannot be inserted, the file just written is
  removed again. An identical file that was already there is left alone — it belongs to the rows
  registered before.
* **Deleting** removes the row first; if the disk then fails, it is **logged, not thrown**. A row
  pointing at nothing means broken links on pages; a file nobody points at costs bytes.

`FileStore` does not join a transaction of the caller. A file stored inside a rolled-back
`Capsule::transaction()` stays on the disk — in this CMS an attachment exists before the post
does anyway, which is what `CleanupOrphanForumFilesUseCase` is for.

## Disks

Configured in `config/autoload/filesystem.*.php`; a disk that is not listed, or a driver that is
not supported, is refused where the settings are read.

```php
'filesystem' => [
    'default' => 'local',
    'disks'   => [
        'local' => ['driver' => 'local', 'root' => UPLOAD_PATH, 'url' => '/upload', 'visibility' => 'public'],
    ],
],
```

**Injecting a disk:** take `StorageInterface` when the disk is known at wiring time. Reach for
`StorageRegistryInterface::disk($name)` **only** where the name is learned at runtime — a row of
`files` says which disk its file is on. Asking a registry for a fixed name is a service locator
in disguise and hides the dependency from whoever reads the constructor.

Operations: `store`, `storeStream`, `storeFile`, `read`, `readStream`, `exists`, `delete`,
`size`, `mimeType`, `url`, `withLocalCopy`. Everything the disk cannot do arrives as
`StorageException` — catch that, never a flysystem type.

### Public and private disks

A disk with a `url` is public: the web server hands its files out, and `StoredFileDTO::url` is an
address under it. A disk with an empty `url` is private — put its root outside `public/` — and
its files are streamed by the `/file/{id}` route instead. Modules need no change either way: the
URL they render switches on its own.

Visibility (`public`/`private`) is a property of the **disk**, not of a single file, and it is
applied on every write: the local adapter otherwise leaves the mode to umask, and a strict umask
produces files the web server cannot read.

### Pictures, getID3, and anything else that needs a real path

`ImageProcessorInterface` and `FileInfo` work with paths, and a file on a remote disk has none.
`withLocalCopy()` bridges that:

```php
$disk->withLocalCopy($path, function (string $localPath) use ($processor): void {
    $processor->saveScaledDown($localPath, CACHE_PATH . 'preview.jpg', 200, 200);
});
```

A local disk passes the file itself and copies nothing; any other disk downloads it to a
temporary file and removes that file afterwards, whether the handler returned or threw. The copy
keeps the extension of the source, because that is what the image processor takes the output
format from.

## Legacy paths

Most of the CMS still builds paths by hand — `UPLOAD_PATH . 'users/album/' . $userId`. That code
predates the port and is being moved over module by module. When touching such a place: if the
files belong to a feature that is being reworked anyway, move it to a disk; otherwise leave it
and do not mix a half-migrated module into an unrelated change.

New code has no excuse: it goes through `FileStore` (registered files) or `StorageInterface`
(files with no row of their own, such as generated caches).
