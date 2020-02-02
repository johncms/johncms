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

use Johncms\System\Users\User;
use Johncms\System\Users\UserConfig;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Psr\Container\ContainerInterface;

class Tools
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Assets
     */
    private $assets;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var User
     */
    private $user;

    /**
     * @var UserConfig
     */
    private $userConfig;

    /**
     * @var array
     */
    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        $this->assets = $container->get(Assets::class);
        $config = $container->get('config');
        $this->config = $config['johncms'] ?? [];
        $this->db = $container->get(\PDO::class);
        $this->user = $container->get(User::class);
        $this->userConfig = $this->user->config;

        return $this;
    }

    public function antiflood()
    {
        $config = $this->config['antiflood'];

        switch ($config['mode']) {
            // Адаптивный режим
            case 1:
                $adm = $this->db->query(
                    'SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > ' . (time() - 300)
                )->fetchColumn();
                $limit = $adm > 0 ? $config['day'] : $config['night'];
                break;
            // День
            case 3:
                $limit = $config['day'];
                break;
            // Ночь
            case 4:
                $limit = $config['night'];
                break;
            // По умолчанию день / ночь
            default:
                $c_time = date('G', time());
                $limit = $c_time > $config['day'] && $c_time < $config['night'] ? $config['day'] : $config['night'];
        }

        // Для Администрации задаем лимит в 4 секунды
        if ($this->user->rights > 0) {
            $limit = 4;
        }

        $flood = $this->user->lastpost + $limit - time();

        return $flood > 0 ? $flood : false;
    }

    /**
     * Обработка текстов перед выводом на экран
     *
     * @param string $str
     * @param int $br Параметр обработки переносов строк
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вместо переносов строки вставляются пробелы
     * @param int $tags Параметр обработки тэгов
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вырезать тэги
     *
     * @return string
     */
    public function checkout($str, $br = 0, $tags = 0)
    {
        $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');

        if ($br == 1) {
            $str = nl2br($str);
        } elseif ($br == 2) {
            $str = str_replace("\r\n", ' ', $str);
        }

        if ($tags == 1) {
            $str = $this->container->get(Bbcode::class)->tags($str);
        } elseif ($tags == 2) {
            $str = $this->container->get(Bbcode::class)->notags($str);
        }

        return trim($str);
    }

    /**
     * Показываем дату с учетом сдвига времени
     *
     * @param int $var Время в Unix формате
     * @return string Отформатированное время
     */
    public function displayDate(int $var)
    {
        $shift = ($this->config['timeshift'] + $this->userConfig->timeshift) * 3600;

        if (date('Y', $var) == date('Y', time())) {
            if (date('z', $var + $shift) == date('z', time() + $shift)) {
                return d__('system', 'Today') . ', ' . date('H:i', $var + $shift);
            }
            if (date('z', $var + $shift) == date('z', time() + $shift) - 1) {
                return d__('system', 'Yesterday') . ', ' . date('H:i', $var + $shift);
            }
        }

        return date('d.m.Y / H:i', $var + $shift);
    }

    /**
     * Сообщения об ошибках
     *
     * @param string|array $error Сообщение об ошибке (или массив с сообщениями)
     * @param string $link Необязательная ссылка перехода
     * @return string
     */
    public function displayError($error = '', $link = '')
    {
        return '<div class="rmenu"><p><b>' . d__('system', 'ERROR') . '!</b><br>'
            . (is_array($error) ? implode('<br>', $error) : $error) . '</p>'
            . (! empty($link) ? '<p>' . $link . '</p>' : '') . '</div>';
    }

    /**
     * Постраничная навигация
     * За основу взята доработанная функция от форума SMF 2.x.x
     *
     * @param string $url
     * @param int $start
     * @param int $total
     * @param int $kmess
     * @return string
     */
    public function displayPagination($url, $start, $total, $kmess): string
    {
        $render = di(Render::class);
        $items = [];
        $neighbors = 2;
        if ($start >= $total) {
            $start = max(0, $total - (($total % $kmess) === 0 ? $kmess : ($total % $kmess)));
        } else {
            $start = max(0, (int) $start - ((int) $start % (int) $kmess));
        }

        $url = strtr($url, ['%' => '%%']);

        if ($start !== 0) {
            $items[] = [
                'url'  => $url . 'page=' . $start / $kmess,
                'name' => '&lt;&lt;',
            ];
        }

        if ($start > $kmess * $neighbors) {
            $items[] = [
                'url'  => $url . 'page=' . 1,
                'name' => '1',
            ];
        }

        if ($start > $kmess * ($neighbors + 1)) {
            $items[] = [
                'url'  => '',
                'name' => '...',
            ];
        }

        for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
            if ($start >= $kmess * $nCont) {
                $tmpStart = $start - $kmess * $nCont;
                $items[] = [
                    'url'  => $url . 'page=' . ($tmpStart / $kmess + 1),
                    'name' => $tmpStart / $kmess + 1,
                ];
            }
        }

        $items[] = [
            'url'    => '',
            'active' => true,
            'name'   => ($start / $kmess + 1),
        ];
        $tmpMaxPages = (int) (($total - 1) / $kmess) * $kmess;

        for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
            if ($start + $kmess * $nCont <= $tmpMaxPages) {
                $tmpStart = $start + $kmess * $nCont;
                $items[] = [
                    'url'  => $url . 'page=' . ($tmpStart / $kmess + 1),
                    'name' => $tmpStart / $kmess + 1,
                ];
            }
        }

        if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) {
            $items[] = [
                'url'  => '',
                'name' => '...',
            ];
        }

        if ($start + $kmess * $neighbors < $tmpMaxPages) {
            $items[] = [
                'url'  => $url . 'page=' . ($tmpMaxPages / $kmess + 1),
                'name' => $tmpMaxPages / $kmess + 1,
            ];
        }

        if ($start + $kmess < $total) {
            $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
            $items[] = [
                'url'  => $url . 'page=' . $display_page,
                'name' => '&gt;&gt;',
            ];
        }

        return $render->render(
            'system::app/pagination',
            [
                'items' => $items,
            ]
        );
    }

    /**
     * Показываем местоположение пользователя
     */
    public function displayPlace(string $place): string
    {
        $place = rtrim($place, '/');

        if (empty($place)) {
            $place = '/';
        }

        $part = explode('?', $place);

        $placelist = [
            '/'                => '<a href="#home#/">' . d__('system', 'On the Homepage') . '</a>',
            '/album'           => '<a href="#home#/album/">' . d__('system', 'Watching the photo album') . '</a>',
            '/community'       => '<a href="#home#/community/">' . d__('system', 'Users') . '</a>',
            '/community/users' => '<a href="#home#/community/users/">' . d__('system', 'Users List') . '</a>',
            '/downloads'       => '<a href="#home#/downloads/">' . d__('system', 'Downloads') . '</a>',
            '/forum'           => '<a href="#home#/forum/">' . d__('system', 'Forum') . '</a>&#160;/&#160;<a href="#home#/forum/?act=who">&gt;&gt;</a>', // phpcs:ignore
            '/guestbook'       => '<a href="#home#/guestbook/">' . d__('system', 'Guestbook') . '</a>',
            '/help'            => '<a href="#home#/help/">' . d__('system', 'Reading the FAQ') . '</a>',
            '/library'         => '<a href="#home#/library/">' . d__('system', 'Library') . '</a>',
            '/login'           => d__('system', 'Login'),
            '/mail'            => d__('system', 'Personal correspondence'),
            '/news'            => '<a href="#home#/news/">' . d__('system', 'Reading the news') . '</a>',
            '/profile'         => d__('system', 'Profile'),
            '/redirect'        => d__('system', 'Redirect on external link to another site'),
            '/registration'    => d__('system', 'Registered on the site'),
            '/users'           => '<a href="#home#/users/">' . d__('system', 'List of users') . '</a>',
            '/online'          => '<a href="#home#/users/?act=online">' . d__('system', 'Who is online?') . '</a>',
            '/online/history'  => '<a href="#home#/users/?act=online">' . d__('system', 'Who is online?') . '</a>',
            '/online/guest'    => '<a href="#home#/users/?act=online">' . d__('system', 'Who is online?') . '</a>',
            '/online/ip'       => '<a href="#home#/users/?act=online">' . d__('system', 'Who is online?') . '</a>',
        ];

        if (array_key_exists($place, $placelist)) {
            return str_replace('#home#', $this->config['homeurl'], $placelist[$place]);
        }

        if (array_key_exists($part[0], $placelist)) {
            return str_replace('#home#', $this->config['homeurl'], $placelist[$part[0]]);
        }

        return '<a href="' . $this->config['homeurl'] . ($this->user->rights >= 6 ? $place : '') . '/">'
            . d__('system', 'Somewhere on the site')
            . '</a>';
    }

    /**
     * Отображения личных данных пользователя
     *
     * @param int $user Массив запроса в таблицу `users`
     * @param array $arg Массив параметров отображения
     *                    [lastvisit] (boolean)   Дата и время последнего визита
     *                    [stshide]   (boolean)   Скрыть статус (если есть)
     *                    [iphide]    (boolean)   Скрыть (не показывать) IP и UserAgent
     *                    [iphist]    (boolean)   Показывать ссылку на историю IP
     *
     *                    [header]    (string)    Текст в строке после Ника пользователя
     *                    [body]      (string)    Основной текст, под ником пользователя
     *                    [sub]       (string)    Строка выводится вверху области "sub"
     *                    [footer]    (string)    Строка выводится внизу области "sub"
     *
     * @return string
     */
    public function displayUser($user = 0, array $arg = [])
    {
        global $mod;
        $out = false;
        $homeurl = $this->config['homeurl'];

        if (! $user['id']) {
            $out = '<b>' . d__('system', 'Guest') . '</b>';

            if (! empty($user['name'])) {
                $out .= ': ' . $user['name'];
            }

            if (! empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }
        } else {
            $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';

            if (file_exists(UPLOAD_PATH . 'users/avatar/' . $user['id'] . '.png')) {
                $out .= '<img src="' . $homeurl . '/upload/users/avatar/' . $user['id'] .
                    '.png" width="32" height="32" alt="" />&#160;';
            } else {
                $out .= '<img src="' . $this->assets->url('images/old/empty.png') . '" alt="" />&#160;';
            }

            $out .= '</td><td>';

            if ($user['sex']) {
                $iconName = ($user['sex'] === 'm' ? 'm' : 'w') .
                    ($user['datereg'] > time() - 86400 ? '_new' : '') . '.png';
                $out .= '<img src="' . $this->assets->url('images/old/' . $iconName) . '" alt="" class="icon-inline">';
            } else {
                $out .= '<img src="' . $this->assets->url('images/old/del.png') . '" alt="" class="icon-inline">';
            }

            $out .= ! $this->user->isValid() || $this->user->id == $user['id']
                ? '<b>' . $user['name'] . '</b>'
                : '<a href="' . $homeurl . '/profile/?user=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
            $rank = [
                0 => '',
                1 => '(GMod)',
                2 => '(CMod)',
                3 => '(FMod)',
                4 => '(DMod)',
                5 => '(LMod)',
                6 => '(Smd)',
                7 => '(Adm)',
                9 => '(SV!)',
            ];
            $rights = $user['rights'] ?? 0;
            $out .= ' ' . $rank[$rights];
            $out .= (time() > $user['lastdate'] + 300
                ? '<span class="red"> [Off]</span>'
                : '<span class="green"> [ON]</span>');

            if (! empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }

            if (! isset($arg['stshide']) && ! empty($user['status'])) {
                $out .= '<div class="status"><img src="' . $this->assets->url('images/old/label.png') . '" alt="" class="icon-inline">' . $user['status'] . '</div>'; // phpcs:ignore
            }

            $out .= '</td></tr></table>';
        }

        if (isset($arg['body'])) {
            $out .= '<div>' . $arg['body'] . '</div>';
        }

        $ipinf = isset($arg['iphide']) ? ! $arg['iphide'] : ($this->user->rights ? 1 : 0);
        $lastvisit = time() > $user['lastdate'] + 300 && isset($arg['lastvisit']) ? $this->displayDate($user['lastdate']) : false; // phpcs:ignore

        if ($ipinf || $lastvisit || ! empty($arg['sub']) || isset($arg['footer'])) {
            $out .= '<div class="sub">';

            if (isset($arg['sub'])) {
                $out .= '<div>' . $arg['sub'] . '</div>';
            }

            if ($lastvisit) {
                $out .= '<div><span class="gray">' . d__('system', 'Last Visit') . ':</span> ' . $lastvisit . '</div>';
            }

            $iphist = '';

            if ($ipinf) {
                $out .= '<div><span class="gray">' . d__('system', 'Browser') . ':</span> ' .
                    htmlspecialchars($user['browser']) . '</div>' .
                    '<div><span class="gray">' . d__('system', 'IP address') . ':</span> ';
                $hist = $mod == 'history' ? '&amp;mod=history' : '';
                $ip = long2ip((int) $user['ip']);

                if ($this->user->rights && isset($user['ip_via_proxy']) && $user['ip_via_proxy']) {
                    $out .= '<b class="red"><a href="' . $homeurl .
                        '/admin/search_ip/?ip=' . $ip . $hist . '">' . $ip . '</a></b>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/ip_whois/?ip=' . $ip . '">?</a>]';
                    $out .= ' / ';
                    $out .= '<a href="' . $homeurl . '/admin/search_ip/?ip=' .
                        long2ip($user['ip_via_proxy']) . $hist . '">' . long2ip($user['ip_via_proxy']) . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/ip_whois/?ip=' .
                        long2ip($user['ip_via_proxy']) . '">?</a>]';
                } elseif ($this->user->rights) {
                    $out .= '<a href="' . $homeurl . '/admin/search_ip/?ip=' . $ip . $hist . '">' .
                        $ip . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/ip_whois/?ip=' . $ip . '">?</a>]';
                } else {
                    $out .= $ip . $iphist;
                }

                if (isset($arg['iphist'])) {
                    $iptotal = $this->db->query(
                        "SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'"
                    )->fetchColumn();
                    $out .= '<div><span class="gray">' . d__('system', 'IP History') .
                        ':</span> <a href="' . $homeurl . '/profile/?act=ip&amp;user=' . $user['id'] .
                        '">[' . $iptotal . ']</a></div>';
                }

                $out .= '</div>';
            }

            if (isset($arg['footer'])) {
                $out .= $arg['footer'];
            }
            $out .= '</div>';
        }

        return $out;
    }

    /**
     * Получение флага для выбранной локали
     *
     * @param string $locale
     * @return string
     * @deprecated
     */
    public function getFlag($locale): string
    {
        return '<img src="' .
            $this->assets->url('images/flags/' . strtolower($locale) . '.png') .
            '" style="margin-right: 8px; vertical-align: middle">';
    }

    /**
     * Получаем данные пользователя
     *
     * @param int $id
     * @return bool|User|mixed
     */
    public function getUser(int $id = 0)
    {
        if ($id === $this->user->id) {
            return $this->user;
        }

        $user = [];

        if ($id > 0) {
            $req = $this->db->query("SELECT * FROM `users` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $user = $req->fetch();
            }
        }

        return new User($user);
    }

    /**
     * Проверка на игнор у получателя
     *
     * @param $id
     * @return bool
     */
    public function isIgnor($id)
    {
        static $user_id = null;
        static $return = false;

        if (! $this->user->isValid() && ! $id) {
            return false;
        }

        if (null === $user_id || $id != $user_id) {
            $user_id = $id;
            $req = $this->db->query(
                "SELECT * FROM `cms_contact` WHERE `user_id` = '${id}' AND `from_id` = " . $this->user->id
            );

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($res['ban'] == 1) {
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Транслитерация с Русского в латиницу
     *
     * @param string $str
     * @return string
     */
    public function rusLat($str)
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

        return strtr(mb_strtolower($str), $replace);
    }

    /**
     * Обработка смайлов
     *
     * @param string $str
     * @param bool $adm
     * @return string
     */
    public function smilies($str, $adm = false)
    {
        static $smiliesCache = [];

        if (empty($smiliesCache)) {
            $file = CACHE_PATH . 'smilies-list.cache';

            if (file_exists($file) && ($smileys = file_get_contents($file)) !== false) {
                $smiliesCache = unserialize($smileys, ['allowed_classes' => false]);

                return strtr(
                    $str,
                    ($adm
                        ? array_merge($smiliesCache['usr'], $smiliesCache['adm'])
                        : $smiliesCache['usr'])
                );
            }

            return $str;
        }

        return strtr($str, ($adm ? array_merge($smiliesCache['usr'], $smiliesCache['adm']) : $smiliesCache['usr']));
    }

    /**
     * Функция пересчета на дни, или часы
     *
     * @param int $var
     * @return bool|string
     */
    public function timecount(int $var)
    {
        if ($var < 0) {
            $var = 0;
        }

        $day = intdiv($var, 86400);

        return $var >= 86400
            ? $day . ' ' . dn__('system', 'Day', 'Days', $day)
            : date('G:i:s', mktime(0, 0, $var));
    }

    // Транслитерация текста
    public function trans($str)
    {
        $replace = [
            'a'  => 'а',
            'b'  => 'б',
            'v'  => 'в',
            'g'  => 'г',
            'd'  => 'д',
            'e'  => 'е',
            'yo' => 'ё',
            'zh' => 'ж',
            'z'  => 'з',
            'i'  => 'и',
            'j'  => 'й',
            'k'  => 'к',
            'l'  => 'л',
            'm'  => 'м',
            'n'  => 'н',
            'o'  => 'о',
            'p'  => 'п',
            'r'  => 'р',
            's'  => 'с',
            't'  => 'т',
            'u'  => 'у',
            'f'  => 'ф',
            'h'  => 'х',
            'c'  => 'ц',
            'ch' => 'ч',
            'w'  => 'ш',
            'sh' => 'щ',
            'q'  => 'ъ',
            'y'  => 'ы',
            'x'  => 'э',
            'yu' => 'ю',
            'ya' => 'я',
            'A'  => 'А',
            'B'  => 'Б',
            'V'  => 'В',
            'G'  => 'Г',
            'D'  => 'Д',
            'E'  => 'Е',
            'YO' => 'Ё',
            'ZH' => 'Ж',
            'Z'  => 'З',
            'I'  => 'И',
            'J'  => 'Й',
            'K'  => 'К',
            'L'  => 'Л',
            'M'  => 'М',
            'N'  => 'Н',
            'O'  => 'О',
            'P'  => 'П',
            'R'  => 'Р',
            'S'  => 'С',
            'T'  => 'Т',
            'U'  => 'У',
            'F'  => 'Ф',
            'H'  => 'Х',
            'C'  => 'Ц',
            'CH' => 'Ч',
            'W'  => 'Ш',
            'SH' => 'Щ',
            'Q'  => 'Ъ',
            'Y'  => 'Ы',
            'X'  => 'Э',
            'YU' => 'Ю',
            'YA' => 'Я',
        ];

        return strtr($str, $replace);
    }

    /**
     * Метод для пересчета сообщений в топике и обновления основных данных топика
     *
     * @param $topic_id
     */
    public function recountForumTopic($topic_id)
    {
        $topic_id = (int) $topic_id;
        $post_count = $this->db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${topic_id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn(); // phpcs:ignore
        $mod_post_count = $this->db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${topic_id}'")->fetchColumn(); // phpcs:ignore

        $last_post = $this->db->query("SELECT * FROM forum_messages WHERE `topic_id` = '${topic_id}' AND (`deleted` != '1' OR `deleted` IS NULL) ORDER BY id DESC LIMIT 1")->fetch(); // phpcs:ignore
        $mod_last_post = $this->db->query("SELECT * FROM forum_messages WHERE `topic_id` = '${topic_id}' ORDER BY id DESC LIMIT 1")->fetch(); // phpcs:ignore

        // Обновляем время топика
        $this->db->exec(
            "UPDATE `forum_topic` SET
            `post_count` = '" . $post_count . "',
            `mod_post_count` = '" . $mod_post_count . "',
            `last_post_date` = '" . $last_post['date'] . "',
            `last_post_author` = '" . $last_post['user_id'] . "',
            `last_post_author_name` = '" . $last_post['user_name'] . "',
            `last_message_id` = '" . $last_post['id'] . "',
            `mod_last_post_date` = '" . $mod_last_post['date'] . "',
            `mod_last_post_author` = '" . $mod_last_post['user_id'] . "',
            `mod_last_post_author_name` = '" . $mod_last_post['user_name'] . "',
            `mod_last_message_id` = '" . $mod_last_post['id'] . "'
            WHERE `id` = '${topic_id}'
        "
        );
    }

    /**
     * Форматирует числа в сокращенный формат
     *
     * @param $number
     * @return int|string
     */
    public function formatNumber($number)
    {
        $prefixes = 'KMGTPEZY';
        if ($number >= 1000) {
            for ($i = -1; $number >= 1000; ++$i) {
                $number /= 1000;
            }

            if ($number > 100) {
                $number = floor($number);
            }

            return round($number, 2) . $prefixes[$i];
        }

        return $number;
    }
}
