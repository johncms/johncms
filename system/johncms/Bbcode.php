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

namespace Johncms;

use Interop\Container\ContainerInterface;

class Bbcode
{
    /**
     * @var \Johncms\Config
     */
    protected $config;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserConfig
     */
    protected $userConfig;

    /**
     * @var \GeSHi
     */
    protected $geshi;

    protected $homeUrl;

    public function __invoke(ContainerInterface $container)
    {
        $this->config = $container->get(Config::class);
        $this->user = $container->get(User::class);
        $this->userConfig = $this->user->getConfig();
        $this->homeUrl = $this->config['homeurl'];

        return $this;
    }

    public function notags($var = '')
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
     * BbCode Toolbar
     *
     * @param string $form
     * @param string $field
     * @return string
     */
    public function buttons($form, $field)
    {
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
            </script>';
        $out .= join('', array_values($this->toolbarButtons()));
        return $out;
    }

    /**
     * Список контента для панели BB-кодов
     * 
     * @return array
     */
    protected function toolbarButtons()
    {
        $result = [
            'b' => '<a href="javascript:tag(\'[b]\', \'[/b]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/bold.gif" alt="b" title="' . _t('Bold', 'system') . '" /></a>',
            'i' => '<a href="javascript:tag(\'[i]\', \'[/i]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/italics.gif" alt="i" title="' . _t('Italic', 'system') . '" /></a>',
            'u' => '<a href="javascript:tag(\'[u]\', \'[/u]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/underline.gif" alt="u" title="' . _t('Underline', 'system') . '" /></a>',
            's' => '<a href="javascript:tag(\'[s]\', \'[/s]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/strike.gif" alt="s" title="' . _t('Strike', 'system') . '" /></a>',
            'list' => '<a href="javascript:tag(\'[*]\', \'[/*]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/list.gif" alt="li" title="' . _t('List', 'system') . '" /></a>',
            'spoiler' => '<a href="javascript:tag(\'[spoiler=]\', \'[/spoiler]\');"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/sp.gif" alt="spoiler" title="' . _t('Spoiler', 'system') . '" /></a>',
            'quote' => '<a href="javascript:tag(\'[c]\', \'[/c]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/quote.gif" alt="quote" title="' . _t('Quote', 'system') . '" /></a>',
            'url' => '<a href="javascript:tag(\'[url=]\', \'[/url]\')"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/link.gif" alt="url" title="' . _t('URL', 'system') . '" /></a>',
            'code' => '<a href="javascript:show_hide(\'code\');"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/php.gif" title="' . _t('Code', 'system') . '" alt="Code" /></a>',
            'color' => '<a href="javascript:show_hide(\'color\');"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/color.gif" title="' . _t('Text Color', 'system') . '" alt="color" /></a>',
            'bg' => '<a href="javascript:show_hide(\'bg\');"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/color_bg.gif" title="' . _t('Background Color', 'system') . '" alt="bg color" /></a>'
        ];
        $result['smileys'] = $this->toolbarSmileys();
        $result['codepopup'] = $this->toolbarCodesPopup();
        $result['colorpopup'] = $this->toolbarColorsPopup();
        return $result;
    }

    /**
     * Контент для смайлов на панели
     *
     * @return string
     */
    protected function toolbarSmileys()
    {
        if (!$this->user->isValid()) {
            return '<br />';
        }
        
        $bb_smileys = '';
        $smileys = !empty($this->user->smileys) ? unserialize($this->user->smileys) : [];
        if (empty($smileys)) {
            $bb_smileys = '<small><a href="' . $this->homeUrl . '/help/?act=smilies">' . _t('Add Smilies', 'system') . '</a></small>';
        } else {
            $res_sm = '';
            $bb_smileys = '<small><a href="' . $this->homeUrl . '/help/?act=my_smilies">' . _t('Edit List', 'system') . '</a></small><br />';

            foreach ($smileys as $value) {
                $res_sm .= '<a href="javascript:tag(\':' . $value . '\', \':\'); show_hide(\'sm\');">:' . $value . ':</a> ';
            }

            /** @var \Johncms\Tools $tools */
            $tools = \App::getContainer()->get('tools');

            $bb_smileys .= $tools->smilies($res_sm, $this->user->rights >= 1 ? 1 : 0);
        }

        return ' <a href="javascript:show_hide(\'sm\');"><img style="border: 0;" src="' . $this->homeUrl . '/images/bb/smileys.gif" alt="sm" title="' . _t('Smilies', 'system') . '" /></a><br />
            <div id="sm" style="display:none">' . $bb_smileys . '</div>';
    }

