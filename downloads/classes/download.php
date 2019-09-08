<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

/**
 * @package     mobiCMS
 * @link        http://mobicms.net
 * @copyright   Copyright (C) 2008-2011 mobiCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://mobicms.net/about
 */
class Download
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
            require_once('pclzip.lib.php');
            $theme = new PclZip($file);
            $content = $theme->extract(PCLZIP_OPT_BY_NAME, 'theme_descriptor.xml', PCLZIP_OPT_EXTRACT_AS_STRING);
            if (!$content) {
                $content = $theme->extract(PCLZIP_OPT_BY_PREG, '\.xml$', PCLZIP_OPT_EXTRACT_AS_STRING);
            }
            $val = simplexml_load_string($content[0]['content'])->wallpaper['src'] or $val = simplexml_load_string($content[0]['content'])->wallpaper['main_display_graphics'];
            $image = $theme->extract(PCLZIP_OPT_BY_NAME, trim($val), PCLZIP_OPT_EXTRACT_AS_STRING);
            $image = $image[0]['content'];
            $file_img = DOWNLOADS_SCR . $id . '/' . $id . '.jpg';
        } elseif ($format_file == 'thm') {
            require_once('Tar.php');
            $theme = new Archive_Tar($file);
            if (!$file_th = $theme->extractInString('Theme.xml') or !$file_th = $theme->extractInString(pathinfo($file, PATHINFO_FILENAME) . '.xml')) {
                $list = $theme->listContent();
                $all = sizeof($list);
                for ($i = 0; $i < $all; ++$i) {
                    if (pathinfo($list[$i]['filename'], PATHINFO_EXTENSION) == 'xml') {
                        $file_th = $theme->extractInString($list[$i]['filename']);
                        break;
                    }
                }
            }
            if (!$file_th) {
                preg_match('/<\?\s*xml\s*version\s*=\s*"1\.0"\s*\?>(.*)<\/.+>/isU', file_get_contents($file), $array);
                $file_th = trim($array[0]);
            }
            $load_file = trim((string )simplexml_load_string($file_th)->Standby_image['Source']);
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (!$load_file) {
                $load_file = trim((string )simplexml_load_string($file_th)->Desktop_image['Source']);
            }
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (!$load_file) {
                $load_file = trim((string )simplexml_load_string($file_th)->Desktop_image['Source']);
            }
            if (strtolower(strrchr($load_file, '.')) == '.swf') {
                $load_file = '';
            }
            if (!$load_file) {
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
        if (!empty($image)) {
            $is_dir = is_dir(DOWNLOADS_SCR . $id);
            if (!$is_dir) {
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
        $out = false;
        $format_file = pathinfo($res_down['name'], PATHINFO_EXTENSION);
        $icon_id = isset(self::$extensions[$format_file]) ? self::$extensions[$format_file] : 9;

        /** @var Psr\Container\ContainerInterface $container */
        $container = App::getContainer();

        /** @var Johncms\Api\UserInterface $systemUser */
        $systemUser = $container->get(Johncms\Api\UserInterface::class);

        /** @var Johncms\Api\ToolsInterface $tools */
        $tools = $container->get(Johncms\Api\ToolsInterface::class);

        /** @var Johncms\Api\ConfigInterface $config */
        $config = $container->get(Johncms\Api\ConfigInterface::class);

        $out .= $tools->image('system/' . $icon_id . '.png') . '&nbsp;';
        $out .= '<a href="?act=view&amp;id=' . $res_down['id'] . '">' . htmlspecialchars($res_down['rus_name']) . '</a> (' . $res_down['field'] . ')';

        if ($res_down['time'] > $old) {
            $out .= ' <span class="red">(NEW)</span>';
        }

        if ($rate) {
            $file_rate = explode('|', $res_down['rate']);
            $out .= '<br>' . _t('Rating') . ': <span class="green">' . $file_rate[0] . '</span>/<span class="red">' . $file_rate[1] . '</span>';
        }

        $sub = false;

        if ($res_down['about']) {
            $about = $res_down['about'];
            if (mb_strlen($about) > 100) {
                $about = mb_substr($about, 0, 90) . '...';
            }
            $sub = '<div>' . htmlspecialchars($about, 2) . '</div>';
        }

        if ($config->mod_down_comm || $systemUser->rights >= 7) {
            $sub .= '<a href="?act=comments&amp;id=' . $res_down['id'] . '">' . _t('Comments') . '</a> (' . $res_down['comm_count'] . ')';
        }

        if ($sub) {
            $out .= '<div class="sub">' . $sub . '</div>';
        }

        return $out;
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
        if (!$value) {
            return htmlspecialchars(iconv('windows-1251', 'UTF-8', $name));
        } else {
            return iconv('UTF-8', 'windows-1251', $name);
        }
    }

    // Вывод ссылок на файл
    public static function downloadLlink($array = [])
    {
        global $old;

        /** @var Johncms\Api\ToolsInterface $tools */
        $tools = App::getContainer()->get(Johncms\Api\ToolsInterface::class);

        $id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
        $morelink = isset($array['more']) ? '&amp;more=' . $array['more'] : '';
        $out = '<table  width="100%"><tr><td width="16" valign="top">';
        $icon_id = isset(self::$extensions[$array['format']]) ? self::$extensions[$array['format']] : 9;
        $out .= $tools->image('system/' . $icon_id . '.png') . '&nbsp;';
        $out .= '</td><td><a href="?act=load_file&amp;id=' . $id . $morelink . '">' . $array['res']['text'] . '</a> (' . Download::displayFileSize((isset($array['res']['size']) ? $array['res']['size'] : filesize($array['res']['dir'] . '/' . $array['res']['name']))) . ')';

        if ($array['res']['time'] > $old) {
            $out .= ' <span class="red">(NEW)</span>';
        }

        $out .= '<div class="sub">' . _t('Uploaded') . ': ' . $tools->displayDate($array['res']['time']);
        $out .= '</div></td></tr></table>';

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
            'ъ' => "",
            'ы' => 'y',
            'ь' => "",
            'э' => 'ye',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        return strtr($str, $replace);
    }

    // Навигация по папкам
    public static function navigation($array = [])
    {
        $category = ['<a href="?"><b>' . _t('Downloads') . '</b></a>'];

        if ($array['refid']) {
            $sql = [];

            if (!isset($array['count'])) {
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
                $db = App::getContainer()->get(PDO::class);
                $req_cat = $db->query("SELECT * FROM `download__category` WHERE `dir` IN ('" . implode("','", $sql) . "') ORDER BY `id` ASC");
                while ($res_cat = $req_cat->fetch()) {
                    $category[] = '<a href="?id=' . $res_cat['id'] . '">' . htmlspecialchars($res_cat['rus_name']) . '</a>';
                }
            }
        }

        if (isset($array['name'])) {
            $category[] = htmlspecialchars($array['name']);
        }

        return implode(' | ', $category);
    }
}
