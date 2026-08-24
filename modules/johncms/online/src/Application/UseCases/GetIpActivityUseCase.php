<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\UseCases;

use Johncms\Security\RequestRateLogInterface;

final readonly class GetIpActivityUseCase
{
    public function __construct(
        private RequestRateLogInterface $requestRateLog,
    ) {
    }

    public function count(): int
    {
        return count(array_count_values($this->requestRateLog->recentAddresses()));
    }

    /**
     * @param string $currentIp The address of the visitor reading the page, marked in the list.
     *                          Handed over by the controller: the address is a fact of the
     *                          request, which this layer must not reach for on its own.
     * @return list<array{ip: string, search_ip: string, whois_ip: string, current_user_ip: bool, count: int}>
     */
    public function getPage(int $limit, int $offset, string $currentIp): array
    {
        $addresses = array_count_values($this->requestRateLog->recentAddresses());
        arsort($addresses);

        $items = [];

        foreach (array_slice($addresses, $offset, $limit, true) as $ip => $count) {
            $ip = (string) $ip;
            $items[] = [
                'ip'              => $ip,
                'search_ip'       => '/admin/ip-search?ip=' . $ip,
                'whois_ip'        => '/admin/ip-whois?ip=' . $ip,
                'current_user_ip' => $ip === $currentIp,
                'count'           => $count,
            ];
        }

        return $items;
    }
}
