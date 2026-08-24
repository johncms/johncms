<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\KarmaSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final readonly class UpdateKarmaSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(KarmaSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['karma']['karma_points'] = $dto->karmaPoints;
        $config['karma']['forum'] = $dto->forumPosts;
        $config['karma']['on'] = $dto->enabled ? 1 : 0;
        $config['karma']['adm'] = $dto->forbidAdmin ? 1 : 0;

        $this->configRepository->saveJohncms($config);
    }
}
