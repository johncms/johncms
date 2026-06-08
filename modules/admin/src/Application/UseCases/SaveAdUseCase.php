<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\AdFormDTO;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;

final readonly class SaveAdUseCase
{
    public function __construct(
        private AdRepositoryInterface $repository,
    ) {
    }

    /**
     * Создаёт или обновляет рекламную ссылку. Возвращает true при обновлении.
     */
    public function execute(?int $id, AdFormDTO $dto): bool
    {
        $attributes = [
            'type'       => $dto->type,
            'view'       => $dto->view,
            'link'       => $dto->link,
            'name'       => $dto->name,
            'color'      => $dto->color,
            'count_link' => $dto->countLink,
            'day'        => $dto->day,
            'layout'     => $dto->layout,
            'show'       => $dto->directLink ? 1 : 0,
            'bold'       => $dto->bold ? 1 : 0,
            'italic'     => $dto->italic ? 1 : 0,
            'underline'  => $dto->underline ? 1 : 0,
        ];

        if ($id !== null && $this->repository->findById($id) !== null) {
            $this->repository->update($id, $attributes);

            return true;
        }

        $this->repository->create($attributes + [
            'mesto' => $this->repository->nextPlace(),
            'count' => 0,
            'time'  => time(),
            'to'    => 0,
        ]);

        return false;
    }
}
