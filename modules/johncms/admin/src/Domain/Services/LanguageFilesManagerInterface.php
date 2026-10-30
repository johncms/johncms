<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface LanguageFilesManagerInterface
{
    /**
     * The languages whose files are on the disk, found by system/locale/*.ini.
     *
     * @return array<string, array{name: string, version: float}>
     */
    public function getInstalled(): array;
}
