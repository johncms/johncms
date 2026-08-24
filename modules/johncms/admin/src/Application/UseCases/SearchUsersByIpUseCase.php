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

    public function count(string $search, IpSearchMode $mode): int
    {
        if ($search === '') {
            return 0;
        }

        $range = $this->parser->parse($search);
        if (! $range->isValid()) {
            return 0;
        }

        return $mode === IpSearchMode::HISTORY
            ? $this->repository->countHistory($range->from, $range->to)
            : $this->repository->countUsers($range->from, $range->to);
    }

    public function getPage(string $search, IpSearchMode $mode, int $limit, int $offset): IpSearchResultDTO
    {
        if ($search === '') {
            return new IpSearchResultDTO(null);
        }

        $range = $this->parser->parse($search);
        if (! $range->isValid()) {
            return new IpSearchResultDTO(null, $range->errors);
        }

        $users = $mode === IpSearchMode::HISTORY
            ? $this->repository->getHistory($range->from, $range->to, $limit, $offset)
            : $this->repository->getUsers($range->from, $range->to, $limit, $offset);

        return new IpSearchResultDTO($users);
    }
}
