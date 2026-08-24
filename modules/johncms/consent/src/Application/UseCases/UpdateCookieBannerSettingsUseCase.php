<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Consent\Application\DTO\CookieBannerSettingsDTO;

final readonly class UpdateCookieBannerSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(CookieBannerSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['cookie_banner_enabled'] = $dto->enabled;
        $config['cookie_banner_version'] = $dto->version;
        $config['cookie_banner_text'] = $dto->texts;

        $this->configRepository->saveJohncms($config);
    }
}
