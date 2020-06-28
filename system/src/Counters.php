<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\Notifications\Notification;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use PDO;
use Psr\Container\ContainerInterface;

class Counters
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $homeurl;

    /** @var User */
    private $user;

    public function __construct(PDO $pdo, Tools $tools, User $user, string $homeUrl)
    {
        $this->db = $pdo;
        $this->user = $user;
        $this->homeurl = $homeUrl;
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return string
     * @deprecated use albumCounters
     * TODO: содержимое albumCounters перенести в этот метод после проверки на использование
     */
    public function album()
    {
        $file = CACHE_PATH . 'count-albums.cache';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $album = $res['album'];
            $photo = $res['photo'];
            $new = $res['new'];
            $new_adm = $res['new_adm'];
        } else {
            $album = $this->db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
            $photo = $this->db->query('SELECT COUNT(*) FROM `cms_album_files`')->fetchColumn();
            $new = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` = 4')->fetchColumn();
            $new_adm = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` > 1')->fetchColumn();
            file_put_contents($file, json_encode(['album' => $album, 'photo' => $photo, 'new' => $new, 'new_adm' => $new_adm]), LOCK_EX);
        }

        $newcount = 0;
        if ($this->user->rights >= 6 && $new_adm) {
            $newcount = $new_adm;
        } elseif ($new) {
            $newcount = $new;
        }

        return $album . '&#160;/&#160;' . $photo .
            ($newcount ? '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/album/?act=top">+' . $newcount . '</a></span>' : '');
    }

    /**
     * Счетчик загруз центра
     *
     * @return string
     * @deprecated use downloadsCounters
     * TODO: содержимое downloadsCounters перенести в этот метод после проверки на использование
     */
    public function downloads()
    {
        $file = CACHE_PATH . 'count-downloads.cache';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $total = $res['total'] ?? 0;
            $new = $res['new'] ?? 0;
            $mod = $res['mod'] ?? 0;
        } else {
            $old = time() - (3 * 24 * 3600);
            $total = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'")->fetchColumn();
            $new = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `time` > '${old}'")->fetchColumn();
            $mod = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();

            file_put_contents($file, json_encode(['total' => $total, 'new' => $new, 'mod' => $mod]), LOCK_EX);
        }

        if ($new > 0) {
            $total .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=new_files">+' . $new . '</a></span>';
        }

        if ($this->user->rights == 4 || $this->user->rights >= 6) {
            if ($mod) {
                $total .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=mod_files">м. ' . $mod . '</a></span>';
            }
        }

        return $total;
    }

    /**
     * Статистика Форума
     *
     * @return string
     * @deprecated use forumCounters
     * TODO: содержимое forumCounters перенести в этот метод после проверки на использование
     */
    public function forum()
    {
        $file = CACHE_PATH . 'count-forum.cache';
        $new = '';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $top = $res['top'];
            $msg = $res['msg'];
        } else {
            $top = $this->db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` != '1' OR deleted IS NULL")->fetchColumn();
            $msg = $this->db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` != '1' OR deleted IS NULL")->fetchColumn();
            file_put_contents($file, json_encode(['top' => $top, 'msg' => $msg]), LOCK_EX);
        }

        if ($this->user->isValid() && ($new_msg = $this->forumNew()) > 0) {
            $new = '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/forum/?act=new">+' . $new_msg . '</a></span>';
        }

        return $top . '&#160;/&#160;' . $msg . $new;
    }

    /**
     * Счетчик непрочитанных тем на форуме
     *
     * $mod = 0   Возвращает число непрочитанных тем
     * $mod = 1   Выводит ссылки на непрочитанное
     *
     * @param int $mod
     * @return bool|int|string
     * @deprecated use forumUnreadCount
     */
    public function forumNew($mod = 0)
    {
        if ($this->user->isValid()) {
            $total = $this->db->query(
                "SELECT COUNT(*) FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->user->id . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                " . ($this->user->rights >= 7 ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)') . '
                '
            )->fetchColumn();

            if ($mod) {
                return $total ? '<a href="?act=new" class="pr-2">' . d__('system', 'Unread') . '</a><span class="badge badge-pill badge-danger mr-3">' . $total . '</span>' : '';
            }

            return $total;
        }
        if ($mod) {
            return '<a href="?act=new">' . d__('system', 'Last activity') . '</a>';
        }

        return false;
    }

    /**
     * @return int|mixed
     */
    public function forumUnreadCount()
    {
        $total = 0;
        if ($this->user->isValid()) {
            $total = $this->db->query(
                "SELECT COUNT(*) FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->user->id . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                " . ($this->user->rights >= 7 ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)') . '
                '
            )->fetchColumn();
        }

        return $total;
    }

    /**
     * Статистика библиотеки
     *
     * @return string
     * @deprecated use libraryCounters
     * TODO: содержимое libraryCounters перенести в этот метод после проверки на использование
     */
    public function library()
    {
        $file = CACHE_PATH . 'count-library.cache';

        if (file_exists($file) && filemtime($file) > (time() - 3200)) {
            $res = json_decode(file_get_contents($file), true);
            $total = $res['total'];
            $new = $res['new'];
            $mod = $res['mod'];
        } else {
            $total = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1')->fetchColumn();
            $new = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1')->fetchColumn();
            $mod = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();

            file_put_contents($file, json_encode(['total' => $total, 'new' => $new, 'mod' => $mod]), LOCK_EX);
        }

        if ($new) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/?act=new">+' . $new . '</a></span>';
        }

        if (($this->user->rights == 5 || $this->user->rights >= 6) && $mod) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/?act=premod">M:' . $mod . '</a></span>';
        }

        return $total;
    }

    /**
     * Счетчик посетителей онлайн
     *
     * @return string
     */
    public function online()
    {
        $file = CACHE_PATH . 'count-online.cache';

        if (file_exists($file) && filemtime($file) > (time() - 10)) {
            $res = json_decode(file_get_contents($file), true);
            $users = $res['users'];
            $guests = $res['guests'];
        } else {
            $users = $this->db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300))->fetchColumn();
            $guests = $this->db->query('SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ' . (time() - 300))->fetchColumn();

            file_put_contents($file, json_encode(['users' => $users, 'guests' => $guests]), LOCK_EX);
        }

        return $users . ' / ' . $guests;
    }

    /**
     * Количество зарегистрированных пользователей
     *
     * @return string
     * @deprecated use usersCounters
     * TODO: содержимое usersCounters перенести в этот метод после проверки на использование
     */
    public function users(): string
    {
        $counter = $this->usersCounters();
        return $counter['total'] . ($counter['new'] ? '&#160;/&#160;<span class="red">+' . $counter['new'] . '</span>' : '');
    }

    /**
     * Количество непрочитанных личных сообщений
     *
     * @return mixed
     */
    public function mail()
    {
        $new_mail = 0;
        if (! $this->user->isValid()) {
            $new_mail = $this->db->query(
                "SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $this->user->id . "'
                            WHERE `cms_mail`.`from_id`='" . $this->user->id . "'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='" . $this->user->id . "'
                            AND `cms_contact`.`ban`!='1'"
            )->fetchColumn();
        }

        return $new_mail;
    }

    /**
     * Метод возвращает количество тем, сообщений и непрочитанных сообщений на форуме
     *
     * @return array
     */
    public function forumCounters(): array
    {
        $file = CACHE_PATH . 'counters-forum.cache';
        $new_messages = 0;

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $topics = $res['topics'];
            $message = $res['messages'];
        } else {
            $topics = $this->db->query(
                "SELECT COUNT(*)
                FROM `forum_topic`
                WHERE `deleted` != '1'
                OR deleted IS NULL"
            )->fetchColumn();
            $message = $this->db->query(
                "SELECT COUNT(*)
                FROM `forum_messages`
                WHERE `deleted` != '1'
                OR deleted IS NULL"
            )->fetchColumn();
            file_put_contents($file, json_encode(['topics' => $topics, 'messages' => $message]), LOCK_EX);
        }

        if ($this->user->isValid() && ($new_msg = $this->forumNew()) > 0) {
            $new_messages = $new_msg;
        }

        return [
            'topics'       => $topics,
            'messages'     => $message,
            'new_messages' => $new_messages,
        ];
    }

    /**
     * Счетчики гостевой и админклуба
     *
     * @param int $mod
     * @return array
     */
    public function guestbookCounters($mod = 0): array
    {
        $guestbook = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm` = 0 AND `time` > ' . (time() - 86400))->fetchColumn();
        $admin_club = 0;
        if ($this->user->rights >= 1) {
            $admin_club = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=\'1\' AND `time`> ' . (time() - 86400))->fetchColumn();
        }

        return [
            'guestbook'  => $guestbook,
            'admin_club' => $admin_club,
        ];
    }

    /**
     * Счетчики загруз-центра
     *
     * @return array
     */
    public function downloadsCounters(): array
    {
        $file = CACHE_PATH . 'counters-downloads.cache';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $total = $res['total'] ?? 0;
            $new = $res['new'] ?? 0;
        } else {
            $old = time() - (3 * 24 * 3600);
            $total = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'")->fetchColumn();
            $new = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `time` > '${old}'")->fetchColumn();

            file_put_contents($file, json_encode(['total' => $total, 'new' => $new]), LOCK_EX);
        }

        return [
            'total' => $total,
            'new'   => $new,
        ];
    }

    /**
     * Статистика библиотеки
     *
     * @return array
     */
    public function libraryCounters(): array
    {
        $file = CACHE_PATH . 'counters-library.cache';

        if (file_exists($file) && filemtime($file) > (time() - 3200)) {
            $res = json_decode(file_get_contents($file), true);
            $total = $res['total'];
            $new = $res['new'];
        } else {
            $total = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1')->fetchColumn();
            $new = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1')->fetchColumn();

            file_put_contents($file, json_encode(['total' => $total, 'new' => $new]), LOCK_EX);
        }

        return [
            'total' => $total,
            'new'   => $new,
        ];
    }

    /**
     * Количество зарегистрированных пользователей
     *
     * @return array
     */
    public function usersCounters(): array
    {
        $file = CACHE_PATH . 'counters-users.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $cache = json_decode(file_get_contents($file), true);
            $total = $cache['total'];
            $new = $cache['new'];
        } else {
            $total = (new Users\User())->approved()->count();
            $new = (new Users\User())->approved()->where('datereg', '>', (time() - 86400))->count();

            file_put_contents($file, json_encode(['total' => $total, 'new' => $new]), LOCK_EX);
        }

        return [
            'total' => $total,
            'new'   => $new,
        ];
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return array
     */
    public function albumCounters(): array
    {
        $file = CACHE_PATH . 'counters-albums.cache';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = json_decode(file_get_contents($file), true);
            $album = $res['album'];
            $photo = $res['photo'];
            $new = $res['new'];
            $new_adm = $res['new_adm'];
        } else {
            $album = $this->db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
            $photo = $this->db->query('SELECT COUNT(*) FROM `cms_album_files`')->fetchColumn();
            $new = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` = 4')->fetchColumn();
            $new_adm = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` > 1')->fetchColumn();
            file_put_contents($file, json_encode(['album' => $album, 'photo' => $photo, 'new' => $new, 'new_adm' => $new_adm]), LOCK_EX);
        }

        if ($this->user->rights >= 6 && $new_adm) {
            $newcount = $new_adm;
        } elseif ($new) {
            $newcount = $new;
        }

        return [
            'album' => $album,
            'photo' => $photo,
            'new'   => $newcount ?? 0,
        ];
    }

    /**
     * Счетчик всех новостей
     *
     * @return array
     */
    public function news(): array
    {
        $total = $this->db->query('SELECT COUNT(*) FROM `news`')->fetchColumn();
        $new = $this->db->query("SELECT COUNT(*) FROM `news` WHERE `time` > '" . (time() - 259200) . "'")->fetchColumn();

        return [
            'total' => $total,
            'new'   => $new,
        ];
    }

    /**
     * Уведомления
     *
     * @return array
     */
    public function notifications(): array
    {
        $notifications = [];

        if (! $this->user->isValid()) {
            return $notifications;
        }

        if ($this->user->rights >= 7) {
            $notifications['reg_total'] = $this->db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn();
            $notifications['library_mod'] = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();
            $notifications['downloads_mod'] = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();
        }

        if (! empty($this->user->ban)) {
            $notifications['ban'] = 1;
        }

        if ($this->user->comm_count > $this->user->comm_old) {
            $notifications['guestbook_comments'] = ($this->user->comm_count - $this->user->comm_old);
        }

        $notifications['new_mail'] = $this->db->query(
            "SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $this->user->id . "'
                            WHERE `cms_mail`.`from_id`='" . $this->user->id . "'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='" . $this->user->id . "'
                            AND `cms_contact`.`ban`!='1'"
        )->fetchColumn();

        $notifications['new_album_comm'] = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = \'' . $this->user->id . '\' AND `unread_comments` = 1')->fetchColumn();

        // Временный костыль для обратной совместимости
        $default = ['show_forum_unread' => false];
        $settings = ! empty($this->user->notification_settings) ? json_decode($this->user->notification_settings, true) : [];
        $notification_settings = array_merge($default, $settings);
        if ($notification_settings['show_forum_unread']) {
            $forum_counters = $this->forumCounters();
            $notifications['forum_new'] = $forum_counters['new_messages'];
        }

        $notifications['notifications'] = (new Notification())->unread()->count();
        $notifications['all'] = array_sum($notifications);

        return $notifications;
    }

    /**
     * Метод получает массив счетчиков различных систем аналитики
     *
     * @return array
     */
    public function counters(): array
    {
        $counters = [];
        $req = $this->db->query('SELECT * FROM `cms_counters` WHERE `switch` = 1 ORDER BY `sort`');

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $link1 = ($res['mode'] === 1 || $res['mode'] === 2) ? $res['link1'] : $res['link2'];
                $link2 = $res['mode'] === 2 ? $res['link1'] : $res['link2'];
                $count = defined('_IS_HOMEPAGE') ? $link1 : $link2;
                if (! empty($count)) {
                    $counters[] = $count;
                }
            }
        }

        return $counters;
    }
}
