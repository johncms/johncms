<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Application\UseCases;

use Johncms\Modules\Redirect\Domain\Repository\AdsRepositoryInterface;

final readonly class RedirectByIdUseCase
{
    public function __construct(
        private AdsRepositoryInterface $adsRepository,
    ) {
    }

    public function execute(int $id): ?string
    {
        $ads = $this->adsRepository->find($id);
        if ($ads === null) {
            return null;
        }

        $this->adsRepository->incrementCount($ads);
        return $ads->link;
    }
}
