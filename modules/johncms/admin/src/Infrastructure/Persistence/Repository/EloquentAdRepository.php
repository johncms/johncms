<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;

final class EloquentAdRepository implements AdRepositoryInterface
{
    public function countByType(int $type): int
    {
        return Ad::query()->where('type', $type)->count();
    }

    /**
     * @return Collection<int, Ad>
     */
    public function getByType(int $type, int $limit, int $offset): Collection
    {
        return Ad::query()
            ->where('type', $type)
            ->orderBy('mesto')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function findById(int $id): ?Ad
    {
        return Ad::query()->find($id);
    }

    public function create(array $attributes): void
    {
        Ad::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        Ad::query()->where('id', $id)->update($attributes);
    }

    public function nextPlace(): int
    {
        return (int) Ad::query()->max('mesto') + 1;
    }

    public function delete(int $id): void
    {
        Ad::query()->where('id', $id)->delete();
    }

    public function deleteInactive(): void
    {
        Ad::query()->where('to', 1)->delete();
    }

    public function toggleActive(int $id): void
    {
        $ad = $this->findById($id);
        if ($ad === null) {
            return;
        }

        $ad->to = $ad->to ? 0 : 1;
        $ad->save();
    }

    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if ($current === null) {
            return;
        }

        $neighbor = Ad::query()
            ->where('type', $current->type)
            ->where('mesto', '<', $current->mesto)
            ->orderByDesc('mesto')
            ->first();

        $this->swapPlace($current, $neighbor);
    }

    public function moveDown(int $id): void
    {
        $current = $this->findById($id);
        if ($current === null) {
            return;
        }

        $neighbor = Ad::query()
            ->where('type', $current->type)
            ->where('mesto', '>', $current->mesto)
            ->orderBy('mesto')
            ->first();

        $this->swapPlace($current, $neighbor);
    }

    private function swapPlace(Ad $current, ?Ad $neighbor): void
    {
        if ($neighbor === null) {
            return;
        }

        $place = $current->mesto;
        $current->mesto = $neighbor->mesto;
        $neighbor->mesto = $place;
        $current->save();
        $neighbor->save();
    }
}
