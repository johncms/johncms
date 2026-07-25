<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Psr\Container\ContainerInterface;

/**
 * Per-request environment facts: the visitor address, the user agent and the short-term
 * request-rate log.
 *
 * Address resolution is delegated to HttpFoundation (plan stage 1e): it honours the trusted
 * proxies configured in config/autoload/http.global.php, so behind a reverse proxy getIp()
 * returns the visitor rather than the proxy, and a forwarded header coming from an untrusted
 * source is ignored instead of being taken at face value.
 */
class Environment
{
    /** Stand-in for an address that cannot be represented as an unsigned-int IPv4 value. */
    private const FALLBACK_IP = '127.0.0.1';

    /**
     * Resolved addresses in their textual form. The long/short representations are derived on
     * demand, so a call with $return_long = false cannot poison the cache for the next call
     * with $return_long = true (it used to: see Users\UserFactory::ipHistory()).
     */
    private string $clientIp = '';

    private ?string $proxyIp = null;

    private bool $addressesResolved = false;

    private ?string $userAgent = null;

    /** @var list<int> */
    private array $ipCount = [];

    private Request $request;

    public function __construct()
    {
        $this->request = di(Request::class);
        $this->ipLog();
    }

    public function __invoke(ContainerInterface $container)
    {
        return $this;
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * The visitor address: the nearest address that is not a trusted proxy.
     *
     * @return int|string Unsigned ip2long value, or the dotted IPv4 form when $return_long is false
     */
    public function getIp(bool $return_long = true)
    {
        $this->resolveAddresses();

        return $return_long
            ? $this->toLong($this->clientIp)
            : $this->toIpv4($this->clientIp);
    }

    /**
     * The address the visitor's own proxy claims to be forwarding for, when there is one:
     * the next hop of the forwarded chain after the visitor. Zero when the visitor did not
     * come through a proxy of their own, or when no forwarded header can be trusted.
     *
     * @return int|string Zero when unknown; otherwise the same shape as getIp()
     */
    public function getIpViaProxy(bool $return_long = true)
    {
        $this->resolveAddresses();

        if ($this->proxyIp === null) {
            return 0;
        }

        return $return_long
            ? $this->toLong($this->proxyIp)
            : $this->toIpv4($this->proxyIp);
    }

    public function getUserAgent(): string
    {
        if ($this->userAgent === null) {
            $userAgent = $this->request->server->filter(
                'HTTP_USER_AGENT',
                'Not Recognised',
                FILTER_SANITIZE_SPECIAL_CHARS
            );
            $this->userAgent = mb_substr((string) $userAgent, 0, 150);
        }

        return $this->userAgent;
    }

    public function getIpLog(): array
    {
        return $this->ipCount;
    }

    /**
     * Reads the address chain once per request.
     *
     * getClientIps() returns the chain with the trusted proxies already stripped and ordered
     * from the nearest hop outwards, so [0] is the visitor and [1], when present, is what the
     * visitor's own proxy claims. That is exactly the ip / ip_via_proxy pair this class used to
     * assemble by hand from X-Forwarded-For — only now malformed entries are dropped by
     * HttpFoundation and a forwarded header from an untrusted peer is not read at all.
     *
     * getClientIps() is typed as a plain array and does return [null] when REMOTE_ADDR is
     * absent (CLI, some worker bridges), hence the explicit is_string filter.
     */
    private function resolveAddresses(): void
    {
        if ($this->addressesResolved) {
            return;
        }

        $chain = array_values(array_filter($this->request->getClientIps(), 'is_string'));

        $this->clientIp = $chain[0] ?? self::FALLBACK_IP;
        $this->proxyIp = $chain[1] ?? null;
        $this->addressesResolved = true;
    }

    private function toLong(string $ip): int
    {
        return (int) sprintf('%u', ip2long($this->toIpv4($ip)));
    }

    /**
     * Both representations go through the same validation on purpose: the `ip` columns are
     * unsigned integers, so an IPv6 visitor cannot be stored and degrades to the loopback
     * address — as it always did. Converting the long form independently used to yield 0 for
     * the same visitor that the short form reported as 127.0.0.1, and callers that mix the two
     * (Users\UserFactory::ipHistory()) silently stopped matching their own records.
     * Real IPv6 support needs a schema migration and is out of scope here.
     */
    private function toIpv4(string $ip): string
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ?: self::FALLBACK_IP;
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
