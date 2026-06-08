<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\SmiliesCacheWriteException;
use Johncms\Modules\Admin\Domain\Repository\SmiliesCacheRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\SmiliesScannerInterface;

final readonly class RebuildSmiliesCacheUseCase
{
    public function __construct(
        private SmiliesScannerInterface $scanner,
        private SmiliesCacheRepositoryInterface $cacheRepository,
    ) {
    }

    /**
     * Пересобирает кэш смайлов и возвращает количество записанных смайлов.
     *
     * @throws SmiliesCacheWriteException
     */
    public function execute(): int
    {
        $smilies = $this->scanner->scan();
        $this->cacheRepository->save($smilies);

        return count($smilies['adm']) + count($smilies['usr']);
    }
}
