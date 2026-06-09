<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\FileIntegrityScanResultDTO;
use Johncms\Modules\Admin\Domain\Services\FileIntegrityScannerInterface;

final readonly class ScanFileIntegrityUseCase
{
    public function __construct(
        private FileIntegrityScannerInterface $scanner,
    ) {
    }

    public function execute(): FileIntegrityScanResultDTO
    {
        if (! $this->scanner->snapshotExists()) {
            return new FileIntegrityScanResultDTO(false, []);
        }

        return new FileIntegrityScanResultDTO(true, $this->scanner->scan());
    }
}
