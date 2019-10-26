<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Http;

use Johncms\Api\EnvironmentInterface;
use Psr\Container\ContainerInterface;

class Environment implements EnvironmentInterface
{
    private $ip;

    private $ipViaProxy;

    private $userAgent;

    private $ipCount = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ipLog($this->getIp());

        return $this;
    }

    public function getIp() : int
    {
        if (null === $this->ip) {
            $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
            $ip = ip2long($ip);
            $this->ip = sprintf('%u', $ip);
        }

        return (int) $this->ip;
    }

    public function getIpViaProxy() : int
    {
        if ($this->ipViaProxy !== null) {
            return $this->ipViaProxy;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && preg_match_all(
                '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s',
                filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_SANITIZE_STRING),
                $vars
            )
        ) {
            foreach ($vars[0] as $var) {
                $ipViaProxy = ip2long($var);

                if ($ipViaProxy && $ipViaProxy != $this->getIp() && ! preg_match('#^(10|172\.16|192\.168)\.#', $var)) {
                    return $this->ipViaProxy = (int) sprintf('%u', $ipViaProxy);
                }
            }
        }

        return $this->ipViaProxy = 0;
    }

    public function getUserAgent() : string
    {
        if ($this->userAgent !== null) {
            return $this->userAgent;
        } elseif (isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) && strlen(trim($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])) > 5) {
            return $this->userAgent = 'Opera Mini: ' . mb_substr(filter_var($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'], FILTER_SANITIZE_SPECIAL_CHARS), 0, 150);
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            return $this->userAgent = mb_substr(filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_SPECIAL_CHARS), 0, 150);
        }

        return $this->userAgent = 'Not Recognised';
    }

    public function getIpLog() : array
    {
        return $this->ipCount;
    }

    private function ipLog($ip) : void
    {
        $file = CACHE_PATH . 'ip-requests-list.cache';
        $tmp = [];
        $requests = 1;

        if (! file_exists($file)) {
            $in = fopen($file, 'w+');
        } else {
            $in = fopen($file, 'r+');
        }

        flock($in, LOCK_EX) || die('Cannot flock ANTIFLOOD file.');
        $now = time();

        while ($block = fread($in, 8)) {
            $arr = unpack('Lip/Ltime', $block);

            if (($now - $arr['time']) > 60) {
                continue;
            }

            if ($arr['ip'] == $ip) {
                $requests++;
            }

            $tmp[] = $arr;
            $this->ipCount[] = $arr['ip'];
        }

        fseek($in, 0);
        ftruncate($in, 0);

        for ($i = 0; $i < count($tmp); $i++) {
            fwrite($in, pack('LL', $tmp[$i]['ip'], $tmp[$i]['time']));
        }

        fwrite($in, pack('LL', $ip, $now));
        fclose($in);
    }
}
