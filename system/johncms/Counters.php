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

use Psr\Container\ContainerInterface;

class Counters
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var Api\UserInterface::class
     */
    private $systemUser;

    /**
     * @var \Johncms\Tools
     */
    private $tools;

    private $homeurl;

    public function __invoke(ContainerInterface $container)
    {

        $this->db = $container->get(\PDO::class);
        $this->systemUser = $container->get(Api\UserInterface::class);
        $this->tools = $container->get(Api\ToolsInterface::class);
        $this->homeurl = $container->get('config')['johncms']['homeurl'];

        return $this;
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return string
     */
    public function album()
    {
        $file = ROOT_PATH . 'files/cache/count_album.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $cnt['album'] = $res['album'];
            $cnt['photo'] = $res['photo'];
            $cnt['new'] = $res['new'];
            $cnt['new_adm'] = $res['new_adm'];
        } else {
            $cnt = $this->db->query('SELECT (
SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`) album, (
SELECT COUNT(*) FROM `cms_album_files`) photo, (
SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` = 4) `new`, (
SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` > 1) new_adm')->fetch();
            file_put_contents($file, serialize(['album' => $cnt['album'], 'photo' => $cnt['photo'], 'new' => $cnt['new'], 'new_adm' => $cnt['new_adm']]), LOCK_EX);
        }

        $newcount = 0;
        if ($this->systemUser->rights >= 6 && $cnt['new_adm']) {
            $newcount = $cnt['new_adm'];
        } elseif ($cnt['new']) {
            $newcount = $cnt['new'];
        }

        return $cnt['album'] . '&#160;/&#160;' . $cnt['photo'] .
            ($newcount ? '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/album/index.php?act=top">+' . $newcount . '</a></span>' : '');
    }

    /**
     * Счетчик загруз центра
     *
     * @return string
     */
    public function downloads()
    {
        $file = ROOT_PATH . 'files/cache/count_downloads.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $cnt['total'] = isset($res['total']) ? $res['total'] : 0;
            $cnt['new'] = isset($res['new']) ? $res['new'] : 0;
            $cnt['mod'] = isset($res['mod']) ? $res['mod'] : 0;
        } else {
            $old = time() - (3 * 24 * 3600);
            $cnt = $this->db->query('SELECT (
SELECT COUNT(*) FROM `download__files` WHERE `type` = 2) total, (
SELECT COUNT(*) FROM `download__files` WHERE `type` = 2 AND `time` > ' . $old . ') `new`, (
SELECT COUNT(*) FROM `download__files` WHERE `type` = 3) `mod`')->fetch();

            file_put_contents($file, serialize(['total' => $cnt['total'], 'new' => $cnt['new'], 'mod' => $cnt['mod']]), LOCK_EX);
        }

        if ($cnt['new'] > 0) {
            $cnt['total'] .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=new_files">+' . $cnt['new'] . '</a></span>';
        }

        if ($this->systemUser->rights == 4 || $this->systemUser->rights >= 6) {
            if ($cnt['mod']) {
                $cnt['total'] .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=mod_files">м. ' . $cnt['mod'] . '</a></span>';
            }
        }

        return $cnt['total'];
    }

    /**
     * Статистика Форума
     *
     * @return string
     */
    public function forum()
    {
        $file = ROOT_PATH . 'files/cache/count_forum.dat';
        $new = '';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $cnt['top'] = $res['top'];
            $cnt['msg'] = $res['msg'];
        } else {
            $cnt = $this->db->query('SELECT (
SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` <> 1 OR deleted IS NULL) top, (
SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` <> 1 OR deleted IS NULL) msg')->fetch();
            file_put_contents($file, serialize(['top' => $cnt['top'], 'msg' => $cnt['msg']]), LOCK_EX);
        }

        if ($this->systemUser->isValid() && ($new_msg = $this->forumNew()) > 0) {
            $new = '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/forum/index.php?act=new">+' . $new_msg . '</a></span>';
        }

        return $cnt['top'] . '&#160;/&#160;' . $cnt['msg'] . $new;
    }

    /**
     * Счетчик непрочитанных тем на форуме
     *
     * $mod = 0   Возвращает число непрочитанных тем
     * $mod = 1   Выводит ссылки на непрочитанное
     *
     * @param int $mod
     * @return bool|int|string
     */
    public function forumNew($mod = 0)
    {
        if ($this->systemUser->isValid()) {
            $total = $this->db->query("SELECT COUNT(*) FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->systemUser->id . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`) 
                " . ($this->systemUser->rights >= 7 ? "" : " AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)") . "
                ")->fetchColumn();

            if ($mod) {
                return '<a href="index.php?act=new&amp;do=period">' . _t('Show for Period', 'system') . '</a>' .
                    ($total ? '<br><a href="index.php?act=new">' . _t('Unread', 'system') . '</a>&#160;<span class="red">(<b>' . $total . '</b>)</span>' : '');
            } else {
                return $total;
            }
        } else {
            if ($mod) {
                return '<a href="index.php?act=new">' . _t('Last activity', 'system') . '</a>';
            } else {
                return false;
            }
        }
    }

    /**
     * Статистика гостевой
     *
     * $mod = 1    колличество новых в гостевой
     * $mod = 2    колличество новых в Админ-Клубе
     *
     * @param int $mod
     * @return int|string
     */
    public function guestbook($mod = 0)
    {
        $count = 0;

        switch ($mod) {
            case 1:
                $count = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=0 AND `time` > ' . (time() - 86400))->fetchColumn();
                break;

            case 2:
                if ($this->systemUser->rights >= 1) {
                    $count = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=1 AND `time` > ' . (time() - 86400))->fetchColumn();
                    //$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time` > '" . (time() - 86400) . "'"), 0);
                }
                break;

            default:
                $count = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm` = 0 AND `time` > ' . (time() - 86400))->fetchColumn();

                if ($this->systemUser->rights >= 1) {
                    $adm = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=\'1\' AND `time`> ' . (time() - 86400))->fetchColumn();
                    $count = $count . '&#160;/&#160;<span class="red"><a href="guestbook/index.php?act=ga&amp;do=set">' . $adm . '</a></span>';
                }
        }

        return $count;
    }

    /**
     * Статистика библиотеки
     *
     * @return string
     */
    public function library()
    {
        $file = ROOT_PATH . 'files/cache/count_library.dat';

        if (file_exists($file) && filemtime($file) > (time() - 3200)) {
            $res = unserialize(file_get_contents($file));
            $cnt['total'] = $res['total'];
            $cnt['new'] = $res['new'];
            $cnt['mod'] = $res['mod'];
        } else {
            $cnt = $this->db->query('SELECT (
SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1) total, (
SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1) `new`, (
SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0) `mod`')->fetch();
            
            file_put_contents($file, serialize(['total' => $cnt['total'], 'new' => $cnt['new'], 'mod' => $cnt['mod']]), LOCK_EX);
        }

        if ($cnt['new']) {
            $cnt['total'] .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/index.php?act=new">+' . $cnt['new'] . '</a></span>';
        }

        if (($this->systemUser->rights == 5 || $this->systemUser->rights >= 6) && $cnt['mod']) {
            $cnt['total'] .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/index.php?act=premod">M:' . $cnt['mod'] . '</a></span>';
        }

        return $cnt['total'];
    }

    /**
     * Счетчик посетителей онлайн
     *
     * @return string
     */
    public function online()
    {
        $file = ROOT_PATH . 'files/cache/count_online.dat';

        if (file_exists($file) && filemtime($file) > (time() - 10)) {
            $res = unserialize(file_get_contents($file));
            $cnt['users'] = $res['users'];
            $cnt['guests'] = $res['guests'];
        } else {
            $cnt = $this->db->query('SELECT (
SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300) . ') users, (
SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ' . (time() - 300) . ') guests')->fetch();
            
            file_put_contents($file, serialize(['users' => $cnt['users'], 'guests' => $cnt['guests']]), LOCK_EX);
        }

        return '<a href="' . $this->homeurl . '/users/index.php?act=online">' . $this->tools->image('menu_online.png') . $cnt['users'] . ' / ' . $cnt['guests'] . '</a>';
    }

    /**
     * Количество зарегистрированных пользователей
     *
     * @return string
     */
    public function users()
    {
        $file = ROOT_PATH . 'files/cache/count_users.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $cnt['total'] = $res['total'];
            $cnt['new'] = $res['new'];
        } else {
            $cnt = $this->db->query('SELECT (
SELECT COUNT(*) FROM `users`) total, (
SELECT COUNT(*) FROM `users` WHERE `datereg` > ' . (time() - 86400) . ') `new`')->fetch();
            
            file_put_contents($file, serialize(['total' => $cnt['total'], 'new' => $cnt['new']]), LOCK_EX);
        }

        return $cnt['total'] . ($cnt['new'] ? '&#160;/&#160;<span class="red">+' . $cnt['new'] . '</span>' : '');
    }
}
