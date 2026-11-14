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

use Johncms\Auth\AuthTables;
use Johncms\Comments\Application\CommentsService;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Storage\StorageInterface;
use PDO;

class UserClean
{
    /**
     * @var PDO
     */
    private $db;

    public function __construct(
        PDO $db,
        private CommentsService $comments,
        private EditorUploads $editorUploads,
    ) {
        $this->db = $db;
    }

    public function removeUser(int $cleanId): void
    {
        // Удаляем историю нарушений
        $this->db->exec("DELETE FROM `cms_ban_users` WHERE `user_id` = '" . $cleanId . "'");
        // Удаляем историю IP
        $this->db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $cleanId . "'");
        // The authentication tables have no foreign keys, so what belongs to the account goes by hand
        foreach ([AuthTables::USER_ROLES, AuthTables::USER_IDENTITIES, AuthTables::AUTH_SESSIONS, AuthTables::PASSWORD_RESET_TOKENS] as $table) {
            $this->db->exec('DELETE FROM `' . $table . '` WHERE `user_id` = ' . $cleanId);
        }
        // The pictures uploaded from an editor and never sent belong to nobody else.
        $this->editorUploads->detachUser($cleanId);
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
        di(StorageInterface::class)->deleteDirectory('users/album/' . $cleanId);

        // Чистим таблицы
        $photoIds = array_map('intval', $this->db->query("SELECT `id` FROM `cms_album_files` WHERE `user_id` = '" . $cleanId . "'")->fetchAll(PDO::FETCH_COLUMN));
        // The comments of the photos are the comments of the core, of the kind the album declares.
        $this->comments->deleteForSubjects('album', $photoIds);
        foreach ($photoIds as $photoId) {
            $this->db->exec("DELETE FROM `cms_album_downloads` WHERE `file_id` = '" . $photoId . "'");
            $this->db->exec("DELETE FROM `cms_album_views` WHERE `file_id` = '" . $photoId . "'");
            $this->db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '" . $photoId . "'");
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
                di(StorageInterface::class)->delete('mail/' . basename((string) $res['file_name']));
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
        // The guestbook of a profile is kept as the comments of the core under the account.
        $this->comments->deleteForSubjects('profile_guestbook', [$cleanId]);
    }

    /**
     * Удаляем все комментарии пользователя
     *
     * @param $cleanId
     * @return void
     */
    public function cleanComments(int $cleanId): void
    {
        $this->comments->deleteForUser($cleanId);
        // Удаляем посты из гостевой
        $this->db->exec("DELETE FROM `guest` WHERE `user_id` = '" . $cleanId . "'");
    }
}
