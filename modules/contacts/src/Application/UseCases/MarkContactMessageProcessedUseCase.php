<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\UseCases;

use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;

final readonly class MarkContactMessageProcessedUseCase
{
    public function __construct(
        private ContactMessageRepositoryInterface $repository,
    ) {
    }

    public function execute(ContactMessage $message): void
    {
        $this->repository->markProcessed($message);
    }
}
