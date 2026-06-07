<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\IpWhoisResultDTO;
use Johncms\Modules\Admin\Domain\Services\WhoisClientInterface;

final readonly class GetIpWhoisUseCase
{
    public function __construct(
        private WhoisClientInterface $whoisClient,
    ) {
    }

    public function execute(string $ip): IpWhoisResultDTO
    {
        return new IpWhoisResultDTO($ip, $this->whoisClient->lookup($ip));
    }
}
