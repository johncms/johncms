<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Infrastructure\Persistence\Repository;

use Johncms\Modules\Redirect\Domain\Models\Ads;
use Johncms\Modules\Redirect\Domain\Repository\AdsRepositoryInterface;

class EloquentAdsRepository implements AdsRepositoryInterface
{
    public function find(int $id): ?Ads
    {
        return Ads::query()->find($id);
    }

    public function incrementCount(Ads $ads): void
    {
        $ads->increment('count');
    }
}
