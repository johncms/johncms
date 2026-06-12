<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Models\BanIp;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;

final readonly class GetIpBanListUseCase
{
    public function __construct(
        private IpBanRepositoryInterface $repository,
    ) {
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * @return Collection<int, BanIp>
     */
    public function getPage(int $limit, int $offset): Collection
    {
        return $this->repository->get($limit, $offset);
    }
}
