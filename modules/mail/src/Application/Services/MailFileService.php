<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Johncms\Http\UploadedFileDTO;
use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;

final readonly class MailFileService
{
    private const string DIRECTORY = 'mail';

    public const ALLOWED_EXTENSIONS = [
        'exe', 'msi',
        'jar', 'jad',
        'sis', 'sisx', 'apk',
        'txt', 'pdf', 'doc', 'docx', 'rtf', 'djvu', 'xls', 'xlsx',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', 'wmf',
        'zip', 'rar', '7z', 'tar', 'gz',
        '3gp', 'avi', 'flv', 'mpeg', 'mp4',
        'mp3', 'amr',
    ];

    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function deleteFile(string $fileName): bool
    {
        if (! $this->fileExists($fileName)) {
            return false;
        }

        try {
            $this->storage->delete($this->path($fileName));
        } catch (StorageException) {
            return false;
        }

        return true;
    }

    public function fileExists(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        return $this->storage->exists($this->path($fileName));
    }

    /**
     * Address the attachment is served at, or an empty string on a disk that is not public.
     */
    public function url(string $fileName): string
    {
        return $this->storage->url($this->path($fileName));
    }

    public function path(string $fileName): string
    {
        // The name is stored in a column and ends up in a path.
        return self::DIRECTORY . '/' . basename($fileName);
    }

    /**
     * Format a byte size into a human-readable string.
     */
    public function formatSize(int $size): string
    {
        if ($size >= 1073741824) {
            return round($size / 1073741824 * 100) / 100 . ' Gb';
        }

        if ($size >= 1048576) {
            return round($size / 1048576 * 100) / 100 . ' Mb';
        }

        if ($size >= 1024) {
            return round($size / 1024 * 100) / 100 . ' Kb';
        }

        return $size . ' b';
    }

    /**
     * Parse a client file name into a sanitized base name and extension.
     *
     * @return array{filename: string, fileext: string}
     */
    public function parseFileName(string $clientName): array
    {
        $ext = mb_strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        $dotPos = mb_strripos($clientName, '.');
        $body = $dotPos === false ? '' : mb_substr($clientName, 0, $dotPos);
        $filename = mb_strtolower(mb_substr(str_replace('.', '_', $body), 0, 38));

        return ['filename' => $filename, 'fileext' => $ext];
    }

    public function isAllowedExtension(string $ext): bool
    {
        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Return a non-colliding file name within the mail upload directory.
     */
    public function uniqueFileName(string $fileName): string
    {
        if ($this->fileExists($fileName)) {
            return time() . '_' . $fileName;
        }

        return $fileName;
    }

    public function storeUploadedFile(UploadedFileDTO $file, string $fileName): bool
    {
        try {
            // The mode comes from the configuration of the disk; the previous chmod(0666) here
            // made every mail attachment writable by anything running on the server.
            $this->storage->storeFile($this->path($fileName), $file->tmpPath);
        } catch (StorageException) {
            return false;
        }

        return true;
    }
}
