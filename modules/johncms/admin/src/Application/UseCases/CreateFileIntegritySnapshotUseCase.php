<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Services\FileIntegrityScannerInterface;

final readonly class CreateFileIntegritySnapshotUseCase
{
    public function __construct(
        private FileIntegrityScannerInterface $scanner,
    ) {
    }

    public function execute(): void
    {
        $this->scanner->createSnapshot();
    }
}
