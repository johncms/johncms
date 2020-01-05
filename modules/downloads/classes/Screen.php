<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Downloads;

use Johncms\FileInfo;

class Screen
{
    /**
     * Метод получает массив скриншотов для файла
     *
     * @param int $file_id
     * @return array
     */
    public static function getScreens(int $file_id): array
    {
        $screens = [];
        $dir = DOWNLOADS_SCR . $file_id;
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (! in_array($file, ['.', '..', 'index.php', 'name.dat'], true)) {
                    $file = new FileInfo($dir . '/' . $file);
                    $url = $file->getPublicPath();

                    $screens[] = [
                        'path'      => $file->getRealPath(),
                        'url'       => $url,
                        'file_name' => $file->getBasename(),
                        'preview'   => '/assets/modules/downloads/preview.php?type=2&amp;img=' . rawurlencode($url),
                    ];
                }
            }
        }
        return $screens;
    }
}
