<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Forum\Application\DTO\ForumSettingsDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumConfigRepositoryInterface;

final readonly class UpdateForumSettingsUseCase
{
    public function __construct(
        private ForumConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(ForumSettingsDTO $dto): void
    {
        $this->configRepository->saveSettings($dto->toArray());
    }
}
