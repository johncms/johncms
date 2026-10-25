<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface BanAmnestyRepositoryInterface
{
    /**
     * Снимает все активные баны со сроком окончания менее 30 дней
     * (баны «до отмены» не затрагиваются).
     */
    public function unbanActiveShortTerm(string $reason): void;
}
