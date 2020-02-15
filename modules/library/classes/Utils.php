<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Library;

use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use PDO;

/**
 * Статические методы помошники
 * Class Utils
 *
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Utils
{
    /**
     * редирект на 404
     */
    public static function redir404(): void
    {
        $config = di('config')['johncms'];
        ob_get_level() && ob_end_clean();
        header('Location: ' . $config['homeurl'] . '/?err');
        exit;
    }

    /**
     * Позиция символа в тексте
     *
     * @param string $text
     * @param string $chr
     * @return int
     */
    public static function position(string $text, string $chr): int
    {
        $result = mb_strpos($text, $chr);

        return $result !== false ? $result : 100;
    }

    /**
     * Сортировка по рейтингу
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function cmprang(array $a, array $b): int
    {
        return ($a['rang'] <=> $b['rang']);
    }

    /**
     * Сортировка по алфавиту
     *
     * @param $a
     * @param $b
     * @return int
     */
    public static function cmpalpha(array $a, array $b): int
    {
        return ($a['name'] <=> $b['name']);
    }

    /**
     * Счетчики для каталогов
     *
     * @param int $id
     * @param int $dir
     * @return int
     */
    public static function libCounter(int $id, int $dir): int
    {
        $db = di(PDO::class);
        return $db->query(
            'SELECT COUNT(*) FROM `' . ($dir ? 'library_cats' : 'library_texts') . '` WHERE '
            . ($dir ? '`parent` = ' . $id : '`cat_id` = ' . $id)
        )->fetchColumn();
    }

    public static function imageUpload(int $id, $image): void
    {
        $smallSize = 32;
        $bigSize = 240;

        /** @var ImageManager $image_manager */
        $image_manager = di(ImageManager::class);
        $img = $image_manager->make($image->getStream());
        // original
        $img->save(UPLOAD_PATH . 'library/images/orig/' . $id . '.png', 100, 'png');
        // big
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
        // small
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

    /**
     * Функция подсветки результатов запроса
     *
     * @param string $search
     * @param string $text
     * @return string
     */
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
