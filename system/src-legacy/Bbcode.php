<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Legacy;

use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\Container\Factory;
use Johncms\System\Users\UserConfig;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Psr\Container\ContainerInterface;

class Bbcode
{
    /** @var Assets */
    protected $asset;

    /**
     * @var array
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

    protected $tags;

    protected $codeId;

    protected $codeIndex;

    protected $codeParts;

    public function __invoke(ContainerInterface $container)
    {
        $this->asset = $container->get(Assets::class);
        $config = $container->get('config');
        $this->config = $config['johncms'];
        $this->user = $container->get(User::class);
        $this->userConfig = $this->user->config;

        $globalcnf = $container->get('config');
        $this->tags = $globalcnf['bbcode'] ?? [];

        $this->codeId = uniqid('', true);
        $this->codeIndex = 0;
        $this->codeParts = [];

        return $this;
    }

    // Обработка тэгов и ссылок
    public function tags(string $var): string
    {
        $var = $this->highlightCode($var);           // Подсветка кода
        $var = $this->parseTime($var);               // Обработка тэга времени
        $var = $this->highlightBb($var);             // Обработка ссылок
        $var = $this->highlightUrl($var);            // Обработка ссылок
        $var = $this->highlightBbcodeUrl($var);      // Обработка ссылок в BBcode
        $var = $this->youtube($var);

        return $var;
    }

    public function notags(string $var = ''): string
    {
        $replacements = array_values($this->tags);
        $search = array_column($replacements, 'from');
        $replace = array_column($replacements, 'data');
        $var = preg_replace($search, $replace, $var);

        $var = preg_replace('#\[timestamp\](.+?)\[/timestamp]#si', '$2', $var);
        $var = preg_replace('#\[code=(.+?)\](.+?)\[/code]#si', '$2', $var);

        $replace = [
            '[youtube]'  => '',
            '[/youtube]' => '',
            '[php]'      => '',
            '[/php]'     => '',
        ];

        return strtr($var, $replace);
    }

    /**
     * BbCode Toolbar
     */
    public function buttons(string $form, string $field): string
    {
        /** @var Render $render */
        $render = di(Render::class);

        // Смайлы
        $smiles = ! empty($this->user->smileys) ? unserialize($this->user->smileys, ['allowed_classes' => false]) : [];

        /** @var Tools $tools */
        $tools = Factory::getContainer()->get(Tools::class);
        $arr_smiles = [];
        foreach ($smiles as $smile) {
            $arr_smiles[] = [
                'title'  => $smile,
                'img'    => $tools->smilies(':' . $smile . ':', $this->user->rights >= 1 ? 1 : 0),
                'bbcode' => ':' . $smile . ':',
            ];
        }

        return $render->render('system::app/bbcode', ['input' => $field, 'smiles' => $arr_smiles]);
    }

