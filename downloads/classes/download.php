<?php

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
        global $screens_path;
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
            $file_img = $screens_path . '/' . $id . '/' . $id . '.jpg';
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
            $file_img = $screens_path . '/' . $id . '/' . $id . '.jpg';
        } else {
            $ffmpeg = new ffmpeg_movie($file, false);
            $frame = $ffmpeg->getFrame(20);
            $image = $frame->toGDImage();
            $file_img = $screens_path . '/' . $id . '/' . $id . '.gif';
            $screen_video = true;
        }
        if (!empty($image)) {
            $is_dir = is_dir($screens_path . '/' . $id);
            if (!$is_dir) {
                $is_dir = mkdir($screens_path . '/' . $id, 0777);
                if ($is_dir == true) {
                    @chmod($screens_path . '/' . $id, 0777);
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
        global $set_down, $screens_path, $old, $set, $rights;
        $out = false;
        $preview = false;
        $format_file = htmlspecialchars($res_down['name']);

        if ($format_file == 'jpg' || $format_file == 'jpeg' || $format_file == 'gif' || $format_file == 'png') {
            $preview = $res_down['dir'] . '/' . $res_down['name'];
        } else {
            if ($format_file == 'thm' || $format_file == 'nth' || $format_file == '3gp' || $format_file == 'avi' || $format_file == 'mp4') {
                if (is_file($screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.jpg')) {
                    $preview = $screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.jpg';
                } elseif (is_file($screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.gif')) {
                    $preview = $screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.gif';
                } elseif (is_file($screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.png')) {
                    $preview = $screens_path . '/' . $res_down['id'] . '/' . $res_down['id'] . '.png';
                } elseif (($format_file == 'thm' || $format_file == 'nth') && $set_down['theme_screen']) {
                    $preview = Download::screenAuto($res_down['dir'] . '/' . $res_down['name'], $res_down['id'], $format_file);
                } elseif ($set_down['video_screen']) {
                    $preview = Download::screenAuto($res_down['dir'] . '/' . $res_down['name'], $res_down['id'], $format_file);
                }
                //$preview = $preview ? $preview : Functions::loadModuleImage('easy.gif'); //TODO: Переделать иконку
            }
        }

        if ($preview) {
            $out = '<img src="' . $set['homeurl'] . '/downloads/preview.php?type=1&amp;img=' . rawurlencode($preview) . '" alt="preview" />&nbsp;';
        }

        if ($format_file == 'jar' && $set_down['icon_java']) {
            $out = Download::javaIcon($res_down['dir'] . '/' . $res_down['name'], $res_down['id']) . '&nbsp;';
        } else {
            $icon_id = isset(self::$extensions[$format_file]) ? self::$extensions[$format_file] : 9;
            //$out .= Functions::getIcon('filetype-' . $icon_id . '.png') . '&nbsp;';//TODO: Переделать на векторные иконки
        }

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

        //TODO: Переделать на получение настроек из таблицы модулей
        if ($set['mod_down_comm'] || $rights >= 7) {
            $sub .= '<a href="?act=comments&amp;id=' . $res_down['id'] . '">' . _t('Comments') . '</a> (' . $res_down['total'] . ')';
        }

        if ($sub) {
            $out .= '<div class="sub">' . $sub . '</div>';
        }

        return $out;
    }

    // Вынимаем иконку из Java
    public static function javaIcon($file, $id)
    {
        $out = false;
        if (is_file('files/download/java_icons/' . $id . '.png')) {
            $out = 'files/download/java_icons/' . $id . '.png';
        } else {
            require_once('pclzip.lib.php');//TODO: Сделать автозагрузку
            $zip = new PclZip($file);

            if ($zip->listContent() == 0) {
                if ($manifest = $zip->extract(PCLZIP_OPT_BY_NAME, 'META-INF/MANIFEST.MF', PCLZIP_OPT_EXTRACT_AS_STRING)) {
                    $text = $manifest[0]['content'];

                    if (strpos($text, 'MIDlet-Icon: ') !== false) {
                        $explode = explode('MIDlet-Icon: ', $text);
                        $icon = str_replace("\r", ' ', str_replace("\n", ' ', $explode[1]));
                        $icon = strtok($icon, ' ');
                        $icon = preg_replace('#^/#', null, $icon);
                    } else {
                        $icon = 'icon.png';
                    }

                    $ext = explode('.', $icon);

                    if ($ext[1] == 'png' && count($ext) == 2) {
                        if ($image = $zip->extract(PCLZIP_OPT_BY_NAME, $icon, PCLZIP_OPT_EXTRACT_AS_STRING)) {
                            $image = imagecreatefromstring($image[0]['content']);
                            $width = imagesx($image);
                            $height = imagesy($image);
                            $x_ratio = 16 / $width;
                            $y_ratio = 16 / $height;

                            if (($width <= 16) && ($height <= 16)) {
                                $tn_width = $width;
                                $tn_height = $height;
                            } elseif (($x_ratio * $height) < 16) {
                                $tn_height = ceil($x_ratio * $height);
                                $tn_width = 16;
                            } else {
                                $tn_width = ceil($y_ratio * $width);
                                $tn_height = 16;
                            }

                            $image_two = ImageCreate($tn_width, $tn_height);
                            imagecopyresampled($image_two, $image, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
                            $image = $image_two;
                            $file_img = 'files/download/java_icons/' . $id . '.png';

                            if (imagepng($image, $file_img)) {
                                $out = $file_img;
                            }

                            imagedestroy($image);
                        }
                    }
                }
            }

            if (!$out) {
                $out = 'assets/icons/filetype-2.png';
                @copy($out, 'files/download/java_icons/' . $id . '.png');
            }
        }

        return '<img src="' . App::cfg()->sys->homeurl . $out . '" alt="file"/> ';
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
        global $set_down, $old;
        $id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
        $morelink = isset($array['more']) ? '&amp;more=' . $array['more'] : '';
        $out = '<table  width="100%"><tr><td width="16" valign="top">';
        $lng = core::load_lng('dl');

        if ($array['format'] == 'jar' && $set_down['icon_java']) {
            $out .= Download::javaIcon($array['res']['dir'] . '/' . $array['res']['name'], (isset($array['more']) ? $array['res']['refid'] . '_' . $array['res']['id'] : $array['res']['id']));
        } else {
            $icon_id = isset(self::$extensions[$array['format']]) ? self::$extensions[$array['format']] : 9;
            //TODO: Разобраться
            //$out .= Functions::getIcon('filetype-' . $icon_id . '.png') . '&nbsp;';
        }

        $out .= '</td><td><a href="?act=load_file&amp;id=' . $id . $morelink . '">' . $array['res']['text'] . '</a> (' . Download::displayFileSize((isset($array['res']['size']) ? $array['res']['size'] : filesize($array['res']['dir'] . '/' . $array['res']['name']))) . ')';

        if ($array['res']['time'] > $old) {
            $out .= ' <span class="red">(NEW)</span>';
        }

        $out .= '<div class="sub">' . $lng['file_time'] . ': ' . functions::display_date($array['res']['time']);

        if ($array['format'] == 'jar') {
            $out .= ', <a href="?act=jad_file&amp;id=' . $id . $morelink . '">JAD</a>';
        } elseif ($array['format'] == 'txt') {
            $out .= ', <a href="?act=txt_in_zip&amp;id=' . $id . $morelink . '">ZIP</a> / <a href="?act=txt_in_jar&amp;id=' . $id . $morelink . '">JAR</a>';
        } else {
            if ($array['format'] == 'zip') {
                $out .= ', <a href="?act=open_zip&amp;id=' . $id . $morelink . '">' . $lng['open_archive'] . '</a>';
            }
        }

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
        $lng = core::load_lng('dl');
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
