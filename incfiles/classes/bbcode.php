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

class bbcode extends core
{
    private static $code_id;
    private static $code_index;
    private static $code_parts;
    /*
    -----------------------------------------------------------------
    Обработка тэгов и ссылок
    -----------------------------------------------------------------
    */
    public static function tags($var)
    {
        $var = self::highlight_code($var);           // Подсветка кода
        $var = self::parse_time($var);               // Обработка тэга времени
        $var = self::highlight_bb($var);               // Обработка ссылок
        $var = self::highlight_url($var);            // Обработка ссылок
        $var = self::highlight_bbcode_url($var);       // Обработка ссылок в BBcode
        $var = self::process_code($var);
        return $var;
    }

    /**
     * Обработка тэга [time]
     *
     * @param string $var
     * @return string
     */
    private static function parse_time($var)
    {
        return preg_replace_callback(
            '#\[time\](.+?)\[\/time\]#s',
            function ($matches) {
                $shift = (core::$system_set['timeshift'] + core::$user_set['timeshift']) * 3600;
                if (($out = strtotime($matches[1])) !== false) {
                    return date("d.m.Y / H:i", $out + $shift);
                } else {
                    return $matches[1];
                }
            },
            $var
        );
    }

    /**
     * Парсинг ссылок
     * За основу взята доработанная функция от форума phpBB 3.x.x
     *
     * @param $text
     * @return mixed
     */
    public static function highlight_url($text)
    {
        if (!function_exists('url_callback')) {
            function url_callback($type, $whitespace, $url, $relative_url)
            {
                $orig_url = $url;
                $orig_relative = $relative_url;
                $url = htmlspecialchars_decode($url);
                $relative_url = htmlspecialchars_decode($relative_url);
                $text = '';
                $chars = array('<', '>', '"');
                $split = false;
                foreach ($chars as $char) {
                    $next_split = strpos($url, $char);
                    if ($next_split !== false) {
                        $split = ($split !== false) ? min($split, $next_split) : $next_split;
                    }
                }
                if ($split !== false) {
                    $url = substr($url, 0, $split);
                    $relative_url = '';
                } else {
                    if ($relative_url) {
                        $split = false;
                        foreach ($chars as $char) {
                            $next_split = strpos($relative_url, $char);
                            if ($next_split !== false) {
                                $split = ($split !== false) ? min($split, $next_split) : $next_split;
                            }
                        }
                        if ($split !== false) {
                            $relative_url = substr($relative_url, 0, $split);
                        }
                    }
                }
                $last_char = ($relative_url) ? $relative_url[strlen($relative_url) - 1] : $url[strlen($url) - 1];
                switch ($last_char) {
                    case '.':
                    case '?':
                    case '!':
                    case ':':
                    case ',':
                        $append = $last_char;
                        if ($relative_url) {
                            $relative_url = substr($relative_url, 0, -1);
                        } else {
                            $url = substr($url, 0, -1);
                        }
                        break;

                    default:
                        $append = '';
                        break;
                }
                $short_url = (mb_strlen($url) > 40) ? mb_substr($url, 0, 30) . ' ... ' . mb_substr($url, -5) : $url;
                switch ($type) {
                    case 1:
                        $relative_url = preg_replace('/[&?]sid=[0-9a-f]{32}$/', '', preg_replace('/([&?])sid=[0-9a-f]{32}&/', '$1', $relative_url));
                        $url = $url . '/' . $relative_url;
                        $text = $relative_url;
                        if (!$relative_url) {
                            return $whitespace . $orig_url . '/' . $orig_relative;
                        }
                        break;

                    case 2:
                        $text = $short_url;
                        if (!isset(core::$user_set['direct_url']) || !core::$user_set['direct_url']) {
                            $url = core::$system_set['homeurl'] . '/go.php?url=' . rawurlencode($url);
                        }
                        break;

                    case 4:
                        $text = $short_url;
                        $url = 'mailto:' . $url;
                        break;
                }
                $url = htmlspecialchars($url);
                $text = htmlspecialchars($text);
                $append = htmlspecialchars($append);

                return $whitespace . '<a href="' . $url . '">' . $text . '</a>' . $append;
            }
        }

        // Обработка внутренних ссылок
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])(' . preg_quote(core::$system_set['homeurl'],
                '#') . ')/((?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return url_callback(1, $matches[1], $matches[2], $matches[3]);
            },
            $text
        );

        // Обработка обычных ссылок типа xxxx://aaaaa.bbb.cccc. ...
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])([a-z][a-z\d+]*:/{2}(?:(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-zа-яё0-9.]+:[a-zа-яё0-9.]+:[a-zа-яё0-9.:]+\])(?::\d*)?(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return url_callback(2, $matches[1], $matches[2], '');
            },
            $text
        );

        return $text;
    }

    /*
    -----------------------------------------------------------------
    Удаление bbCode из текста
    -----------------------------------------------------------------
    */
    static function notags($var = '')
    {
        $var = preg_replace('#\[color=(.+?)\](.+?)\[/color]#si', '$2', $var);
        $var = preg_replace('#\[code=(.+?)\](.+?)\[/code]#si', '$2', $var);
        $var = preg_replace('!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', '$2', $var);
        $var = preg_replace('#\[spoiler=(.+?)\]#si', '$2', $var);
        $replace = array(
            '[small]' => '',
            '[/small]' => '',
            '[big]' => '',
            '[/big]' => '',
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
            '[quote]' => '',
            '[/quote]' => '',
            '[php]' => '',
            '[/php]' => '',
            '[c]' => '',
            '[/c]' => '',
            '[*]' => '',
            '[/*]' => ''
        );

        return strtr($var, $replace);
    }

    /*
    -----------------------------------------------------------------
    Подсветка кода
    -----------------------------------------------------------------
    */
    private static function highlight_code($var)
    {
        self::$code_id = uniqid();
        self::$code_index = 0;
        self::$code_parts = array();
        $var = preg_replace_callback('#\[php\](.+?)\[\/php\]#s', 'self::phpCodeCallback', $var);
        $var = preg_replace_callback('#\[code=(.+?)\](.+?)\[\/code]#is', 'self::codeCallback', $var);

        return $var;
    }

    private static $geshi;

    private static function phpCodeCallback($code)
    {
        return self::codeCallback(array(1 => 'php', 2 => $code[1]));
    }

    private static function codeCallback($code)
    {
        $parsers = array(
            'php'  => 'php',
            'css'  => 'css',
            'html' => 'html5',
            'js'   => 'javascript',
            'sql'  => 'sql',
            'xml'  => 'xml',
        );

        $parser = isset($code[1]) && isset($parsers[$code[1]]) ? $parsers[$code[1]] : 'php';

        if (null === self::$geshi) {
            require_once 'geshi.php';
            self::$geshi = new \GeSHi;
            self::$geshi->set_link_styles(GESHI_LINK, 'text-decoration: none');
            self::$geshi->set_link_target('_blank');
            self::$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
            self::$geshi->set_line_style('background: rgba(255, 255, 255, 0.5)', 'background: rgba(255, 255, 255, 0.35)', false);
            self::$geshi->set_code_style('padding-left: 6px; white-space: pre-wrap');
        }

        self::$geshi->set_language($parser);
        $php = strtr($code[2], array('<br />' => ''));
        $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
        self::$geshi->set_source($php);
        self::$code_index++;
        self::$code_parts[self::$code_index] = array(
            'type'   => $parser, // can reuse if needed
            'source' => self::$geshi->parse_code()
        );

        return '[code|' . self::$code_id . ']' . self::$code_index . '[/code]';
    }

    private static function process_code($var)
    {
        $var = preg_replace_callback(
            '#\[code\|' . self::$code_id . '\](\d+)\[\/code\]#s',
            function ($code)
            {
                $part = self::$code_parts[$code[1]];
                unset(self::$code_parts[$code[1]]);
                return '<div class="phpcode" style="overflow-x: auto">' . $part['source'] . '</div>';
            },
            $var);

        return $var;
    }

    /*
    -----------------------------------------------------------------
    Обработка URL в тэгах BBcode
    -----------------------------------------------------------------
    */
    private static function highlight_bbcode_url($var)
    {
        if (!function_exists('process_url')) {
            function process_url($url)
            {
                $home = parse_url(core::$system_set['homeurl']);
                $tmp = parse_url($url[1]);
                if ($home['host'] == $tmp['host'] || isset(core::$user_set['direct_url']) && core::$user_set['direct_url']) {
                    if (mb_strpos($url[1], 'javascript') === 0) {
                        return '[url=' . $url[1] . ']' . $url[1] . '[/url]';
                    }
                    return '<a href="' . $url[1] . '">' . $url[2] . '</a>';
                } else {
                    return '<a href="' . core::$system_set['homeurl'] . '/go.php?url=' . urlencode(htmlspecialchars_decode($url[1])) . '">' . $url[2] . '</a>';
                }
            }
        }

        return preg_replace_callback('~\\[url=(.+?)\\](.+?)\\[/url\\]~', 'process_url', $var);
    }

    /*
    -----------------------------------------------------------------
    Обработка bbCode
    -----------------------------------------------------------------
    */
    private static function highlight_bb($var)
    {
        // Список поиска
        $search = array(
            '#\[b](.+?)\[/b]#is', // Жирный
            '#\[i](.+?)\[/i]#is', // Курсив
            '#\[u](.+?)\[/u]#is', // Подчеркнутый
            '#\[s](.+?)\[/s]#is', // Зачеркнутый
            '#\[small](.+?)\[/small]#is', // Маленький шрифт
            '#\[big](.+?)\[/big]#is', // Большой шрифт
            '#\[red](.+?)\[/red]#is', // Красный
            '#\[green](.+?)\[/green]#is', // Зеленый
            '#\[blue](.+?)\[/blue]#is', // Синий
            '!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/color]!is', // Цвет шрифта
            '!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', // Цвет фона
            '#\[(quote|c)](.+?)\[/(quote|c)]#is', // Цитата
            '#\[\*](.+?)\[/\*]#is', // Список
            '#\[spoiler=(.+?)](.+?)\[/spoiler]#is' // Спойлер
        );
        // Список замены
        $replace = array(
            '<span style="font-weight: bold">$1</span>',
            // Жирный
            '<span style="font-style:italic">$1</span>',
            // Курсив
            '<span style="text-decoration:underline">$1</span>',
            // Подчеркнутый
            '<span style="text-decoration:line-through">$1</span>',
            // Зачеркнутый
            '<span style="font-size:x-small">$1</span>',
            // Маленький шрифт
            '<span style="font-size:large">$1</span>',
            // Большой шрифт
            '<span style="color:red">$1</span>',
            // Красный
            '<span style="color:green">$1</span>',
            // Зеленый
            '<span style="color:blue">$1</span>',
            // Синий
            '<span style="color:$1">$2</span>',
            // Цвет шрифта
            '<span style="background-color:$1">$2</span>',
            // Цвет фона
            '<span class="quote" style="display:block">$2</span>',
            // Цитата
            '<span class="bblist">$1</span>',
            // Список
            '<div><div class="spoilerhead" style="cursor:pointer;" onclick="var _n=this.parentNode.getElementsByTagName(\'div\')[1];if(_n.style.display==\'none\'){_n.style.display=\'\';}else{_n.style.display=\'none\';}">$1 (+/-)</div><div class="spoilerbody" style="display:none">$2</div></div>'
            // Спойлер
        );

        return preg_replace($search, $replace, $var);
    }

    /*
    -----------------------------------------------------------------
    Панель кнопок bbCode (для компьютеров)
    -----------------------------------------------------------------
    */
    public static function auto_bb($form, $field)
    {
        $colors = array(
            'ffffff',
            'bcbcbc',
            '708090',
            '6c6c6c',
            '454545',
            'fcc9c9',
            'fe8c8c',
            'fe5e5e',
            'fd5b36',
            'f82e00',
            'ffe1c6',
            'ffc998',
            'fcad66',
            'ff9331',
            'ff810f',
            'd8ffe0',
            '92f9a7',
            '34ff5d',
            'b2fb82',
            '89f641',
            'b7e9ec',
            '56e5ed',
            '21cad3',
            '03939b',
            '039b80',
            'cac8e9',
            '9690ea',
            '6a60ec',
            '4866e7',
            '173bd3',
            'f3cafb',
            'e287f4',
            'c238dd',
            'a476af',
            'b53dd2'
        );
        $font_color = '';
        $bg_color = '';

        foreach ($colors as $value) {
            $font_color .= '<a href="javascript:tag(\'[color=#' . $value . ']\', \'[/color]\'); show_hide(\'color\');" style="background-color:#' . $value . ';"></a>';
            $bg_color .= '<a href="javascript:tag(\'[bg=#' . $value . ']\', \'[/bg]\'); show_hide(\'bg\');" style="background-color:#' . $value . ';"></a>';
        }

        // Смайлы
        $smileys = !empty(self::$user_data['smileys']) ? unserialize(self::$user_data['smileys']) : '';

        if (!empty($smileys)) {
            $res_sm = '';
            $bb_smileys = '<small><a href="' . self::$system_set['homeurl'] . '/pages/faq.php?act=my_smileys">' . self::$lng['edit_list'] . '</a></small><br />';
            foreach ($smileys as $value) {
                $res_sm .= '<a href="javascript:tag(\':' . $value . '\', \':\'); show_hide(\'sm\');">:' . $value . ':</a> ';
            }
            $bb_smileys .= functions::smileys($res_sm, self::$user_data['rights'] >= 1 ? 1 : 0);
        } else {
            $bb_smileys = '<small><a href="' . self::$system_set['homeurl'] . '/pages/faq.php?act=smileys">' . self::$lng['add_smileys'] . '</a></small>';
        }

        // Код
        $code = array(
            'php',
            'css',
            'js',
            'html',
            'sql',
            'xml',
        );

        $codebtn = '';
        foreach ($code as $val) {
            $codebtn .= '<a href="javascript:tag(\'[code=' . $val . ']\', \'[/code]\'); show_hide(\'code\');">' . strtoupper($val) . '</a>';
        }

        $out = '<style>
.codepopup {margin-top: 3px;}
.codepopup a {
border: 1px solid #a7a7a7;
border-radius: 3px;
background-color: #dddddd;
color: black;
font-weight: bold;
padding: 2px 6px 2px 6px;
display: inline-block;
margin-right: 6px;
margin-bottom: 3px;
text-decoration: none;
}
</style>
            <script>
            function tag(text1, text2) {
              if ((document.selection)) {
                document.' . $form . '.' . $field . '.focus();
                document.' . $form . '.document.selection.createRange().text = text1+document.' . $form . '.document.selection.createRange().text+text2;
              } else if(document.forms[\'' . $form . '\'].elements[\'' . $field . '\'].selectionStart!=undefined) {
                var element = document.forms[\'' . $form . '\'].elements[\'' . $field . '\'];
                var str = element.value;
                var start = element.selectionStart;
                var length = element.selectionEnd - element.selectionStart;
                element.value = str.substr(0, start) + text1 + str.substr(start, length) + text2 + str.substr(start + length);
              } else {
                document.' . $form . '.' . $field . '.value += text1+text2;
              }
            }
            function show_hide(elem) {
              obj = document.getElementById(elem);
              if( obj.style.display == "none" ) {
                obj.style.display = "block";
              } else {
                obj.style.display = "none";
              }
            }
            </script>
            <a href="javascript:tag(\'[b]\', \'[/b]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/bold.gif" alt="b" title="' . self::$lng['tag_bold'] . '" /></a>
            <a href="javascript:tag(\'[i]\', \'[/i]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/italics.gif" alt="i" title="' . self::$lng['tag_italic'] . '" /></a>
            <a href="javascript:tag(\'[u]\', \'[/u]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/underline.gif" alt="u" title="' . self::$lng['tag_underline'] . '" /></a>
            <a href="javascript:tag(\'[s]\', \'[/s]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/strike.gif" alt="s" title="' . self::$lng['tag_strike'] . '" /></a>
            <a href="javascript:tag(\'[*]\', \'[/*]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/list.gif" alt="s" title="' . self::$lng['tag_list'] . '" /></a>
            <a href="javascript:tag(\'[spoiler=]\', \'[/spoiler]\');"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/sp.gif" alt="spoiler" title="Спойлер" /></a>
            <a href="javascript:tag(\'[c]\', \'[/c]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/quote.gif" alt="quote" title="' . self::$lng['tag_quote'] . '" /></a>
            <a href="javascript:tag(\'[url=]\', \'[/url]\')"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/link.gif" alt="url" title="' . self::$lng['tag_link'] . '" /></a>
            <a href="javascript:show_hide(\'code\');"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/php.gif" title="Code" alt="Code" /></a>
            <a href="javascript:show_hide(\'color\');"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/color.gif" title="' . self::$lng['color_text'] . '" alt="color" /></a>
            <a href="javascript:show_hide(\'bg\');"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/color_bg.gif" title="' . self::$lng['color_bg'] . '" alt="bg color" /></a>';

        if (self::$user_id) {
            $out .= ' <a href="javascript:show_hide(\'sm\');"><img style="border: 0;" src="' . self::$system_set['homeurl'] . '/images/bb/smileys.gif" alt="sm" title="' . self::$lng['smileys'] . '" /></a><br />
                <div id="sm" style="display:none">' . $bb_smileys . '</div>';
        } else {
            $out .= '<br />';
        }
        $out .= '<div id="code" class="codepopup" style="display:none;">' . $codebtn . '</div>' .
            '<div id="color" class="bbpopup" style="display:none;">' . $font_color . '</div>' .
            '<div id="bg" class="bbpopup" style="display:none">' . $bg_color . '</div>';

        return $out;
    }
}
