<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface LanguageCatalogInterface
{
    /**
     * Доступные для установки языки из внешнего каталога johncms.com.
     *
     * @return array<string, array{name: string, version: float, path?: string}>
     */
    public function getAvailable(): array;

    /**
     * Скачивает и распаковывает архив языка в систему.
     */
    public function install(string $code): void;
}
