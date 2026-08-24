<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class FileIntegrityScanResultDTO
{
    /**
     * @param list<string> $changedFiles
     */
    public function __construct(
        public bool $snapshotExists,
        public array $changedFiles,
    ) {
    }
}
