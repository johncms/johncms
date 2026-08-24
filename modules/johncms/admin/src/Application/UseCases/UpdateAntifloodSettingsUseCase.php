<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\AntifloodSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final readonly class UpdateAntifloodSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(AntifloodSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['antiflood'] = [
            'mode'    => $dto->mode >= 1 && $dto->mode <= 4 ? $dto->mode : 1,
            'day'     => $this->clamp($dto->day, 4, 300),
            'night'   => $this->clamp($dto->night, 4, 300),
            'dayfrom' => $this->clamp($dto->dayFrom, 6, 12),
            'dayto'   => $this->clamp($dto->dayTo, 17, 23),
        ];

        $this->configRepository->saveJohncms($config);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
