<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface ThemeListProviderInterface
{
    /**
     * Список доступных тем оформления сайта (без служебной темы admin).
     *
     * @return list<string>
     */
    public function getAvailable(): array;
}
