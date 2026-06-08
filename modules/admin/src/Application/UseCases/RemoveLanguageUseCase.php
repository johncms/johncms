<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

final readonly class RemoveLanguageUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
        private LanguageFilesManagerInterface $filesManager,
        private UpdateLanguagesListUseCase $updateLanguagesList,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(string $code): void
    {
        $config = $this->configRepository->getJohncms();
        if (! array_key_exists($code, $config['lng_list'] ?? [])) {
            return;
        }

        $this->filesManager->remove($code);
        $this->updateLanguagesList->execute();
    }
}
