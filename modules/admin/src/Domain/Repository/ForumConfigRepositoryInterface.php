<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

interface ForumConfigRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array;

    /**
     * @param array<string, mixed> $settings
     *
     * @throws ConfigWriteException
     */
    public function saveSettings(array $settings): void;
}
