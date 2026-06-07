<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\IpSearchResultDTO;
use Johncms\Modules\Admin\Application\Services\IpRangeParser;
use Johncms\Modules\Admin\Domain\Enums\IpSearchMode;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;

final readonly class SearchUsersByIpUseCase
{
    public function __construct(
        private IpRangeParser $parser,
        private IpSearchRepositoryInterface $repository,
    ) {
    }

    public function execute(string $search, IpSearchMode $mode, int $page, int $perPage): IpSearchResultDTO
    {
        if ($search === '') {
            return new IpSearchResultDTO(null);
        }

        $range = $this->parser->parse($search);
        if (! $range->isValid()) {
            return new IpSearchResultDTO(null, $range->errors);
        }

        $users = $mode === IpSearchMode::HISTORY
            ? $this->repository->paginateHistory($range->from, $range->to, $page, $perPage)
            : $this->repository->paginateUsers($range->from, $range->to, $page, $perPage);

        return new IpSearchResultDTO($users);
    }
}
