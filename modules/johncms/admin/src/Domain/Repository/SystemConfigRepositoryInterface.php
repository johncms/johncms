<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

/**
 * Чтение и сохранение основного конфига системы (ключ `johncms` в system.local.php).
 */
interface SystemConfigRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getJohncms(): array;

    /**
     * @param array<string, mixed> $johncms
     * @throws ConfigWriteException
     */
    public function saveJohncms(array $johncms): void;
}
