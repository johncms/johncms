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

use Carbon\Carbon;
use Johncms\System\i18n\Translator;
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
    public function displayDate(int $var): string
    {
        $shift = $this->config['timeshift'] + $this->userConfig->timeshift;

        /** @var Translator $translator */
        $translator = di(Translator::class);

        return Carbon::createFromTimestamp($var, $shift)
            ->locale($translator->getLocale())
            ->calendar(
                null,
                [
                    'lastWeek' => 'lll',
                    'sameElse' => 'lll',
                ]
            );
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

        $url = filter_var($url, FILTER_SANITIZE_URL);

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
     *
     * @param string $place
     * @return string
     */
    public function displayPlace(string $place): string
    {
        $place = rtrim($place, '/');

        if (empty($place)) {
            $place = '/';
        }

        $part = explode('?', $place);

        $places = require CONFIG_PATH . 'places.global.php';

        $places_local = [];
        if (file_exists(CONFIG_PATH . 'places.local.php')) {
            $places_local = require CONFIG_PATH . 'places.local.php';
        }

        $places_list = array_merge($places, $places_local);

        if (array_key_exists($place, $places_list)) {
            return str_replace('#home#', $this->config['homeurl'], $places_list[$place]);
        }

        if (array_key_exists($part[0], $places_list)) {
            return str_replace('#home#', $this->config['homeurl'], $places_list[$part[0]]);
        }

        return '<a href="' . $this->config['homeurl'] . ($this->user->rights >= 6 ? $place : '') . '/">'
            . d__('system', 'Somewhere on the site')
            . '</a>';
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
     * @param bool $to_lowercase
     * @return string
     */
    public function rusLat($str, bool $to_lowercase = true): string
    {
        $replace = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
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
            'ь' => '\'',
            'ы' => 'y',
            'ъ' => '\'',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ь' => '\'',
            'Ы' => 'Y',
            'Ъ' => '\'',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        ];

        if ($to_lowercase) {
            $str = mb_strtolower($str);
        }
        return strtr($str, $replace);
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

    /**
     * get all parent sections
     *
     * @param $items , $parent
     * @return array
     */
    public function getSections(array &$items, $parent): array
    {
        $res = $this->db->query("SELECT `id`, `name`, `section_type`, `parent` FROM `forum_sections` WHERE `id` = '${parent}'")->fetch();
        if ($res != false) {
            $items[] = $res;
            return $this->getSections($items, $res['parent']);
        }
        krsort($items);
        return $items;
    }

    public function getSectionsTree(array &$section_tree, $parent = 0, $mark = ''): array
    {
        $req = $this->db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${parent}' ORDER BY `sort` ASC");
        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $section_tree[] = [
                    'id'     => $res['id'],
                    'name'   => $mark . ' ' . $res['name'],
                    'parent' => $res['parent'],
                ];
                $section_tree = $this->getSectionsTree($section_tree, $res['id'], $mark . ' . ');
            }
        }
        return $section_tree;
    }
}
