<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use Illuminate\Database\Capsule\Manager;
use Johncms\System\Http\Environment;

class BanIP
{
    protected $ip;
    protected $ip_via_proxy;

    public function __construct()
    {
        /** @var Environment $env */
        $env = di(Environment::class);
        $this->ip = $env->getIp();
        $this->ip_via_proxy = $env->getIpViaProxy();
    }

    public function checkBan(): void
    {
        $connection = Manager::connection();
        $res = $connection->table('cms_ban_ip')->whereRaw('? BETWEEN `ip1` AND `ip2`', $this->ip);
        if (! empty($this->ip_via_proxy)) {
            $res->orWhereRaw('? BETWEEN `ip1` AND `ip2`', $this->ip_via_proxy);
        }
        $check_ip = $res->first();
        if ($check_ip !== null) {
            switch ($check_ip->ban_type) {
                case 2:
                    $this->redirect($check_ip->link ?? '');
                    break;
                case 3:
                    //TODO: реализовать запрет регистрации
                    //self::$deny_registration = true;
                    break;
                default:
                    $this->block();
            }
        }
    }

    protected function redirect(string $url = ''): void
    {
        if (! empty($url)) {
            header('Location: ' . $url);
        } else {
            header('Location: http://johncms.com');
        }
        exit;
    }

    protected function block(): void
    {
        http_response_code(403);
        exit('Access denied');
    }
}
