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
     * @param $a
     * @param $b
     * @return int
     */
    public static function cmpalpha(array $a, array $b): int
    {
        return ($a['name'] <=> $b['name']);
    }

    public static function libCounter(int $id, int $dir): string
    {
        $db = di(PDO::class);
        return $db->query('SELECT COUNT(*) FROM `' . ($dir ? 'library_cats' : 'library_texts') . '` WHERE '
                          . ($dir ? '`parent` = ' . $id : '`cat_id` = ' . $id))->fetchColumn()
            . ' ' . ($dir ? ' ' . _t('Sections') : ' ' . _t('Articles')) . ')';
    }

    public static function sectionsListAdminPanel(int $sectionId, int $sectionItemId, int $positionId, int $total): string
    {
        return '<div class="sub">'
            . ($positionId !== 1 ? '<a href="?do=dir&amp;id=' . $sectionId . '&amp;act=move&amp;moveset=up&amp;posid=' . $positionId . '">' . _t('Up')
                . '</a> | ' : '' . _t('Up') . ' | ')
            . ($positionId !== $total
                ? '<a href="?do=dir&amp;id=' . $sectionId . '&amp;act=move&amp;moveset=down&amp;posid=' . $positionId . '">' . _t('Down') . '</a>'
                : _t('Down'))
            . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $sectionItemId . '">' . _t('Edit') . '</a>'
            . ' | <a href="?act=del&amp;type=dir&amp;id=' . $sectionItemId . '">' . _t('Delete') . '</a></div>';
    }

    public static function sectionAdminPanel(int $id): string
    {
        return '<p><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
            . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a><br>'
            . '<a href="?act=mkdir&amp;id=' . $id . '">' . _t('Create') . '</a></p>';
    }
}
