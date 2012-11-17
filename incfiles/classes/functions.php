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

class functions extends core
{
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
    public static function antiflood()
    {
        $default = array(
            'mode' => 2,
            'day' => 10,
            'night' => 30,
            'dayfrom' => 10,
            'dayto' => 22
        );
        $af = isset(self::$system_set['antiflood']) ? unserialize(self::$system_set['antiflood']) : $default;
        switch ($af['mode']) {
            case 1:
                // Адаптивный режим
                $adm = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > " . (time() - 300)), 0);
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
                $c_time = date('G', time());
                $limit = $c_time > $af['day'] && $c_time < $af['night'] ? $af['day'] : $af['night'];
        }
        if (self::$user_rights > 0)
            $limit = 4; // Для Администрации задаем лимит в 4 секунды
        $flood = self::$user_data['lastpost'] + $limit - time();
        if ($flood > 0)
            return $flood;
        else
            return FALSE;
    }

    /*
    -----------------------------------------------------------------
    Маскировка ссылок в тексте
    -----------------------------------------------------------------
    */
    public static function antilink($var)
    {
        $var = preg_replace('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', '###', $var);
        $replace = array(
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
        );
        return strtr($var, $replace);
    }

    /*
     * Фильтрация строк
     */
    public static function checkin($str){
        if (function_exists('iconv')) {
            $str = iconv("UTF-8", "UTF-8", $str);
        }

        // Удаляем лишние знаки препинания
        $str = preg_replace('#(\.|\?|!|\(|\)){3,}#', '\1\1\1', $str);

        // Фильтруем символы
        $str = nl2br($str);
        $str = preg_replace('!\p{C}!u', '', $str);
        $str = str_replace('<br />', "\n", $str);

        // Удаляем лишние пробелы
        $str = preg_replace('# {2,}#', ' ', $str);

        // Удаляем более 2-х переносов строк подряд
        $str = preg_replace("/(\n)+(\n)/i", "\n\n", $str);

        return trim($str);
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
    public static function checkout($str, $br = 0, $tags = 0)
    {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
        if ($br == 1) {
            // Вставляем переносы строк
            $str = nl2br($str);
        } elseif ($br == 2) {
            $str = str_replace("\r\n", ' ', $str);
        }
        if ($tags == 1) {
            $str = bbcode::tags($str);
        } elseif ($tags == 2) {
            $str = bbcode::notags($str);
        }

        return trim($str);
    }

    /*
    -----------------------------------------------------------------
    Показ различных счетчиков внизу страницы
    -----------------------------------------------------------------
    */
    public static function display_counters()
    {
        global $headmod;
        $req = mysql_query("SELECT * FROM `cms_counters` WHERE `switch` = '1' ORDER BY `sort` ASC");
        if (mysql_num_rows($req) > 0) {
            while (($res = mysql_fetch_array($req)) !== FALSE) {
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
    Показываем дату с учетом сдвига времени
    -----------------------------------------------------------------
    */
    public static function display_date($var)
    {
        $shift = (self::$system_set['timeshift'] + self::$user_set['timeshift']) * 3600;
        if (date('Y', $var) == date('Y', time())) {
            if (date('z', $var + $shift) == date('z', time() + $shift))
                return self::$lng['today'] . ', ' . date("H:i", $var + $shift);
            if (date('z', $var + $shift) == date('z', time() + $shift) - 1)
                return self::$lng['yesterday'] . ', ' . date("H:i", $var + $shift);
        }
        return date("d.m.Y / H:i", $var + $shift);
    }

    /*
    -----------------------------------------------------------------
    Сообщения об ошибках
    -----------------------------------------------------------------
    */
    public static function display_error($error = NULL, $link = NULL)
    {
        if (!empty($error)) {
            return '<div class="rmenu"><p><b>' . self::$lng['error'] . '!</b><br />' .
                (is_array($error) ? implode('<br />', $error) : $error) . '</p>' .
                (!empty($link) ? '<p>' . $link . '</p>' : '') . '</div>';
        } else {
            return FALSE;
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
    public static function display_menu($val = array(), $delimiter = ' | ', $end_space = '')
    {
        return implode($delimiter, array_diff($val, array(''))) . $end_space;
    }

    /*
    -----------------------------------------------------------------
    Постраничная навигация
    -----------------------------------------------------------------
    За основу взята доработанная функция от форума SMF 2.x.x
    -----------------------------------------------------------------
    */
    public static function display_pagination($url, $start, $total, $kmess)
    {
        $neighbors = 2;
        if ($start >= $total)
            $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
        else
            $start = max(0, (int)$start - ((int)$start % (int)$kmess));
        $base_link = '<a class="pagenav" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a>';
        $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '&lt;&lt;');
        if ($start > $kmess * $neighbors)
            $out[] = sprintf($base_link, 1, '1');
        if ($start > $kmess * ($neighbors + 1))
            $out[] = '<span style="font-weight: bold;">...</span>';
        for ($nCont = $neighbors; $nCont >= 1; $nCont--)
            if ($start >= $kmess * $nCont) {
                $tmpStart = $start - $kmess * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
            }
        $out[] = '<span class="currentpage"><b>' . ($start / $kmess + 1) . '</b></span>';
        $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
        for ($nCont = 1; $nCont <= $neighbors; $nCont++)
            if ($start + $kmess * $nCont <= $tmpMaxPages) {
                $tmpStart = $start + $kmess * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
            }
        if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages)
            $out[] = '<span style="font-weight: bold;">...</span>';
        if ($start + $kmess * $neighbors < $tmpMaxPages)
            $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
        if ($start + $kmess < $total) {
            $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
            $out[] = sprintf($base_link, $display_page, '&gt;&gt;');
        }
        return implode(' ', $out);
    }

    /*
    -----------------------------------------------------------------
    Показываем местоположение пользователя
    -----------------------------------------------------------------
    */
    public static function display_place($user_id = '', $place = '')
    {
        global $headmod;
        $place = explode(",", $place);
        $placelist = parent::load_lng('places');
        if (array_key_exists($place[0], $placelist)) {
            if ($place[0] == 'profile') {
                if ($place[1] == $user_id) {
                    return '<a href="' . self::$system_set['homeurl'] . '/users/profile.php?user=' . $place[1] . '">' . $placelist['profile_personal'] . '</a>';
                } else {
                    $user = self::get_user($place[1]);
                    return $placelist['profile'] . ': <a href="' . self::$system_set['homeurl'] . '/users/profile.php?user=' . $user['id'] . '">' . $user['name'] . '</a>';
                }
            }
            elseif ($place[0] == 'online' && isset($headmod) && $headmod == 'online') return $placelist['here'];
            else return str_replace('#home#', self::$system_set['homeurl'], $placelist[$place[0]]);
        }
        else return '<a href="' . self::$system_set['homeurl'] . '/index.php">' . $placelist['homepage'] . '</a>';
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
    public static function display_user($user = FALSE, $arg = FALSE)
    {
        global $rootpath, $mod;
        $out = FALSE;

        if (!$user['id']) {
            $out = '<b>' . self::$lng['guest'] . '</b>';
            if (!empty($user['name']))
                $out .= ': ' . $user['name'];
            if (!empty($arg['header']))
                $out .= ' ' . $arg['header'];
        } else {
            if (self::$user_set['avatar']) {
                $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';
                if (file_exists(($rootpath . 'files/users/avatar/' . $user['id'] . '.png')))
                    $out .= '<img src="' . self::$system_set['homeurl'] . '/files/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="" />&#160;';
                else
                    $out .= '<img src="' . self::$system_set['homeurl'] . '/images/empty.png" width="32" height="32" alt="" />&#160;';
                $out .= '</td><td>';
            }
            if ($user['sex'])
                $out .= '<img src="' . self::$system_set['homeurl'] . '/theme/' . self::$user_set['skin'] . '/images/' . ($user['sex'] == 'm' ? 'm' : 'w') . ($user['datereg'] > time() - 86400 ? '_new' : '')
                    . '.png" width="16" height="16" align="middle" alt="' . ($user['sex'] == 'm' ? 'М' : 'Ж') . '" />&#160;';
            else
                $out .= '<img src="' . self::$system_set['homeurl'] . '/images/del.png" width="12" height="12" align="middle" />&#160;';
            $out .= !self::$user_id || self::$user_id == $user['id'] ? '<b>' . $user['name'] . '</b>' : '<a href="' . self::$system_set['homeurl'] . '/users/profile.php?user=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
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
            $out .= (time() > $user['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');
            if (!empty($arg['header']))
                $out .= ' ' . $arg['header'];
            if (!isset($arg['stshide']) && !empty($user['status']))
                $out .= '<div class="status"><img src="' . self::$system_set['homeurl'] . '/theme/' . self::$user_set['skin'] . '/images/label.png" alt="" align="middle" />&#160;' . $user['status'] . '</div>';
            if (self::$user_set['avatar'])
                $out .= '</td></tr></table>';
        }
        if (isset($arg['body']))
            $out .= '<div>' . $arg['body'] . '</div>';
        $ipinf = !isset($arg['iphide']) && (self::$user_rights || ($user['id'] && $user['id'] == self::$user_id)) ? 1 : 0;
        $lastvisit = time() > $user['lastdate'] + 300 && isset($arg['lastvisit']) ? self::display_date($user['lastdate']) : FALSE;
        if ($ipinf || $lastvisit || isset($arg['sub']) && !empty($arg['sub']) || isset($arg['footer'])) {
            $out .= '<div class="sub">';
            if (isset($arg['sub']))
                $out .= '<div>' . $arg['sub'] . '</div>';
            if ($lastvisit)
                $out .= '<div><span class="gray">' . self::$lng['last_visit'] . ':</span> ' . $lastvisit . '</div>';
            $iphist = '';
            if ($ipinf) {
                $out .= '<div><span class="gray">' . self::$lng['browser'] . ':</span> ' . $user['browser'] . '</div>' .
                    '<div><span class="gray">' . self::$lng['ip_address'] . ':</span> ';
                $hist = $mod == 'history' ? '&amp;mod=history' : '';
                $ip = long2ip($user['ip']);
                if (self::$user_rights && isset($user['ip_via_proxy']) && $user['ip_via_proxy']) {
                    $out .= '<b class="red"><a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=search_ip&amp;ip=' . $ip . $hist . '">' . $ip . '</a></b>';
                    $out .= '&#160;[<a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                    $out .= ' / ';
                    $out .= '<a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=search_ip&amp;ip=' . long2ip($user['ip_via_proxy']) . $hist . '">' . long2ip($user['ip_via_proxy']) . '</a>';
                    $out .= '&#160;[<a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=ip_whois&amp;ip=' . long2ip($user['ip_via_proxy']) . '">?</a>]';
                } elseif (self::$user_rights) {
                    $out .= '<a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=search_ip&amp;ip=' . $ip . $hist . '">' . $ip .'</a>';
                    $out .= '&#160;[<a href="' . self::$system_set['homeurl'] . '/' . self::$system_set['admp'] . '/index.php?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                } else {
                    $out .= $ip . $iphist;
                }
                if (isset($arg['iphist'])) {
                    $iptotal = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'"), 0);
                    $out .= '<div><span class="gray">' . self::$lng['ip_history'] . ':</span> <a href="' . self::$system_set['homeurl'] . '/users/profile.php?act=ip&amp;user=' . $user['id'] . '">[' . $iptotal . ']</a></div>';
                }
                $out .= '</div>';
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
    public static function format($name)
    {
        $f1 = strrpos($name, ".");
        $f2 = substr($name, $f1 + 1, 999);
        $fname = strtolower($f2);
        return $fname;
    }

    /*
    -----------------------------------------------------------------
    Получаем данные пользователя
    -----------------------------------------------------------------
    */
    public static function get_user($id = FALSE)
    {
        if ($id && $id != self::$user_id) {
            $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id'");
            if (mysql_num_rows($req)) {
                return mysql_fetch_assoc($req);
            } else {
                return FALSE;
            }
        } else {
            return self::$user_data;
        }
    }

    /*
    -----------------------------------------------------------------
    Транслитерация с Русского в латиницу
    -----------------------------------------------------------------
    */
    public static function rus_lat($str)
    {
        $replace = array(
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
        );
        return strtr($str, $replace);
    }

    /*
    -----------------------------------------------------------------
    Обработка смайлов
    -----------------------------------------------------------------
    */
    public static function smileys($str, $adm = FALSE)
    {
        global $rootpath;
        static $smileys_cache = array();
        if (empty($smileys_cache)) {
            $file = $rootpath . 'files/cache/smileys.dat';
            if (file_exists($file) && ($smileys = file_get_contents($file)) !== FALSE) {
                $smileys_cache = unserialize($smileys);
                return strtr($str, ($adm ? array_merge($smileys_cache['usr'], $smileys_cache['adm']) : $smileys_cache['usr']));
            } else {
                return $str;
            }
        } else {
            return strtr($str, ($adm ? array_merge($smileys_cache['usr'], $smileys_cache['adm']) : $smileys_cache['usr']));
        }
    }

    /*
    -----------------------------------------------------------------
    Функция пересчета на дни, или часы
    -----------------------------------------------------------------
    */
    public static function timecount($var)
    {
        global $lng;
        if ($var < 0) $var = 0;
        $day = ceil($var / 86400);
        if ($var > 345600) return $day . ' ' . $lng['timecount_days'];
        if ($var >= 172800) return $day . ' ' . $lng['timecount_days_r'];
        if ($var >= 86400) return '1 ' . $lng['timecount_day'];
        return date("G:i:s", mktime(0, 0, $var));
    }

    /*
    -----------------------------------------------------------------
    Транслитерация текста
    -----------------------------------------------------------------
    */
    public static function trans($str)
    {
        $replace = array(
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
        );
        return strtr($str, $replace);
    }

    /*
    -----------------------------------------------------------------
    Старая функция проверки переменных.
    В новых разработках не применять!
    Вместо данной функции использовать checkin()
    -----------------------------------------------------------------
    */
    public static function check($str)
    {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
        $str = self::checkin($str);
        $str = nl2br($str);
        $str = mysql_real_escape_string($str);
        return $str;
    }
}