<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\UseCases;

use Johncms\System\Http\Environment;

final readonly class GetIpActivityUseCase
{
    public function __construct(
        private Environment $env,
    ) {
    }

    public function count(): int
    {
        return count(array_count_values($this->env->getIpLog()));
    }

    /**
     * @return list<array{ip: string, search_ip: string, whois_ip: string, current_user_ip: bool, count: int}>
     */
    public function getPage(int $limit, int $offset): array
    {
        $ipArray = array_count_values($this->env->getIpLog());
        arsort($ipArray);

        $currentIp = $this->env->getIp();
        $items = [];

        foreach (array_slice($ipArray, $offset, $limit, true) as $ipLong => $count) {
            $ip = long2ip((int) $ipLong);
            $items[] = [
                'ip'              => $ip,
                'search_ip'       => '/admin/ip-search?ip=' . $ip,
                'whois_ip'        => '/admin/ip-whois?ip=' . $ip,
                'current_user_ip' => ((string) $ipLong === (string) $currentIp),
                'count'           => $count,
            ];
        }

        return $items;
    }
}
