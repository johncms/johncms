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

use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
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

        /** @var ImageManager $image_manager */
        $image_manager = di(ImageManager::class);
        $img           = $image_manager->make($image->getPathname());
        $img->save(UPLOAD_PATH . 'library/images/orig/' . $id . '.png', 100, 'png');
        $img->resize(
            $bigSize,
            null,
            static function ($constraint) {
                /** @var $constraint Constraint */
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $img->save(UPLOAD_PATH . 'library/images/big/' . $id . '.png', 100, 'png');
        $img->resize(
            $smallSize,
            null,
            static function ($constraint) {
                /** @var $constraint Constraint */
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $img->save(UPLOAD_PATH . 'library/images/small/' . $id . '.png', 100, 'png');
    }

    public static function replaceKeywords(string $search, string $text): string
    {
        $search = str_replace('*', '', $search);

        return mb_strlen($search) < 3 ? $text : preg_replace('|(' . preg_quote($search, '/') . ')|siu', '<span style="background-color: #FFFF33">$1</span>', $text);
    }

    public static function unlinkImages(int $id): void
    {
        if (file_exists(UPLOAD_PATH . 'library/images/small/' . $id . '.png')) {
            @unlink(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
            @unlink(UPLOAD_PATH . 'library/images/orig/' . $id . '.png');
            @unlink(UPLOAD_PATH . 'library/images/small/' . $id . '.png');
        }
    }
}
