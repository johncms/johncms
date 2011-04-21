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

class functions {

    /*
    -----------------------------------------------------------------
    Антифлуд
    -----------------------------------------------------------------
    Режимы работы:
    1 - Адаптивный
    2 - День / Ночь
    3 - День
    4 - Ночь
    -----------------------------------------------------------------
    */
    static function antiflood() {
        global $set, $datauser, $realtime;
        $default = array(
            'mode' => 2,
            'day' => 10,
            'night' => 30,
            'dayfrom' => 10,
            'dayto' => 22
        );
        $af = isset($set['antiflood']) ? unserialize($set['antiflood']) : $default;
        switch ($af['mode']) {
            case 1:
                // Адаптивный режим
                $onltime = $realtime - 600;
                $adm = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > '$onltime'"), 0);
                $limit = $adm > 0 ? $af['day'] : $af['night'];
                break;
            case 3:
                // День
                $limit = $af['day'];
                break;
            case 4:
                // Ночь
                $limit = $af['night'];
                break;
            default:
                // По умолчанию день / ночь
                $c_time = date('G', $realtime);
                $limit = $c_time > $af['day'] && $c_time < $af['night'] ? $af['day'] : $af['night'];
        }
        if ($datauser['rights'] > 0)
            $limit = 4; // Для Администрации задаем лимит в 4 секунды
        $flood = $datauser['lastpost'] + $limit - $realtime;
        if ($flood > 0)
            return $flood;
        else
            return false;
    }

    /*
    -----------------------------------------------------------------
    Маскировка ссылок в тексте
    -----------------------------------------------------------------
    */
    static function antilink($var) {
        $var = preg_replace('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', '###', $var);
        $var = strtr($var, array(
            '.ru' => '***',
            '.com' => '***',
            '.biz' => '***',
            '.cn' => '***',
            '.in' => '***',
            '.net' => '***',
            '.org' => '***',
            '.info' => '***',
            '.mobi' => '***',
            '.wen' => '***',
            '.kmx' => '***',
            '.h2m' => '***'
        ));
        return $var;
    }

