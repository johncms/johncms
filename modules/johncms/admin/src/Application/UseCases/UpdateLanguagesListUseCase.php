<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

final readonly class UpdateLanguagesListUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
        private LanguageFilesManagerInterface $filesManager,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(): void
    {
        $config = $this->configRepository->getJohncms();
        $config['lng_list'] = $this->filesManager->getInstalled();
        $this->configRepository->saveJohncms($config);
    }
}
