<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Utility;

use Johncms\Api\BbcodeInterface;
use Johncms\Api\ConfigInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use Johncms\Users\UserConfig;
use Johncms\View\Extension\Assets;
use Psr\Container\ContainerInterface;

class Tools implements ToolsInterface
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
     * @var UserInterface::class
     */
    private $user;

    /**
     * @var UserConfig
     */
    private $userConfig;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        $this->assets = $container->get(Assets::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->db = $container->get(\PDO::class);
        $this->user = $container->get(UserInterface::class);
        $this->userConfig = $this->user->config;

        return $this;
    }

    public function antiflood()
    {
        $config = $this->config['antiflood'];

        switch ($config['mode']) {
            // Адаптивный режим
            case 1:
                $adm = $this->db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0 AND `lastdate` > ' . (time() - 300))->fetchColumn();
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
     * @param int    $br   Параметр обработки переносов строк
     *                     0 - не обрабатывать (по умолчанию)
     *                     1 - обрабатывать
     *                     2 - вместо переносов строки вставляются пробелы
     * @param int    $tags Параметр обработки тэгов
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
            $str = $this->container->get(BbcodeInterface::class)->tags($str);
        } elseif ($tags == 2) {
            $str = $this->container->get(BbcodeInterface::class)->notags($str);
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
        $shift = ($this->config->timeshift + $this->userConfig->timeshift) * 3600;

        if (date('Y', $var) == date('Y', time())) {
            if (date('z', $var + $shift) == date('z', time() + $shift)) {
                return _t('Today', 'system') . ', ' . date('H:i', $var + $shift);
            }
            if (date('z', $var + $shift) == date('z', time() + $shift) - 1) {
                return _t('Yesterday', 'system') . ', ' . date('H:i', $var + $shift);
            }
        }

        return date('d.m.Y / H:i', $var + $shift);
    }

    /**
     * Сообщения об ошибках
     *
     * @param string|array $error Сообщение об ошибке (или массив с сообщениями)
     * @param string       $link  Необязательная ссылка перехода
     * @return string
     */
    public function displayError($error = '', $link = '')
    {
        return '<div class="rmenu"><p><b>' . _t('ERROR', 'system') . '!</b><br>'
            . (is_array($error) ? implode('<br>', $error) : $error) . '</p>'
            . (! empty($link) ? '<p>' . $link . '</p>' : '') . '</div>';
    }

    /**
     * Постраничная навигация
     * За основу взята доработанная функция от форума SMF 2.x.x
     *
     * @param string $url
     * @param int    $start
     * @param int    $total
     * @param int    $kmess
     * @return string
     */
    public function displayPagination($url, $start, $total, $kmess)
    {
        $neighbors = 2;
        if ($start >= $total) {
            $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
        } else {
            $start = max(0, (int) $start - ((int) $start % (int) $kmess));
        }

        $out[] = '<ul class="pagination">';
        $base_link = '<li class="page-item"><a class="page-link" href="' . strtr($url, ['%' => '%%']) . 'page=%d' . '">%s</a></li>';
        $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '&lt;&lt;');

        if ($start > $kmess * $neighbors) {
            $out[] = sprintf($base_link, 1, '1');
        }

        if ($start > $kmess * ($neighbors + 1)) {
            $out[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }

        for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
            if ($start >= $kmess * $nCont) {
                $tmpStart = $start - $kmess * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
            }
        }

        $out[] = '<li class="page-item active"><a class="page-link" href="#">' . ($start / $kmess + 1) . '</a></li>';
        $tmpMaxPages = (int) (($total - 1) / $kmess) * $kmess;

        for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
            if ($start + $kmess * $nCont <= $tmpMaxPages) {
                $tmpStart = $start + $kmess * $nCont;
                $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
            }
        }

        if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) {
            $out[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }

        if ($start + $kmess * $neighbors < $tmpMaxPages) {
            $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
        }

        if ($start + $kmess < $total) {
            $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
            $out[] = sprintf($base_link, $display_page, '&gt;&gt;');
        }

        $out[] = '</ul>';

        return implode(' ', $out);
    }

    /**
     * Показываем местоположение пользователя
     */
    public function displayPlace(string $place, int $userId = 0) : string
    {
        $place = rtrim($place, '/');

        if (empty($place)) {
            $place = '/';
        }

        $part = explode('?', $place);

        $placelist = [
            '/'                 => '<a href="#home#/">' . _t('On the Homepage', 'system') . '</a>',
            '/album'            => '<a href="#home#/album/">' . _t('Watching the photo album', 'system') . '</a>',
            '/downloads'        => '<a href="#home#/downloads/">' . _t('Downloads', 'system') . '</a>',
            '/forum'            => '<a href="#home#/forum/">' . _t('Forum', 'system') . '</a>&#160;/&#160;<a href="#home#/forum/?act=who">&gt;&gt;</a>',
            '/guestbook'        => '<a href="#home#/guestbook/">' . _t('Guestbook', 'system') . '</a>',
            '/help'             => '<a href="#home#/help/">' . _t('Reading the FAQ', 'system') . '</a>',
            '/library'          => '<a href="#home#/library/">' . _t('Library', 'system') . '</a>',
            '/login'            => _t('Login', 'system'),
            '/mail'             => _t('Personal correspondence', 'system'),
            '/news'             => '<a href="#home#/news/">' . _t('Reading the news', 'system') . '</a>',
            '/profile'          => _t('Profile', 'system'),
            '/redirect'         => _t('Redirect on external link to another site', 'system'),
            '/registration'     => _t('Registered on the site', 'system'),
            '/users'            => '<a href="#home#/users/">' . _t('List of users', 'system') . '</a>',
            '/online'           => '<a href="#home#/users/?act=online">' . _t('Who is online?', 'system') . '</a>',
            '/online/history'   => '<a href="#home#/users/?act=online">' . _t('Who is online?', 'system') . '</a>',
            '/online/guest'     => '<a href="#home#/users/?act=online">' . _t('Who is online?', 'system') . '</a>',
            '/online/ip'        => '<a href="#home#/users/?act=online">' . _t('Who is online?', 'system') . '</a>',
        ];

        if (array_key_exists($place, $placelist)) {
            return str_replace('#home#', $this->config->homeurl, $placelist[$place]);
        } elseif (array_key_exists($part[0], $placelist)) {
            return str_replace('#home#', $this->config->homeurl, $placelist[$part[0]]);
        }

        return '<a href="' . $this->config->homeurl . '/">'
            . ($this->user->rights >= 6 ? '[' . ($place) . ']' : _t('Somewhere on the site', 'system'))
            . '</a>';
    }

    /**
     * Отображения личных данных пользователя
     *
     * @param int   $user Массив запроса в таблицу `users`
     * @param array $arg  Массив параметров отображения
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
            $out = '<b>' . _t('Guest', 'system') . '</b>';

            if (! empty($user['name'])) {
                $out .= ': ' . $user['name'];
            }

            if (! empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }
        } else {
            $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';

            if (file_exists(UPLOAD_PATH . 'users/avatar/' . $user['id'] . '.png')) {
                $out .= '<img src="' . $homeurl . '/upload/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="" />&#160;';
            } else {
                $out .= '<img src="' . $this->assets->url('images/old/empty.png') . '" alt="" />&#160;';
            }

            $out .= '</td><td>';

            if ($user['sex']) {
                $iconName = ($user['sex'] === 'm' ? 'm' : 'w') . ($user['datereg'] > time() - 86400 ? '_new' : '') . '.png';
                $out .= '<img src="' . $this->assets->url('images/old/' . $iconName) . '" alt="" class="icon-inline">';
            } else {
                $out .= '<img src="' . $this->assets->url('images/old/del.png') . '" alt="" class="icon-inline">';
            }

            $out .= ! $this->user->isValid() || $this->user->id == $user['id'] ? '<b>' . $user['name'] . '</b>' : '<a href="' . $homeurl . '/profile/?user=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
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
            $out .= (time() > $user['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');

            if (! empty($arg['header'])) {
                $out .= ' ' . $arg['header'];
            }

            if (! isset($arg['stshide']) && ! empty($user['status'])) {
                $out .= '<div class="status"><img src="' . $this->assets->url('images/old/label.png') . '" alt="" class="icon-inline">' . $user['status'] . '</div>';
            }

            $out .= '</td></tr></table>';
        }

        if (isset($arg['body'])) {
            $out .= '<div>' . $arg['body'] . '</div>';
        }

        $ipinf = isset($arg['iphide']) ? ! $arg['iphide'] : ($this->user->rights ? 1 : 0);
        $lastvisit = time() > $user['lastdate'] + 300 && isset($arg['lastvisit']) ? $this->displayDate($user['lastdate']) : false;

        if ($ipinf || $lastvisit || ! empty($arg['sub']) || isset($arg['footer'])) {
            $out .= '<div class="sub">';

            if (isset($arg['sub'])) {
                $out .= '<div>' . $arg['sub'] . '</div>';
            }

            if ($lastvisit) {
                $out .= '<div><span class="gray">' . _t('Last Visit', 'system') . ':</span> ' . $lastvisit . '</div>';
            }

            $iphist = '';

            if ($ipinf) {
                $out .= '<div><span class="gray">' . _t('Browser', 'system') . ':</span> ' . htmlspecialchars($user['browser']) . '</div>' .
                    '<div><span class="gray">' . _t('IP address', 'system') . ':</span> ';
                $hist = $mod == 'history' ? '&amp;mod=history' : '';
                $ip = long2ip((int) $user['ip']);

                if ($this->user->rights && isset($user['ip_via_proxy']) && $user['ip_via_proxy']) {
                    $out .= '<b class="red"><a href="' . $homeurl . '/admin/?act=search_ip&amp;ip=' . $ip . $hist . '">' . $ip . '</a></b>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                    $out .= ' / ';
                    $out .= '<a href="' . $homeurl . '/admin/?act=search_ip&amp;ip=' . long2ip($user['ip_via_proxy']) . $hist . '">' . long2ip($user['ip_via_proxy']) . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/?act=ip_whois&amp;ip=' . long2ip($user['ip_via_proxy']) . '">?</a>]';
                } elseif ($this->user->rights) {
                    $out .= '<a href="' . $homeurl . '/admin/?act=search_ip&amp;ip=' . $ip . $hist . '">' . $ip . '</a>';
                    $out .= '&#160;[<a href="' . $homeurl . '/admin/?act=ip_whois&amp;ip=' . $ip . '">?</a>]';
                } else {
                    $out .= $ip . $iphist;
                }

                if (isset($arg['iphist'])) {
                    $iptotal = $this->db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
                    $out .= '<div><span class="gray">' . _t('IP History', 'system') . ':</span> <a href="' . $homeurl . '/profile/?act=ip&amp;user=' . $user['id'] . '">[' . $iptotal . ']</a></div>';
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
    public function getFlag($locale) : string
    {
        return '<img src="' .
            $this->assets->url('images/flags/' . strtolower($locale) . '.png') .
            '" style="margin-right: 8px; vertical-align: middle">';
    }

    /**
     * Получаем данные пользователя
     *
     * @param int $id
     * @return bool|UserInterface|mixed
     */
    public function getUser(int $id = 0)
    {
        if ($id && $id !== $this->user->id) {
            $req = $this->db->query("SELECT * FROM `users` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                return $req->fetch();
            }

            return false;
        }

        return $this->user;
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
            $req = $this->db->query("SELECT * FROM `cms_contact` WHERE `user_id` = '${id}' AND `from_id` = " . $this->user->id);

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
     * @param bool   $adm
     * @return string
     */
    public function smilies($str, $adm = false)
    {
        static $smiliesCache = [];

        if (empty($smiliesCache)) {
            $file = CACHE_PATH . 'smilies-list.cache';

            if (file_exists($file) && ($smileys = file_get_contents($file)) !== false) {
                $smiliesCache = unserialize($smileys, ['allowed_classes' => false]);

                return strtr($str, ($adm ? array_merge($smiliesCache['usr'], $smiliesCache['adm']) : $smiliesCache['usr']));
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

        $day = ceil($var / 86400);

        return $var >= 86400
            ? $day . ' ' . _p('Day', 'Days', $day, 'system')
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
        $post_count = $this->db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${topic_id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
        $mod_post_count = $this->db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${topic_id}'")->fetchColumn();

        $last_post = $this->db->query("SELECT * FROM forum_messages WHERE `topic_id` = '${topic_id}' AND (`deleted` != '1' OR `deleted` IS NULL) ORDER BY id DESC LIMIT 1")->fetch();
        $mod_last_post = $this->db->query("SELECT * FROM forum_messages WHERE `topic_id` = '${topic_id}' ORDER BY id DESC LIMIT 1")->fetch();

        // Обновляем время топика
        $this->db->exec("UPDATE `forum_topic` SET
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
        ");
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
