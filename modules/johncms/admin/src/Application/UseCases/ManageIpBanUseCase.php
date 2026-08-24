<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Models\BanIp;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;

final readonly class ManageIpBanUseCase
{
    public function __construct(
        private IpBanRepositoryInterface $repository,
    ) {
    }

    public function findById(int $id): ?BanIp
    {
        return $this->repository->findById($id);
    }

    public function findByIp(int $ip): ?BanIp
    {
        return $this->repository->findByIp($ip);
    }

    public function delete(int $id): void
    {
        $this->repository->deleteById($id);
    }

    public function clearAll(): void
    {
        $this->repository->clearAll();
    }
}
