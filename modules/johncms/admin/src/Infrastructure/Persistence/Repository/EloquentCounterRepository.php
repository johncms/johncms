<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\Counter;
use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;

final class EloquentCounterRepository implements CounterRepositoryInterface
{
    public function all(): Collection
    {
        return Counter::query()->orderBy('sort')->get();
    }

    public function findById(int $id): ?Counter
    {
        return Counter::query()->find($id);
    }

    public function create(string $name, string $link1, string $link2, int $mode, bool $requireCookieConsent, bool $enabled): void
    {
        $nextSort = (int) Counter::query()->max('sort') + 1;

        Counter::query()->create([
            'name'                   => $name,
            'sort'                   => $nextSort,
            'link1'                  => $link1,
            'link2'                  => $link2,
            'mode'                   => $mode,
            'require_cookie_consent' => $requireCookieConsent ? 1 : 0,
            'switch'                 => $enabled ? 1 : 0,
        ]);
    }

    public function update(int $id, string $name, string $link1, string $link2, int $mode, bool $requireCookieConsent, bool $enabled): void
    {
        Counter::query()->where('id', $id)->update([
            'name'                   => $name,
            'link1'                  => $link1,
            'link2'                  => $link2,
            'mode'                   => $mode,
            'require_cookie_consent' => $requireCookieConsent ? 1 : 0,
            'switch'                 => $enabled ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        Counter::query()->where('id', $id)->delete();
    }

    public function setSwitch(int $id, bool $enabled): void
    {
        Counter::query()->where('id', $id)->update(['switch' => $enabled ? 1 : 0]);
    }

    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if ($current === null) {
            return;
        }

        $neighbor = Counter::query()
            ->where('sort', '<', $current->sort)
            ->orderByDesc('sort')
            ->first();

        $this->swapSort($current, $neighbor);
    }

    public function moveDown(int $id): void
    {
        $current = $this->findById($id);
        if ($current === null) {
            return;
        }

        $neighbor = Counter::query()
            ->where('sort', '>', $current->sort)
            ->orderBy('sort')
            ->first();

        $this->swapSort($current, $neighbor);
    }

    private function swapSort(Counter $current, ?Counter $neighbor): void
    {
        if ($neighbor === null) {
            return;
        }

        $currentSort = $current->sort;
        $current->sort = $neighbor->sort;
        $neighbor->sort = $currentSort;
        $current->save();
        $neighbor->save();
    }
}
