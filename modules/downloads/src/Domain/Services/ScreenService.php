<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Services;

use Johncms\FileInfo;

final class ScreenService
{
    public static function getScreens(int $fileId): array
    {
        $screens = [];
        $dir = UPLOAD_PATH . 'downloads' . DS . 'screen' . DS . $fileId;
        if (! is_dir($dir)) {
            return $screens;
        }

        foreach (scandir($dir) as $file) {
            if (in_array($file, ['.', '..', 'index.php', 'name.dat'], true)) {
                continue;
            }
            $info = new FileInfo($dir . '/' . $file);
            $url = $info->getPublicPath();
            $screens[] = [
                'path'      => $info->getRealPath(),
                'url'       => $url,
                'file_name' => $info->getBasename(),
                'preview'   => '/downloads/preview/' . $fileId . '/' . rawurlencode($info->getBasename()),
            ];
        }

        return $screens;
    }
}