    /**
     * Обработка тэга [time]
     *
     * @param string $var
     * @return string
     */
    protected function parseTime($var)
    {
        $var = preg_replace_callback(
            '#\[time\](.+?)\[\/time\]#s',
            function ($matches) {
                $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

                if (($out = strtotime($matches[1])) !== false) {
                    return date('d.m.Y / H:i', $out + $shift);
                }

                return $matches[1];
            },
            $var
        );

        $var = preg_replace_callback(
            '#\[timestamp\](.+?)\[\/timestamp\]#s',
            function ($matches) {
                $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

                if (($out = strtotime($matches[1])) !== false) {
                    return '<small class="gray">' . d__('system', 'Added') . ': ' . date('d.m.Y / H:i', $out + $shift) . '</small>'; // phpcs:ignore
                }

                return $matches[1];
            },
            $var
        );

        return $var;
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
        $homeurl = $this->config['homeurl'];

        // Обработка внутренних ссылок
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])(' . preg_quote(
                $homeurl,
                '#'
            ) . ')/((?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            // phpcs:ignore
            // phpcs:ignore
            function ($matches) {
                return $this->urlCallback(1, $matches[1], $matches[2], $matches[3]);
            },
            $text
        );

        // Обработка обычных ссылок типа xxxx://aaaaa.bbb.cccc. ...
        $text = preg_replace_callback(
            '#(^|[\n\t (>.])([a-z][a-z\d+]*:/{2}(?:(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-zа-яё0-9.]+:[a-zа-яё0-9.]+:[a-zа-яё0-9.:]+\])(?::\d*)?(?:/(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-zа-яё0-9\-._~!$&\'(*+,;=:@/?|]+|%[\dA-F]{2})*)?)#iu',
            // phpcs:ignore
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
                $relative_url = preg_replace('/[&?]sid=[0-9a-f]{32}$/', '', preg_replace('/([&?])sid=[0-9a-f]{32}&/', '$1', $relative_url)); // phpcs:ignore
                $url = $url . '/' . $relative_url;
                $text = $relative_url;
                if (! $relative_url) {
                    return $whitespace . $orig_url . '/' . $orig_relative;
                }
                break;

            case 2:
                $text = $short_url;
                if (! $this->userConfig->directUrl) {
                    $url = $this->config['homeurl'] . '/redirect/?url=' . rawurlencode($url);
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
     * Вырезает содержимое тега code и помещает в отдельный массив
     * во избежание последующей обработки другими тегами
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
            'html' => 'html',
            'js'   => 'javascript',
            'sql'  => 'sql',
            'xml'  => 'xml',
        ];
        $text = trim(strtr($code[2], ['<br />' => '']));

        $code_lang = (array_key_exists($code[1], $parsers) ? $parsers[$code[1]] : 'php');

        return '<pre class="line-numbers"><code class="language-' . $code_lang . '">' . $text . '</code></pre>';
    }

    /**
     * Обработка URL в тэгах BBcode
     *
     * @param $var
     * @return mixed
     */
    protected function highlightBbcodeUrl($var)
    {
        return preg_replace_callback(
            '~\[url=(https?://.+?|//.+?)](.+?)\[/url]~iu',
            function ($url) {
                $home = parse_url($this->config['homeurl']);
                $tmp = parse_url($url[1]);

                if ($home['host'] == $tmp['host'] || $this->userConfig->directUrl) {
                    return '<a href="' . $url[1] . '">' . $url[2] . '</a>';
                }

                return '<a href="' . $this->config['homeurl'] . '/redirect/?url=' . urlencode(htmlspecialchars_decode($url[1])) . '">' . $url[2] . '</a>'; // phpcs:ignore
            },
            $var
        );
    }

    /**
     * Обработка bbCode
     *
     * @param string $var
     * @return string
     */
    protected function highlightBb($var)
    {
        $replacements = array_values($this->tags);
        $search = array_column($replacements, 'from');
        $replace = array_column($replacements, 'to');

        return preg_replace($search, $replace, $var);
    }

    /**
     * Youtube bbcode
     *
     * @param string $var
     * @return string
     */
    protected function youtube($var)
    {
        return preg_replace_callback(
            '#\[youtube\](.+?)\[\/youtube\]#s',
            function ($matches) {
                if (preg_match('/youtube.com/', $matches[1])) {
                    $values = explode('=', $matches[1]);
                    $valuesto = explode('&', $values[1]);

                    return $this->youtubePlayer($valuesto[0]);
                } elseif (preg_match('/youtu.be/', $matches[1])) {
                    return $this->youtubePlayer(trim(parse_url($matches[1])['path'], '//'));
                }
                $valuesto = explode('&', $matches[1]);

                return $this->youtubePlayer($valuesto[0]);
            },
            $var,
            3
        );
    }

    protected function youtubePlayer($result)
    {
        if ($this->userConfig->youtube) {
            return '
<div style="max-width: 600px">
<div class="embed-responsive embed-responsive-16by9">
<iframe allowfullscreen="allowfullscreen" src="//www.youtube.com/embed/' . $result . '" frameborder="0"></iframe>
</div></div>';
        }

        return '<div>
        <a class="youtube-preview" target="_blank" href="//m.youtube.com/watch?v=' . $result . '">
            <div class="play-button">
                <svg class="icon icon-youtube">
                    <use xlink:href="' . $this->asset->url('icons/sprite.svg') . '#youtube"/>
                </svg>
            </div>
            <img src="//img.youtube.com/vi/' . $result . '/mqdefault.jpg" alt="youtube.com/embed/' . $result . '">
        </a>
        </div>';
    }
}
