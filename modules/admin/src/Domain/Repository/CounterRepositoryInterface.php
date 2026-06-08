<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\Counter;

interface CounterRepositoryInterface
{
    /**
     * @return Collection<int, Counter>
     */
    public function all(): Collection;

    public function findById(int $id): ?Counter;

    public function create(string $name, string $link1, string $link2, int $mode): void;

    public function update(int $id, string $name, string $link1, string $link2, int $mode): void;

    public function delete(int $id): void;

    public function setSwitch(int $id, bool $enabled): void;

    public function moveUp(int $id): void;

    public function moveDown(int $id): void;
}
