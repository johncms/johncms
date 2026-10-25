<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

interface NewsConfigRepositoryInterface
{
    /**
     * The settings of the news as the site runs with them: the defaults with the changes over them.
     *
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