    /*
    -----------------------------------------------------------------
    ББ панель (для компьютеров)
    -----------------------------------------------------------------
    */
    static function auto_bb($form, $field) {
        global $set, $datauser, $lng, $user_id, $is_mobile;
        if($is_mobile){
            return false;
        }
        $smileys = !empty($datauser['smileys']) ? unserialize($datauser['smileys']) : '';
        if (!empty($smileys)) {
            $res_sm = '';
            $my_smileys = '<small><a href="' . $set['homeurl'] . '/pages/faq.php?act=my_smileys">' . $lng['edit_list'] . '</a></small><br />';
            foreach ($smileys as $value)
                $res_sm .= '<a href="javascript:tag(\'' . $value . '\', \'\', \':\');">:' . $value . ':</a> ';
            $my_smileys .= functions::smileys($res_sm, $datauser['rights'] >= 1 ? 1 : 0);
        } else {
            $my_smileys = '<small><a href="' . $set['homeurl'] . '/pages/faq.php?act=smileys">' . $lng['add_smileys'] . '</a></small>';
        }
        $out = '<style>
            .smileys{
			background-color: rgba(178,178,178,0.5);
            padding: 5px;
            border-radius: 3px;
            border: 1px solid white;
            display: none;
            overflow: auto;
            max-width: 250px;
            max-height: 100px;
            position: absolute;
            }
            .smileys_from:hover .smileys{
            display: block;
            }
            </style>
            <script language="JavaScript" type="text/javascript">
            function tag(text1, text2, text3) {
            if ((document.selection)) {
                document.' . $form . '.' . $field . '.focus();
                document.' . $form . '.document.selection.createRange().text = text3+text1+document.' . $form . '.document.selection.createRange().text+text2+text3;
            } else if(document.forms[\'' . $form . '\'].elements[\'' . $field . '\'].selectionStart!=undefined) {
                var element = document.forms[\'' . $form . '\'].elements[\'' . $field . '\'];
                var str = element.value;
                var start = element.selectionStart;
                var length = element.selectionEnd - element.selectionStart;
                element.value = str.substr(0, start) + text3 + text1 + str.substr(start, length) + text2 + text3 + str.substr(start + length);
            } else document.' . $form . '.' . $field . '.value += text3+text1+text2+text3;}</script>
            <a href="javascript:tag(\'[b]\', \'[/b]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/b.png" alt="b" title="' . $lng['tag_bold'] . '" border="0"/></a>
            <a href="javascript:tag(\'[i]\', \'[/i]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/i.png" alt="i" title="' . $lng['tag_italic'] . '" border="0"/></a>
            <a href="javascript:tag(\'[u]\', \'[/u]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/u.png" alt="u" title="' . $lng['tag_underline'] . '" border="0"/></a>
            <a href="javascript:tag(\'[s]\', \'[/s]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/s.png" alt="s" title="' . $lng['tag_strike'] . '" border="0"/></a>
            <a href="javascript:tag(\'[c]\', \'[/c]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/q.png" alt="quote" title="' . $lng['tag_quote'] . '" border="0"/></a>
            <a href="javascript:tag(\'[php]\', \'[/php]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/cod.png" alt="cod" title="' . $lng['tag_code'] . '" border="0"/></a>
            <a href="javascript:tag(\'[url=]\', \'[/url]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/l.png" alt="url" title="' . $lng['tag_link'] . '" border="0"/></a>
            <a href="javascript:tag(\'[red]\', \'[/red]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/re.png" alt="red" title="' . $lng['tag_red'] . '" border="0"/></a>
            <a href="javascript:tag(\'[green]\', \'[/green]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/gr.png" alt="green" title="' . $lng['tag_green'] . '" border="0"/></a>
            <a href="javascript:tag(\'[blue]\', \'[/blue]\', \'\')"><img src="' . $set['homeurl'] . '/images/bb/bl.png" alt="blue" title="' . $lng['tag_blue'] . '" border="0"/></a>';
        if ($user_id) {
            $out .= ' <span class="smileys_from" style="display: inline-block; cursor:pointer"><img src="' . $set['homeurl'] . '/images/bb/sm.png" alt="sm" title="' . $lng['smileys'] . '" border="0"/>
                <div class="smileys">' . $my_smileys . '</div></span>';
        }
        return $out . '<br />';
    }

    /*
    -----------------------------------------------------------------
    Проверка переменных
    -----------------------------------------------------------------
    */
    static function check($str) {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
        $str = nl2br($str);
        $str = strtr($str, array(
            chr(0) => '',
            chr(1) => '',
            chr(2) => '',
            chr(3) => '',
            chr(4) => '',
            chr(5) => '',
            chr(6) => '',
            chr(7) => '',
            chr(8) => '',
            chr(9) => '',
            chr(10) => '',
            chr(11) => '',
            chr(12) => '',
            chr(13) => '',
            chr(14) => '',
            chr(15) => '',
            chr(16) => '',
            chr(17) => '',
            chr(18) => '',
            chr(19) => '',
            chr(20) => '',
            chr(21) => '',
            chr(22) => '',
            chr(23) => '',
            chr(24) => '',
            chr(25) => '',
            chr(26) => '',
            chr(27) => '',
            chr(28) => '',
            chr(29) => '',
            chr(30) => '',
            chr(31) => ''
        ));
        $str = str_replace("'", "&#39;", $str);
        $str = str_replace('\\', "&#92;", $str);
        $str = str_replace("|", "I", $str);
        $str = str_replace("||", "I", $str);
        $str = str_replace("/\\\$/", "&#36;", $str);
        $str = mysql_real_escape_string($str);
        return $str;
    }

    /*
    -----------------------------------------------------------------
    Обработка текстов перед выводом на экран
    -----------------------------------------------------------------
    $br=1           обработка переносов строк
    $br=2           подстановка пробела, вместо переноса
    $tags=1         обработка тэгов
    $tags=2         вырезание тэгов
    -----------------------------------------------------------------
    */
    static function checkout($str, $br = 0, $tags = 0) {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
        if ($br == 1)
            $str = nl2br($str);
        elseif ($br == 2)
            $str = str_replace("\r\n", ' ', $str);
        //TODO: Передеать на новую функцию подсветки Тэгов
        if ($tags == 1)
            $str = call_user_func('tags', $str);
        elseif ($tags == 2)
            $str = self::notags($str);
        $str = strtr($str, array(
            chr(0) => '',
            chr(1) => '',
            chr(2) => '',
            chr(3) => '',
            chr(4) => '',
            chr(5) => '',
            chr(6) => '',
            chr(7) => '',
            chr(8) => '',
            chr(9) => '',
            chr(11) => '',
            chr(12) => '',
            chr(13) => '',
            chr(14) => '',
            chr(15) => '',
            chr(16) => '',
            chr(17) => '',
            chr(18) => '',
            chr(19) => '',
            chr(20) => '',
            chr(21) => '',
            chr(22) => '',
            chr(23) => '',
            chr(24) => '',
            chr(25) => '',
            chr(26) => '',
            chr(27) => '',
            chr(28) => '',
            chr(29) => '',
            chr(30) => '',
            chr(31) => ''
        ));
        return $str;
    }

    /*
    -----------------------------------------------------------------
    Счетчик Фотоальбомов / фотографий юзеров
    -----------------------------------------------------------------
    */
    static function count_photo() {
        global $realtime, $set;
        $albumcount = mysql_result(mysql_query("SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`"), 0);
        $photocount = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files`"), 0);
        $newcount = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . ($realtime - 259200) . "' AND `access` > '1'"), 0);
        return $albumcount . '&#160;/&#160;' . $photocount . ($newcount ? '&#160;/&#160;<span class="red"><a href="' . $set['homeurl'] . '/users/album.php?act=top">+' . $newcount . '</a></span>' : '');
    }

    /*
    -----------------------------------------------------------------
    Показ различных счетчиков внизу страницы
    -----------------------------------------------------------------
    */
    static function display_counters() {
        global $headmod;
        $req = mysql_query("SELECT * FROM `cms_counters` WHERE `switch` = '1' ORDER BY `sort` ASC");
        if (mysql_num_rows($req) > 0) {
            while (($res = mysql_fetch_array($req)) !== false) {
                $link1 = ($res['mode'] == 1 || $res['mode'] == 2) ? $res['link1'] : $res['link2'];
                $link2 = $res['mode'] == 2 ? $res['link1'] : $res['link2'];
                $count = ($headmod == 'mainpage') ? $link1 : $link2;
                if (!empty($count))
                    echo $count;
            }
        }
    }

    /*
    -----------------------------------------------------------------
    Сообщения об ошибках
    -----------------------------------------------------------------
    */
    static function display_error($error = '', $link = '') {
        global $lng;
        if ($error) {
            $out = '<div class="rmenu"><p><b>' . $lng['error'] . '!</b><br />';
            $out .= is_array($error) ? implode('<br />', $error) : $error;
            $out .= '</p><p>' . $link . '</p></div>';
            return $out;
        } else {
            return false;
        }
    }

    /*
    -----------------------------------------------------------------
    Отображение различных меню
    -----------------------------------------------------------------
    $delimiter - разделитель между пунктами
    $end_space - выводится в конце
    -----------------------------------------------------------------
    */
    static function display_menu($val = array(), $delimiter = ' | ', $end_space = '') {
        return implode($delimiter, array_diff($val, array(''))) . $end_space;
    }

    /*
    -----------------------------------------------------------------
    Постраничная навигация
    За основу взята аналогичная функция от форума SMF2.0
    -----------------------------------------------------------------
    */
    static function display_pagination($base_url, $start, $max_value, $num_per_page) {
        $neighbors = 2;
        if ($start >= $max_value)
            $start = max(0, (int)$max_value - (((int)$max_value % (int)$num_per_page) == 0 ? $num_per_page : ((int)$max_value % (int)$num_per_page)));
        else
            $start = max(0, (int)$start - ((int)$start % (int)$num_per_page));
        $base_link = '<a class="navpg" href="' . strtr($base_url, array('%' => '%%')) . 'start=%d' . '">%s</a> ';
        $pageindex = $start == 0 ? '' : sprintf($base_link, $start - $num_per_page, '&lt;&lt;');
        if ($start > $num_per_page * $neighbors)
            $pageindex .= sprintf($base_link, 0, '1');
        if ($start > $num_per_page * ($neighbors + 1))
            $pageindex .= '<span style="font-weight: bold;"> ... </span>';
        for ($nCont = $neighbors; $nCont >= 1; $nCont--)
            if ($start >= $num_per_page * $nCont) {
                $tmpStart = $start - $num_per_page * $nCont;
                $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
            }
        $pageindex .= '[<b>' . ($start / $num_per_page + 1) . '</b>] ';
        $tmpMaxPages = (int)(($max_value - 1) / $num_per_page) * $num_per_page;
        for ($nCont = 1; $nCont <= $neighbors; $nCont++)
            if ($start + $num_per_page * $nCont <= $tmpMaxPages) {
                $tmpStart = $start + $num_per_page * $nCont;
                $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
            }
        if ($start + $num_per_page * ($neighbors + 1) < $tmpMaxPages)
            $pageindex .= '<span style="font-weight: bold;"> ... </span>';
        if ($start + $num_per_page * $neighbors < $tmpMaxPages)
            $pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1);
        if ($start + $num_per_page < $max_value) {
            $display_page = ($start + $num_per_page) > $max_value ? $max_value : ($start + $num_per_page);
            $pageindex .= sprintf($base_link, $display_page, '&gt;&gt;');
        }
        return $pageindex;
    }

    /*
    -----------------------------------------------------------------
    Отображения личных данных пользователя
    -----------------------------------------------------------------
    $user          (array)     массив запроса в таблицу `users`
    $arg           (array)     Массив параметров отображения
       [lastvisit] (boolean)   Дата и время последнего визита
       [stshide]   (boolean)   Скрыть статус (если есть)
       [iphide]    (boolean)   Скрыть (не показывать) IP и UserAgent
       [iphist]    (boolean)   Показывать ссылку на историю IP

       [header]    (string)    Текст в строке после Ника пользователя
       [body]      (string)    Основной текст, под ником пользователя
       [sub]       (string)    Строка выводится вверху области "sub"
       [footer]    (string)    Строка выводится внизу области "sub"
    -----------------------------------------------------------------
    */
    static function display_user($user = false, $arg = false) {
        global $set, $set_user, $realtime, $user_id, $rights, $lng, $rootpath;
        $out = false;

        if (!$user['id']) {
            $out = '<b>' . $lng['guest'] . '</b>';
            if (!empty($user['name']))
                $out .= ': ' . $user['name'];
            if (!empty($arg['header']))
                $out .= ' ' . $arg['header'];
        } else {
            if ($set_user['avatar']) {
                $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';
                if (file_exists(($rootpath . 'files/users/avatar/' . $user['id'] . '.png')))
                    $out .= '<img src="' . $set['homeurl'] . '/files/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="" />&#160;';
                else
                    $out .= '<img src="' . $set['homeurl'] . '/images/empty.png" width="32" height="32" alt="" />&#160;';
                $out .= '</td><td>';
            }
            if ($user['sex'])
                $out .= '<img src="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/images/' . ($user['sex'] == 'm' ? 'm' : 'w') . ($user['datereg'] > $realtime - 86400 ? '_new' : '')
                        . '.png" width="16" height="16" align="middle" alt="' . ($user['sex'] == 'm' ? 'М' : 'Ж') . '" />&#160;';
            else
                $out .= '<img src="' . $set['homeurl'] . '/images/del.png" width="12" height="12" align="middle" />&#160;';
            $out .= !$user_id || $user_id == $user['id'] ? '<b>' . $user['name'] . '</b>' : '<a href="' . $set['homeurl'] . '/users/profile.php?user=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
            $rank = array(
                0 => '',
                1 => '(GMod)',
                2 => '(CMod)',
                3 => '(FMod)',
                4 => '(DMod)',
                5 => '(LMod)',
                6 => '(Smd)',
                7 => '(Adm)',
                9 => '(SV!)'
            );
            $out .= ' ' . $rank[$user['rights']];
            $out .= ($realtime > $user['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');
            if (!empty($arg['header']))
                $out .= ' ' . $arg['header'];
            if (!isset($arg['stshide']) && !empty($user['status']))
                $out .= '<div class="status"><img src="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/images/label.png" alt="" align="middle" />&#160;' . $user['status'] . '</div>';
            if ($set_user['avatar'])
                $out .= '</td></tr></table>';
        }
        if (isset($arg['body']))
            $out .= '<div>' . $arg['body'] . '</div>';
        $ipinf = ($rights || $user['id'] && $user['id'] == $user_id) && !isset($arg['iphide']) ? 1 : 0;
        $lastvisit = $realtime > $user['lastdate'] + 300 && isset($arg['lastvisit']) ? date("d.m.Y (H:i)", $user['lastdate']) : false;

        if ($ipinf || $lastvisit || isset($arg['sub']) || isset($arg['footer'])) {
            $out .= '<div class="sub">';
            if (isset($arg['sub']))
                $out .= '<div>' . $arg['sub'] . '</div>';
            if ($lastvisit)
                $out .= '<div><span class="gray">' . $lng['last_visit'] . ':</span> ' . $lastvisit . '</div>';
            $iphist = '';
            if ($ipinf && isset($arg['iphist'])) {
                $iptotal = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'"), 0);
                $iphist = '&#160;<a href="' . $set['homeurl'] . '/users/profile.php?act=ip&amp;user=' . $user['id'] . '">[' . $iptotal . ']</a>';
            }
            if ($ipinf) {
                $out .= '<div><span class="gray">UserAgent:</span> ' . $user['browser'] . '</div>';
                if ($rights)
                    $out .= '<div><span class="gray">' . $lng['last_ip'] . ':</span> <a href="' . $set['homeurl'] . '/' . $set['admp'] . '/index.php?act=search_ip&amp;ip=' . $user['ip'] . '">' . long2ip($user['ip']) . '</a>' . $iphist
                            . '</div>';
                else
                    $out .= '<div><span class="gray">' . $lng['last_ip'] . ':</span> ' . long2ip($user['ip']) . $iphist . '</div>';
            }
            if (isset($arg['footer']))
                $out .= $arg['footer'];
            $out .= '</div>';
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Форматирование имени файла
    -----------------------------------------------------------------
    */
    static function format($name) {
        $f1 = strrpos($name, ".");
        $f2 = substr($name, $f1 + 1, 999);
        $fname = strtolower($f2);
        return $fname;
    }

    /*
    -----------------------------------------------------------------
    Вспомогательная Функция обработки ссылок форума
    -----------------------------------------------------------------
    */
    static function forum_link($m) {
        global $set;
        if (!isset($m[3])) {
            return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
        } else {
            $p = parse_url($m[3]);
            if ('http://' . $p['host'] . $p['path'] . '?id=' == $set['homeurl'] . '/forum/index.php?id=') {
                $thid = abs(intval(preg_replace('/(.*?)id=/si', '', $m[3])));
                $req = mysql_query("SELECT `text` FROM `forum` WHERE `id`= '$thid' AND `type` = 't' AND `close` != '1'");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $name = strtr($res['text'], array(
                        '&quot;' => '',
                        '&amp;' => '',
                        '&lt;' => '',
                        '&gt;' => '',
                        '&#039;' => '',
                        '[' => '',
                        ']' => ''
                    ));
                    if (mb_strlen($name) > 40)
                        $name = mb_substr($name, 0, 40) . '...';
                    return '[url=' . $m[3] . ']' . $name . '[/url]';
                } else {
                    return $m[3];
                }
            } else
                return $m[3];
        }
    }

    /*
    -----------------------------------------------------------------
    Счетчик непрочитанных тем на форуме
    -----------------------------------------------------------------
    $mod = 0   Возвращает число непрочитанных тем
    $mod = 1   Выводит ссылки на непрочитанное
    -----------------------------------------------------------------
    */
    static function forum_new($mod = 0) {
        global $user_id, $rights, $lng;
        if ($user_id) {
            $req = mysql_query("SELECT COUNT(*) FROM `forum`
        LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id . "'
        WHERE `forum`.`type`='t'" . ($rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
        AND (`cms_forum_rdm`.`topic_id` Is Null
        OR `forum`.`time` > `cms_forum_rdm`.`time`)");
            $total = mysql_result($req, 0);
            if ($mod)
                return '<a href="index.php?act=new">' . $lng['unread'] . '</a>&#160;' . ($total ? '<span class="red">(<b>' . $total . '</b>)</span>' : '');
            else
                return $total;
        } else {
            if ($mod)
                return '<a href="index.php?act=new">' . $lng['last_activity'] . '</a>';
            else
                return false;
        }
    }

    /*
    -----------------------------------------------------------------
    Получаем данные пользователя
    -----------------------------------------------------------------
    */
    static function get_user($id = false) {
        global $datauser, $user_id;
        if ($id && $id != $user_id) {
            $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id'");
            if (mysql_num_rows($req)) {
                return mysql_fetch_assoc($req);
            } else {
                return false;
            }
        } else {
            return $datauser;
        }
    }

    /*
    -----------------------------------------------------------------
    Вырезание BBcode тэгов из текста
    -----------------------------------------------------------------
    */
    static function notags($var = '') {
        $var = strtr($var, array(
            '[green]' => '',
            '[/green]' => '',
            '[red]' => '',
            '[/red]' => '',
            '[blue]' => '',
            '[/blue]' => '',
            '[b]' => '',
            '[/b]' => '',
            '[i]' => '',
            '[/i]' => '',
            '[u]' => '',
            '[/u]' => '',
            '[s]' => '',
            '[/s]' => '',
            '[c]' => '',
            '[/c]' => ''
        ));
        return $var;
    }

    /*
    -----------------------------------------------------------------
    Транслитерация с Русского в латиницу
    -----------------------------------------------------------------
    */
    static function rus_lat($str) {
        $str = strtr($str, array(
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
            'я' => 'ya'
        ));
        return $str;
    }

    /*
    -----------------------------------------------------------------
    Обработка смайлов
    -----------------------------------------------------------------
    $adm=1 покажет и обычные и Админские смайлы
    $adm=2 пересоздаст кэш смайлов
    -----------------------------------------------------------------
    */
    static function smileys($str, $adm = 0) {
        global $rootpath, $set;
        // Записываем КЭШ смайлов
        if ($adm == 2) {
            $count = 0;
            // Обрабатываем простые смайлы
            $array1 = array();
            $path = 'images/smileys/simply/';
            $dir = opendir($rootpath . $path);
            while (($file = readdir($dir)) !== false) {
                $name = explode(".", $file);
                if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                    $array1[':' . $name[0]] = '<img src="' . $set['homeurl'] . '/' . $path . $file . '" alt="" />';
                    ++$count;
                }
            }
            closedir($dir);
            // Обрабатываем Админские смайлы
            $array2 = array();
            $array3 = array();
            $path = 'images/smileys/admin/';
            $dir = opendir($rootpath . $path);
            while (($file = readdir($dir)) !== false) {
                $name = explode(".", $file);
                if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                    $array2[':' . self::trans($name[0]) . ':'] = '<img src="' . $set['homeurl'] . '/' . $path . $file . '" alt="" />';
                    $array3[':' . $name[0] . ':'] = '<img src="' . $set['homeurl'] . '/' . $path . $file . '" alt="" />';
                    ++$count;
                }
            }
            // Обрабатываем смайлы в каталогах
            $array4 = array();
            $array5 = array();
            $cat = glob($rootpath . 'images/smileys/user/*', GLOB_ONLYDIR);
            $total = count($cat);
            for ($i = 0; $i < $total; $i++) {
                $dir = opendir($cat[$i]);
                while (($file = readdir($dir)) !== false) {
                    $name = explode(".", $file);
                    if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                        $path = str_replace('..', $set['homeurl'], $cat[$i]);
                        $array4[':' . self::trans($name[0]) . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
                        $array5[':' . $name[0] . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
                        ++$count;
                    }
                }
                closedir($dir);
            }
            $smileys = serialize(array_merge($array1, $array4, $array5));
            $smileys_adm = serialize(array_merge($array2, $array3));
            // Записываем в файл Кэша
            if (($fp = fopen($rootpath . 'files/cache/smileys_cache.dat', 'w')) !== false) {
                fputs($fp, $smileys . "\r\n" . $smileys_adm);
                fclose($fp);
                return $count;
            } else {
                return false;
            }
        } else {
            // Выдаем кэшированные смайлы
            if (file_exists($rootpath . 'files/cache/smileys_cache.dat')) {
                $file = file($rootpath . 'files/cache/smileys_cache.dat');
                $smileys = unserialize($file[0]);
                if ($adm)
                    $smileys = array_merge($smileys, unserialize($file[1]));
                return strtr($str, $smileys);
            } else {
                return $str;
            }
        }
    }

    /*
    -----------------------------------------------------------------
    Колличество зарегистрированных пользователей
    -----------------------------------------------------------------
    */
    static function stat_users() {
        global $realtime;
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0);
        $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . ($realtime - 86400) . "'"), 0);
        if ($res > 0)
            $total .= '&#160;/&#160;<span class="red">+' . $res . '</span>';
        return $total;
    }

    /*
    -----------------------------------------------------------------
    Статистика загрузок
    -----------------------------------------------------------------
    */
    static function stat_download() {
        global $realtime;
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file'"), 0);
        $old = $realtime - (3 * 24 * 3600);
        $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . $old . "' AND `type` = 'file'"), 0);
        if ($new > 0)
            $total .= '&#160;/&#160;<span class="red"><a href="/download/?act=new">+' . $new . '</a></span>';
        return $total;
    }

    /*
    -----------------------------------------------------------------
    Статистика Форума
    -----------------------------------------------------------------
    */
    static function stat_forum() {
        global $user_id, $rights, $set;
        $total_thm = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'" . ($rights >= 7 ? "" : " AND `close` != '1'")), 0);
        $total_msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'" . ($rights >= 7 ? "" : " AND `close` != '1'")), 0);
        $out = $total_thm . '&#160;/&#160;' . $total_msg . '';
        if ($user_id) {
            $new = self::forum_new();
            if ($new)
                $out .= '&#160;/&#160;<span class="red"><a href="' . $set['homeurl'] . '/forum/index.php?act=new">+' . $new . '</a></span>';
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Статистика галлереи
    -----------------------------------------------------------------
    $mod = 1    будет выдавать только колличество новых картинок
    -----------------------------------------------------------------
    */
    static function stat_gallery($mod = 0) {
        global $realtime;
        $old = $realtime - (3 * 24 * 3600);
        $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `time` > '" . $old . "' AND `type` = 'ft'"), 0);

        if ($mod == 0) {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `type` = 'ft'"), 0);
            $out = $total;
            if ($new > 0)
                $out .= '&#160;/&#160;<span class="red"><a href="/gallery/index.php?act=new">+' . $new . '</a></span>';
        } else {
            $out = $new;
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Статистика гостевой
    -----------------------------------------------------------------
    $mod = 1    колличество новых в гостевой
    $mod = 2    колличество новых в Админ-Клубе
    -----------------------------------------------------------------
    */
    static function stat_guestbook($mod = 0) {
        global $realtime, $rights;
        $count = 0;
        switch ($mod) {
            case 1:
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . ($realtime - 86400) . "'"), 0);
                break;

            case 2:
                if ($rights >= 1)
                    $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time` > '" . ($realtime - 86400) . "'"), 0);
                break;

            default:
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . ($realtime - 86400) . "'"), 0);
                if ($rights >= 1) {
                    $req = mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "'");
                    $count = $count . '&#160;/&#160;<span class="red"><a href="guestbook/index.php?act=ga&amp;do=set">' . mysql_result($req, 0) . '</a></span>';
                }
        }
        return $count;
    }

    /*
    -----------------------------------------------------------------
    Вывод коэффициента сжатия Zlib
    -----------------------------------------------------------------
    */
    static function stat_gzip() {
        global $set, $lng;

        if ($set['gzip']) {
            $Contents = ob_get_contents();
            $gzib_file = strlen($Contents);
            $gzib_file_out = strlen(gzcompress($Contents, 9));
            $gzib_pro = round(100 - (100 / ($gzib_file / $gzib_file_out)), 1);
            echo '<div>' . $lng['gzip_on'] . ' (' . $gzib_pro . '%)</div>';
        } else {
            echo '<div>' . $lng['gzip_off'] . '</div>';
        }
    }

    /*
    -----------------------------------------------------------------
    Статистика библиотеки
    -----------------------------------------------------------------
    */
    static function stat_library() {
        global $realtime, $rights, $set;
        $countf = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '1'"), 0);
        $old = $realtime - (3 * 24 * 3600);
        $countf1 = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . $old . "' AND `type` = 'bk' AND `moder` = '1'"), 0);
        $out = $countf;
        if ($countf1 > 0)
            $out = $out . '&#160;/&#160;<span class="red"><a href="/library/index.php?act=new">+' . $countf1 . '</a></span>';
        $countm = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'"), 0);

        if (($rights == 5 || $rights >= 6) && $countm > 0)
            $out = $out . "/<a href='" . $set['homeurl'] . "/library/index.php?act=moder'><font color='#FF0000'> M:$countm</font></a>";
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Дата последней новости
    -----------------------------------------------------------------
    */
    static function stat_news() {
        //TODO: Разобраться, нужна ли функция, если нет, то удалить
        global $set_user;
        $req = mysql_query("SELECT `time` FROM `news` ORDER BY `time` DESC LIMIT 1");
        if (mysql_num_rows($req)) {
            $res = mysql_fetch_array($req);
            return date("H:i/d.m.y", $res['time'] + $set_user['sdvig'] * 3600);
        } else {
            return false;
        }
    }

    /*
    -----------------------------------------------------------------
    Счетчик посетителей онлайн
    -----------------------------------------------------------------
    */
    static function stat_online() {
        global $realtime, $user_id, $lng, $set;
        $users = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `lastdate` > '" . ($realtime - 300) . "'"), 0);
        $guests = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_guests` WHERE `lastdate` > '" . ($realtime - 300) . "'"), 0);
        return ($user_id || $set['active'] ? '<a href="' . $set['homeurl'] . '/users/index.php?act=online">' . $lng['online'] . ': ' . $users . ' / ' . $guests . '</a>' : $lng['online'] . ': ' . $users . ' / ' . $guests);
    }

    /*
    -----------------------------------------------------------------
    Счетсик времени, проведенного на сайте
    -----------------------------------------------------------------
    */
    static function stat_timeonline() {
        global $realtime, $datauser, $user_id, $lng;
        if ($user_id)
            echo '<div>' . $lng['online'] . ': ' . gmdate('H:i:s', ($realtime - $datauser['sestime'])) . '</div>';
    }

    /*
    -----------------------------------------------------------------
    Функция пересчета на дни, или часы
    -----------------------------------------------------------------
    */
    static function timecount($var) {
        global $lng;
        if ($var < 0)
            $var = 0;
        $day = ceil($var / 86400);
        if ($var > 345600) {
            $str = $day . ' ' . $lng['timecount_days'];
        } elseif ($var >= 172800) {
            $str = $day . ' ' . $lng['timecount_days_r'];
        } elseif ($var >= 86400) {
            $str = '1 ' . $lng['timecount_day'];
        } else {
            $str = date('G:i', $var);
        }
        return $str;
    }

    /*
    -----------------------------------------------------------------
    Транслитерация текста
    -----------------------------------------------------------------
    */
    static function trans($str) {
        $str = strtr($str, array(
            'a' => 'а',
            'b' => 'б',
            'v' => 'в',
            'g' => 'г',
            'd' => 'д',
            'e' => 'е',
            'yo' => 'ё',
            'zh' => 'ж',
            'z' => 'з',
            'i' => 'и',
            'j' => 'й',
            'k' => 'к',
            'l' => 'л',
            'm' => 'м',
            'n' => 'н',
            'o' => 'о',
            'p' => 'п',
            'r' => 'р',
            's' => 'с',
            't' => 'т',
            'u' => 'у',
            'f' => 'ф',
            'h' => 'х',
            'c' => 'ц',
            'ch' => 'ч',
            'w' => 'ш',
            'sh' => 'щ',
            'q' => 'ъ',
            'y' => 'ы',
            'x' => 'э',
            'yu' => 'ю',
            'ya' => 'я',
            'A' => 'А',
            'B' => 'Б',
            'V' => 'В',
            'G' => 'Г',
            'D' => 'Д',
            'E' => 'Е',
            'YO' => 'Ё',
            'ZH' => 'Ж',
            'Z' => 'З',
            'I' => 'И',
            'J' => 'Й',
            'K' => 'К',
            'L' => 'Л',
            'M' => 'М',
            'N' => 'Н',
            'O' => 'О',
            'P' => 'П',
            'R' => 'Р',
            'S' => 'С',
            'T' => 'Т',
            'U' => 'У',
            'F' => 'Ф',
            'H' => 'Х',
            'C' => 'Ц',
            'CH' => 'Ч',
            'W' => 'Ш',
            'SH' => 'Щ',
            'Q' => 'Ъ',
            'Y' => 'Ы',
            'X' => 'Э',
            'YU' => 'Ю',
            'YA' => 'Я'
        ));
        return $str;
    }

    /*
    -----------------------------------------------------------------
    Проверка, мобильный ли браузер?
    -----------------------------------------------------------------
    За основу взята функция от ManHunter http://www.manhunter.ru
    -----------------------------------------------------------------
    */
    static function mobile_detect() {
        if (isset($_SESSION['is_mobile'])) {
            return $_SESSION['is_mobile'];
        }
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $accept = strtolower($_SERVER['HTTP_ACCEPT']);
        if ((strpos($accept, 'text/vnd.wap.wml') !== false) || (strpos($accept, 'application/vnd.wap.xhtml+xml') !== false)) {
            $_SESSION['is_mobile'] = 1;
            return 1;
        }
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            $_SESSION['is_mobile'] = 2;
            return 2;
        }
        if (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|' .
                       'wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|' .
                       'lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|' .
                       'mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|' .
                       'm881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|' .
                       'r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|' .
                       'i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|' .
                       'htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|' .
                       'sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|' .
                       'p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|' .
                       '_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|' .
                       's800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|' .
                       'd736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |' .
                       'sonyericsson|samsung|240x|x320vx10|nokia|sony cmd|motorola|' .
                       'up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|' .
                       'pocket|kindle|mobile|psp|treo)/', $user_agent)) {
            $_SESSION['is_mobile'] = 3;
            return 3;
        }

        if (in_array(substr($user_agent, 0, 4), Array(
            "1207",
            "3gso",
            "4thp",
            "501i",
            "502i",
            "503i",
            "504i",
            "505i",
            "506i",
            "6310",
            "6590",
            "770s",
            "802s",
            "a wa",
            "abac",
            "acer",
            "acoo",
            "acs-",
            "aiko",
            "airn",
            "alav",
            "alca",
            "alco",
            "amoi",
            "anex",
            "anny",
            "anyw",
            "aptu",
            "arch",
            "argo",
            "aste",
            "asus",
            "attw",
            "au-m",
            "audi",
            "aur ",
            "aus ",
            "avan",
            "beck",
            "bell",
            "benq",
            "bilb",
            "bird",
            "blac",
            "blaz",
            "brew",
            "brvw",
            "bumb",
            "bw-n",
            "bw-u",
            "c55/",
            "capi",
            "ccwa",
            "cdm-",
            "cell",
            "chtm",
            "cldc",
            "cmd-",
            "cond",
            "craw",
            "dait",
            "dall",
            "dang",
            "dbte",
            "dc-s",
            "devi",
            "dica",
            "dmob",
            "doco",
            "dopo",
            "ds-d",
            "ds12",
            "el49",
            "elai",
            "eml2",
            "emul",
            "eric",
            "erk0",
            "esl8",
            "ez40",
            "ez60",
            "ez70",
            "ezos",
            "ezwa",
            "ezze",
            "fake",
            "fetc",
            "fly-",
            "fly_",
            "g-mo",
            "g1 u",
            "g560",
            "gene",
            "gf-5",
            "go.w",
            "good",
            "grad",
            "grun",
            "haie",
            "hcit",
            "hd-m",
            "hd-p",
            "hd-t",
            "hei-",
            "hiba",
            "hipt",
            "hita",
            "hp i",
            "hpip",
            "hs-c",
            "htc ",
            "htc-",
            "htc_",
            "htca",
            "htcg",
            "htcp",
            "htcs",
            "htct",
            "http",
            "huaw",
            "hutc",
            "i-20",
            "i-go",
            "i-ma",
            "i230",
            "iac",
            "iac-",
            "iac/",
            "ibro",
            "idea",
            "ig01",
            "ikom",
            "im1k",
            "inno",
            "ipaq",
            "iris",
            "jata",
            "java",
            "jbro",
            "jemu",
            "jigs",
            "kddi",
            "keji",
            "kgt",
            "kgt/",
            "klon",
            "kpt ",
            "kwc-",
            "kyoc",
            "kyok",
            "leno",
            "lexi",
            "lg g",
            "lg-a",
            "lg-b",
            "lg-c",
            "lg-d",
            "lg-f",
            "lg-g",
            "lg-k",
            "lg-l",
            "lg-m",
            "lg-o",
            "lg-p",
            "lg-s",
            "lg-t",
            "lg-u",
            "lg-w",
            "lg/k",
            "lg/l",
            "lg/u",
            "lg50",
            "lg54",
            "lge-",
            "lge/",
            "libw",
            "lynx",
            "m-cr",
            "m1-w",
            "m3ga",
            "m50/",
            "mate",
            "maui",
            "maxo",
            "mc01",
            "mc21",
            "mcca",
            "medi",
            "merc",
            "meri",
            "midp",
            "mio8",
            "mioa",
            "mits",
            "mmef",
            "mo01",
            "mo02",
            "mobi",
            "mode",
            "modo",
            "mot ",
            "mot-",
            "moto",
            "motv",
            "mozz",
            "mt50",
            "mtp1",
            "mtv ",
            "mwbp",
            "mywa",
            "n100",
            "n101",
            "n102",
            "n202",
            "n203",
            "n300",
            "n302",
            "n500",
            "n502",
            "n505",
            "n700",
            "n701",
            "n710",
            "nec-",
            "nem-",
            "neon",
            "netf",
            "newg",
            "newt",
            "nok6",
            "noki",
            "nzph",
            "o2 x",
            "o2-x",
            "o2im",
            "opti",
            "opwv",
            "oran",
            "owg1",
            "p800",
            "palm",
            "pana",
            "pand",
            "pant",
            "pdxg",
            "pg-1",
            "pg-2",
            "pg-3",
            "pg-6",
            "pg-8",
            "pg-c",
            "pg13",
            "phil",
            "pire",
            "play",
            "pluc",
            "pn-2",
            "pock",
            "port",
            "pose",
            "prox",
            "psio",
            "pt-g",
            "qa-a",
            "qc-2",
            "qc-3",
            "qc-5",
            "qc-7",
            "qc07",
            "qc12",
            "qc21",
            "qc32",
            "qc60",
            "qci-",
            "qtek",
            "qwap",
            "r380",
            "r600",
            "raks",
            "rim9",
            "rove",
            "rozo",
            "s55/",
            "sage",
            "sama",
            "samm",
            "sams",
            "sany",
            "sava",
            "sc01",
            "sch-",
            "scoo",
            "scp-",
            "sdk/",
            "se47",
            "sec-",
            "sec0",
            "sec1",
            "semc",
            "send",
            "seri",
            "sgh-",
            "shar",
            "sie-",
            "siem",
            "sk-0",
            "sl45",
            "slid",
            "smal",
            "smar",
            "smb3",
            "smit",
            "smt5",
            "soft",
            "sony",
            "sp01",
            "sph-",
            "spv ",
            "spv-",
            "sy01",
            "symb",
            "t-mo",
            "t218",
            "t250",
            "t600",
            "t610",
            "t618",
            "tagt",
            "talk",
            "tcl-",
            "tdg-",
            "teli",
            "telm",
            "tim-",
            "topl",
            "tosh",
            "treo",
            "ts70",
            "tsm-",
            "tsm3",
            "tsm5",
            "tx-9",
            "up.b",
            "upg1",
            "upsi",
            "utst",
            "v400",
            "v750",
            "veri",
            "virg",
            "vite",
            "vk-v",
            "vk40",
            "vk50",
            "vk52",
            "vk53",
            "vm40",
            "voda",
            "vulc",
            "vx52",
            "vx53",
            "vx60",
            "vx61",
            "vx70",
            "vx80",
            "vx81",
            "vx83",
            "vx85",
            "vx98",
            "w3c ",
            "w3c-",
            "wap-",
            "wapa",
            "wapi",
            "wapj",
            "wapm",
            "wapp",
            "wapr",
            "waps",
            "wapt",
            "wapu",
            "wapv",
            "wapy",
            "webc",
            "whit",
            "wig ",
            "winc",
            "winw",
            "wmlb",
            "wonu",
            "x700",
            "xda-",
            "xda2",
            "xdag",
            "yas-",
            "your",
            "zeto",
            "zte-"
        ))) {
            $_SESSION['is_mobile'] = 4;
            return 4;
        }
        $_SESSION['is_mobile'] = 0;
        return false;
    }
}

?>