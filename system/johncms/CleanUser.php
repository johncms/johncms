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

class CleanUser
{
    /**
     * @var \PDO
     */
    private $db;

    public function __construct()
    {
        $this->db = \App::getContainer()->get(\PDO::class);
    }

    public function removeUser($clean_id)
    {
        // Удаляем историю нарушений
        $this->db->exec("DELETE FROM `cms_ban_users` WHERE `user_id` = '" . $clean_id . "'");
        // Удаляем историю IP
        $this->db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $clean_id . "'");
        // Удаляем пользователя
        $this->db->exec("DELETE FROM `users` WHERE `id` = '" . $clean_id . "'");
    }

    /**
     * Удаляем пользовательские альбомы
     *
     * @param $clean_id
     */
    public function removeAlbum($clean_id)
    {
        // Удаляем папку с файлами картинок
        $dir = ROOT_PATH . 'files/users/album/' . $clean_id;
        if (is_dir($dir)) {
            $this->removeDir($dir);
        }

        // Чистим таблицы
        $req = $this->db->query("SELECT `id` FROM `cms_album_files` WHERE `user_id` = '" . $clean_id . "'");
        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $this->db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_downloads` WHERE `file_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '" . $res['id'] . "'");
            }
        }

        $this->db->exec("DELETE FROM `cms_album_cat` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_album_files` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_album_downloads` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_album_views` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_album_votes` WHERE `user_id` = '" . $clean_id . "'");
    }

    /**
     * Удаляем почту и контакты
     *
     * @param $clean_id
     */
    public function removeMail($clean_id)
    {
        // Удаляем файлы юзера из почты
        $req = $this->db->query("SELECT * FROM `cms_mail` WHERE (`user_id` OR `from_id` = '" . $clean_id . "') AND `file_name` != ''");

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                // Удаляем файлы почты
                if (is_file(ROOT_PATH . 'files/mail/' . $res['file_name'])) {
                    @unlink('../files/mail/' . $res['file_name']);
                }
            }
        }

        $this->db->exec("DELETE FROM `cms_mail` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_mail` WHERE `from_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_contact` WHERE `user_id` = '" . $clean_id . "'");
        $this->db->exec("DELETE FROM `cms_contact` WHERE `from_id` = '" . $clean_id . "'");
    }

    /**
     * Удаляем Карму
     *
     * @param $clean_id
     */
    public function removeKarma($clean_id)
    {
        $this->db->exec("DELETE FROM `karma_users` WHERE `karma_user` = '" . $clean_id . "'");
    }

    public function cleanForum($clean_id)
    {
        // Скрываем темы на форуме
        $this->db->exec("UPDATE `forum` SET `close` = '1', `close_who` = 'SYSTEM' WHERE `type` = 't' AND `user_id` = '" . $clean_id . "'");
        // Скрываем посты на форуме
        $this->db->exec("UPDATE `forum` SET `close` = '1', `close_who` = 'SYSTEM' WHERE `type` = 'm' AND `user_id` = '" . $clean_id . "'");
        // Удаляем метки прочтения на Форуме
        $this->db->exec("DELETE FROM `cms_forum_rdm` WHERE `user_id` = '" . $clean_id . "'");
    }

    /**
     * Удаляем личную гостевую
     *
     * @param $clean_id
     */
    public function removeGuestbook($clean_id)
    {
        $this->db->exec("DELETE FROM `cms_users_guestbook` WHERE `sub_id` = '" . $clean_id . "'");
    }

    /**
     * Удаляем все комментарии пользователя
     *
     * @param $clean_id
     */
    public function cleanComments($clean_id)
    {
        $req = $this->db->query("SELECT `name` FROM `users` WHERE `id` = " . $clean_id);

        if ($req->rowCount()) {
            $res = $req->fetch();

            // Удаляем из Библиотеки
            $this->db->exec("DELETE FROM `cms_library_comments` WHERE `user_id` = '" . $clean_id . "'");
            // Удаляем из Загрузок
            $this->db->exec("DELETE FROM `download__comments` WHERE `user_id` = '" . $clean_id . "'");
            // Удаляем комментарии из личных гостевых
            $this->db->exec("DELETE FROM `cms_users_guestbook` WHERE `user_id` = '" . $clean_id . "'");
            // Удаляем комментарии из личных фотоальбомов
            $this->db->exec("DELETE FROM `cms_album_comments` WHERE `user_id` = '" . $clean_id . "'");
            // Удаляем посты из гостевой
            $this->db->exec("DELETE FROM `guest` WHERE `user_id` = '" . $clean_id . "'");
        }
    }

    /**
     * Рекурсивная функция удаления каталогов с файлами
     *
     * @param $dir
     */
    private function removeDir($dir)
    {
        if ($objs = glob($dir . "/*")) {
            foreach ($objs as $obj) {
                is_dir($obj) ? $this->removeDir($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }
}
