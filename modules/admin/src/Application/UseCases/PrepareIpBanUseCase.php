<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\PreparedIpBanDTO;
use Johncms\Modules\Admin\Application\Services\IpRangeParser;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;
use Johncms\Http\Environment;

final readonly class PrepareIpBanUseCase
{
    public function __construct(
        private IpRangeParser $parser,
        private IpBanRepositoryInterface $repository,
        private Environment $environment,
    ) {
    }

    public function execute(string $rawIp): PreparedIpBanDTO
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

        if ($this->isOwnIpInRange($ip1, $ip2)) {
            return new PreparedIpBanDTO(errors: [__('Ban impossible. Your own IP address in the range')]);
        }

        return new PreparedIpBanDTO(
            ip1: $ip1,
            ip2: $ip2,
            mode: $this->detectMode($rawIp),
        );
    }

    private function isOwnIpInRange(int $ip1, int $ip2): bool
    {
        $ip = (int) $this->environment->getIp();
        $proxy = (int) $this->environment->getIpViaProxy();

        return ($ip >= $ip1 && $ip <= $ip2) || ($proxy >= $ip1 && $proxy <= $ip2);
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
