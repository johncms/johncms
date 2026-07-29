<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\PreparedIpBanDTO;
use Johncms\Modules\Admin\Application\Services\IpRangeParser;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;
use Johncms\Security\ClientInfoDTO;

final readonly class PrepareIpBanUseCase
{
    public function __construct(
        private IpRangeParser $parser,
        private IpBanRepositoryInterface $repository,
    ) {
    }

    public function execute(string $rawIp, ClientInfoDTO $clientInfo): PreparedIpBanDTO
    {
        $rawIp = trim($rawIp);
        if ($rawIp === '') {
            return new PreparedIpBanDTO(errors: [__('Invalid IP')]);
        }

        $range = $this->parser->parse($rawIp);
        if (! $range->isValid()) {
            return new PreparedIpBanDTO(errors: $range->errors);
        }

        $ip1 = $range->from;
        $ip2 = $range->to;

        $conflicts = $this->repository->findConflicts($ip1, $ip2);
        if ($conflicts->isNotEmpty()) {
            return new PreparedIpBanDTO(conflicts: $conflicts);
        }

        if ($this->isOwnIpInRange($clientInfo, $ip1, $ip2)) {
            return new PreparedIpBanDTO(errors: [__('Ban impossible. Your own IP address in the range')]);
        }

        return new PreparedIpBanDTO(
            ip1: $ip1,
            ip2: $ip2,
            mode: $this->detectMode($rawIp),
        );
    }

    /**
     * The admin's own addresses go through the same parser as the range being banned, so both
     * sides of the comparison are produced the same way. An address that does not parse — the
     * empty proxy address of a visitor who came directly — simply takes part in no comparison.
     */
    private function isOwnIpInRange(ClientInfoDTO $clientInfo, int $ip1, int $ip2): bool
    {
        foreach ([$clientInfo->ip, $clientInfo->ipViaProxy] as $address) {
            $ownIp = $this->parser->parse($address);
            if ($ownIp->isValid() && $ownIp->from >= $ip1 && $ownIp->from <= $ip2) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return 'single'|'range'|'mask'
     */
    private function detectMode(string $rawIp): string
    {
        return match (true) {
            str_contains($rawIp, '-') => 'range',
            str_contains($rawIp, '*') => 'mask',
            default                   => 'single',
        };
    }
}
