<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Users;

use Johncms\System\Container\Factory;
use PDO;

class UserClean
{
    /**
     * @var PDO
     */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function removeUser(int $cleanId): void
    {
        // Удаляем историю нарушений
        $this->db->exec("DELETE FROM `cms_ban_users` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем историю IP
        $this->db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем пользователя
        $this->db->exec("DELETE FROM `users` WHERE `id` = '" . $cleanId . "'");
    }

    /**
     * Удаляем пользовательские альбомы
     *
     * @param $cleanId
     * @return void
     */
    public function removeAlbum(int $cleanId): void
    {
        // Удаляем папку с файлами картинок
        $dir = UPLOAD_PATH . 'users/album/' . $cleanId;
        if (is_dir($dir)) {
            $this->removeDir($dir);
        }

        // Чистим таблицы
        $req = $this->db->query("SELECT `id` FROM `cms_album_files` WHERE `user_id` = '" . $cleanId . "'");
        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $this->db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_downloads` WHERE `file_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'");
                $this->db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '" . $res['id'] . "'");
            }
        }

        $this->db->exec("DELETE FROM `cms_album_cat` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_album_files` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_album_downloads` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_album_views` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_album_votes` WHERE `user_id` = '" . $cleanId . "'");
    }

    /**
     * Удаляем почту и контакты
     *
     * @param $cleanId
     * @return void
     */
    public function removeMail(int $cleanId): void
    {
        // Удаляем файлы юзера из почты
        $req = $this->db->query(
            "SELECT * FROM `cms_mail` WHERE (`user_id` OR `from_id` = '" . $cleanId . "') AND `file_name` != ''"
        );

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                // Удаляем файлы почты
                if (is_file(UPLOAD_PATH . 'mail/' . $res['file_name'])) {
                    unlink(UPLOAD_PATH . 'mail/' . $res['file_name']);
                }
            }
        }

        $this->db->exec("DELETE FROM `cms_mail` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_mail` WHERE `from_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_contact` WHERE `user_id` = '" . $cleanId . "'");
        $this->db->exec("DELETE FROM `cms_contact` WHERE `from_id` = '" . $cleanId . "'");
    }

    /**
     * Удаляем Карму
     *
     * @param $cleanId
     * @return void
     */
    public function removeKarma(int $cleanId): void
    {
        $this->db->exec("DELETE FROM `karma_users` WHERE `karma_user` = '" . $cleanId . "'");
    }

    public function cleanForum(int $cleanId): void
    {
        // Скрываем темы на форуме
        $this->db->exec(
            "UPDATE `forum_topic` SET `deleted` = '1', `deleted_by` = 'SYSTEM' WHERE `user_id` = '" . $cleanId . "'"
        );
        // Скрываем посты на форуме
        $this->db->exec(
            "UPDATE `forum_messages` SET `deleted` = '1', `deleted_by` = 'SYSTEM' WHERE `user_id` = '" . $cleanId . "'"
        );
        // Удаляем метки прочтения на Форуме
        $this->db->exec("DELETE FROM `cms_forum_rdm` WHERE `user_id` = '" . $cleanId . "'");
    }

    /**
     * Удаляем личную гостевую
     *
     * @param $cleanId
     * @return void
     */
    public function removeGuestbook(int $cleanId): void
    {
        $this->db->exec("DELETE FROM `cms_users_guestbook` WHERE `sub_id` = '" . $cleanId . "'");
    }

    /**
     * Удаляем все комментарии пользователя
     *
     * @param $cleanId
     * @return void
     */
    public function cleanComments(int $cleanId): void
    {
        // Удаляем из Библиотеки
        $this->db->exec("DELETE FROM `cms_library_comments` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем из Загрузок
        $this->db->exec("DELETE FROM `download__comments` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем комментарии из личных гостевых
        $this->db->exec("DELETE FROM `cms_users_guestbook` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем комментарии из личных фотоальбомов
        $this->db->exec("DELETE FROM `cms_album_comments` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем посты из гостевой
        $this->db->exec("DELETE FROM `guest` WHERE `user_id` = '" . $cleanId . "'");
    }

    private function removeDir(string $dir): void
    {
        if ($objs = glob($dir . '/*')) {
            foreach ($objs as $obj) {
                is_dir($obj) ? $this->removeDir($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }
}
