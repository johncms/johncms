<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Users;

use Johncms\Api\EnvironmentInterface;
use Psr\Container\ContainerInterface;

class UserFactory
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var EnvironmentInterface
     */
    private $env;

    private $userData;

    public function __invoke(ContainerInterface $container)
    {
        $this->db = $container->get(\PDO::class);
        $this->env = $container->get(EnvironmentInterface::class);
        $this->userData = $this->authorize();

        return new User($this->userData);
    }

    /**
     * Авторизация пользователя и получение его данных из базы
     */
    protected function authorize()
    {
        $user_id = false;
        $user_ps = false;

        if (isset($_SESSION['uid'], $_SESSION['ups'])) {
            // Авторизация по сессии
            $user_id = (int) ($_SESSION['uid']);
            $user_ps = $_SESSION['ups'];
        } elseif (isset($_COOKIE['cuid'], $_COOKIE['cups'])) {
            // Авторизация по COOKIE
            $user_id = abs((int) (base64_decode(trim($_COOKIE['cuid']))));
            $_SESSION['uid'] = $user_id;
            $user_ps = md5(trim($_COOKIE['cups']));
            $_SESSION['ups'] = $user_ps;
        }

        if ($user_id && $user_ps) {
            $req = $this->db->query('SELECT * FROM `users` WHERE `id` = ' . $user_id);

            if ($req->rowCount()) {
                $userData = $req->fetch();
                $permit = $userData['failed_login'] < 3
                || $userData['failed_login'] > 2
                && $userData['ip'] == $this->env->getIp()
                && $userData['browser'] == $this->env->getUserAgent()
                    ? true
                    : false;

                if ($permit && $user_ps === $userData['password']) {
                    // Проверяем на бан
                    $userData['ban'] = $this->banCheck($userData['id']);

                    // Если есть бан, обнуляем привилегии
                    if (! empty($userData['ban'])) {
                        $userData['rights'] = 0;
                    }

                    // Фиксируем историю IP
                    if ($userData['ip'] != $this->env->getIp() || $userData['ip_via_proxy'] != $this->env->getIpViaProxy()) {
                        $this->ipHistory($userData);
                    }

                    return $userData;
                }
                // Если авторизация не прошла
                $this->db->query("UPDATE `users` SET `failed_login` = '" . ($userData['failed_login'] + 1) . "' WHERE `id` = " . $userData['id']);
                $this->userUnset();
            } else {
                // Если пользователь не существует
                $this->userUnset();
            }
        }

        return $this->userTemplate();
    }

    /**
     * Проверка на бан
     *
     * @param int $userId
     * @return array
     */
    protected function banCheck($userId)
    {
        $ban = [];
        $req = $this->db->query('SELECT * FROM `cms_ban_users` WHERE `user_id` = ' . $userId . " AND `ban_time` > '" . time() . "'");

        while ($res = $req->fetch()) {
            $ban[$res['ban_type']] = 1;
        }

        return $ban;
    }

    /**
     * Фиксация истории IP адресов пользователя
     *
     * @param array $userData
     */
    protected function ipHistory(array $userData)
    {
        // Удаляем из истории текущий адрес (если есть)
        $this->db->exec("DELETE FROM `cms_users_iphistory`
          WHERE `user_id` = '" . $userData['id'] . "'
          AND `ip` = '" . $this->env->getIp() . "'
          AND `ip_via_proxy` = '" . $this->env->getIpViaProxy() . "'
          LIMIT 1
        ");

        // Вставляем в историю предыдущий адрес IP
        $this->db->exec("INSERT INTO `cms_users_iphistory` SET
          `user_id` = '" . $userData['id'] . "',
          `ip` = '" . $userData['ip'] . "',
          `ip_via_proxy` = '" . $userData['ip_via_proxy'] . "',
          `time` = '" . $userData['lastdate'] . "'
        ");

        // Обновляем текущий адрес в таблице `users`
        $this->db->exec("UPDATE `users` SET
          `ip` = '" . $this->env->getIp() . "',
          `ip_via_proxy` = '" . $this->env->getIpViaProxy() . "'
          WHERE `id` = '" . $userData['id'] . "'
        ");
    }

    protected function userTemplate() : array
    {
        return [
            'id'            => 0,
            'name'          => '',
            'name_lat'      => '',
            'password'      => '',
            'rights'        => 0,
            'failed_login'  => 0,
            'imname'        => '',
            'sex'           => '',
            'komm'          => 0,
            'postforum'     => 0,
            'postguest'     => 0,
            'yearofbirth'   => 0,
            'datereg'       => 0,
            'lastdate'      => 0,
            'mail'          => '',
            'icq'           => '',
            'skype'         => '',
            'jabber'        => '',
            'www'           => '',
            'about'         => '',
            'live'          => '',
            'mibile'        => '',
            'status'        => '',
            'ip'            => '',
            'ip_via_proxy'  => '',
            'browser'       => '',
            'preg'          => '',
            'regadm'        => '',
            'mailvis'       => '',
            'dayb'          => '',
            'monthb'        => '',
            'sestime'       => '',
            'total_on_site' => '',
            'lastpost'      => '',
            'rest_code'     => '',
            'rest_time'     => '',
            'movings'       => '',
            'place'         => '',
            'set_user'      => '',
            'set_forum'     => '',
            'set_mail'      => '',
            'karma_plus'    => '',
            'karma_minus'   => '',
            'karma_time'    => '',
            'karma_off'     => '',
            'comm_count'    => '',
            'comm_old'      => '',
            'smileys'       => '',
            'ban'           => [],
        ];
    }

    /**
     * Уничтожаем данные авторизации юзера
     */
    protected function userUnset()
    {
        unset($_SESSION['uid'], $_SESSION['ups']);

        setcookie('cuid', '');
        setcookie('cups', '');
    }
}
