<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Image\EditorImageFormat;
use Johncms\Modules\Admin\Application\DTO\EditorImageSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final readonly class UpdateEditorImageSettingsUseCase
{
    /** Below this a bound stops being a limit and starts being a thumbnail. */
    private const int MIN_BOUND = 100;
    private const int MAX_BOUND = 10000;
    private const int MAX_SIZE_LIMIT = 102400;

    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(EditorImageSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['editor_images'] = [
            'max_size'   => $this->clamp($dto->maxSize, 1, self::MAX_SIZE_LIMIT),
            'max_width'  => $this->bound($dto->maxWidth),
            'max_height' => $this->bound($dto->maxHeight),
            'quality'    => $this->clamp($dto->quality, 1, 100),
            'convert'    => EditorImageFormat::fromValue($dto->convert)->value,
        ];

        $this->configRepository->saveJohncms($config);
    }

    /**
     * A bound of zero is kept as it is: it is how the form says a side is not constrained.
     */
    private function bound(int $value): int
    {
        return $value <= 0 ? 0 : $this->clamp($value, self::MIN_BOUND, self::MAX_BOUND);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
