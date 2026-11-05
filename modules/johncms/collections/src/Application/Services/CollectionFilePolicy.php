<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;

final readonly class CollectionFilePolicy
{
    public const int DEFAULT_MAX_BYTES = 10485760;
    public const array TYPES = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'video/mp4' => 'mp4',
    ];

    public function __construct(private FileStore $files)
    {
    }

    /** @param array<string, mixed> $settings */
    public function assertAllowed(string $code, int $size, string $mime, array $settings): void
    {
        if (! isset(self::TYPES[$mime]) || $size < 0 || $size > ($settings['max_file_bytes'] ?? self::DEFAULT_MAX_BYTES) || ! in_array($mime, $settings['allowed_mime_types'] ?? array_keys(self::TYPES), true)) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The file exceeds this field size limit or its content type is not allowed.')]]);
        }
    }

    /** @param array<string, mixed> $settings */
    public function assertStored(int $id, string $code, array $settings): void
    {
        try {
            $stream = $this->files->openStream($id);
        } catch (FileStoreException $exception) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The registered file is unavailable. Restore it or upload another file.')]], $exception);
        }
        if ($stream === null) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The registered file is unavailable. Restore it or upload another file.')]]);
        }
        try {
            $this->assertAllowed($code, $stream->size, $stream->mimeType, $settings);
        } finally {
            fclose($stream->stream);
        }
    }
}
