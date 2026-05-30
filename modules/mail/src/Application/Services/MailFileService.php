<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Psr\Http\Message\UploadedFileInterface;

final class MailFileService
{
    private const UPLOAD_MAIL_PATH = UPLOAD_PATH . 'mail/';

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

    public function deleteFile(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        $filePath = self::UPLOAD_MAIL_PATH . $fileName;
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return false;
    }

    public function fileExists(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        return file_exists(self::UPLOAD_MAIL_PATH . $fileName);
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

    public function storeUploadedFile(UploadedFileInterface $file, string $fileName): bool
    {
        $target = self::UPLOAD_MAIL_PATH . $fileName;

        try {
            $file->moveTo($target);
            @chmod($target, 0666);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
