<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Library\Infrastructure\Storage\LibraryCoverSize;
use Johncms\Modules\Library\Infrastructure\Storage\LibraryCoverStorage;
use PDO;

class Utils
{
    public static function redir404(): void
    {
        $config = config('johncms');
        redirect($config['homeurl'] . '/?err');
    }

    public static function position(string $text, string $chr): int
    {
        $result = mb_strpos($text, $chr);

        return $result !== false ? $result : 100;
    }

    public static function cmprang(array $a, array $b): int
    {
        return ($a['rang'] <=> $b['rang']);
    }

    public static function cmpalpha(array $a, array $b): int
    {
        return ($a['name'] <=> $b['name']);
    }

    public static function libCounter(int $id, int $dir): int
    {
        $db = di(PDO::class);

        return $db->query(
            'SELECT COUNT(*) FROM `' . ($dir ? 'library_cats' : 'library_texts') . '` WHERE '
            . ($dir ? '`parent` = ' . $id : '`cat_id` = ' . $id . ' AND `premod` = 1')
        )->fetchColumn();
    }

    public static function imageUpload(int $id, mixed $image): void
    {
        $smallSize = 32;
        $bigSize   = 240;

        $source = $image->getPathname();
        $processor = di(ImageProcessorInterface::class);
        $covers = di(LibraryCoverStorage::class);

        // The original keeps its size and is only re-encoded to PNG; the two smaller copies are
        // scaled by width, with the height following from the proportions of the picture.
        $covers->store($id, LibraryCoverSize::Original, static function (string $target) use ($processor, $source): void {
            $processor->saveConverted($source, $target);
        });
        $covers->store($id, LibraryCoverSize::Big, static function (string $target) use ($processor, $source, $bigSize): void {
            $processor->saveScaledDown($source, $target, $bigSize);
        });
        $covers->store($id, LibraryCoverSize::Small, static function (string $target) use ($processor, $source, $smallSize): void {
            $processor->saveScaledDown($source, $target, $smallSize);
        });
    }

    public static function replaceKeywords(string $search, string $text): string
    {
        $search = str_replace('*', '', $search);

        return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $text);
    }

    public static function unlinkImages(int $id): void
    {
        di(LibraryCoverStorage::class)->delete($id);
    }
}
