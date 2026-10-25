<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\BanListEntryDTO;
use Johncms\Modules\Admin\Domain\Enums\BanListStatus;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\BanListFilter;

final readonly class GetBanListUseCase
{
    public function __construct(
        private BanListRepositoryInterface $repository,
    ) {
    }

    public function count(BanListFilter $filter, ?int $now = null): int
    {
        return $this->repository->count($filter, $now ?? time());
    }

    /**
     * How many accounts each tab holds, whatever else the list is narrowed to.
     *
     * @return array<string, int> Keyed by the value of BanListStatus.
     */
    public function tabCounts(?int $now = null): array
    {
        $now ??= time();
        $counts = [];

        foreach (BanListStatus::cases() as $status) {
            $counts[$status->value] = $this->repository->count(new BanListFilter(status: $status), $now);
        }

        return $counts;
    }

    /**
     * @return list<BanListEntryDTO>
     */
    public function getPage(BanListFilter $filter, int $limit, int $offset, ?int $now = null): array
    {
        $users = $this->repository->get($filter, $now ?? time(), $limit, $offset);
        $bans = $this->repository->bansByIds(
            array_values(array_filter($users->map(static fn ($user): int => (int) $user->getAttribute('ban_id'))->all()))
        );

        $entries = [];
        foreach ($users as $user) {
            $ban = $bans[(int) $user->getAttribute('ban_id')] ?? null;

            // A ban deleted between the two queries leaves nothing to show.
            if ($ban !== null) {
                $entries[] = new BanListEntryDTO($user, $ban, (int) $user->getAttribute('bancount'));
            }
        }

        return $entries;
    }
}
