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
use Johncms\Http\Request;
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
     * to is not known at container build time. It is authenticate() that loads the visitor into
     * it, once per request.
     */
    public function __invoke(): User
    {
        return new User();
    }

    /**
     * Identifies the visitor of the given request by their cookies and loads them into the shared
     * current-user instance. Also runs the ban check and records the IP history.
     */
    public function authenticate(User $currentUser, Request $request): void
    {
        $currentUser->setProperties($this->getUserData($request));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserData(Request $request): array
    {
        $userPassword = md5($request->cookies->getString('cups', ''));
        $userId = $request->cookies->getInt('cuid', 0);

        if ($userId && $userPassword) {
            return $this->authentification($userId, $userPassword);
        }

        return [];
    }

    private function authentification(int $userId, string $userPassword): array
    {
        $req = $this->db->query('SELECT * FROM `users` WHERE `id` = ' . $userId);

        if ($req->rowCount()) {
            $userData = $req->fetch();

            if ($this->checkPermit($userData) && $userPassword === $userData['password']) {
                $this->banCheck($userData); // Проверяем на бан
                $this->ipHistory($userData); // Фиксируем историю IP
                return $userData;
            }
            // Если авторизация не прошла
            $this->db->exec(
                "UPDATE `users` SET `failed_login` = '" . ($userData['failed_login'] + 1) .
                "' WHERE `id` = " . $userData['id']
            );
            $this->userUnset();
        } else {
            // Если пользователь не существует
            $this->userUnset();
        }

        return [];
    }

    private function checkPermit(array $userData): bool
    {
        return $userData['failed_login'] < 3
            || ($userData['failed_login'] > 2
                && $userData['ip'] == $this->env->getIp()
                && $userData['browser'] == $this->env->getUserAgent());
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

    /**
     * Уничтожаем данные авторизации юзера
     *
     * @return void
     */
    protected function userUnset(): void
    {
        setcookie('cuid', '');
        setcookie('cups', '');
    }
}
