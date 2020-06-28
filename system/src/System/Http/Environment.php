<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Http;

use Psr\Container\ContainerInterface;

class Environment
{
    /** @var null|int */
    private $ip;

    /** @var null|int */
    private $ipViaProxy;

    /** @var null|string */
    private $userAgent;

    /** @var array */
    private $ipCount = [];

    /** @var Request */
    private $request;

    public function __invoke(ContainerInterface $container)
    {
        $this->request = $container->get(Request::class);
        $this->ipLog();
        return $this;
    }

    public function getIp(bool $return_long = true)
    {
        if (! $return_long) {
            return $this->request->getServer('REMOTE_ADDR', '127.0.0.1', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }

        if (null === $this->ip) {
            /** @psalm-suppress PossiblyNullArgument */
            $ip = ip2long($this->request->getServer('REMOTE_ADDR', '127.0.0.1', FILTER_VALIDATE_IP));
            $this->ip = (int) sprintf('%u', $ip);
        }

        return (int) $this->ip;
    }

    public function getIpViaProxy(bool $return_long = true)
    {
        if ($this->ipViaProxy !== null) {
            return $this->ipViaProxy;
        }

        $httpString = $this->request->getServer('HTTP_X_FORWARDED_FOR', '', FILTER_SANITIZE_STRING);
        return $this->ipViaProxy = (
        ! empty($httpString) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $httpString, $vars)
            ? $this->extractIpFromString($vars, $return_long)
            : 0
        );
    }

    public function getUserAgent(): string
    {
        if ($this->userAgent === null) {
            $userAgent = $this->request->getServer(
                'HTTP_USER_AGENT',
                'Not Recognised',
                FILTER_SANITIZE_SPECIAL_CHARS
            );
            /** @psalm-suppress PossiblyNullArgument */
            $this->userAgent = mb_substr($userAgent, 0, 150);
        }

        return $this->userAgent;
    }

    public function getIpLog(): array
    {
        return $this->ipCount;
    }

    private function extractIpFromString(array $vars, bool $return_long = true)
    {
        foreach ($vars[0] as $var) {
            $ipViaProxy = ip2long($var);

            if ($ipViaProxy && $ipViaProxy !== $this->getIp() && ! preg_match('#^(10|172\.16|192\.168)\.#', $var)) {
                if (! $return_long) {
                    return long2ip($ipViaProxy);
                }
                return (int) sprintf('%u', $ipViaProxy);
            }
        }

        return 0;
    }

    private function ipLog(): void
    {
        $file = CACHE_PATH . 'ip-requests-list.cache';
        $in = $this->openIpCache($file);
        $tmp = [];

        if (false !== $in && flock($in, LOCK_EX)) {
            while ($block = fread($in, 8)) {
                $arr = unpack('Lip/Ltime', $block);

                if ((time() - $arr['time']) > 60) {
                    continue;
                }

                $tmp[] = $arr;
                $this->ipCount[] = $arr['ip'];
            }

            $this->writeIpCache($in, $tmp);
        }
    }

    /**
     * @param string $file
     * @return false|resource
     */
    private function openIpCache(string $file)
    {
        return fopen($file, (file_exists($file) ? 'r+' : 'w+'));
    }

    /**
     * @param resource $resource
     * @param array $array
     */
    private function writeIpCache($resource, array $array): void
    {
        fseek($resource, 0);
        ftruncate($resource, 0);

        foreach ($array as $iValue) {
            fwrite($resource, pack('LL', $iValue['ip'], $iValue['time']));
        }

        fwrite($resource, pack('LL', $this->getIp(), time()));
        fclose($resource);
    }
}
