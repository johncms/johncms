<?php

defined('_IN_JOHNCMS') or die('Restricted access');

class bbcode extends core //TODO: убрать extends
{
    // Обработка тэгов и ссылок
    public static function tags($var)
    {
        $var = self::parse_time($var);               // Обработка тэга времени
        $var = self::highlight_code($var);           // Подсветка кода
        $var = self::highlight_bb($var);               // Обработка ссылок
        $var = self::highlight_url($var);            // Обработка ссылок
        $var = self::highlight_bbcode_url($var);       // Обработка ссылок в BBcode
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
                $timeshift = App::getContainer()->get('config')['johncms']['timeshift'];
                $shift = ($timeshift + core::$user_set['timeshift']) * 3600;
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
        $homeurl = App::getContainer()->get('config')['johncms']['homeurl'];

        if (!function_exists('url_callback')) {
            function url_callback($type, $whitespace, $url, $relative_url)
            {
                global $homeurl;
                $orig_url = $url;
                $orig_relative = $relative_url;
                $url = htmlspecialchars_decode($url);
                $relative_url = htmlspecialchars_decode($relative_url);
                $text = '';
                $chars = ['<', '>', '"'];
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
                            $url = $homeurl . '/go.php?url=' . rawurlencode($url);
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
            '#(^|[\n\t (>.])(' . preg_quote($homeurl,
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

    /**
     * Удаление bbCode из текста
     *
     * @param string $var
     * @return string
     */
    static function notags($var = '')
    {
        $var = preg_replace('#\[color=(.+?)\](.+?)\[/color]#si', '$2', $var);
        $var = preg_replace('#\[code=(.+?)\](.+?)\[/code]#si', '$2', $var);
        $var = preg_replace('!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', '$2', $var);
        $var = preg_replace('#\[spoiler=(.+?)\]#si', '$2', $var);
        $replace = [
            '[small]'  => '',
            '[/small]' => '',
            '[big]'    => '',
            '[/big]'   => '',
            '[green]'  => '',
            '[/green]' => '',
            '[red]'    => '',
            '[/red]'   => '',
            '[blue]'   => '',
            '[/blue]'  => '',
            '[b]'      => '',
            '[/b]'     => '',
            '[i]'      => '',
            '[/i]'     => '',
            '[u]'      => '',
            '[/u]'     => '',
            '[s]'      => '',
            '[/s]'     => '',
            '[quote]'  => '',
            '[/quote]' => '',
            '[php]'    => '',
            '[/php]'   => '',
            '[c]'      => '',
            '[/c]'     => '',
            '[*]'      => '',
            '[/*]'     => '',
        ];

        return strtr($var, $replace);
    }

    /**
     * Подсветка кода
     *
     * @param string $var
     * @return mixed
     */
    private static function highlight_code($var)
    {
        $var = preg_replace_callback('#\[php\](.+?)\[\/php\]#s', 'self::phpCodeCallback', $var);
        $var = preg_replace_callback('#\[code=(.+?)\](.+?)\[\/code]#is', 'self::codeCallback', $var);

        return $var;
    }

    private static $geshi;

    private static function phpCodeCallback($code)
    {
        return self::codeCallback([1 => 'php', 2 => $code[1]]);
    }

    private static function codeCallback($code)
    {
        $parsers = [
            'php'  => 'php',
            'css'  => 'css',
            'html' => 'html5',
            'js'   => 'javascript',
            'sql'  => 'sql',
            'xml'  => 'xml',
        ];

        $parser = isset($code[1]) && isset($parsers[$code[1]]) ? $parsers[$code[1]] : 'php';

        if (null === self::$geshi) {
            self::$geshi = new \GeSHi;
            self::$geshi->set_link_styles(GESHI_LINK, 'text-decoration: none');
            self::$geshi->set_link_target('_blank');
            self::$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
            self::$geshi->set_line_style('background: rgba(255, 255, 255, 0.5)', 'background: rgba(255, 255, 255, 0.35)', false);
            self::$geshi->set_code_style('padding-left: 6px; white-space: pre-wrap');
        }

        self::$geshi->set_language($parser);
        $php = strtr($code[2], ['<br />' => '']);
        $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
        self::$geshi->set_source($php);

        return '<div class="phpcode" style="overflow-x: auto">' . self::$geshi->parse_code() . '</div>';
    }

    /**
     * Обработка URL в тэгах BBcode
     *
     * @param $var
     * @return mixed
     */
    private static function highlight_bbcode_url($var)
    {
        if (!function_exists('process_url')) {
            function process_url($url)
            {
                $homeurl = App::getContainer()->get('config')['johncms']['homeurl'];
                $home = parse_url($homeurl);
                $tmp = parse_url($url[1]);
                if ($home['host'] == $tmp['host'] || isset(core::$user_set['direct_url']) && core::$user_set['direct_url']) {
                    return '<a href="' . $url[1] . '">' . $url[2] . '</a>';
                } else {
                    return '<a href="' . $homeurl . '/go.php?url=' . urlencode(htmlspecialchars_decode($url[1])) . '">' . $url[2] . '</a>';
                }
            }
        }

        return preg_replace_callback('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]~', 'process_url', $var);
    }

    /**
     * Обработка bbCode
     *
     * @param string $var
     * @return string
     */
    private static function highlight_bb($var)
    {
        // Список поиска
        $search = [
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
        ];
        // Список замены
        $replace = [
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
        ];

        return preg_replace($search, $replace, $var);
    }
}
