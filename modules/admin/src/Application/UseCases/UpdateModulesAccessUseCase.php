<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\ModulesAccessDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final readonly class UpdateModulesAccessUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(ModulesAccessDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['mod_reg'] = $dto->registration;
        $config['mod_lib_comm'] = $dto->libraryComments;
        $config['mod_down_comm'] = $dto->downloadsComments;
        $config['active'] = $dto->community;

        $this->configRepository->saveJohncms($config);
    }
}