    /**
     * Контент для всплывающей панели кода
     *
     * @return string
     */
    protected function toolbarCodesPopup()
    {
        $code = [
            'php',
            'css',
            'js',
            'html',
            'sql',
            'xml',
        ];

        $codepopup = '';
        foreach ($code as $val) {
            $codepopup .= '<a href="javascript:tag(\'[code=' . $val . ']\', \'[/code]\'); show_hide(\'code\');">' . strtoupper($val) . '</a>';
        }
        return '<div id="code" class="codepopup" style="display:none;">' . $codepopup . '</div>';
    }

    /**
     * Контент для всплывающей панели цвета текста и фона
     * 
     * @return array
     */
    protected function toolbarColorsPopup()
    {
        $colors = [
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
            'b53dd2',
        ];

        $font_color = '';
        $bg_color = '';
        foreach ($colors as $value) {
            $font_color .= '<a href="javascript:tag(\'[color=#' . $value . ']\', \'[/color]\'); show_hide(\'color\');" style="background-color:#' . $value . ';"></a>';
            $bg_color .= '<a href="javascript:tag(\'[bg=#' . $value . ']\', \'[/bg]\'); show_hide(\'bg\');" style="background-color:#' . $value . ';"></a>';
        }

        return '<div id="color" class="bbpopup" style="display:none;">' . $font_color . '</div>' .
            '<div id="bg" class="bbpopup" style="display:none">' . $bg_color . '</div>';
    }

    // Обработка тэгов и ссылок
    public function tags($var)
    {
        $var = $this->parseTime($var);               // Обработка тэга времени
        $var = $this->highlightCode($var);           // Подсветка кода
        $var = $this->highlightBb($var);               // Обработка ссылок
        $var = $this->highlightUrl($var);            // Обработка ссылок
        $var = $this->highlightBbcodeUrl($var);       // Обработка ссылок в BBcode

        return $var;
    }

    /**
     * Обработка тэга [time]
     *
     * @param string $var
     * @return string
     */
    protected function parseTime($var)
    {
        return preg_replace_callback(
            '#\[time\](.+?)\[\/time\]#s',
            function ($matches) {
                $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

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
    protected function highlightUrl($text)
    {
        $homeurl = $this->homeUrl;

        // Обработка внутренних ссылок
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])(' . preg_quote($homeurl,
                '#') . ')/((?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return $this->urlCallback(1, $matches[1], $matches[2], $matches[3]);
            },
            $text
        );

