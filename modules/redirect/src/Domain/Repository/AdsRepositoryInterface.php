<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Domain\Repository;

use Johncms\Modules\Redirect\Domain\Models\Ads;

interface AdsRepositoryInterface
{
    public function find(int $id): ?Ads;

    public function incrementCount(Ads $ads): void;
}
