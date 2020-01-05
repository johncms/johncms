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

use Johncms;
use Johncms\NavChain;
use PDO;

/**
 * @package     mobiCMS
 * @link        http://mobicms.net
 * @copyright   Copyright (C) 2008-2011 mobiCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://mobicms.net/about
 */
class Download // phpcs:ignore
{
    private static $extensions =
        [
            'mp3'  => 8,
            'png'  => 5,
            'jpg'  => 5,
            'gif'  => 5,
            'rar'  => 5,
            'zip'  => 6,
            '3gp'  => 7,
            'mp4'  => 7,
            'txt'  => 4,
            'jar'  => 2,
            'sis'  => 1,
            'sisx' => 1,
            'thm'  => 10,
            'nth'  => 11,
        ];

    // Вывод файла в ЗЦ
    public static function displayFile($res_down = [], $rate = 0)
    {
        global $old;
        $file = $res_down;
        $format_file = pathinfo($res_down['name'], PATHINFO_EXTENSION);
        $icon_id = self::$extensions[$format_file] ?? 9;

        /** @var Johncms\System\View\Extension\Assets $assets */
        $assets = di(Johncms\System\View\Extension\Assets::class);

        /** @var Johncms\System\Users\User $systemUser */
        $systemUser = di(Johncms\System\Users\User::class);

        /** @var Johncms\System\Legacy\Tools $tools */
        $tools = di(Johncms\System\Legacy\Tools::class);

        $config = di('config')['johncms'];

        $file['icon'] = $assets->url('images/old/system/' . $icon_id . '.png');
        $file['detail_url'] = '?act=view&amp;id=' . $res_down['id'];
        $file['filtered_name'] = htmlspecialchars($res_down['rus_name']);

        $file['is_new'] = ($res_down['time'] > $old);

        $file['rating'] = [];
        if ($rate) {
            $file_rate = explode('|', $res_down['rate']);
            $file['rating']['plus'] = $file_rate[0];
            $file['rating']['minus'] = $file_rate[1];
        }

        $file['preview_text'] = '';
        if ($res_down['about']) {
            $about = $res_down['about'];
            if (mb_strlen($about) > 100) {
                $about = mb_substr($about, 0, 90) . '...';
            }
            $file['preview_text'] = $tools->checkout($about, 0, 1);
        }

        $file['comments_url'] = '';
        if ($config['mod_down_comm'] || $systemUser->rights >= 7) {
            $file['comments_url'] = '?act=comments&amp;id=' . $res_down['id'];
        }

        return $file;
    }

    // Форматирование размера файлов
    public static function displayFileSize($size)
    {
        if ($size >= 1073741824) {
            $size = round($size / 1073741824 * 100) / 100 . ' Gb';
        } elseif ($size >= 1048576) {
            $size = round($size / 1048576 * 100) / 100 . ' Mb';
        } elseif ($size >= 1024) {
            $size = round($size / 1024 * 100) / 100 . ' Kb';
        } else {
            $size .= ' b';
        }

        return $size;
    }

    // Обработка mp3 тегов
    public static function mp3tagsOut($name, $value = false)
    {
        if (! $value) {
            return htmlspecialchars(iconv('windows-1251', 'UTF-8', $name));
        }

        return iconv('UTF-8', 'windows-1251', $name);
    }

    // Вывод ссылок на файл
    public static function downloadLlink($array = []): array
    {
        global $old;

        /** @var Johncms\System\Legacy\Tools $tools */
        $tools = di(Johncms\System\Legacy\Tools::class);

        /** @var Johncms\System\View\Extension\Assets $assets */
        $assets = di(Johncms\System\View\Extension\Assets::class);

        $id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
        $morelink = isset($array['more']) ? '&amp;more=' . $array['more'] : '';
        $icon_id = self::$extensions[$array['format']] ?? 9;
        $out = [
            'icon'        => $assets->url('images/old/system/' . $icon_id . '.png'),
            'source_url'  => '/' . $array['res']['dir'] . '/' . $array['res']['name'],
            'url'         => '?act=load_file&amp;id=' . $id . $morelink,
            'name'        => $array['res']['text'],
            'size'        => self::displayFileSize(($array['res']['size'] ?? filesize($array['res']['dir'] . '/' . $array['res']['name']))),
            'is_new'      => $array['res']['time'] > $old,
            'upload_date' => $tools->displayDate((int) $array['res']['time']),
        ];

        return $out;
    }

    // Навигация по папкам
    public static function navigation($array = [])
    {
        /** @var NavChain $nav_chain */
        $nav_chain = di(NavChain::class);

        if ($array['refid']) {
            $sql = [];

            if (! isset($array['count'])) {
                $array['count'] = 1;
            }

            $explode = explode('/', $array['dir']);

            for ($i = 0; $i < (count($explode) - $array['count']); $i++) {
                if ($i) {
                    $explode[$i] = $explode[$i - 1] . '/' . $explode[$i];
                }

                if ($i > 2) {
                    $sql[] = $explode[$i];
                }
            }

            if ($sql) {
                /** @var PDO $db */
                $db = di(PDO::class);
                $req_cat = $db->query(
                    "SELECT * FROM `download__category` WHERE `dir` IN ('" . implode(
                        "','",
                        $sql
                    ) . "') ORDER BY `id`"
                );
                while ($res_cat = $req_cat->fetch()) {
                    $nav_chain->add(htmlspecialchars($res_cat['rus_name']), '?id=' . $res_cat['id']);
                }
            }
        }

        if (isset($array['name'])) {
            $nav_chain->add(htmlspecialchars($array['name']));
        }
    }
}
