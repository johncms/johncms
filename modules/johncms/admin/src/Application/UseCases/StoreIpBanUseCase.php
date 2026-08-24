<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Enums\IpBanType;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;

final readonly class StoreIpBanUseCase
{
    public function __construct(
        private IpBanRepositoryInterface $repository,
    ) {
    }

    public function execute(int $ip1, int $ip2, int $banType, string $link, string $who, string $reason): void
    {
        $type = IpBanType::fromValueOrBlock($banType);

        $this->repository->create(
            $ip1,
            $ip2,
            $type->value,
            $type === IpBanType::REDIRECT ? $link : '',
            $who,
            $reason,
        );
    }
}
