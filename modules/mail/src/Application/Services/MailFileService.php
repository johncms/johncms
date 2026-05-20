<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

final class MailFileService
{
    private const UPLOAD_MAIL_PATH = UPLOAD_PATH . 'mail/';

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
}