        // Обработка обычных ссылок типа xxxx://aaaaa.bbb.cccc. ...
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])([a-z][a-z\d+]*:/{2}(?:(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-zа-яё0-9.]+:[a-zа-яё0-9.]+:[a-zа-яё0-9.:]+\])(?::\d*)?(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            function ($matches) {
                return $this->urlCallback(2, $matches[1], $matches[2], '');
            },
            $text
        );

        return $text;
    }

    private function urlCallback($type, $whitespace, $url, $relative_url)
    {
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
                if (!$this->userConfig->directUrl) {
                    $url = $this->homeUrl . '/go.php?url=' . rawurlencode($url);
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

    /**
     * Подсветка кода
     *
     * @param string $var
     * @return mixed
     */
    protected function highlightCode($var)
    {
        $var = preg_replace_callback('#\[php\](.+?)\[\/php\]#s', [$this, 'phpCodeCallback'], $var);
        $var = preg_replace_callback('#\[code=(.+?)\](.+?)\[\/code]#is', [$this, 'codeCallback'], $var);

        return $var;
    }

    private function phpCodeCallback($code)
    {
        return $this->codeCallback([1 => 'php', 2 => $code[1]]);
    }

    private function codeCallback($code)
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

        if (null === $this->geshi) {
            $this->geshi = new \GeSHi;
            $this->geshi->set_link_styles(GESHI_LINK, 'text-decoration: none');
            $this->geshi->set_link_target('_blank');
            $this->geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
            $this->geshi->set_line_style('background: rgba(255, 255, 255, 0.5)', 'background: rgba(255, 255, 255, 0.35)', false);
            $this->geshi->set_code_style('padding-left: 6px; white-space: pre-wrap');
        }

        $this->geshi->set_language($parser);
        $php = strtr($code[2], ['<br />' => '']);
        $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
        $this->geshi->set_source($php);

        return '<div class="phpcode" style="overflow-x: auto">' . $this->geshi->parse_code() . '</div>';
    }

    /**
     * Обработка URL в тэгах BBcode
     *
     * @param $var
     * @return mixed
     */
    protected function highlightBbcodeUrl($var)
    {
        return preg_replace_callback('~\[url=(https?://.+?|//.+?)](.+?)\[/url]~iu',
            function ($url) {
                $home = parse_url($this->homeUrl);
                $tmp = parse_url($url[1]);

                if ($home['host'] == $tmp['host'] || $this->userConfig->directUrl) {
                    return '<a href="' . $url[1] . '">' . $url[2] . '</a>';
                } else {
                    return '<a href="' . $this->homeUrl . '/go.php?url=' . urlencode(htmlspecialchars_decode($url[1])) . '">' . $url[2] . '</a>';
                }
            },
            $var);
    }

    /**
     * Список замен для основных тегов BB-кода.
     *
     * @return array
     */
    protected function replacements()
    {
        return [
            // Жирный
            'b' => [
                'from' => '#\[b](.+?)\[/b]#is',
                'to' => '<span style="font-weight: bold">$1</span>'
            ],
            // Курсив
            'i' => [
                'from' => '#\[i](.+?)\[/i]#is',
                'to' => '<span style="font-style:italic">$1</span>'
            ],
            // Подчёркнутый
            'u' => [
                'from' => '#\[u](.+?)\[/u]#is',
                'to' => '<span style="text-decoration:underline">$1</span>'
            ],
            // Зачёркнутый
            's' => [
                'from' => '#\[s](.+?)\[/s]#is',
                'to' => '<span style="text-decoration:line-through">$1</span>'
            ],
            // Маленький шрифт
            'small' => [
                'from' => '#\[small](.+?)\[/small]#is',
                'to' => '<span style="font-size:x-small">$1</span>'
            ],
            // Большой шрифт
            'big' => [
                'from' => '#\[big](.+?)\[/big]#is',
                'to' => '<span style="font-size:large">$1</span>'
            ],
            // Красный
            'red' => [
                'from' => '#\[red](.+?)\[/red]#is',
                'to' => '<span style="color:red">$1</span>'
            ],
            // Зеленый
            'green' => [
                'from' => '#\[green](.+?)\[/green]#is',
                'to' => '<span style="color:green">$1</span>'
            ],
            // Синий
            'blue' => [
                'from' => '#\[blue](.+?)\[/blue]#is',
                'to' => '<span style="color:blue">$1</span>'
            ],
            // Цвет шрифта
            'color' => [
                'from' => '!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/color]!is',
                'to' => '<span style="color:$1">$2</span>'
            ],
            // Цвет фона
            'bg' => [
                'from' => '!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is',
                'to' => '<span style="background-color:$1">$2</span>'
            ],
            // Цитата
            'quote' => [
                'from' => '#\[(quote|c)](.+?)\[/(quote|c)]#is',
                'to' => '<span class="quote" style="display:block">$2</span>'
            ],
            // Список
            'list' => [
                'from' => '#\[\*](.+?)\[/\*]#is',
                'to' => '<span class="bblist">$1</span>'
            ],
            // Спойлер
            'spoiler' => [
                'from' => '#\[spoiler=(.+?)](.+?)\[/spoiler]#is',
                'to' => '<div><div class="spoilerhead" style="cursor:pointer;" onclick="var _n=this.parentNode.getElementsByTagName(\'div\')[1];if(_n.style.display==\'none\'){_n.style.display=\'\';}else{_n.style.display=\'none\';}">$1 (+/-)</div><div class="spoilerbody" style="display:none">$2</div></div>'
            ]
        ];
    }

    /**
     * Обработка bbCode
     *
     * @param string $var
     * @return string
     */
    protected function highlightBb($var)
    {
        $replacements = $this->replacements();
        $replacements = array_values($replacements);
        $search = array_column($replacements, 'from');
        $replace = array_column($replacements, 'to');
        return preg_replace($search, $replace, $var);
    }
}
