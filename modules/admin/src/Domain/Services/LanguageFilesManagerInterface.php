<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface LanguageFilesManagerInterface
{
    /**
     * Установленные в системе языки по файлам system/locale/*.ini.
     *
     * @return array<string, array{name: string, version: float}>
     */
    public function getInstalled(): array;

    /**
     * Удаляет файлы языка (локали модулей/системы, флаги).
     */
    public function remove(string $code): void;

    /**
     * true, если хотя бы один файл языка недоступен для записи.
     */
    public function hasAccessProblem(string $code): bool;
}
