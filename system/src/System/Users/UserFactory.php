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

use Johncms\Http\Environment;
use PDO;

/**
 * Class UserFactory
 *
 * @package Johncms\System\Users
 *
 * Keep the @deprecated tag without an inline description: Symfony's
 * ContainerBuilder logs a deprecation on every non-cached container build
 * for services whose class docblock contains "@deprecated <text>".
 *
 * @deprecated
 * @see \Johncms\Users\UserFactory
 */
class UserFactory
{
    public function __construct(
        private readonly PDO $db,
        private readonly Environment $env,
    ) {
    }

    /**
     * Builds the shared instance holding the current user: a guest, since the request it belongs
     * to is not known at container build time. It is load() that puts the visitor into it, once
     * per request.
     */
    public function __invoke(): User
    {
        return new User();
    }

    /**
     * Loads the user the authenticator chain identified into the shared current-user instance.
     * Also runs the ban check and records the IP history.
     */
    public function load(User $currentUser, int $userId): void
    {
        $currentUser->setProperties($this->getUserData($userId));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserData(int $userId): array
    {
        if ($userId === 0) {
            return [];
        }

        $req = $this->db->query('SELECT * FROM `users` WHERE `id` = ' . $userId);

        if ($req->rowCount() === 0) {
            return [];
        }

        $userData = $req->fetch();

        $this->banCheck($userData); // Проверяем на бан
        $this->ipHistory($userData); // Фиксируем историю IP

        return $userData;
    }

    protected function banCheck(array &$userData): void
    {
        $userData['ban'] = [];

        $req = $this->db->query(
            'SELECT * FROM `cms_ban_users`
            WHERE `user_id` = ' . $userData['id'] . '
            AND `ban_time` > ' . time()
        );

        if ($req->rowCount()) {
            $userData['rights'] = 0;

            while ($res = $req->fetch()) {
                $userData['ban'][$res['ban_type']] = 1;
            }
        }
    }

    /**
     * Фиксация истории IP адресов пользователя
     *
     * @param array $userData
     * @return void
     */
    protected function ipHistory(array $userData): void
    {
        if ($userData['ip'] != $this->env->getIp() || $userData['ip_via_proxy'] != $this->env->getIpViaProxy()) {
            // Удаляем из истории текущий адрес (если есть)
            $this->db->exec(
                'DELETE FROM `cms_users_iphistory`
                WHERE `user_id` = ' . $userData['id'] . "
                AND `ip` = '" . $this->env->getIp() . "'
                AND `ip_via_proxy` = '" . $this->env->getIpViaProxy() . "'
                LIMIT 1"
            );

            // Вставляем в историю предыдущий адрес IP
            $this->db->exec(
                'INSERT INTO `cms_users_iphistory` SET
                `user_id` = ' . $userData['id'] . ",
                `ip` = '" . $userData['ip'] . "',
                `ip_via_proxy` = '" . $userData['ip_via_proxy'] . "',
                `time` = '" . $userData['lastdate'] . "'"
            );

            // Обновляем текущий адрес в таблице `users`
            $this->db->exec(
                "UPDATE `users` SET
                `ip` = '" . $this->env->getIp() . "',
                `ip_via_proxy` = '" . $this->env->getIpViaProxy() . "'
                WHERE `id` = " . $userData['id']
            );
        }
    }
}
