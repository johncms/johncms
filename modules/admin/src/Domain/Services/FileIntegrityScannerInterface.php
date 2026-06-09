<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface FileIntegrityScannerInterface
{
    public function snapshotExists(): bool;

    /**
     * Делает снимок контрольных сумм скриптовых файлов сайта.
     */
    public function createSnapshot(): void;

    /**
     * Сравнивает текущие файлы со снимком.
     *
     * @return list<string> Пути изменённых файлов.
     */
    public function scan(): array;
}
