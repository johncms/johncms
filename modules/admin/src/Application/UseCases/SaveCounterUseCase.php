<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;

final readonly class SaveCounterUseCase
{
    public function __construct(
        private CounterRepositoryInterface $repository,
    ) {
    }

    /**
     * Создаёт или обновляет счётчик. Возвращает true, если это было обновление.
     */
    public function execute(?int $id, string $name, string $link1, string $link2, int $mode): bool
    {
        if ($id !== null && $this->repository->findById($id) !== null) {
            $this->repository->update($id, $name, $link1, $link2, $mode);

            return true;
        }

        $this->repository->create($name, $link1, $link2, $mode);

        return false;
    }
}
