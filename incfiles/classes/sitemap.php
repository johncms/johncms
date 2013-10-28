<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNCMS') or die('Restricted access');
class sitemap {
    // Настройки карты форума
    private $cache_forum_map = 72;           // Время кэширования карты форума (часов)
    private $cache_forum_contents = 48;      // Время кэширования оглавления форума (часов)
    private $cache_forum_file = 'map_forum'; // Имя файла кэша (без расширения)

    // Настройки карты Библиотеки
    private $cache_lib_map = 72;         // Время кэширования карты библиотеки (часов)
    private $cache_lib_contents = 48;    // Время кэширования оглавления библиотеки (часов)
    private $cache_lib_file = 'map_lib'; // Имя файла кэша (без расширения)

    // Системные настройки
    private $links_count = 140; // Число ссылок в блоке
    private $set;               // Системные настройки модуля
    private $page;              //

    /*
    -----------------------------------------------------------------
    Задаем настройки
    -----------------------------------------------------------------
    */
    function __construct() {
        global $set;
        $this->set = isset($set['sitemap']) ? unserialize($set['sitemap']) : array();
        $this->page = isset($_GET['p']) ? abs(intval($_GET['p'])) : 0;
    }

    /*
    -----------------------------------------------------------------
    Карта сайта
    -----------------------------------------------------------------
    */
    public function site() {
        return ($this->set['forum'] ? '<p><b>Forum Map</b><br />' . $this->forum_map() . '</p>' : '') .
        ($this->set['lib'] ? '<p><b>Library Map</b><br />' . $this->library_map() . '</p>' : '');
    }

    /*
    -----------------------------------------------------------------
    Содержание разделов форума
    -----------------------------------------------------------------
    */
    public function forum_contents() {
        global $set, $id, $lng;
        $file = ROOTPATH . 'files/cache/' . $this->cache_forum_file . '_' . $id . ($this->page ? '_' . $this->page : '') . '.dat';
        if (!$id)
            return functions::display_error($lng['error_wrong_data']);
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_forum_contents * 3600)) {
            return file_get_contents($file);
        } else {
            $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r'");
            if (mysql_num_rows($req)) {
                $row = array();
                $res = mysql_fetch_assoc($req);
                $req_t = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 't' AND `close` != '1' ORDER BY `time` DESC LIMIT " . ($this->page * $this->links_count) . ", " . $this->links_count);
                if (mysql_num_rows($req_t)) {
                    while (($res_t = mysql_fetch_assoc($req_t)) !== false) $row[] = '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $res_t['id'] . '">' . $res_t['text'] . '</a>';
                    $out = '<div class="phdr"><b>' . $lng['forum'] . '</b> | ' . $res['text'] . '</div><div class="menu">' . implode('<br />' . "\r\n", $row) . '</div>';
                    return file_put_contents($file, $out) ? $out : 'Forum Contents cache error';
                }
            }
        }
        return false;
    }

    /*
    -----------------------------------------------------------------
    Содержание разделов Библиотеки
    -----------------------------------------------------------------
    */
    public function library_contents() {
        global $set, $id, $lng;
        $file = ROOTPATH . 'files/cache/' . $this->cache_lib_file . '_' . $id . ($this->page ? '_' . $this->page : '') . '.dat';
        if (!$id)
            return functions::display_error($lng['error_wrong_data']);
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_lib_contents * 3600)) {
            return file_get_contents($file);
        } else {
            $req = mysql_query("SELECT * FROM `lib` WHERE `id` = '$id' AND `type` = 'cat' AND `ip` = '0'");
            if (mysql_num_rows($req)) {
                $row = array();
                $res = mysql_fetch_assoc($req);
                $req_a = mysql_query("SELECT * FROM `lib` WHERE `refid` = '$id' AND `type` = 'bk' AND `moder` = '1' ORDER BY `time` ASC LIMIT " . ($this->page * $this->links_count) . ", " . $this->links_count);
                if (mysql_num_rows($req_a)) {
                    while (($res_a = mysql_fetch_assoc($req_a)) !== false) $row[] = '<a href="' . $set['homeurl'] . '/library/index.php?id=' . $res_a['id'] . '">' . functions::checkout($res_a['name']) . '</a>';
                    $out = '<div class="phdr"><b>' . $lng['library'] . '</b> | ' . $res['text'] . '</div><div class="menu">' . implode('<br />' . "\r\n", $row) . '</div>';
                    return file_put_contents($file, $out) ? $out : 'Library Contents cache error';
                }
            }
        }
        return false;
    }

    /*
    -----------------------------------------------------------------
    Кэш карты Форума
    -----------------------------------------------------------------
    */
    private function forum_map() {
        global $set;
        $file = ROOTPATH . 'files/cache/' . $this->cache_forum_file . '.dat';
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_forum_map * 3600)) {
            return file_get_contents($file);
        } else {
            $req = mysql_query("SELECT * FROM `forum` WHERE `type` = 'r'");
            if (mysql_num_rows($req)) {
                while (($res = mysql_fetch_assoc($req)) !== false) {
                    $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 't' AND `close` != '1'"), 0);
                    if ($count) {
                        $text = html_entity_decode($res['text']);
                        $text = mb_substr($text, 0, 40);
                        $pages = ceil($count / $this->links_count);
                        if ($pages > 1) {
                            for ($i = 0; $i < $pages; $i++) {
                                $out[] = '<a href="' . $set['homeurl'] . '/forum/contents.php?id=' . $res['id'] . '&amp;p=' . $i . '">' . functions::checkout($text) . ' (' . ($i + 1) . ')</a>';
                            }
                        } else {
                            $out[] = '<a href="' . $set['homeurl'] . '/forum/contents.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>';
                        }
                    }
                }
                if(isset($out))
                    return file_put_contents($file, implode('<br />' . "\r\n", $out)) ? implode('<br />', $out) : 'Forum cache error';
            }
        }
        return false;
    }

    /*
    -----------------------------------------------------------------
    Кэш карты Библиотеки
    -----------------------------------------------------------------
    */
    private function library_map() {
        global $set;
        $file = ROOTPATH . 'files/cache/' . $this->cache_lib_file . '.dat';
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_lib_map * 3600)) {
            return file_get_contents($file);
        } else {
            $req = mysql_query("SELECT * FROM `lib` WHERE `type` = 'cat' AND `ip` = '0'");
            if (mysql_num_rows($req)) {
                while (($res = mysql_fetch_assoc($req)) !== false) {
                    $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'bk' AND `moder` = '1'"), 0);
                    if ($count) {
                        $text = html_entity_decode($res['text']);
                        $text = mb_substr($text, 0, 40);
                        $pages = ceil($count / $this->links_count);
                        if ($pages > 1) {
                            for ($i = 0; $i < $pages; $i++) {
                                $out[] = '<a href="' . $set['homeurl'] . '/library/contents.php?id=' . $res['id'] . '&amp;p=' . $i . '">' . functions::checkout($text) . ' (' . ($i + 1) . ')</a>';
                            }
                        } else {
                            $out []= '<a href="../library/contents.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>';
                        }
                    }
                }
                if(isset($out))
                    return file_put_contents($file, implode('<br />' . "\r\n", $out)) ? implode('<br />', $out) : 'Library cache error';
            }
        }
        return false;
    }
}