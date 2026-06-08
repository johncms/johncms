<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

final readonly class SaveLanguageSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
        private LanguageFilesManagerInterface $filesManager,
    ) {
    }

    /**
     * Сохраняет язык по умолчанию (если задан и установлен) и при необходимости
     * пересканирует список установленных языков. Один проход записи конфига.
     *
     * @throws ConfigWriteException
     */
    public function execute(?string $defaultCode, bool $updateList): void
    {
        $config = $this->configRepository->getJohncms();

        if ($defaultCode !== null && isset($config['lng_list'][$defaultCode])) {
            $config['lng'] = $defaultCode;
        }

        if ($updateList) {
            $config['lng_list'] = $this->filesManager->getInstalled();
        }

        $this->configRepository->saveJohncms($config);
    }
}
