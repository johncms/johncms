<?php

declare(strict_types=1);

namespace Johncms\Http;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class IpLogger
{
    private array $ipCount = [];
    private Request $request;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): static
    {
        $this->request = $container->get(Request::class);
        $this->ipLog();
        return $this;
    }

    public function getIpLog(): array
    {
        return $this->ipCount;
    }

    private function ipLog(): void
    {
        $file = CACHE_PATH . 'ip-requests-list.cache';
        $in = fopen($file, (file_exists($file) ? 'r+' : 'w+'));
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

        $ip = ip2long($this->request->getServer('REMOTE_ADDR', '127.0.0.1', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        fwrite($resource, pack('LL', $ip, time()));
        fclose($resource);
    }
}
