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

use Johncms\System\Http\Environment;
use Psr\Container\ContainerInterface;

class UserStat
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get(\PDO::class);
        $this->env = $container->get(Environment::class);
        $this->user = $container->get(User::class);
        $this->config = $container->get('config');

        if ($this->user->isValid()) {
            $this->processUser();
        } else {
            $this->processGuest();
        }
    }

    private function processUser(): void
    {
        $place = $this->determinePlace();

        if ($this->user->lastdate < (time() - 300)) {
            $sestime = time();
            $movings = 1;
        } else {
            $sestime = $this->user->sestime;
            $movings = $this->user->place != $place
                ? $this->user->movings + 1
                : $this->user->movings;
        }

        $karma_time = $this->user->karma_time;
        if (
            ! $this->user->karma_off &&
            $this->config['johncms']['karma']['on'] &&
            $this->user->karma_time <= (time() - 86400)
        ) {
            $karma_time = time();
        }

        $update = $this->db->prepare(
            'UPDATE `users` SET
            `lastdate` = ?,
            `sestime`  = ?,
            `movings`  = ?,
            `place` = ?,
            `browser` = ?,
            `karma_time` = ?
            WHERE `id` = ?'
        );
        $update->execute(
            [
                time(),
                $sestime,
                $movings,
                $place,
                $this->env->getUserAgent(),
                $karma_time,
                $this->user->id,
            ]
        );
    }

    private function processGuest(): void
    {
        $place = $this->determinePlace();
        $session = md5($this->env->getIp() . $this->env->getIpViaProxy() . $this->env->getUserAgent());

        $stmt = $this->db->prepare('SELECT * FROM `cms_sessions` WHERE `session_id` = ?');
        $stmt->execute([$session]);

        if ($stmt->rowCount()) {
            // Если есть в базе, то обновляем данные
            $res = $stmt->fetch();

            if ($res['sestime'] < (time() - 300)) {
                $res['sestime'] = time();
                $res['views'] = 0;
                $movings = 1;
            } else {
                $movings = $res['place'] != $place
                    ? $res['movings'] + 1
                    : $res['movings'];
            }

            $update = $this->db->prepare(
                'UPDATE `cms_sessions` SET
                `sestime`  = ?,
                `lastdate` = ?,
                `place`    = ?,
                `views`    = ?,
                `movings`  = ?
                WHERE `session_id` = ?'
            );
            $update->execute(
                [
                    $res['sestime'],
                    time(),
                    $place,
                    $res['views'] + 1,
                    $movings,
                    $session,
                ]
            );
        } else {
            // Если еще небыло в базе, то добавляем запись
            $insert = $this->db->prepare(
                'INSERT IGNORE INTO `cms_sessions` SET
                `session_id`   = ?,
                `ip`           = ?,
                `ip_via_proxy` = ?,
                `browser`      = ?,
                `lastdate`     = ?,
                `sestime`      = ?,
                `views`        = 1,
                `movings`      = 1,
                `place`        = ?'
            );
            $insert->execute(
                [
                    $session,
                    $this->env->getIp(),
                    $this->env->getIpViaProxy(),
                    $this->env->getUserAgent(),
                    time(),
                    time(),
                    $place,
                ]
            );
        }
    }

    private function determinePlace(): string
    {
        $uri = rawurldecode($_SERVER['REQUEST_URI']);
        $path = trim(str_ireplace('index.php', '', parse_url($uri, PHP_URL_PATH)), '/');
        $act = $_GET['act'] ?? '';
        $type = $_GET['type'] ?? '';
        $id = $_GET['id'] ?? '';
        $query = [];

        if (! empty($act)) {
            $query[] = 'act=' . substr($act, 0, 15);
        }

        if (! empty($type)) {
            $query[] = 'type=' . substr($type, 0, 15);
        }

        if (! empty($id)) {
            $query[] = 'id=' . abs((int) $id);
        }

        return '/' . $path . (! empty($query) ? '?' . implode('&', $query) : '');
    }
}
