<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Config\Config;

/**
 * @package     mobiCMS
 * @link        http://mobicms.net
 * @copyright   Copyright (C) 2008-2011 mobiCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://mobicms.net/about
 */
class download
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

    // Автоматическое создание скриншотов
    public static function screenAuto($file, $id, $format_file)
    {
        $screen = false;
        $screen_video = false;
        if ($format_file == 'nth') {
            require_once 'pclzip.lib.php';
            $theme = new PclZip($file);
            $content = $theme->extract(PCLZIP_OPT_BY_NAME, 'theme_descriptor.xml', PCLZIP_OPT_EXTRACT_AS_STRING);
            if (! $content) {
                $content = $theme->extract(PCLZIP_OPT_BY_PREG, '\.xml$', PCLZIP_OPT_EXTRACT_AS_STRING);
            }
            $val = simplexml_load_string($content[0]['content'])->wallpaper['src'] || $val = simplexml_load_string($content[0]['content'])->wallpaper['main_display_graphics'];
            $image = $theme->extract(PCLZIP_OPT_BY_NAME, trim($val), PCLZIP_OPT_EXTRACT_AS_STRING);
            $image = $image[0]['content'];
            $file_img = DOWNLOADS_SCR . $id . '/' . $id . '.jpg';
        } elseif ($format_file == 'thm') {
            require_once 'Tar.php';
            $theme = new Archive_Tar($file);
            if (! $file_th = ($theme->extractInString('Theme.xml') || ! $file_th = $theme->extractInString(
                    pathinfo(
                        $file,
                        PATHINFO_FILENAME
                    ) . '.xml'
                ))) {
                $list = $theme->listContent();
                $all = count($list);
                for ($i = 0; $i < $all; ++$i) {
                    if (pathinfo($list[$i]['filename'], PATHINFO_EXTENSION) == 'xml') {
                        $file_th = $theme->extractInString($list[$i]['filename']);
                        break;
                    }
                }
            }
            if (! $file_th) {
                preg_match('/<\?\s*xml\s*version\s*=\s*"1\.0"\s*\?>(.*)<\/.+>/isU', file_get_contents($file), $array);
                $file_th = trim($array[0]);
            }
            $load_file = trim((string ) simplexml_load_string($file_th)->Standby_image['Source']);
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (! $load_file) {
                $load_file = trim((string ) simplexml_load_string($file_th)->Desktop_image['Source']);
            }
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (! $load_file) {
                $load_file = trim((string ) simplexml_load_string($file_th)->Desktop_image['Source']);
            }
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (! $load_file) {
                exit;
            }
            $image = $theme->extractInString($load_file);
            $file_img = DOWNLOADS_SCR . $id . '/' . $id . '.jpg';
        } else {
            $ffmpeg = new ffmpeg_movie($file, false);
            $frame = $ffmpeg->getFrame(20);
            $image = $frame->toGDImage();
            $file_img = DOWNLOADS_SCR . $id . '/' . $id . '.gif';
            $screen_video = true;
        }
        if (! empty($image)) {
            $is_dir = is_dir(DOWNLOADS_SCR . $id);
            if (! $is_dir) {
                $is_dir = mkdir(DOWNLOADS_SCR . $id, 0777);
                if ($is_dir == true) {
                    @chmod(DOWNLOADS_SCR . $id, 0777);
                }
            }
            if ($is_dir) {
                $file_put = $screen_video ? imagegif($image, $file_img) : file_put_contents($file_img, $image);
                if ($file_put == true) {
                    $screen = $file_img;
                }
            }
        }

        return $screen;
    }

    // Вывод файла в ЗЦ
    public static function displayFile($res_down = [], $rate = 0)
    {
        global $old;
        $file = $res_down;
        $format_file = pathinfo($res_down['name'], PATHINFO_EXTENSION);
        $icon_id = isset(self::$extensions[$format_file]) ? self::$extensions[$format_file] : 9;

        /** @var Johncms\View\Extension\Assets $assets */
        $assets = di(Johncms\View\Extension\Assets::class);

        /** @var Johncms\System\Users\User $systemUser */
        $systemUser = di(Johncms\System\Users\User::class);

        /** @var Config $config */
        $config = di(Config::class);

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
            $file['preview_text'] = htmlspecialchars($about, 2);
        }

        $file['comments_url'] = '';
        if ($config->mod_down_comm || $systemUser->rights >= 7) {
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
            $size = $size . ' b';
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

        /** @var Johncms\Api\ToolsInterface $tools */
        $tools = di(Johncms\Api\ToolsInterface::class);

        /** @var Johncms\View\Extension\Assets $assets */
        $assets = di(Johncms\View\Extension\Assets::class);

        $id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
        $morelink = isset($array['more']) ? '&amp;more=' . $array['more'] : '';
        $icon_id = isset(self::$extensions[$array['format']]) ? self::$extensions[$array['format']] : 9;
        $out = [
            'icon'        => $assets->url('images/old/system/' . $icon_id . '.png'),
            'url'         => '?act=load_file&amp;id=' . $id . $morelink,
            'name'        => $array['res']['text'],
            'size'        => self::displayFileSize(($array['res']['size'] ?? filesize($array['res']['dir'] . '/' . $array['res']['name']))),
            'is_new'      => $array['res']['time'] > $old,
            'upload_date' => $tools->displayDate((int) $array['res']['time']),
        ];

        return $out;
    }

    // Транслитерация с Русского в латиницу
    public static function translateFileName($str)
    {
        $replace = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'j',
            'з' => 'z',
            'и' => 'i',
            'й' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'ye',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        return strtr($str, $replace);
    }

    // Навигация по папкам
    public static function navigation($array = [])
    {
        /** @var Johncms\Api\NavChainInterface $nav_chain */
        $nav_chain = di(Johncms\Api\NavChainInterface::class);

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
                    ) . "') ORDER BY `id` ASC"
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
